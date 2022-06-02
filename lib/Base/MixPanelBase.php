<?php

namespace MixPanel\Base;

use Carbon\Carbon;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Logger;
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
     * An array of options to be used by the Mixpanel library.
     * @var array
     */
    protected $token;


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
            $date = Carbon::now()->format('Y-m-d');
            $this->logger = new Logger('MixPanel');
            $this->logger->pushHandler(
                new StreamHandler(storage_path('logs/mixpanel-' . $date . '.log')),
                Logger::DEBUG
            );

            $this->logger->pushHandler(
                new FilterHandler(
                    new StreamHandler('php://stdout'),
                    Logger::DEBUG
                )
            );
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
        $this->logger->debug("[ $class - line $line ] : " . $msg);
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
