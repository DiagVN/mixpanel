<?php

namespace MixPanel\Services;

use MixPanel\Base\MixPanelBase;
use MixPanel\Producers\MixPanelEvents;
use MixPanel\Producers\MixPanelPeople;

class MixPanelService extends MixPanelBase
{
    /**
     * An instance of the MixPanelPeople class (used to create/update profiles)
     * @var MixPanelPeople
     */
    public $people;


    /**
     * An instance of the MixPanelEvents class
     * @var MixPanelEvents
     */
    private $events;

    /**
     * Add an array representing a message to be sent to Mixpanel to the in-memory queue.
     * @param array $message
     */
    public function enqueue($message = array())
    {
        $this->events->enqueue($message);
    }


    /**
     * Add an array representing a list of messages to be sent to Mixpanel to a queue.
     * @param array $messages
     */
    public function enqueueAll($messages = array())
    {
        $this->events->enqueueAll($messages);
    }


    /**
     * Flush the events queue
     * @param int $desiredBatchSize
     */
    public function flush($desiredBatchSize = 50)
    {
        $this->events->flush($desiredBatchSize);
    }


    /**
     * Empty the events queue
     */
    public function reset()
    {
        $this->events->reset();
    }


    /**
     * Identify the user you want to associate to tracked events. The $anon_id must be UUID v4 format and not already merged to an $identified_id.
     * All identify calls with a new and valid $anon_id will trigger a track $identify event, and merge to the $identified_id.
     * @param string|int $user_id
     * @param string|int $anon_id [optional]
     */
    public function identify($userId, $anonId = null)
    {
        $this->events->identify($userId, $anonId);
    }

    /**
     * Track an event defined by $event associated with metadata defined by $properties
     * @param string $event
     * @param array $properties
     */
    public function track($event, $properties = array())
    {
        $this->events->track($event, $properties);
    }


    /**
     * Register a property to be sent with every event.
     *
     * If the property has already been registered, it will be
     * overwritten. NOTE: Registered properties are only persisted for the life of the Mixpanel class instance.
     * @param string $property
     * @param mixed $value
     */
    public function register($property, $value)
    {
        $this->events->register($property, $value);
    }


    /**
     * Register multiple properties to be sent with every event.
     *
     * If any of the properties have already been registered,
     * they will be overwritten. NOTE: Registered properties are only persisted for the life of the Mixpanel class
     * instance.
     * @param array $propsAndVals
     */
    public function registerAll($propsAndVals = array())
    {
        $this->events->registerAll($propsAndVals);
    }


    /**
     * Register a property to be sent with every event.
     *
     * If the property has already been registered, it will NOT be
     * overwritten. NOTE: Registered properties are only persisted for the life of the Mixpanel class instance.
     * @param $property
     * @param $value
     */
    public function registerOnce($property, $value)
    {
        $this->events->registerOnce($property, $value);
    }


    /**
     * Register multiple properties to be sent with every event.
     *
     * If any of the properties have already been registered,
     * they will NOT be overwritten. NOTE: Registered properties are only persisted for the life of the Mixpanel class
     * instance.
     * @param array $propsAndVals
     */
    public function registerAllOnce($propsAndVals = array())
    {
        $this->events->registerAllOnce($propsAndVals);
    }


    /**
     * Un-register an property to be sent with every event.
     * @param string $property
     */
    public function unregister($property)
    {
        $this->events->unregister($property);
    }


    /**
     * Un-register a list of properties to be sent with every event.
     * @param array $properties
     */
    public function unregisterAll($properties)
    {
        $this->events->unregisterAll($properties);
    }


    /**
     * Get a property that is set to be sent with every event
     * @param string $property
     * @return mixed
     */
    public function getProperty($property)
    {
        return $this->events->getProperty($property);
    }


    /**
     * An alias to be merged with the distinct_id. Each alias can only map to one distinct_id.
     * This is helpful when you want to associate a generated id (such as a session id) to a user id or username.
     * @param string|int $distinct_id
     * @param string|int $alias
     */
    public function createAlias($distinctId, $alias)
    {
        $this->events->createAlias($distinctId, $alias);
    }

    /***
     * Custom function to set token
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->people = new MixPanelPeople($token, $this->options);
        $this->events = new MixPanelEvents($token, $this->options);
    }
}
