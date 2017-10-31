<?php
require realpath(__DIR__ . "/../../../../") . '/vendor/autoload.php';
use \Impress\ProcessManager\Fork;
use \Impress\ProcessManager\Process;

$f = new Fork(4);

$f->parentProgress(function(Process $p, $once) use ($f){
    if($once){
        $p->signal(12, function() use ($f){
            print("Parent process received SIGINT;" . PHP_EOL);
            var_dump($f->getChildPids());
        });
        print("Parent process pid: " . $p->pid . PHP_EOL . PHP_EOL);
    }
});

$f->childProgress(function(Process $child){
    $child->signal(SIGINT, function() use ($child){
        print(PHP_EOL);
        print("child - " . $child->pid . " received SIGINT, it will be exit;" . PHP_EOL);
        exit;
    });

    print("Child process pid: " . $child->pid . PHP_EOL);
    #$child->tick_signal_dispatch();

    sleep(10);
    exit(0);
});

$f->run();
var_dump($f->getParent()->pid);

$f->waitCallbackTicks(function($s, $pid, $f){
    pcntl_signal_dispatch();
    if($pid>0){
        var_dump($pid);
        var_dump($f->getChildPids());
    }
});
$f->waitTicks();


#$f->getParent()->tick_signal_dispatch();


