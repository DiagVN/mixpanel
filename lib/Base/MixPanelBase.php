<?php

namespace MixPanel\Base;

use Psr\Log\LoggerInterface;

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
    private $defaults = [];


    /**
     * An array of options to be used by the Mixpanel library.
     * @var array
     */
    protected $options = array();


    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Construct a new MixPanelBase object and merge custom options with defaults
     * @param array $options
     */
    public function __construct(
        $options = array(),
        LoggerInterface $logger = null
    ) {
        $this->defaults = config('mixpanel');
        $options = array_merge($this->defaults, $options);
        if (!$logger) {
            $this->logger = app()->make(LoggerInterface::class);
        }
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
