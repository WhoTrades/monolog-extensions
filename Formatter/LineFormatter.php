<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Formatter;

use \Monolog\Formatter\LineFormatter as MonologLineFormatter;
use \whotrades\MonologExtensions\LoggerWt;

class LineFormatter extends MonologLineFormatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = null, $ignoreEmptyContextAndExtra = null)
    {
        $format = $format ?? "[%datetime%] %level_abbr% %logger_name%  %message% %context% %extra.operations%\n";
        $dateFormat = $dateFormat ?? 'd M y H:i:s e';
        $allowInlineLineBreaks = $allowInlineLineBreaks ?? true;
        $ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra ?? true;

        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $record = $this->formatLevelAbbr($record);
        $record = $this->formatLoggerName($record);
        $record = $this->checkExtraOperations($record);

        return parent::format($record);
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function formatLevelAbbr($record)
    {
        $levelAbbr = '';
        switch (true) {
            case $record['level'] === \Monolog\Logger::WARNING:
                $levelAbbr = '[W]';
                break;
            case $record['level'] >= \Monolog\Logger::ERROR:
                $levelAbbr = '[E]';
                break;
        }

        $record['level_abbr'] = $levelAbbr;

        return $record;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function formatLoggerName($record)
    {
        $loggerName = [];
        if (isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME])) {
            $loggerName['logger'] = $record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME];
        }

        if (isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_PARTITION])) {
            $loggerName['partition'] = $record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_PARTITION];
        }

        $record['logger_name'] = parent::stringify(LoggerWt::arrayToString($loggerName));

        return $record;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function checkExtraOperations($record)
    {
        if (empty($record['extra'][LoggerWt::CONTEXT_OPERATIONS])) {
            $record['extra'][LoggerWt::CONTEXT_OPERATIONS] = '';
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeException($e)
    {
        $exceptionInfo = parent::normalizeException($e);

        if (class_exists('ApplicationException') && $e instanceof \ApplicationException) {
            $exceptionInfo .= "\n" . 'ApplicationException:ApplicationMessage: ' . LoggerWt::arrayToString((array) $e->getApplicationMessage()) .
                "\n" . 'ApplicationException:MoreExceptionInformation: ' . LoggerWt::arrayToString($e->getMoreExceptionInformation());
        }

        return $exceptionInfo;
    }
}
