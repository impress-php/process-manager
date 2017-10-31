<?php
require realpath(__DIR__ . "/../../../../") . '/vendor/autoload.php';
use \Impress\ProcessManager\Fork;
use \Impress\ProcessManager\Process;

$f = new Fork(2);

$f->parentProgress(function(Process $p, $once){
    if($once){
        print("Parent process pid: " . $p->pid . PHP_EOL . PHP_EOL);
    }
});

$f->childProgress(function(Process $child){
    $child->signal(SIGUSR2, function() use ($child){
        print(PHP_EOL);
        print("child - " . $child->pid . " received SIGUSR2, it will be exit;" . PHP_EOL);
        exit(0);
    });
    print("Child process pid: " . $child->pid . PHP_EOL);
    $child->tick_signal_dispatch();
    exit(0);
});

$f->run();

$f->waitCallbackBlock(function($status, $pid) use ($f){
    if($pid){
        $ret = array(
            'pid' => $pid,
            'status' => $status,
            'wifexited' => pcntl_wifexited($status),        // bool 是否代表一个正常的退出
            'wifstopped' => pcntl_wifstopped($status),      // bool 子进程当前是否已经停止
            'wifsignaled' => pcntl_wifsignaled($status),    // bool 子进程是否是由于某个未捕获的信号退出的
            'wexitstatus' => pcntl_wexitstatus($status),    // int  中断的子进程的返回代码
            'wstopsig' => pcntl_wstopsig($status),          // int  导致子进程停止的信号
            'wtermsig' => pcntl_wtermsig($status)           // int  导致子进程中断的信号
        );
        var_dump($ret);
        if(!$ret['wifexited']){
            $f->forkOne();
        }
    }
    if(!$f->getChildPids()){
        print("All of childs have already exited. The parent process will be exit;" . PHP_EOL);
        sleep(1);
        exit(0);
    }
});

$f->waitBlock();

