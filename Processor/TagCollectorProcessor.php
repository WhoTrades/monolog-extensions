<?php
/**
 * @package whotrades\monolog-extensions
 */
namespace whotrades\MonologExtensions\Processor;

class TagCollectorProcessor
{
    /*
     * array
     */
    private $tags;

    /**
     * TagCollectorProcessor constructor.
     *
     * @param array $tags
     */
    public function __construct(array $tags = null)
    {
        $this->setTags((array) $tags);
    }

    /**
     * @param array $tags
     */
    public function addTags(array $tags = null)
    {
        $this->tags = array_merge($this->tags, (array) $tags);
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags = null)
    {
        $this->tags = (array) $tags;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        if (!isset($record['extra']['tags'])) {
            $record['extra']['tags'] = [];
        }

        $record['extra']['tags'] = array_merge((array) $record['extra']['tags'], (array) $this->tags);

        return $record;
    }
}
