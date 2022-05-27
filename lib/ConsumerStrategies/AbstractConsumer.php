<?php

namespace MixPanel\ConsumerStrategies;

use MixPanel\Base\MixPanelBase;

/**
 * Provides some base methods for use by a Consumer implementation
 */
abstract class AbstractConsumer extends MixPanelBase
{
    /**
     * Creates a new AbstractConsumer
     * @param array $options
     */
    public function __construct($options = array())
    {

        parent::__construct($options);

        if ($this->debug()) {
            $this->log("Instantiated new Consumer");
        }
    }

    /**
     * Encode an array to be persisted
     * @param array $params
     * @return string
     */
    protected function _encode($params)
    {
        return base64_encode(json_encode($params));
    }

    /**
     * Handles errors that occur in a consumer
     * @param $code
     * @param $msg
     */
    protected function handleError($code, $msg)
    {
        if (isset($this->options['error_callback'])) {
            $handler = $this->options['error_callback'];
            call_user_func($handler, $code, $msg);
        }

        if ($this->debug()) {
            $arr = debug_backtrace();
            $class = get_class($arr[0]['object']);
            $line = $arr[0]['line'];
            $this->log("[ $class - line $line ] : " . print_r($msg, true));
        }
    }

    /**
     * Number of requests/batches that will be processed in parallel.
     * @return int
     */
    public function getNumThreads()
    {
        return 1;
    }

    /**
     * Persist a batch of messages in whatever way the implementer sees fit
     * @param array $batch an array of messages to consume
     * @return boolean success or fail
     */
    abstract public function persist($batch);
}
