<?php

namespace Glopgar\Monolog\Processor;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

/**
 * Namespaced function that takes precedence over the global one
 *
 * @return float
 */
function microtime()
{
    return MicrotimeStub::getMicrotime();
}

class MicrotimeStub
{
    /**
     * @var null|double
     */
    private static $microtime = null;

    /**
     * @return double
     */
    public static function getMicrotime()
    {
        if (null === self::$microtime) {
            return \microtime(true);    // the global one
        } else {
            return self::$microtime;
        }
    }

    /**
     * @param double $microtime
     */
    public static function setMicrotime($microtime)
    {
        self::$microtime = $microtime;
    }

    public static function resetMicrotime()
    {
        self::setMicrotime(null);
    }
}


class TimerProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestHandler
     */
    private $testHandler;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Configure Subject Under Test
     *
     * @param string $level threshold for the TestHandler
     */
    private function configureSut($level = Logger::DEBUG)
    {
        $sut = new TimerProcessor();
        $this->testHandler = new TestHandler($level);
        $this->logger = new \Monolog\Logger('test', [$this->testHandler]);
        $this->logger->pushProcessor($sut);
        return $sut;
    }

    public function testTimer()
    {
        $sut = $this->configureSut();

        MicrotimeStub::setMicrotime(1470000000);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'start']]);

        MicrotimeStub::setMicrotime(1470000001);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'stop']]);

        $this->assertEquals(
            [
                'timer' => [
                    'foo' => [
                        'time' => 1,
                        'totalTime' => 1,
                        'count' => 1
                    ]
                ]
            ],
            $this->testHandler->getRecords()[1]['context']
        );

        $this->assertEquals(
            [
                'foo' => [
                    'totalTime' => 1,
                    'count' => 1
                ]
            ],
            $sut->getTimers()
        );
    }

    public function testMultipleTimers()
    {
        $sut = $this->configureSut();

        MicrotimeStub::setMicrotime(1470000000);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'start', 'bar' => 'start']]);

        MicrotimeStub::setMicrotime(1470000001);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['baz' => 'start']]);

        MicrotimeStub::setMicrotime(1470000002);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'stop']]);

        $this->assertEquals(
            [
                'timer' => [
                    'foo' => [
                        'time' => 2,
                        'totalTime' => 2,
                        'count' => 1
                    ]
                ]
            ],
            $this->testHandler->getRecords()[2]['context']
        );

        MicrotimeStub::setMicrotime(1470000003);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['bar' => 'stop', 'baz' => 'stop']]);

        $this->assertEquals(
            [
                'timer' => [
                    'bar' => [
                        'time' => 3,
                        'totalTime' => 3,
                        'count' => 1
                    ],
                    'baz' => [
                        'time' => 2,
                        'totalTime' => 2,
                        'count' => 1
                    ]
                ]
            ],
            $this->testHandler->getRecords()[3]['context']
        );

        $this->assertEquals(
            [
                'foo' => [
                    'totalTime' => 2,
                    'count' => 1
                ],
                'bar' => [
                    'totalTime' => 3,
                    'count' => 1
                ],
                'baz' => [
                    'totalTime' => 2,
                    'count' => 1
                ]
            ],
            $sut->getTimers()
        );
    }

    public function testAccumulatedTime()
    {
        $sut = $this->configureSut();

        MicrotimeStub::setMicrotime(1470000000);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'start']]);

        MicrotimeStub::setMicrotime(1470000001);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'stop']]);

        MicrotimeStub::setMicrotime(1470000002);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'start']]);

        MicrotimeStub::setMicrotime(1470000003);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'stop']]);

        $this->assertEquals(
            [
                'timer' => [
                    'foo' => [
                        'time' => 1,
                        'totalTime' => 2,
                        'count' => 2
                    ]
                ]
            ],
            $this->testHandler->getRecords()[3]['context']
        );

        $this->assertEquals(
            [
                'foo' => [
                    'totalTime' => 2,
                    'count' => 2
                ]
            ],
            $sut->getTimers()
        );
    }

    public function testNotStarted()
    {
        $sut = $this->configureSut();

        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'stop']]);

        $this->assertEquals(
            [
                'timer' => [
                    'foo' => [
                        'time' => null,
                        'totalTime' => null,
                        'count' => 0
                    ]
                ]
            ],
            $this->testHandler->getRecords()[0]['context']
        );

        $this->assertEquals(
            [
                'foo' => [
                    'totalTime' => null,
                    'count' => 0
                ]
            ],
            $sut->getTimers()
        );
    }

    public function testNotStopped()
    {
        $sut = $this->configureSut();

        MicrotimeStub::setMicrotime(1470000000);
        $this->logger->log(Logger::DEBUG, "test", ['timer' => ['foo' => 'start']]);

        $this->assertEquals(
            [
                'foo' => [
                    'start' => 1470000000,
                    'totalTime' => null,
                    'count' => 0
                ]
            ],
            $sut->getTimers()
        );
    }
}
