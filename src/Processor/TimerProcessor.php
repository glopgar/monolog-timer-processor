<?php

namespace Glopgar\Monolog\Processor;

class TimerProcessor
{
    /**
     * @var integer with the decimals to format the times
     */
    private $timerPrecision;

    /**
     * @var array
     */
    private $timers = [];

    /**
     * @param int $timePrecision
     * @param string $timeFormat
     */
    public function __construct($timerPrecision = 2)
    {
        $this->timerPrecision = 2;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        if (!isset($record['context']['timer'])) {
            return $record;
        }

        if (is_array($record['context']['timer'])) {
            foreach ($record['context']['timer'] as $timer => &$timerInfo) {

                if ('start' === $timerInfo) {

                    if (! isset($this->timers[$timer])) {
                        $this->timers[$timer] = [
                            'totalTime' => null,
                            'count' => 0
                        ];
                    }
                    $this->timers[$timer]['start'] = microtime(true);

                } else if ('stop' === $timerInfo) {

                    if (isset($this->timers[$timer]['start'])) {
                        $time = microtime(true) - $this->timers[$timer]['start'];
                        unset($this->timers[$timer]['start']);
                        $totalTime = $this->timers[$timer]['totalTime'] + $time;
                        $this->timers[$timer]['totalTime'] += $time;
                        $count = $this->timers[$timer]['count'] + 1;
                    } else {
                        $time = $totalTime = null;
                        $this->timers[$timer]['totalTime'] = null;
                        $count = 0;
                    }

                    $timerInfo = [
                        'time' => number_format($time, $this->timerPrecision),
                        'totalTime' => number_format($totalTime, $this->timerPrecision),
                        'count' => $count
                    ];
                    $this->timers[$timer]['count'] = $count;
                }
            }
        }
        return $record;
    }

    /**
     * @return array with the timers info
     */
    public function getTimers()
    {
        return $this->timers;
    }
}
