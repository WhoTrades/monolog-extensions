<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\tests\Processor;

use whotrades\MonologExtensions\tests\TestCase;

class OperationProcessorTest extends TestCase
{
    /**
     * @param array $contextPre
     * @param array $context
     * @param array $result
     *
     * @dataProvider providerProcessor
     */
    public function testProcessor(array $contextPre, array $context, array $result)
    {
        $processor = new \whotrades\MonologExtensions\Processor\OperationProcessor();

        if ($contextPre) {
            $processor($this->getRecord(null, null, $contextPre));
        }
        $record = $processor($this->getRecord(null, null, $context));

        if (!empty($result['name'])) {
            $this->assertTrue(isset($record['extra']['operations'][$result['name']]));

            $operationInfoArray = $record['extra']['operations'][$result['name']];

            $this->assertEquals($result['name'], $operationInfoArray['name']);
            $this->assertEquals($result['status'], $operationInfoArray['status']);

            $this->assertEquals(empty($result['last_start_time']), empty($operationInfoArray['last_start_time']));
            $this->assertEquals(empty($result['last_running_time']), empty($operationInfoArray['last_running_time']));
            $this->assertEquals(empty($result['total_running_time']), empty($operationInfoArray['total_running_time']));
            $this->assertEquals(empty($result['running_count']), empty($operationInfoArray['running_count']));
            $this->assertEquals(empty($result['error']), empty($operationInfoArray['error']));
        } else {
            $this->assertFalse(isset($record['extra']['operations']));
        }
    }

    /**
     * @return array
     */
    public function providerProcessor()
    {
        return [
            [
                'contextPre' => [],
                'context' => [],
                'result' => [],
            ],
            [
                'contextPre' => [],
                'context' => ['operations' => ['testOne' => 'start']],
                'result' => [
                    'name' => 'testOne',
                    'status' => 'running',
                    'last_start_time' => true,
                    'running_count' => true,
                ],
            ],
            [
                'contextPre' => [],
                'context' => ['operations' => ['testOne' => 'stop']],
                'result' => [
                    'name' => 'testOne',
                    'status' => 'stopped',
                    'last_start_time' => false,
                    'error' => true,
                ],
            ],
            [
                'contextPre' => ['operations' => ['testOne' => 'start']],
                'context' => ['operations' => ['testOne' => 'start']],
                'result' => [
                    'name' => 'testOne',
                    'status' => 'running',
                    'last_start_time' => true,
                    'running_count' => true,
                    'error' => true,
                ],
            ],
        ];
    }
}
