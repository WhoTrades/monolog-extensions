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
        $format = $format ?? "%datetime%%level_abbr%%message% %context% %logger_info% %extra.operations%";
        $allowInlineLineBreaks = $allowInlineLineBreaks ?? true;
        $ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra ?? true;

        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);

        $this->dateFormat = $dateFormat ?? '[d M y H:i:s e] ';
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        $record = $this->formatLevelAbbr($record);
        $record = $this->formatLoggerName($record);
        $record = $this->checkExtraOperations($record);

        if ($record['level'] >= LoggerWt::ERROR) {
            $record['start_tag'] = '<error>';
            $record['end_tag'] = '</error>';
        } elseif ($record['level'] >= LoggerWt::NOTICE) {
            $record['start_tag'] = '<comment>';
            $record['end_tag'] = '</comment>';
        } elseif ($record['level'] >= LoggerWt::INFO) {
            $record['start_tag'] = '<info>';
            $record['end_tag'] = '</info>';
        } else {
            $record['start_tag'] = '';
            $record['end_tag'] = '';
        }

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
                $levelAbbr = '[W] ';
                break;
            case $record['level'] >= \Monolog\Logger::ERROR:
                $levelAbbr = '[E] ';
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
        $record['logger_info'] = '';
        if (isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME])) {
            $record['logger_info'] .= "logger={$record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME]}";
        }

        if (isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_PARTITION])) {
            $record['logger_info'] .= " partition={$record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_PARTITION]}";
        }

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
    protected function normalizeException(\Throwable $e, int $depth = 0): string
    {
        $exceptionInfo = parent::normalizeException($e);

        if (class_exists('ApplicationException') && $e instanceof \ApplicationException) {
            $exceptionInfo .= "\n" . 'ApplicationException:ApplicationMessage: ' . LoggerWt::arrayToString((array) $e->getApplicationMessage()) .
                "\n" . 'ApplicationException:MoreExceptionInformation: ' . LoggerWt::arrayToString($e->getMoreExceptionInformation());
        }

        return $exceptionInfo;
    }
}
