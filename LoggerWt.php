<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions;

use Monolog\DateTimeImmutable;
use Monolog\Logger;
use whotrades\MonologExtensions\Handler;
use whotrades\MonologExtensions\Processor;

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
    const CONTEXT_OPERATIONS = 'operations'; // ag: Operations for processing with Processor\OperationProcessor
    const CONTEXT_FINISH_LOGGING = 'finish_logging'; // ag: Context flag for getting additional information from processors while destructing
    const CONTEXT_EXTRA_USER = 'user'; // ag: Context extra flag for collecting user information
    const CONTEXT_FINGERPRINT = 'fingerprint'; // ag: Context flag for collecting user fingerprint

    const TAG_LOGGER_NAME = 'logger';
    const TAG_PARTITION = 'partition';

    const PROCESS_STATUS_RETRY = 'retry';

    const DEFAULT_AUTHOR = 'all';
    const DEFAULT_STATUS = 'unknown';
    const DEFAULT_RETRY_TIME = 'unknown';

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->debug('Finish logging', [self::CONTEXT_FINISH_LOGGING => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord(int $level, string $message, array $context = [], DateTimeImmutable $datetime = null): bool
    {
        $context = $context ?? [];

        if (!$res = parent::addRecord($level, $message, $context, $datetime)) {
            // ag: Force tagging
            if (isset($context[LoggerWt::CONTEXT_TAGS])) {
                foreach ($this->processors as $processor) {
                    if ($processor instanceof Processor\TagCollectorProcessor) {
                        $processor->addTags($context[LoggerWt::CONTEXT_TAGS]);
                    }
                }
            }

            // ag: Force process operations
            if (isset($context[LoggerWt::CONTEXT_OPERATIONS])) {
                foreach ($this->processors as $processor) {
                    if ($processor instanceof Processor\OperationProcessor) {
                        $processor(['context' => $context]);
                    }
                }
            }
        }

        foreach ($this->handlers as $handler) {
            if ($handler instanceof Handler\SentryHandlerInterface) {
                if ($handler->isHandling(['level' => $level])) {
                    // vdm: делаем переликновку между дампами в sentry в логами приложения @since #WTA-897
                    $this->warning('process=dump, status=sent, target=sentry, event_id=' . $this->getLastSentryEventID());
                }
            }
        }

        return $res;
    }

    /**
     * @deprecated Use getLastSentryEventID instead
     *
     * Return the last captured Raven event's ID or null if none available.
     *
     * @return string | null
     */
    public function getLastRavenEventID()
    {
        return $this->getLastSentryEventID();
    }

    /**
     * Return the last captured Raven event's ID or null if none available.
     *
     * @return string|null
     */
    public function getLastSentryEventID()
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof Handler\SentryHandlerInterface) {
                return $handler->getLastEventID();
            }
        }

        return null;
    }

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
