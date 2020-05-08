<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Processor;

use Monolog\Processor\ProcessorInterface;
use whotrades\MonologExtensions\LoggerWt;

class LoggerNameProcessor implements ProcessorInterface
{
    const GENERATED_NAME_LENGTH = 10;

    /*
     * string
     */
    private $loggerName;

    /**
     * LoggerNameProcessor constructor.
     */
    public function __construct()
    {
        $this->loggerName = $this->generateReadableWord(self::GENERATED_NAME_LENGTH);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record)
    {
        $loggerName = isset($record['channel']) ? "{$record['channel']}:{$this->loggerName}" : $this->loggerName;

        if (!isset($record['extra'][LoggerWt::CONTEXT_TAGS])) {
            $record['extra'][LoggerWt::CONTEXT_TAGS] = [];
        }

        // ag: Add tag logger to extra
        if (isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME])) {
            // ag: Change existed name
            $record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME] = $loggerName;
        } else {
            // ag: Set logger name to beginning of tags array
            $record['extra'][LoggerWt::CONTEXT_TAGS] = [LoggerWt::TAG_LOGGER_NAME => $loggerName] + $record['extra'][LoggerWt::CONTEXT_TAGS];
        }

        return $record;
    }

    /**
     * @return string
     */
    public function getLoggerName()
    {
        return $this->loggerName;
    }

    /**
     * Simple almost-readable word generator (a bit improved from PHP manual)
     *
     * @param int $minLength // min word's length
     * @param int $maxLength // max word's length
     *
     * @return string
     */
    protected function generateReadableWord($minLength, $maxLength = null)
    {
        if ($maxLength === null) {
            $length = $minLength;
        } else {
            $length = mt_rand($minLength, $maxLength);
        }
        $_vowels = array ('a', 'e', 'i', 'o', 'u');
        $_consonants = array ('b', 'c', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'z');
        $_syllables = array ();
        foreach ($_vowels as $v) {
            foreach ($_consonants as $c) {
                array_push($_syllables, "$c$v");
                array_push($_syllables, "$v$c");
            }
        }
        $newpass = '';
        for ($i = 0; $i <= (min($length/2, ($length - 1) / 2)); $i++) {
            $newpass .= $_syllables[array_rand($_syllables)];
        }

        return substr($newpass, 0, $length);
    }
}
