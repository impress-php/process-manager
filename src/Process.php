<?php
namespace Impress\ProcessManager;

use Exception;
use RuntimeException;

class Process
{
    public $pid;
    public $progress;

    public function __construct()
    {
    }

    public function signal($signo, callable $callable, $restart_syscalls = true)
    {
        declare(ticks = 1);
        pcntl_signal($signo, $callable, $restart_syscalls);
    }

    public function signal_dispatch()
    {
        pcntl_signal_dispatch();
    }

    public function tick_signal_dispatch($micro_seconds = 1000000)
    {
        while(1){
            pcntl_signal_dispatch();
            usleep($micro_seconds);
        }
    }
}
