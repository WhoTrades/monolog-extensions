<?php
/**
 * @author Anton Gorlanov
 */
namespace whotrades\MonologExtensions;

use Monolog\Logger;

class LoggerWt extends Logger
{
    const CONTEXT_AUTHOR = 'author'; // ag: author of log
    const CONTEXT_EXCEPTION = 'exception'; // ag: Object of type \Throwable
    const CONTEXT_DUMP = 'dump'; // ag: Force send log to sentry
    const CONTEXT_COLLECT_TRACE = 'collect_trace'; // ag: Add to log collected trace
    const CONTEXT_PROCESS = 'process';
    const CONTEXT_STATUS = 'status';
    const CONTEXT_REASON = 'reason';
}
