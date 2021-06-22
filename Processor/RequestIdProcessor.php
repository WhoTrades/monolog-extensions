<?php

namespace whotrades\MonologExtensions\Processor;

use Monolog\Processor\ProcessorInterface;
use whotrades\MonologExtensions\LoggerWt;

class RequestIdProcessor implements ProcessorInterface
{
    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array
    {
        if (empty($_SERVER['HTTP_REQUEST_ID'])) {
            return $record;
        }

        if (!isset($record['extra'][LoggerWt::CONTEXT_TAGS])) {
            $record['extra'][LoggerWt::CONTEXT_TAGS] = [];
        }

        $record['extra'][LoggerWt::CONTEXT_TAGS]['request_id'] = $_SERVER['HTTP_REQUEST_ID'];

        return $record;
    }
}
