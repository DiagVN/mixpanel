<?php

namespace MixPanel\Producers;

use Carbon\Carbon;

use Exception;

/**
 * Provides an API to track events on Mixpanel
 */
class MixPanelEvents extends MixPanelBaseProducer
{
    /**
     * An array of properties to attach to every tracked event
     * @var array
     */
    private $superProperties = array("mp_lib" => "php");

    /**
     * Track an event defined by $event associated with metadata defined by $properties
     * @param string $event
     * @param array $properties
     */
    public function track($event, $properties = array())
    {

        // if no token is passed in, use current token
        if (!array_key_exists("token", $properties)) {
            $properties['token'] = $this->token;
        }

        // if no time is passed in, use the current time
        if (!array_key_exists('time', $properties)) {
            $properties['time'] = config('mixpanel.timezone') ? Carbon::now(config('mixpanel.timezone'))->timestamp : time();
        }

        $params['event'] = $event;
        $params['properties'] = array_merge($this->superProperties, $properties);

        $this->enqueue($params);
    }


    /**
     * Register a property to be sent with every event. If the property has already been registered, it will be
     * overwritten.
     * @param string $property
     * @param mixed $value
     */
    public function register($property, $value)
    {
        $this->superProperties[$property] = $value;
    }


    /**
     * Register multiple properties to be sent with every event. If any of the properties have already been registered,
     * they will be overwritten.
     * @param array $propsAndVals
     */
    public function registerAll($propsAndVals = array())
    {
        foreach ($propsAndVals as $property => $value) {
            $this->register($property, $value);
        }
    }


    /**
     * Register a property to be sent with every event. If the property has already been registered, it will NOT be
     * overwritten.
     * @param $property
     * @param $value
     */
    public function registerOnce($property, $value)
    {
        if (!isset($this->superProperties[$property])) {
            $this->register($property, $value);
        }
    }


    /**
     * Register multiple properties to be sent with every event. If any of the properties have already been registered,
     * they will NOT be overwritten.
     * @param array $propsAndVals
     */
    public function registerAllOnce($propsAndVals = array())
    {
        foreach ($propsAndVals as $property => $value) {
            if (!isset($this->superProperties[$property])) {
                $this->register($property, $value);
            }
        }
    }


    /**
     * Un-register an property to be sent with every event.
     * @param string $property
     */
    public function unregister($property)
    {
        unset($this->superProperties[$property]);
    }


    /**
     * Un-register a list of properties to be sent with every event.
     * @param array $properties
     */
    public function unregisterAll($properties)
    {
        foreach ($properties as $property) {
            $this->unregister($property);
        }
    }


    /**
     * Get a property that is set to be sent with every event
     * @param string $property
     * @return mixed
     */
    public function getProperty($property)
    {
        return $this->superProperties[$property];
    }


    /**
     * Identify the user you want to associate to tracked events. The $anon_id must be UUID v4 format and not already merged to an $identified_id.
     * All identify calls with a new and valid $anon_id will trigger a track $identify event, and merge to the $identified_id.
     * @param string|int $user_id
     * @param string|int $anon_id [optional]
     */
    public function identify($userId, $anonId = null)
    {
        $this->register("distinct_id", $userId);

        $UUIDv4 = '/^[a-zA-Z0-9]*-[a-zA-Z0-9]*-[a-zA-Z0-9]*-[a-zA-Z0-9]*-[a-zA-Z0-9]*$/i';
        if (!empty($anonId)) {
            if (preg_match($UUIDv4, $anonId) !== 1) {
                /* not a valid uuid */
                $this->log("Running Identify method (identified_id: $userId, anon_id: $anonId) failed, anon_id not in UUID v4 format");
            } else {
                $this->track('$identify', array(
                    '$identified_id' => $userId,
                    '$anon_id'       => $anonId
                ));
            }
        }
    }


    /**
     * An alias to be merged with the distinct_id. Each alias can only map to one distinct_id.
     * This is helpful when you want to associate a generated id (such as a session id) to a user id or username.
     *
     * Because aliasing can be extremely vulnerable to race conditions and ordering issues, we'll make a synchronous
     * call directly to Mixpanel when this method is called. If it fails we'll throw an Exception as subsequent
     * events are likely to be incorrectly tracked.
     * @param string|int $distinct_id
     * @param string|int $alias
     * @return array $msg
     * @throws Exception
     */
    public function createAlias($distinctId, $alias)
    {
        $msg = array(
            "event"  => '$create_alias',
            "properties" =>  array(
                "distinct_id" => $distinctId,
                "alias" => $alias,
                "token" => $this->token
            )
        );

        // Save the current fork/async options
        $old_fork = isset($this->options['fork']) ? $this->options['fork'] : false;
        $oldasync = isset($this->options['async']) ? $this->options['async'] : false;

        // Override fork/async to make the new consumer synchronous
        $this->options['fork'] = false;
        $this->options['async'] = false;

        // The name is ambiguous, but this creates a new consumer with current $this->options
        $consumer = $this->getConsumer();
        $success = $consumer->persist(array($msg));

        // Restore the original fork/async settings
        $this->options['fork'] = $old_fork;
        $this->options['async'] = $oldasync;

        if (!$success) {
            $this->log("Creating Mixpanel Alias (distinct id: $distinctId, alias: $alias) failed");
            throw new Exception("Tried to create an alias but the call was not successful");
        } else {
            return $msg;
        }
    }


    /**
     * Returns the "events" endpoint
     * @return string
     */
    public function getEndpoint()
    {
        return $this->options['events_endpoint'];
    }
}
