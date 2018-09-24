<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Handler;

use Monolog\Handler\RavenHandler as MonologRavenHandler;

class RavenHandler extends MonologRavenHandler
{
    /**
     * Return the last captured event's ID or null if none available.
     *
     * @return string | null
     */
    public function getLastEventID()
    {
        return $this->ravenClient->getLastEventID();
    }
}
