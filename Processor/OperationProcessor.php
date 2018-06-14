<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Processor;

use whotrades\MonologExtensions\LoggerWt;
use whotrades\MonologExtensions\Item;

class OperationProcessor
{
    const ACTION_START = 'start';
    const ACTION_STOP = 'stop';

    /**
     * @var Item\Operation[]
     */
    protected $operations = [];

    /**
     * @param  array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        if ((!isset($record['context'][LoggerWt::CONTEXT_OPERATIONS])
                || !is_array($record['context'][LoggerWt::CONTEXT_OPERATIONS])
            ) && empty($record['context'][LoggerWt::CONTEXT_FINISH_LOGGING])) {
            return $record;
        }

        foreach ($record['context'][LoggerWt::CONTEXT_OPERATIONS] as $operationName => $operationAction) {
            switch ($operationAction) {
                case self::ACTION_START:
                    if (!isset($this->operations[$operationName])) {
                        $this->operations[$operationName] = new Item\Operation($operationName);
                    }

                    $this->operations[$operationName]->start();
                    $record['extra'][LoggerWt::CONTEXT_OPERATIONS][$operationName] = $this->operations[$operationName]->getInfoArray();
                    break;
                case self::ACTION_STOP:
                    if (!isset($this->operations[$operationName])) {
                        $this->operations[$operationName] = new Item\Operation($operationName);
                    }

                    $this->operations[$operationName]->stop();
                    $record['extra'][LoggerWt::CONTEXT_OPERATIONS][$operationName] = $this->operations[$operationName]->getInfoArray();
                    break;
                default:
            }
        }

        if (!empty($record['context'][LoggerWt::CONTEXT_FINISH_LOGGING])) {
            foreach ($this->operations as $operationName => $operation) {
                if ($operation->isRunning()) {
                    $this->operations[$operationName]->stop();
                    $record['extra'][LoggerWt::CONTEXT_OPERATIONS][$operationName] = $this->operations[$operationName]->getInfoArray();
                }
            }
        }

        return $record;
    }
}
