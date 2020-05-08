<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Processor;

use Monolog\Processor\ProcessorInterface;
use whotrades\MonologExtensions\LoggerWt;

class TagProcessProcessor implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record)
    {
        if (isset($record['context'][LoggerWt::CONTEXT_PROCESS])) {
            // ag: Set default values
            $messageSyslogArray[LoggerWt::CONTEXT_PROCESS] = $record['context'][LoggerWt::CONTEXT_PROCESS];
            $messageSyslogArray[LoggerWt::CONTEXT_STATUS] = LoggerWt::DEFAULT_STATUS;
            if (isset($record['context'][LoggerWt::CONTEXT_STATUS]) && $record['context'][LoggerWt::CONTEXT_STATUS] === LoggerWt::PROCESS_STATUS_RETRY) {
                $messageSyslogArray[LoggerWt::CONTEXT_RETRY_TIME] = LoggerWt::DEFAULT_RETRY_TIME;
            }
            $messageSyslogArray[LoggerWt::CONTEXT_REASON] = $record['message'];

            // ag: Overwrite values if they exists in context
            $messageSyslogArray = array_merge($messageSyslogArray, array_intersect_key($record['context'], $messageSyslogArray));

            // ag: Replace context with others fields
            $record['context'] = array_diff_key($record['context'], $messageSyslogArray);

            // ag: Replace message with generated from context
            $record['message'] = LoggerWt::arrayToString($messageSyslogArray);
        }

        return $record;
    }
}
