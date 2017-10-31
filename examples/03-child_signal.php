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

$f->waitCallbackBlock(function() use ($f){
    if(!$f->getChildPids()){
        print("All of childs have already exited. The parent process will be exit;" . PHP_EOL);
        sleep(1);
        exit(0);
    }
});
$f->waitBlock();
