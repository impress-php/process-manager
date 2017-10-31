<?php
require realpath(__DIR__ . "/../../../../") . '/vendor/autoload.php';
use \Impress\ProcessManager\Fork;
use \Impress\ProcessManager\Process;

$f = new Fork(4);

$f->parentProgress(function(Process $p, $once){
    if($once){
        print("Parent process pid: " . $p->pid . PHP_EOL . PHP_EOL);

        $p->signal(SIGUSR2, function(){
            print("Parent process received SIGUSR2." . PHP_EOL);
        });
    }
});

$f->childProgress(function(Process $child){
    print("Child process pid: " . $child->pid . PHP_EOL);
    sleep(10);
    exit(0);
});

$f->run();

$f->waitCallbackTicks(function($status, $pid){
    pcntl_signal_dispatch();
    if($pid > 0){
        print("child: {$pid} has been exit." . PHP_EOL);
    }
});
$f->waitTicks();


