<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Handler;

use whotrades\MonologExtensions\LoggerWt;
use Monolog\Handler\SyslogHandler as MonologSyslogHandler;

class SyslogHandler extends MonologSyslogHandler
{
    const ENV_VAR_TOOL_LOG_IDENT = 'TOOL_LOG_IDENT';

    /**
     * {@inheritdoc}
     */
    public function __construct($ident, $facility = null, $level = null, $bubble = null, $logopts = null)
    {
        // ag: Try to redefine level by verbosity for tools
        if (php_sapi_name() === 'cli') {
            $level = $this->getLogLevelFromArgv() ?? $level;
        }

        parent::__construct($ident, $facility, $level, $bubble, $logopts);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $identOld = null;
        if (isset($_ENV[self::ENV_VAR_TOOL_LOG_IDENT])) {
            $identOld = $this->ident;
            $this->ident = $_ENV[self::ENV_VAR_TOOL_LOG_IDENT];
        }

        parent::write($record);

        if ($identOld) {
            $this->ident = $identOld;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter()
    {
        return new \whotrades\MonologExtensions\Formatter\LineFormatter(null, '');
    }

    /**
     * @return int
     */
    protected function getLogLevelFromArgv()
    {
        foreach ($_SERVER['argv'] as $arg) {
            // -v, -vv, ...
            if (0 === strpos($arg, '-v')) {
                if (preg_match('/-(v+)$/', $arg, $matches)) {
                    switch (mb_strlen($matches[1])) {
                        case 1: // ag: -v
                            return LoggerWt::NOTICE;
                        case 2: // ag: -vv
                            return LoggerWt::INFO;
                        case 3: // ag: -vvv
                            return LoggerWt::DEBUG;
                    }
                    continue;
                }
            }
            // --verbose
            if ($arg === '--verbose') {
                return LoggerWt::NOTICE;
            }
        }

        return LoggerWt::WARNING;
    }
}
