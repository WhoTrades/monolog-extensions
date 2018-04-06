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
    const CONTEXT_CONTEXT = 'context';
    const CONTEXT_RETRY_TIME = 'retry_time';

    const DEFAULT_AUTHOR = 'all';
    const DEFAULT_STATUS = 'unknown';

    /**
     * @param array $array
     * @param bool $coverToBrackets
     *
     * @return string
     */
    public static function arrayToString(array $array, $coverToBrackets = null)
    {
        $coverToBrackets = $coverToBrackets ?? true;

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::arrayToString($value);
            } elseif (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $value = (string) $value;
                } else {
                    $value = 'Class: ' . get_class($value);
                }
            }

            $value = (is_int($key) ? $value : ($key . '=' . $value));
        }

        if ($coverToBrackets) {
            return '[' . implode(', ', $array) . ']';
        }

        return implode(', ', $array);
    }
}
