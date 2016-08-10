monolog-timer-processor
=======================

Processor for [monolog](https://github.com/Seldaek/monolog) that allows to time fragments of code by adding timer info
the message contexts.

#### Basic usage

```php

$logger = new \Monolog\Logger('timer.example');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

$logger->pushProcessor(new Glopgar\Monolog\Processor\TimerProcessor());

// start a timer with name: 'exampleProcess'
$logger->debug('Process started', ['timer' => ['exampleProcess' => 'start']]);
usleep(1500000);
// stop the timer
$logger->debug('Process ended', ['timer' => ['exampleProcess' => 'stop']]);
$logger->debug('Process started again', ['timer' => ['exampleProcess' => 'start']]);
usleep(2300000);
$logger->debug('Process ended', ['timer' => ['exampleProcess' => 'stop']]);
```

Log output:

```
[2016-08-10 17:07:26] timer.example.DEBUG: Process started {"timer":{"exampleProcess":"start"}} []
[2016-08-10 17:07:27] timer.example.DEBUG: Process ended {"timer":{"exampleProcess":{"time":"1.50 s.","totalTime":"1.50 s.","count":1}}} []
[2016-08-10 17:07:27] timer.example.DEBUG: Process started again {"timer":{"exampleProcess":"start"}} []
[2016-08-10 17:07:30] timer.example.DEBUG: Process ended {"timer":{"exampleProcess":{"time":"2.30 s.","totalTime":"3.81 s.","count":2}}} []
```


#### Multiple timers

You can start or stop more than one timer on each log line:

```php

$logger->debug('Processes started', ['timer' => ['process1' => 'start', 'process2' => 'start']]);
sleep(1);
$logger->debug('Process ended, process started', ['timer' => ['process1' => 'stop', 'process3' => 'start']]);
sleep(1);
$logger->debug('Processes ended', ['timer' => ['process2' => 'stop', 'process3' => 'stop']]);

```

Log output:

```
[2016-08-10 17:17:26] timer.example.DEBUG: Processes started {"timer":{"process1":"start","process2":"start"}} []
[2016-08-10 17:17:27] timer.example.DEBUG: Process ended, process started {"timer":{"process1":{"time":"1.00","totalTime":"1.00","count":1},"process3":"start"}} []
[2016-08-10 17:17:28] timer.example.DEBUG: Processes ended {"timer":{"process2":{"time":"2.01","totalTime":"2.01","count":1},"process3":{"time":"1.00","totalTime":"1.00","count":1}}} []
```

#### Getting timers info

You can obtain an array with all the timers information using the `TimerProcessor::getTimers` method:

This can be useful for logging the timer totals at the end of the process:

```php
$timers = $processor->getTimers();
foreach ($timers as $timer => $info) {
    $logger->notice('%s executed %d times, total time: %.2f s.', $timer, $info['count'], $info['totalTime']);
}
```


Author:
Gonzalo López Garmendia

[github:glopgar](https://github.com/glopgar)


