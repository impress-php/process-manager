<?php
namespace Impress\ProcessManager;

use Exception;
use RuntimeException;

class Fork
{
    protected $parentProgressRun = 0;
    protected $parentProgress;
    protected $childProgress;
    protected $waitCallbackBlock;
    protected $waitCallbackTicks;
    protected $count;
    protected $daemonize;
    protected $parent;
    protected $childPids = [];

    public function __construct($count = 1, $daemonize = FALSE)
    {
        $this->count = $count;
        $this->daemonize = $daemonize;
    }

    public function run()
    {
        $this->daemonize();

        for($i = 0; $i < $this->count; $i++){
            $this->forkOne();
        }
    }

    /**
     * Run as deamon mode.
     *
     * @throws Exception
     */
    protected function daemonize()
    {
        if (!$this->daemonize) {
            return;
        }
        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception('fork fail');
        } elseif ($pid > 0) {
            exit(0);
        }
        if (-1 === posix_setsid()) {
            throw new Exception("setsid fail");
        }
        // Fork again avoid SVR4 system regain the control of terminal.
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception("fork fail");
        } elseif (0 !== $pid) {
            exit(0);
        }
    }

    public function wait($pid = -1, $options = WUNTRACED)
    {
        $w = pcntl_waitpid($pid, $status, $options);
        return array(
            'pid' => $w,
            'status' => $status
        );
    }

    /**
     * block wait process status from forked.
     *
     * Can not run with pcntl_signal_dispatch,
     * if run with pcntl_signal_dispatch both, please use waitTicks.
     *
     * @return void
     */
    public function waitBlock($pid = -1, $options = WUNTRACED)
    {
        while($wpid = $this->wait($pid, $options)){
            if($wpid['pid'] <= 0){
                break;
            }
            $this->destroyChild($wpid['pid']);
            if(is_callable($this->waitCallbackBlock)){
                call_user_func_array($this->waitCallbackBlock, array($wpid['status'], $wpid['pid'], $this));
            }
        }
    }

    /**
     * ticks wait process status from forked.
     *
     * @return void
     */
    public function waitTicks($micro_seconds = 200000, $pid = -1, $options = WUNTRACED)
    {
        while(1){
            $wpid = $this->wait($pid, $options | WNOHANG);
            if($wpid['pid'] > 0){
                $this->destroyChild($wpid['pid']);
            }
            if(is_callable($this->waitCallbackTicks)){
                call_user_func_array($this->waitCallbackTicks, array($wpid['status'], $wpid['pid'], $this));
            }
            usleep($micro_seconds);
        }
    }

    public function forkOne()
    {
        $pid = pcntl_fork();
        switch($pid){
        case -1:
            throw new Exception('fork fail');
            break;
        case 0:
            $p = new Process();
            $p->pid = posix_getpid();
            $p->progress = $this->childProgress;
            if(is_callable($p->progress)){
                call_user_func_array($p->progress, array($p));
            }
            break;
        default:
            $this->childPids[$pid] = $pid;
            $this->parent = $p = new Process();
            $p->pid = posix_getpid();
            $p->progress = $this->parentProgress;
            if(is_callable($p->progress)){
                call_user_func_array($p->progress, array($p, (!$this->parentProgressRun++)));
            }
            break;
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getParentPid()
    {
        return $this->getParent()->pid;
    }

    public function getChildPids()
    {
        return $this->childPids;
    }

    public function destroyChild($child_pid)
    {
        unset($this->childPids[$child_pid]);
    }

    public function parentProgress(callable $progress)
    {
        $this->parentProgress = $progress;
    }

    public function childProgress(callable $progress)
    {
        $this->childProgress = $progress;
    }

    public function waitCallbackBlock(callable $callback)
    {
        $this->waitCallbackBlock = $callback;
    }

    public function waitCallbackTicks(callable $callback)
    {
        $this->waitCallbackTicks = $callback;
    }
}

