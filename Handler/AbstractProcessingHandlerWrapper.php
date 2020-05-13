<?php
/**
 * Wrapper for handlers with internal processors
 *
 * Process $record with processors before execute custom preHandle actions and handle wrapped handler
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\MonologExtensions\Handler;

use Monolog\Handler\HandlerWrapper;
use Monolog\Handler\ProcessableHandlerTrait;
use LogicException;

abstract class AbstractProcessingHandlerWrapper extends HandlerWrapper
{
    use ProcessableHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): void
    {
        if (!$this->isHandling($record)) {
            return;
        }

        $this->popProcessorsFromWrappedHandler();
        $record = $this->processRecord($record);
        $record = $this->preHandle($record);
        $this->handler->handle($record);
        $this->pushProcessorsToWrappedHandler();
    }

    /**
     * @param array $record
     *
     * @return array
     */
    abstract protected function preHandle(array $record): array;

    /**
     * @return void
     */
    private function popProcessorsFromWrappedHandler(): void
    {
        try {
            while ($processor = $this->handler->popProcessor()) {
                $this->pushProcessor($processor);
            }
        } catch (LogicException $e) {
            // ag: All processors have been popped
        }
    }

    /**
     * @return void
     */
    private function pushProcessorsToWrappedHandler(): void
    {
        try {
            while ($processor = $this->popProcessor()) {
                $this->handler->pushProcessor($processor);
            }
        } catch (LogicException $e) {
            // ag: All processors have been pushed
        }
    }
}
