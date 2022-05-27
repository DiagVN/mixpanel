<?php

namespace MixPanel\Producers;

use MixPanel\ConsumerStrategies\FileConsumer;
use MixPanel\ConsumerStrategies\SocketConsumer;
use MixPanel\Base\MixPanelBase;
use Psr\Log\LoggerInterface;

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
    private $_consumer = null;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @var array The list of available consumers
     */
    private $_consumers = array(
        "file" =>  FileConsumer::class,
        "curl" =>  CurlConsumer::class,
        "socket" =>  SocketConsumer::class,
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
        $options = array(),
        LoggerInterface $logger = null
    ) {

        parent::__construct($options);

        // register any customer consumers
        if (array_key_exists("consumers", $options)) {
            $this->_consumers = array_merge($this->_consumers, $options['consumers']);
        }

        // set max queue size
        if (array_key_exists("max_queue_size", $options)) {
            $this->maxQueueSize = $options['max_queue_size'];
        }

        // associate token
        $this->token = $token;

        if ($this->debug()) {
            $this->log("Using token: " . $this->token);
        }

        // instantiate the chosen consumer
        $this->_consumer = $this->getConsumer();

        $this->logger = $logger;
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
            if ($this->debug()) {
                $this->log("destruct flush attempt #" . ($attempts + 1));
            }
            $success = $this->flush();
            $attempts++;
        }
    }


    /**
     * Iterate the queue and write in batches using the instantiated Consumer Strategy
     * @param int $desired_batch_size
     * @return bool whether or not the flush was successful
     */
    public function flush($desired_batch_size = 50)
    {
        $queue_size = count($this->queue);
        $succeeded = true;
        $num_threads = $this->_consumer->getNumThreads();

        if ($this->debug()) {
            $this->log("Flush called - queue size: " . $queue_size);
        }

        while ($queue_size > 0 && $succeeded) {
            $batch_size = min(array($queue_size, $desired_batch_size * $num_threads, $this->options['max_batch_size'] * $num_threads));
            $batch = array_splice($this->queue, 0, $batch_size);
            $succeeded = $this->_persist($batch);

            if (!$succeeded) {
                if ($this->debug()) {
                    $this->log("Batch consumption failed!");
                }
                $this->queue = array_merge($batch, $this->queue);

                if ($this->debug()) {
                    $this->log("added batch back to queue, queue size is now $queue_size");
                }
            }

            $queue_size = count($this->queue);

            if ($this->debug()) {
                $this->log("Batch of $batch_size consumed, queue size is now $queue_size");
            }
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
        $Strategy = $this->_consumers[$key];
        if ($this->debug()) {
            $this->log("Using consumer: " . $key . " -> " . $Strategy);
        }
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
        if (count($this->queue) > $this->maxQueueSize) {
            $this->flush();
        }

        if ($this->debug()) {
            $this->log("Queued message: " . json_encode($message));
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
        return $this->_consumer->persist($message);
    }




    /**
     * Return the endpoint that should be used by a consumer that consumes messages produced by this producer.
     * @return string
     */
    abstract public function getEndpoint();
}
