<?php

namespace MixPanel\ConsumerStrategies;

use Exception;

class SocketConsumer extends AbstractConsumer
{
    /**
     * @var string the host to connect to (e.g. api.mixpanel.com)
     */
    private $host;


    /**
     * @var string the host-relative endpoint to write to (e.g. /engage)
     */
    private $endpoint;


    /**
     * @var int connectTimeout the socket connection timeout in seconds
     */
    private $connectTimeout;


    /**
     * @var string the protocol to use for the socket connection
     */
    private $protocol;


    /**
     * @var resource holds the socket resource
     */
    private $socket;

    /**
     * @var bool whether or not to wait for a response
     */
    private $async;


    /**
     * Creates a new SocketConsumer and assigns properties from the $options array
     * @param array $options
     */
    public function __construct($options)
    {
        parent::__construct($options);
        $this->host = $options['host'];
        $this->endpoint = $options['endpoint'];
        $this->connectTimeout = array_key_exists('connect_timeout', $options) ? $options['connect_timeout'] : 5;
        $this->async = array_key_exists('async', $options) && $options['async'] === false ? false : true;

        if (array_key_exists('use_ssl', $options) && $options['use_ssl'] == true) {
            $this->protocol = "ssl";
            $this->_port = 443;
        } else {
            $this->protocol = "tcp";
            $this->_port = 80;
        }
    }


    /**
     * Write using a persistent socket connection.
     * @param array $batch
     * @return bool
     */
    public function persist($batch)
    {

        $socket = $this->getSocket();
        if (!is_resource($socket)) {
            return false;
        }

        $data = "data=" . $this->_encode($batch);

        $body = "";
        $body .= "POST " . $this->endpoint . " HTTP/1.1\r\n";
        $body .= "Host: " . $this->host . "\r\n";
        $body .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $body .= "Accept: application/json\r\n";
        $body .= "Content-length: " . strlen($data) . "\r\n";
        $body .= "\r\n";
        $body .= $data;

        return $this->write($socket, $body);
    }


    /**
     * Return cached socket if open or create a new persistent socket
     * @return bool|resource
     */
    private function getSocket()
    {
        if (is_resource($this->socket)) {

            if ($this->debug()) {
                $this->log("Using existing socket");
            }

            return $this->socket;
        } else {

            if ($this->debug()) {
                $this->log("Creating new socket at " . time());
            }

            return $this->_createSocket();
        }
    }

    /**
     * Attempt to open a new socket connection, cache it, and return the resource
     * @param bool $retry
     * @return bool|resource
     */
    private function _createSocket($retry = true)
    {
        try {
            $socket = pfsockopen($this->protocol . "://" . $this->host, $this->_port, $err_no, $err_msg, $this->connectTimeout);

            if ($this->debug()) {
                $this->log("Opening socket connection to " . $this->protocol . "://" . $this->host . ":" . $this->_port);
            }

            if ($err_no != 0) {
                $this->handleError($err_no, $err_msg);
                return $retry == true ? $this->_createSocket(false) : false;
            } else {
                // cache the socket
                $this->socket = $socket;
                return $socket;
            }
        } catch (Exception $e) {
            $this->handleError($e->getCode(), $e->getMessage());
            return $retry == true ? $this->_createSocket(false) : false;
        }
    }

    /**
     * Attempt to close and dereference a socket resource
     */
    private function destroySocket()
    {
        $socket = $this->socket;
        $this->socket = null;
        fclose($socket);
    }


    /**
     * Write $data through the given $socket
     * @param $socket
     * @param $data
     * @param bool $retry
     * @return bool
     */
    private function write($socket, $data, $retry = true)
    {

        $bytes_sent = 0;
        $bytes_total = strlen($data);
        $socket_closed = false;
        $success = true;
        $max_bytes_per_write = 8192;

        // if we have no data to write just return true
        if ($bytes_total == 0) {
            return true;
        }

        // try to write the data
        while (!$socket_closed && $bytes_sent < $bytes_total) {

            try {
                $bytes = fwrite($socket, $data, $max_bytes_per_write);

                if ($this->debug()) {
                    $this->log("Socket wrote " . $bytes . " bytes");
                }

                // if we actually wrote data, then remove the written portion from $data left to write
                if ($bytes > 0) {
                    $data = substr($data, $max_bytes_per_write);
                }
            } catch (Exception $e) {
                $this->handleError($e->getCode(), $e->getMessage());
                $socket_closed = true;
            }

            if (isset($bytes) && $bytes) {
                $bytes_sent += $bytes;
            } else {
                $socket_closed = true;
            }
        }

        // create a new socket if the current one is closed and retry the message
        if ($socket_closed) {
            $this->destroySocket();
            if ($retry) {
                if ($this->debug()) {
                    $this->log("Retrying socket write...");
                }
                $socket = $this->getSocket();
                if ($socket) {
                    return $this->write($socket, $data, false);
                }
            }

            return false;
        }


        // only wait for the response in debug mode or if we explicitly want to be synchronous
        if ($this->debug() || !$this->async) {
            $res = $this->handleResponse(fread($socket, 2048));
            if ($res["status"] != "200") {
                $this->handleError($res["status"], $res["body"]);
                $success = false;
            }
        }

        return $success;
    }


    /**
     * Parse the response from a socket write (only used for debugging)
     * @param $response
     * @return array
     */
    private function handleResponse($response)
    {

        $lines = explode("\n", $response);

        // extract headers
        $headers = array();
        foreach ($lines as $line) {
            $kvsplit = explode(":", $line);
            if (count($kvsplit) == 2) {
                $header = $kvsplit[0];
                $value = $kvsplit[1];
                $headers[$header] = trim($value);
            }
        }

        // extract status
        $line_one_exploded = explode(" ", $lines[0]);
        $status = $line_one_exploded[1];

        // extract body
        $body = $lines[count($lines) - 1];

        // if the connection has been closed lets kill the socket
        if (array_key_exists("Connection", $headers) and $headers['Connection'] == "close") {
            $this->destroySocket();
            if ($this->debug()) {
                $this->log("Server told us connection closed so lets destroy the socket so it'll reconnect on next call");
            }
        }

        $ret = array(
            "status"  => $status,
            "body" => $body,
        );

        return $ret;
    }
}
