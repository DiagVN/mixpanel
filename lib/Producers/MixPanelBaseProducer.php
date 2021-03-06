<?php

namespace MixPanel\Producers;

use MixPanel\ConsumerStrategies\FileConsumer;
use MixPanel\ConsumerStrategies\SocketConsumer;
use MixPanel\Base\MixPanelBase;
use MixPanel\ConsumerStrategies\CurlConsumer;
use MixPanel\ConsumerStrategies\GuzzleConsumer;

/**
 * Provides some base methods for use by a message Producer
 */
abstract class MixPanelBaseProducer extends MixPanelBase
{
    /**
     * @var string a token associated to a Mixpanel project
     */
    protected $token;

    /**
     * @var array a queue to hold messages in memory before flushing in batches
     */
    private $queue = array();


    /**
     * @var AbstractConsumer the consumer to use when flushing messages
     */
    private $consumer = null;


    /**
     * @var array The list of available consumers
     */
    private $consumers = array(
        "file" =>  FileConsumer::class,
        "curl" =>  CurlConsumer::class,
        "socket" =>  SocketConsumer::class,
        "guzzle" => GuzzleConsumer::class,
    );


    /**
     * If the queue reaches this size we'll auto-flush to prevent out of memory errors
     * @var int
     */
    protected $maxQueueSize = 1000;


    /**
     * Creates a new MixpanelBaseProducer, assings Mixpanel project token, registers custom Consumers, and instantiates
     * the desired consumer
     * @param $token
     * @param array $options
     */
    public function __construct(
        $token,
        $options = array()
    ) {

        parent::__construct($options);

        // register any customer consumers
        if (array_key_exists("consumers", $this->options)) {
            $this->consumers = array_merge($this->consumers, $this->options['consumers']);
        }

        // set max queue size
        if (array_key_exists("max_queue_size", $this->options)) {
            $this->maxQueueSize = $this->options['max_queue_size'];
        }

        // associate token
        $this->token = $token;

        // instantiate the chosen consumer
        $this->consumer = $this->getConsumer();
    }


    /**
     * Flush the queue when we destruct the client with retries
     */
    public function __destruct()
    {
        $attempts = 0;
        $max_attempts = 10;
        $success = false;
        while (!$success && $attempts < $max_attempts) {
            $success = $this->flush();
            $attempts++;
        }
    }


    /**
     * Iterate the queue and write in batches using the instantiated Consumer Strategy
     * @param int $desiredBatchSize
     * @return bool whether or not the flush was successful
     */
    public function flush($desiredBatchSize = 50)
    {
        $queue_size = count($this->queue);
        $succeeded = true;
        $numThreads = $this->consumer->getNumThreads();
        while ($queue_size > 0 && $succeeded) {
            $batch_size = min(array($queue_size, $desiredBatchSize * $numThreads, $this->options['max_batch_size'] * $numThreads));
            $batch = array_splice($this->queue, 0, $batch_size);
            $succeeded = $this->_persist($batch);
            $queue_size = count($this->queue);
        }
        return $succeeded;
    }


    /**
     * Empties the queue without persisting any of the messages
     */
    public function reset()
    {
        $this->queue = array();
    }


    /**
     * Returns the in-memory queue
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Returns the current Mixpanel project token
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * Given a strategy type, return a new PersistenceStrategy object
     * @return AbstractConsumer
     */
    protected function getConsumer()
    {
        $key = $this->options['consumer'];
        $Strategy = $this->consumers[$key];
        $this->options['endpoint'] = $this->getEndpoint();

        return new $Strategy($this->options);
    }

    /**
     * Add an array representing a message to be sent to Mixpanel to a queue.
     * @param array $message
     */
    public function enqueue($message = array())
    {
        array_push($this->queue, $message);

        // force a flush if we've reached our threshold
        if (count($this->queue) >= $this->maxQueueSize) {
            $this->flush();
        }
    }


    /**
     * Add an array representing a list of messages to be sent to Mixpanel to a queue.
     * @param array $messages
     */
    public function enqueueAll($messages = array())
    {
        foreach ($messages as $message) {
            $this->enqueue($message);
        }
    }


    /**
     * Given an array of messages, persist it with the instantiated Persistence Strategy
     * @param $message
     * @return mixed
     */
    protected function _persist($message)
    {
        return $this->consumer->persist($message);
    }

    /**
     * Return the endpoint that should be used by a consumer that consumes messages produced by this producer.
     * @return string
     */
    abstract public function getEndpoint();
}
