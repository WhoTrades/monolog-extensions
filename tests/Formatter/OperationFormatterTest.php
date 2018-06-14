<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\tests\Formatter;

use whotrades\MonologExtensions\tests\TestCase;

class OperationFormatterTest extends TestCase
{
    /**
     * @param array $extra
     * @param array $result
     *
     * @dataProvider providerFormatter
     */
    public function testFormatter(array $extra, array $result)
    {
        $formatter = new \whotrades\MonologExtensions\Formatter\OperationFormatter();

        $recordFormatted = $formatter->format($this->getRecord(null, null, null, $extra));

        $operationFormatted = $recordFormatted['extra']['operations'][$result['name']];

        $this->assertEquals($result, $operationFormatted);
    }

    /**
     * @return array
     */
    public function providerFormatter()
    {
        return [
            [
                'extra' => [
                    'operations' => [
                        'testOperation' => [
                            'name' => 'testOperation',
                            'status' => 'stopped',
                            'last_start_time' => 0,
                            'last_running_time' => 0,
                            'total_running_time' => 0,
                            'running_count' => 0,
                            'error' => null,
                        ],
                    ],
                ],
                'result' => [
                    'name' => 'testOperation',
                    'status' => 'stopped',
                    'start_time' => 0,
                ],
            ],
            [
                'extra' => [
                    'operations' => [
                        'testOperation' => [
                            'name' => 'testOperation',
                            'status' => 'stopped',
                            'last_start_time' => 0,
                            'last_running_time' => 0,
                            'total_running_time' => 0,
                            'running_count' => 0,
                            'error' => '[E] Operation is not running',
                        ],
                    ],
                ],
                'result' => [
                    'name' => 'testOperation',
                    'status' => 'stopped',
                    'start_time' => 0,
                    'error' => '[E] Operation is not running',
                ],
            ],
            [
                'extra' => [
                    'operations' => [
                        'testOperation' => [
                            'name' => 'testOperation',
                            'status' => 'running',
                            'last_start_time' => 1528973133.4567,
                            'last_running_time' => 0,
                            'total_running_time' => 0,
                            'running_count' => 1,
                            'error' => null,
                        ],
                    ],
                ],
                'result' => [
                    'name' => 'testOperation',
                    'status' => 'running',
                    'start_time' => '2018-06-14T10:45:33+00:00 (timestamp: 1528973133.4567)',
                    'running_count' => 1,
                ],
            ],
            [
                'extra' => [
                    'operations' => [
                        'testOperation' => [
                            'name' => 'testOperation',
                            'status' => 'running',
                            'last_start_time' => 1528973133.4567,
                            'last_running_time' => 0,
                            'total_running_time' => 0,
                            'running_count' => 1,
                            'error' => '[E] Operation is running already',
                        ],
                    ],
                ],
                'result' => [
                    'name' => 'testOperation',
                    'status' => 'running',
                    'start_time' => '2018-06-14T10:45:33+00:00 (timestamp: 1528973133.4567)',
                    'running_count' => 1,
                    'error' => '[E] Operation is running already',
                ],
            ],
            [
                'extra' => [
                    'operations' => [
                        'testOperation' => [
                            'name' => 'testOperation',
                            'status' => 'stopped',
                            'last_start_time' => 1528973133.4567,
                            'last_running_time' => 10.34,
                            'total_running_time' => 10.34,
                            'running_count' => 1,
                            'error' => null,
                        ],
                    ],
                ],
                'result' => [
                    'name' => 'testOperation',
                    'status' => 'stopped',
                    'start_time' => '2018-06-14T10:45:33+00:00 (timestamp: 1528973133.4567)',
                    'running_time' => 10.34,
                    'running_count' => 1,
                ],
            ],
            [
                'extra' => [
                    'operations' => [
                        'testOperation' => [
                            'name' => 'testOperation',
                            'status' => 'stopped',
                            'last_start_time' => 1528973133.4567,
                            'last_running_time' => 10.34,
                            'total_running_time' => 20.34,
                            'running_count' => 2,
                            'error' => null,
                        ],
                    ],
                ],
                'result' => [
                    'name' => 'testOperation',
                    'status' => 'stopped',
                    'last_start_time' => '2018-06-14T10:45:33+00:00 (timestamp: 1528973133.4567)',
                    'last_running_time' => 10.34,
                    'total_running_time' => 20.34,
                    'running_count' => 2,
                ],
            ],
        ];
    }
}
