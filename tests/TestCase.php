<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 *
 * @package whotrades\MonologExtensions\tests
 */
namespace whotrades\MonologExtensions\tests;

use Monolog\Logger;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array Record
     */

    /**
     * @param string | null $level
     * @param string | null $message
     * @param array | null $context
     * @param array | null $extra
     *
     * @return array
     */
    protected function getRecord($level = null, $message = null, array $context = null, array $extra = null)
    {
        $level = $level ?? Logger::NOTICE;
        $message = $message ?? 'test';
        $context = $context ?? [];
        $extra = $extra ?? [];

        return array(
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => $extra,
        );
    }
}
