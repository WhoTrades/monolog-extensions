<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\MonologExtensions\Handler;

interface SentryHandlerInterface
{
    /**
     * Return the last captured event's ID or null if none available.
     *
     * @return string|null
     */
    public function getLastEventID(): ?string;
}
