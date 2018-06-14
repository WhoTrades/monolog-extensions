<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Item;

class Operation
{
    const STATUS_RUNNING = 'running';
    const STATUS_STOPPED = 'stopped';

    const INFO_FIELD_NAME = 'name';
    const INFO_FIELD_STATUS = 'status';
    const INFO_FIELD_LAST_START_TIME = 'last_start_time';
    const INFO_FIELD_LAST_RUNNING_TIME = 'last_running_time';
    const INFO_FIELD_TOTAL_RUNNING_TIME = 'total_running_time';
    const INFO_FIELD_RUNNING_COUNT = 'running_count';
    const INFO_FIELD_ERROR = 'error';

    protected $name;
    protected $status = self::STATUS_STOPPED;
    protected $lastStartTime = 0;
    protected $lastRunningTime = 0;
    protected $totalRunningTime = 0;
    protected $runningCount = 0;
    protected $lastError;

    /**
     * Operation constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return void
     */
    public function stop()
    {
        $this->lastError = null;
        if ($this->status === self::STATUS_RUNNING) {
            $this->status = self::STATUS_STOPPED;
            $this->lastRunningTime = microtime(true) - $this->lastStartTime;
            $this->totalRunningTime = $this->totalRunningTime + $this->lastRunningTime;
        } else {
            $this->lastError = '[E] Operation is not running';
        }
    }

    /**
     * @return void
     */
    public function start()
    {
        $this->lastError = null;
        if ($this->status === self::STATUS_STOPPED) {
            $this->status = self::STATUS_RUNNING;
            $this->lastStartTime = microtime(true);
            $this->runningCount++;
        } else {
            $this->lastError = '[E] Operation is running already';
        }
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * @return bool
     */
    public function isStopped()
    {
        return $this->status === self::STATUS_STOPPED;
    }

    /**
     * @return array
     */
    public function getInfoArray()
    {
        $result = [];

        $result[self::INFO_FIELD_NAME] = $this->name;
        $result[self::INFO_FIELD_STATUS] = $this->status;
        $result[self::INFO_FIELD_LAST_START_TIME] = $this->lastStartTime;
        $result[self::INFO_FIELD_LAST_RUNNING_TIME] = $this->lastRunningTime;
        $result[self::INFO_FIELD_TOTAL_RUNNING_TIME] = $this->totalRunningTime;
        $result[self::INFO_FIELD_RUNNING_COUNT] = $this->runningCount;
        $result[self::INFO_FIELD_ERROR] = $this->lastError;

        return $result;
    }
}
