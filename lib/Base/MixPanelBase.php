<?php

namespace MixPanel\Base;

/**
 * This a Base class which all Mixpanel classes extend from to provide some very basic
 * debugging and logging functionality. It also serves to persist $options across the library.
 *
 */
class MixPanelBase
{
    /**
     * Default options that can be overridden via the $options constructor arg
     * @var array
     */
    private $_defaults = array(
        "max_batch_size" => 50, // the max batch size Mixpanel will accept is 50,
        "max_queue_size" => 1000, // the max num of items to hold in memory before flushing
        "debug" => false, // enable/disable debug mode
        "consumer" => "curl", // which consumer to use
        "host" => "api.mixpanel.com", // the host name for api calls
        "events_endpoint" => "/track", // host relative endpoint for events
        "people_endpoint" => "/engage", // host relative endpoint for people updates
        "use_ssl" => true, // use ssl when available
        "error_callback" => null // callback to use on consumption failures
    );


    /**
     * An array of options to be used by the Mixpanel library.
     * @var array
     */
    protected $options = array();


    /**
     * Construct a new MixPanelBase object and merge custom options with defaults
     * @param array $options
     */
    public function __construct($options = array())
    {
        $options = array_merge($this->_defaults, $options);
        $this->options = $options;
    }


    /**
     * Log a message to PHP's error log
     * @param $msg
     */
    protected function log($msg)
    {
        $arr = debug_backtrace();
        $class = $arr[0]['class'];
        $line = $arr[0]['line'];
        $this->logger->error("[ $class - line $line ] : " . $msg);
    }


    /**
     * Returns true if in debug mode, false if in production mode
     * @return bool
     */
    protected function debug()
    {
        return array_key_exists("debug", $this->options) && $this->options["debug"] == true;
    }
}
