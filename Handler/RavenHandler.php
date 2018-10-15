<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Handler;

use Monolog\Handler\RavenHandler as MonologRavenHandler;
use whotrades\MonologExtensions\LoggerWt;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Raven_Client;

class RavenHandler extends MonologRavenHandler
{
    //******************************************************************************************************************
    // Copy-paste from MonologRavenHandler
    // Reason: These properties are private, but we need use them in this class
    //

    /**
     * Translates Monolog log levels to Raven log levels.
     */
    private $logLevels = array(
        Logger::DEBUG     => Raven_Client::DEBUG,
        Logger::INFO      => Raven_Client::INFO,
        Logger::NOTICE    => Raven_Client::INFO,
        Logger::WARNING   => Raven_Client::WARNING,
        Logger::ERROR     => Raven_Client::ERROR,
        Logger::CRITICAL  => Raven_Client::FATAL,
        Logger::ALERT     => Raven_Client::FATAL,
        Logger::EMERGENCY => Raven_Client::FATAL,
    );

    /**
     * @var string should represent the current version of the calling
     *             software. Can be any string (git commit, version number)
     */
    private $release;

    //
    // Copy-paste from MonologRavenHandler
    //******************************************************************************************************************

    /**
     * @var array | string | null
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function __construct(Raven_Client $ravenClient, $level = null, $bubble = null, $release = null, $user = null)
    {
        $this->release = $release;
        $this->user = $user;

        $level = $level ?? Logger::DEBUG;
        $bubble = $bubble ?? true;

        parent::__construct($ravenClient, $level, $bubble);
    }

    /**
     * @param array | string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Return the last captured event's ID or null if none available.
     *
     * @return string | null
     */
    public function getLastEventID()
    {
        return $this->ravenClient->getLastEventID();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $options = array();

        // ag: Add loggerName for MonologRavenHandler
        if (isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME])) {
            $options['logger'] = $record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME];
        }

        // ag: Add author for MonologRavenHandler
        if (isset($record['context'][LoggerWt::CONTEXT_AUTHOR])) {
            $record['context']['tags'][LoggerWt::CONTEXT_AUTHOR] = $record['context'][LoggerWt::CONTEXT_AUTHOR];
        } else {
            $record['context']['tags'][LoggerWt::CONTEXT_AUTHOR] = LoggerWt::DEFAULT_AUTHOR;
        }

        $record['context']['user'] = $this->user;

        $fileLine = $this->getFileLine($record);
        $options['extra']['ERROR'] = [
            'file'      => $fileLine['file'],
            'line'      => $fileLine['line'],
            'message'   => $record['message'],
            'timestamp' => date(DATE_W3C),

            'author'        => $record['context']['tags'][LoggerWt::CONTEXT_AUTHOR],
            'version'       => $this->release,
            'level'      => $record['level'],
            'levelName'  => $record['level_name'],
            'context'  => $record['context'],
            'exception' => isset($record['context']['exception']) ? $this->formatException($record['context']['exception']) : null,
        ];

        $options['extra']['ENVIRONMENT'] = [
            'URL'       => $this->getUrl(),
            'SERVER'    => $_SERVER,
            'POST'      => @$_POST,
            'FILES'     => @$_FILES,
            'possible last error' => error_get_last(),
            'executed queries'  => class_exists('Mapper', false) ? \Mapper::getExecutedQueries() : null,
            'PID'        => getmypid(),
            'hostname'   => gethostname(),
        ];

        $options['extra']['TRACE'] = $this->getStackTrace($record);

        if (class_exists('ApplicationException')) {
            // possible exception stack
            foreach (\ApplicationException::getExceptions() as $e) {
                $options['extra']['Application exceptions'][] = self::formatException($e);
            }
        }

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            /** @var \Exception $exception */
            $exception = $record['context']['exception'];
            if (method_exists($exception, 'getMoreExceptionInformation')) {
                $options['extra']['Exception information'] = $exception->getMoreExceptionInformation();
            }
        }

        //******************************************************************************************************************
        // Copy-paste from MonologRavenHandler
        // Reasons:
        //     1. It is impossible to add section $options['extra'][...something...] using only inheritance
        //     2. It is impossible to force collect trace in ravenClient->captureMessage using only inheritance
        //

        $options['level'] = $this->logLevels[$record['level']];
        $options['tags'] = array();
        if (!empty($record['extra']['tags'])) {
            $options['tags'] = array_merge($options['tags'], $record['extra']['tags']);
            unset($record['extra']['tags']);
        }
        if (!empty($record['context']['tags'])) {
            $options['tags'] = array_merge($options['tags'], $record['context']['tags']);
            unset($record['context']['tags']);
        }
        if (!empty($record['context']['fingerprint'])) {
            $options['fingerprint'] = $record['context']['fingerprint'];
            unset($record['context']['fingerprint']);
        }

        foreach ($this->getExtraParameters() as $key) {
            foreach (array('extra', 'context') as $source) {
                if (!empty($record[$source][$key])) {
                    $options[$key] = $record[$source][$key];
                    unset($record[$source][$key]);
                }
            }
        }

        $previousUserContext = false;
        if (!empty($record['context'])) {
            $options['extra']['context'] = $record['context'];
            if (!empty($record['context']['user'])) {
                $previousUserContext = $this->ravenClient->context->user;
                $this->ravenClient->user_context($record['context']['user']);
                unset($options['extra']['context']['user']);
            }
        }
        if (!empty($record['extra'])) {
            $options['extra']['extra'] = $record['extra'];
        }

        if (!empty($this->release) && !isset($options['release'])) {
            $options['release'] = $this->release;
        }

        //
        // Copy-paste from MonologRavenHandler
        //******************************************************************************************************************

        if (isset($record['context']['exception']) &&
            ($record['context']['exception'] instanceof \Exception || (PHP_VERSION_ID >= 70000 && $record['context']['exception'] instanceof \Throwable))) {
            $options['extra']['message'] = $record['formatted'];
            $this->ravenClient->captureException($record['context']['exception'], $options);
        } else {
            $this->ravenClient->captureMessage($record['formatted'], array(), $options, $record['context'][LoggerWt::CONTEXT_COLLECT_TRACE] ?? false);
        }

        //******************************************************************************************************************
        // Copy-paste from MonologRavenHandler
        //

        if ($previousUserContext !== false) {
            $this->ravenClient->user_context($previousUserContext);
        }

        //
        // Copy-paste from MonologRavenHandler
        //******************************************************************************************************************
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter('%message%');
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function getFileLine($record)
    {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            /** @var \Exception $exception */
            $exception = $record['context']['exception'];

            return [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        // try to get file and line from context similar to result of error_get_last()
        if (!empty($record['context']['file']) && !empty($record['context']['line'])) {
            return [
                'file' => $record['context']['file'],
                'line' => $record['context']['line'],
            ];
        }

        if ($stackTrace = $this->getStackTrace()) {
            return [
                'file' => $stackTrace[0]['file'],
                'line' => $stackTrace[0]['line'],
            ];
        }

        return [
            'file' => null,
            'line' => null,
        ];
    }

    /**
     * @param array | null $record
     *
     * @return array | null
     */
    protected function getStackTrace(array $record = null)
    {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            /** @var \Exception $exception */
            $exception = $record['context']['exception'];

            return $exception->getTrace();
        }

        $stackTrace = null;
        if (extension_loaded('xdebug') && function_exists('xdebug_get_function_stack')) {
            // xdebug provides reversed trace - last call at the end of trace
            $stackTrace =  array_reverse(xdebug_get_function_stack());
        } elseif (class_exists('Debug_HackerConsole_Main')) {
            $stackTrace = \Debug_HackerConsole_Main::debug_backtrace_smart();
        }

        return $stackTrace;
    }

    /**
     * @param \Throwable $e
     *
     * @return array | \Throwable
     */
    protected function formatException(\Throwable $e)
    {
        if (class_exists('ApplicationException') && $e instanceof \ApplicationException) {
            return [
                'ApplicationException:ApplicationMessage: ' => $e->getApplicationMessage(),
                'ApplicationException:MoreExceptionInformation: ' => $e->getMoreExceptionInformation(),
                $e,
            ];
        }

        return $e;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SERVER_NAME'])) {
            return "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
        }

        return '';
    }
}
