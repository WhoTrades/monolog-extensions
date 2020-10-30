<?php
/**
 * Sentry Monolog Handler
 *
 * Wrap Sentry\Monolog\Handler for adding additional data to sentry event
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\MonologExtensions\Handler;

use Monolog\Formatter\FormatterInterface;
use Sentry\Monolog\Handler as SentryHandlerGeneric;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use whotrades\MonologExtensions\LoggerWt;
use whotrades\MonologExtensions\Formatter\LineFormatter;

class SentryHandler extends AbstractProcessingHandlerWrapper implements SentryHandlerInterface
{
    const EXTRA_ENVIRONMENT = 'ENVIRONMENT';
    const EXTRA_ERROR = 'ERROR';
    const TAG_LOGGER_ID = 'logger_id';


    /**
     * @var SentryHandlerGeneric
     */
    protected $handler;

    /**
     * @var HubInterface
     */
    protected $hub;

    /**
     * @param HubInterface $hub    The hub to which errors are reported
     * @param int|null     $level  The minimum logging level at which this
     *                             handler will be triggered
     * @param bool|null    $bubble Whether the messages that are handled can
     *                             bubble up the stack or not
     */
    public function __construct(HubInterface $hub, int $level = null, bool $bubble = null)
    {
        $bubble = $bubble ?? true;

        $this->hub = $hub;

        parent::__construct(new SentryHandlerGeneric($hub, $level, $bubble));
        $this->handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * {@inheritdoc}
     */
    protected function preHandle(array $record): array
    {
        $this->trySetAttachStacktrace($record);
        $this->hub->configureScope(function (Scope $scope) use (&$record): void {
            $this->trySetTagLoggerId($scope, $record);
            if ($this->hasUserData($record)) {
                $scope->setUser($this->getUserData($record));
                $record = $this->unsetUserData($record);
            }
            if ($this->hasFingerprint($record)) {
                $scope->setFingerprint($this->getFingerprint($record));
                $record = $this->unsetFingerprint($record);
            }
            $this->setExtraEnvironmentToScope($scope);
            $this->setExtraErrorToScope($scope, $record);
            // Compatibility with 2.x sentry-php library
            //$this->setExtraToScope($scope, $record);
            //$this->setTagsToScope($scope, $record);
        });

        return $record;
    }

    /**
     * Return the last captured event's ID or null if none available.
     *
     * @return string|null
     */
    public function getLastEventID(): ?string
    {
        return $this->hub->getLastEventID();
    }

    /**
     * Gets the default formatter
     *
     * @return FormatterInterface
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter(null, '');
    }

    /**
     * @param array $record
     *
     * @return void
     */
    private function trySetAttachStacktrace(array $record): void
    {
        if (!empty($record['context'][LoggerWt::CONTEXT_COLLECT_TRACE])) {
            $this->hub->getClient()->getOptions()->setAttachStacktrace(true);
        }
    }

    /**
     * @param Scope $scope
     * @param array $record
     *
     * @return void
     */
    private function trySetTagLoggerId(Scope $scope, array $record): void
    {
        if ($this->hasTagLoggerId($record)) {
            $scope->setTag(self::TAG_LOGGER_ID, $this->getTagLoggerId($record));
        }
    }

    /**
     * @param array $record
     *
     * @return bool
     */
    private function hasTagLoggerId(array $record): bool
    {
        return isset($record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME]);
    }

    /**
     * @param array $record
     *
     * @return string
     */
    private function getTagLoggerId(array $record): string
    {
        if (!$this->hasTagLoggerId($record)) {
            return "Doesn't have logger_id";
        }

        return $record['extra'][LoggerWt::CONTEXT_TAGS][LoggerWt::TAG_LOGGER_NAME];
    }

    /**
     * @param Scope $scope
     *
     * @return void
     */
    private function setExtraEnvironmentToScope(Scope $scope): void
    {
        $scope->setExtra(self::EXTRA_ENVIRONMENT, $this->getExtraEnvironment());
    }

    /**
     * @return array
     */
    private function getExtraEnvironment(): array
    {
        return [
            'SERVER'   => $_SERVER,
            'PID'      => getmypid(),
            'hostname' => gethostname(),
        ];
    }

    /**
     * @param Scope $scope
     * @param array $record
     *
     * @return void
     */
    private function setExtraErrorToScope(Scope $scope, array $record): void
    {
        $scope->setExtra(self::EXTRA_ERROR, $this->getExtraError($record));
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function getExtraError(array $record): array
    {
        return [
            'message' => $record['message'],
            'author'  => $record['context']['tags'][LoggerWt::CONTEXT_AUTHOR] ?? LoggerWt::DEFAULT_AUTHOR,
            'context' => $record['context'],
            'possible last error' => error_get_last(),
        ];
    }

    /**
     * @param Scope $scope
     * @param array $record
     */
    private function setExtraToScope(Scope $scope, array $record): void
    {
        if (isset($record['context']['extra']) && \is_array($record['context']['extra'])) {
            foreach ($record['context']['extra'] as $key => $value) {
                $scope->setExtra((string) $key, $value);
            }
        }
    }

    /**
     * @param Scope $scope
     * @param array $record
     */
    private function setTagsToScope(Scope $scope, array $record): void
    {
        if (isset($record['context']['tags']) && \is_array($record['context']['tags'])) {
            foreach ($record['context']['tags'] as $key => $value) {
                $scope->setTag($key, $value);
            }
        }
    }

    /**
     * @param array $record
     *
     * @return bool
     */
    private function hasUserData(array $record) : bool
    {
        return isset($record['context']['extra'][LoggerWt::CONTEXT_EXTRA_USER]);
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function getUserData(array $record): array
    {
        if (!$this->hasUserData($record)) {
            return ["Doesn't have user data"];
        }

        return $record['context']['extra'][LoggerWt::CONTEXT_EXTRA_USER];
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function unsetUserData(array $record): array
    {
        unset($record['context']['extra'][LoggerWt::CONTEXT_EXTRA_USER]);

        return $record;
    }

    /**
     * @param array $record
     *
     * @return bool
     */
    private function hasFingerprint(array $record) : bool
    {
        return isset($record['context'][LoggerWt::CONTEXT_FINGERPRINT]);
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function getFingerprint(array $record): array
    {
        if (!$this->hasFingerprint($record)) {
            return ["Doesn't have fingerprint"];
        }

        return $record['context'][LoggerWt::CONTEXT_FINGERPRINT];
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function unsetFingerprint(array $record): array
    {
        unset($record['context'][LoggerWt::CONTEXT_FINGERPRINT]);

        return $record;
    }
}
