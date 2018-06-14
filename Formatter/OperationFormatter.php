<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Formatter;

use \Monolog\Formatter\FormatterInterface;
use \whotrades\MonologExtensions\LoggerWt;
use \whotrades\MonologExtensions\Item\Operation;

class OperationFormatter implements FormatterInterface
{
    const PREFIX_LAST = 'last_';

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        if (empty($record['extra'][LoggerWt::CONTEXT_OPERATIONS])) {
            return $record;
        }

        foreach ($record['extra'][LoggerWt::CONTEXT_OPERATIONS] as &$operationInfo) {
            if ($operationInfo[Operation::INFO_FIELD_LAST_START_TIME]) {
                $dateTime = \DateTime::createFromFormat('U.u', sprintf('%.6F', $operationInfo[Operation::INFO_FIELD_LAST_START_TIME]));

                $operationInfo[Operation::INFO_FIELD_LAST_START_TIME] = $dateTime->format('c') .
                    " (timestamp: {$operationInfo[Operation::INFO_FIELD_LAST_START_TIME]})";
            }

            // ag: Correct some field's names and remove redundant fields for operations those ran only once
            if ($operationInfo[Operation::INFO_FIELD_RUNNING_COUNT] <= 1) {
                // ag: Set new names for useful fields
                $operationInfo[str_replace(self::PREFIX_LAST, '', Operation::INFO_FIELD_LAST_START_TIME)] = $operationInfo[Operation::INFO_FIELD_LAST_START_TIME];
                if ($operationInfo[Operation::INFO_FIELD_STATUS] === Operation::STATUS_STOPPED && $operationInfo[Operation::INFO_FIELD_LAST_RUNNING_TIME]) {
                    $operationInfo[str_replace(self::PREFIX_LAST, '', Operation::INFO_FIELD_LAST_RUNNING_TIME)] = $operationInfo[Operation::INFO_FIELD_LAST_RUNNING_TIME];
                }

                // ag: Remove inappropriate fields
                unset($operationInfo[Operation::INFO_FIELD_LAST_START_TIME]);
                unset($operationInfo[Operation::INFO_FIELD_LAST_RUNNING_TIME]);
                unset($operationInfo[Operation::INFO_FIELD_TOTAL_RUNNING_TIME]);
            }

            if (empty($operationInfo[Operation::INFO_FIELD_RUNNING_COUNT])) {
                unset($operationInfo[Operation::INFO_FIELD_RUNNING_COUNT]);
            }

            if (empty($operationInfo[Operation::INFO_FIELD_ERROR])) {
                unset($operationInfo[Operation::INFO_FIELD_ERROR]);
            }
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        $formatted = [];

        foreach ($records as $record) {
            $formatted[] = $this->format($record);
        }

        return $formatted;
    }
}
