<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Formatter;

use \whotrades\MonologExtensions\LoggerWt;

class ConsoleFormatter extends LineFormatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = null, $ignoreEmptyContextAndExtra = null)
    {
        $format = $format ?? "%start_tag%%datetime%%level_abbr%%end_tag%%message% %context% %logger_info% %extra.operations%\n";

        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
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
}
