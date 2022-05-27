<?php

namespace MixPanel\ConsumerStrategies;

/**
 * Consumes messages and writes them to a file
 */
class FileConsumer extends AbstractConsumer
{
    /**
     * @var string path to a file that we want to write the messages to
     */
    private $file;


    /**
     * Creates a new FileConsumer and assigns properties from the $options array
     * @param array $options
     */
    public function __construct($options)
    {
        parent::__construct($options);

        // what file to write to?
        $this->file = array_key_exists("file", $options) ? $options['file'] :  dirname(__FILE__) . "/../../messages.txt";
    }


    /**
     * Append $batch to a file
     * @param array $batch
     * @return bool
     */
    public function persist($batch)
    {
        if (count($batch) > 0) {
            return file_put_contents($this->file, json_encode($batch) . "\n", FILE_APPEND | LOCK_EX) !== false;
        } else {
            return true;
        }
    }
}
