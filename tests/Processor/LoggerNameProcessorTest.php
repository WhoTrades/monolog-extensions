<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\tests\Processor;

use whotrades\MonologExtensions\tests\TestCase;

class LoggerNameProcessorTest extends TestCase
{
    /**
     * @param array $extra
     *
     * @dataProvider providerProcessor
     */
    public function testProcessor(array $extra)
    {
        $processor = new \whotrades\MonologExtensions\Processor\LoggerNameProcessor();
        $loggerName = $processor->getLoggerName();

        $record = $processor($this->getRecord(null, null, null, $extra));

        $this->assertTrue(isset($record['extra']['tags']['logger']));
        $this->assertEquals(['logger' => $loggerName] + $extra['tags'], $record['extra']['tags']);
    }

    /**
     * @param array $extra
     *
     * @dataProvider providerProcessor
     */
    public function testDoubleProcessor(array $extra)
    {
        $record = $this->getRecord(null, null, null, $extra);

        $processorOne = new \whotrades\MonologExtensions\Processor\LoggerNameProcessor();
        $loggerNameOne = $processorOne->getLoggerName();
        $record = $processorOne($record);

        $processorTwo = new \whotrades\MonologExtensions\Processor\LoggerNameProcessor();
        $loggerNameTwo = $processorTwo->getLoggerName();
        $record = $processorTwo($record);

        $this->assertTrue(isset($record['extra']['tags']['logger']));
        $this->assertEquals($loggerNameTwo, $record['extra']['tags']['logger']);
    }

    /**
     * @return array
     */
    public function providerProcessor()
    {
        return [
            ['extra' => ['tags' => []]],
            ['extra' => ['tags' => ['tag1' => 'tag1', 'tag2' => 'tag2']]],
            ['extra' => ['tags' => ['logger' => 'logger_name', 'tag1' => 'tag1', 'tag2' => 'tag2']]],
        ];
    }
}
