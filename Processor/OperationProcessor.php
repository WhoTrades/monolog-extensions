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

    const PREFIX_LAST = 'last_';

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
        if (isset($record['context'][LoggerWt::CONTEXT_OPERATIONS]) && is_array($record['context'][LoggerWt::CONTEXT_OPERATIONS])) {
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
        }

        if (!empty($record['context'][LoggerWt::CONTEXT_FINISH_LOGGING])) {
            foreach ($this->operations as $operationName => $operation) {
                if ($operation->isRunning()) {
                    $this->operations[$operationName]->stop();
                    $record['extra'][LoggerWt::CONTEXT_OPERATIONS][$operationName] = $this->operations[$operationName]->getInfoArray();
                }
            }
        }

        $record = $this->formatOperations($record);

        return $record;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function formatOperations(array $record)
    {
        if (empty($record['extra'][LoggerWt::CONTEXT_OPERATIONS])) {
            return $record;
        }

        foreach ($record['extra'][LoggerWt::CONTEXT_OPERATIONS] as &$operationInfo) {
            if ($operationInfo[Item\Operation::INFO_FIELD_LAST_START_TIME]) {
                $dateTime = \DateTime::createFromFormat('U.u', sprintf('%.6F', $operationInfo[Item\Operation::INFO_FIELD_LAST_START_TIME]));

                $operationInfo[Item\Operation::INFO_FIELD_LAST_START_TIME] = $dateTime->format('c') .
                    " (timestamp: {$operationInfo[Item\Operation::INFO_FIELD_LAST_START_TIME]})";
            }

            // ag: Correct some field's names and remove redundant fields for operations those ran only once
            if ($operationInfo[Item\Operation::INFO_FIELD_RUNNING_COUNT] <= 1) {
                // ag: Set new names for useful fields
                $operationInfo[str_replace(self::PREFIX_LAST, '', Item\Operation::INFO_FIELD_LAST_START_TIME)] = $operationInfo[Item\Operation::INFO_FIELD_LAST_START_TIME];
                if ($operationInfo[Item\Operation::INFO_FIELD_STATUS] === Item\Operation::STATUS_STOPPED && $operationInfo[Item\Operation::INFO_FIELD_LAST_RUNNING_TIME]) {
                    $operationInfo[str_replace(self::PREFIX_LAST, '', Item\Operation::INFO_FIELD_LAST_RUNNING_TIME)] = $operationInfo[Item\Operation::INFO_FIELD_LAST_RUNNING_TIME];
                }

                // ag: Remove inappropriate fields
                unset($operationInfo[Item\Operation::INFO_FIELD_LAST_START_TIME]);
                unset($operationInfo[Item\Operation::INFO_FIELD_LAST_RUNNING_TIME]);
                unset($operationInfo[Item\Operation::INFO_FIELD_TOTAL_RUNNING_TIME]);
            }

            if (empty($operationInfo[Item\Operation::INFO_FIELD_RUNNING_COUNT])) {
                unset($operationInfo[Item\Operation::INFO_FIELD_RUNNING_COUNT]);
            }

            if (empty($operationInfo[Item\Operation::INFO_FIELD_ERROR])) {
                unset($operationInfo[Item\Operation::INFO_FIELD_ERROR]);
            }
        }

        return $record;
    }
}
