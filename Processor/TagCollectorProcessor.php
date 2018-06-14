<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\MonologExtensions\Processor;

use whotrades\MonologExtensions\LoggerWt;

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
        // ag: Collect tags from context
        if (isset($record['context'][LoggerWt::CONTEXT_TAGS])) {
            $this->addTags((array) $record['context'][LoggerWt::CONTEXT_TAGS]);
        }

        if (!isset($record['extra'][LoggerWt::CONTEXT_TAGS])) {
            $record['extra'][LoggerWt::CONTEXT_TAGS] = [];
        }

        // ag: Add tags to extra
        $record['extra'][LoggerWt::CONTEXT_TAGS] = array_merge((array) $record['extra'][LoggerWt::CONTEXT_TAGS], (array) $this->tags);

        return $record;
    }
}
