<?php
/**
 * @author Anton Gorlanov
 */
namespace whotrades\MonologExtensions;

use Monolog\Logger;

class LoggerWt extends Logger
{
    const CONTEXT_AUTHOR = 'author'; // ag: Author of log
    const CONTEXT_EXCEPTION = 'exception'; // ag: Object of type \Throwable
    const CONTEXT_DUMP = 'dump'; // ag: Force send log to sentry
    const CONTEXT_COLLECT_TRACE = 'collect_trace'; // ag: Add to log collected trace
    const CONTEXT_PROCESS = 'process'; // ag: Use for formatting message
    const CONTEXT_STATUS = 'status'; // ag: Use for formatting message
    const CONTEXT_RETRY_TIME = 'retry_time'; // ag: Use for formatting message
    const CONTEXT_REASON = 'reason'; // ag: Use for formatting message
    const CONTEXT_CONTEXT = 'context'; // ag: Use for additional leveling of context
    const CONTEXT_TAGS = 'tags'; // ag: Tags for collecting with Processor\TagCollectorProcessor

    const PROCESS_STATUS_RETRY = 'retry';

    const DEFAULT_AUTHOR = 'all';
    const DEFAULT_STATUS = 'unknown';
    const DEFAULT_RETRY_TIME = 'unknown';

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
