<?php

namespace MixPanel\Producers;

/**
 * Provides an API to create/update profiles on Mixpanel
 */
class MixPanelPeople extends MixPanelBaseProducer
{
    /**
     * Internal method to prepare a message given the message data
     * @param $distinct_id
     * @param $operation
     * @param $value
     * @param null $ip
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found
     * @return array
     */
    private function _constructPayload($distinctId, $operation, $value, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $payload = array(
            '$token' => $this->token,
            '$distinct_id' => $distinctId,
            $operation => $value
        );
        if ($ip !== null) {
            $payload['$ip'] = $ip;
        }
        if ($ignoreTime === true) {
            $payload['$ignore_time'] = true;
        }
        if ($ignoreAlias === true) {
            $payload['$ignore_alias'] = true;
        }
        return $payload;
    }

    /**
     * Set properties on a user record. If the profile does not exist, it creates it with these properties.
     * If it does exist, it sets the properties to these values, overwriting existing values.
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param array $props associative array of properties to set on the profile
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found
     */
    public function set($distinctId, $props, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $payload = $this->_constructPayload($distinctId, '$set', $props, $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Set properties on a user record. If the profile does not exist, it creates it with these properties.
     * If it does exist, it sets the properties to these values but WILL NOT overwrite existing values.
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param array $props associative array of properties to set on the profile
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function setOnce($distinctId, $props, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $payload = $this->_constructPayload($distinctId, '$set_once', $props, $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Unset properties on a user record. If the profile does not exist, it creates it with no properties.
     * If it does exist, it unsets these properties. NOTE: In other libraries we use 'unset' which is
     * a reserved word in PHP.
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param array $props associative array of properties to unset on the profile
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function remove($distinctId, $props, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $payload = $this->_constructPayload($distinctId, '$unset', $props, $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Increments the value of a property on a user record. If the profile does not exist, it creates it and sets the
     * property to the increment value.
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param $prop string the property to increment
     * @param int $val the amount to increment the property by
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function increment($distinctId, $prop, $val, $ip = null, $ignoreTime = false, $ignore_alias = false)
    {
        $payload = $this->_constructPayload($distinctId, '$add', array("$prop" => $val), $ip, $ignoreTime, $ignore_alias);
        $this->enqueue($payload);
    }

    /**
     * Adds $val to a list located at $prop. If the property does not exist, it will be created. If $val is a string
     * and the list is empty or does not exist, a new list with one value will be created.
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param string $prop the property that holds the list
     * @param string|array $val items to add to the list
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function append($distinctId, $prop, $val, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $operation = gettype($val) == "array" ? '$union' : '$append';
        $payload = $this->_constructPayload($distinctId, $operation, array("$prop" => $val), $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Adds a transaction to the user's profile for revenue tracking
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param string $amount the transaction amount e.g. "20.50"
     * @param null $timestamp the timestamp of when the transaction occurred (default to current timestamp)
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function trackCharge($distinctId, $amount, $timestamp = null, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $timestamp = $timestamp == null ? time() : $timestamp;
        $date_iso = date("c", $timestamp);
        $transaction = array(
            '$time' => $date_iso,
            '$amount' => $amount
        );
        $val = array('$transactions' => $transaction);
        $payload = $this->_constructPayload($distinctId, '$append', $val, $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Clear all transactions stored on a user's profile
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function clearCharges($distinctId, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $payload = $this->_constructPayload($distinctId, '$set', array('$transactions' => array()), $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Delete this profile from Mixpanel
     * @param string|int $distinct_id the distinct_id or alias of a user
     * @param string|null $ip the ip address of the client (used for geo-location)
     * @param boolean $ignore_time If the $ignore_time property is true, Mixpanel will not automatically update the "Last Seen" property of the profile. Otherwise, Mixpanel will add a "Last Seen" property associated with the current time
     * @param boolean $ignore_alias If the $ignore_alias property is true, an alias look up will not be performed after ingestion. Otherwise, a lookup for the distinct ID will be performed, and replaced if a match is found     
     */
    public function deleteUser($distinctId, $ip = null, $ignoreTime = false, $ignoreAlias = false)
    {
        $payload = $this->_constructPayload($distinctId, '$delete', "", $ip, $ignoreTime, $ignoreAlias);
        $this->enqueue($payload);
    }

    /**
     * Returns the "engage" endpoint
     * @return string
     */
    public function getEndpoint()
    {
        return $this->options['people_endpoint'];
    }
}
