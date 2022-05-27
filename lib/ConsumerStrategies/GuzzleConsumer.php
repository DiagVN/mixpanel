<?php

namespace MixPanel\ConsumerStrategies;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Illuminate\Http\Response;

/**
 * Consumes messages and sends them to a host/endpoint using cURL
 */
class GuzzleConsumer extends AbstractConsumer
{
    /**
     * @var string the host to connect to (e.g. api.mixpanel.com)
     */
    protected $host;


    /**
     * @var string the host-relative endpoint to write to (e.g. /engage)
     */
    protected $endpoint;


    /**
     * @var int connectTimeout The number of seconds to wait while trying to connect. Default is 5 seconds.
     */
    protected $connectTimeout;


    /**
     * @var int timeout The maximum number of seconds to allow cURL call to execute. Default is 30 seconds.
     */
    protected $timeout;


    /**
     * @var string the protocol to use for the cURL connection
     */
    protected $protocol;


    /**
     * @var bool|null true to fork the cURL process (using exec) or false to use PHP's cURL extension. false by default
     */
    protected $fork = null;


    /**
     * @var int number of cURL requests to run in parallel. 1 by default
     */
    protected $num_threads;


    /**
     * Creates a new CurlConsumer and assigns properties from the $options array
     * @param array $options
     * @throws Exception
     */
    public function __construct($options)
    {
        parent::__construct($options);

        $this->host = $options['host'];
        $this->endpoint = $options['endpoint'];
        $this->connectTimeout = array_key_exists('connect_timeout', $options) ? $options['connect_timeout'] : 5;
        $this->timeout = array_key_exists('timeout', $options) ? $options['timeout'] : 30;
        $this->protocol = array_key_exists('use_ssl', $options) && $options['use_ssl'] == true ? "https" : "http";
        $this->fork = array_key_exists('fork', $options) ? ($options['fork'] == true) : false;
        $this->num_threads = array_key_exists('num_threads', $options) ? max(1, intval($options['num_threads'])) : 1;
    }


    /**
     * Write to the given host/endpoint using either a forked cURL process or using PHP's cURL extension
     * @param array $batch
     * @return bool
     */
    public function persist($batch)
    {
        if (count($batch) > 0) {
            $url = $this->protocol . "://" . $this->host . $this->endpoint;
            return $this->_execute($url, $batch);
        } else {
            return true;
        }
    }


    /**
     * Write using the cURL php extension
     * @param $url
     * @param $batch
     * @return bool
     */
    protected function _execute($url, $batch)
    {
        if ($this->debug()) {
            $this->log("Making blocking cURL call to $url");
        }

        $client = new Client([
            'timeout' => $this->timeout, // Response timeout
            'connect_timeout' => $this->connectTimeout, // Connection timeout
            'http_errors' => config('mixpanel.ignore_http_errors')
        ]);
        $promises = [];
        $batch_size = ceil(count($batch) / $this->num_threads);
        for ($i = 0; $i < $this->num_threads && !empty($batch); $i++) {
            $promises[] = $client->postAsync($url, [
                'form_params' => [
                    'data' => $this->_encode(array_splice($batch, 0, $batch_size))
                ]
            ]);
        }
        $responses = Utils::settle($promises)->wait();
        $error = false;
        /** @var Response */
        foreach ($responses as $response) {
            if ($response['value'] && $response['value']->getStatusCode() != Response::HTTP_OK) {
                $this->log("Error: Code: " . $response['value']->getReasonPhrase() . "-Body:" . $response['value']->getBody()->getContents());
                $error = true;
            } else if (!$response) {
                $this->log("Error: Body:" . $response['value']->getBody()->getContents());
                $error = true;
            } else if ($response['value'] && trim($response['value']->getBody()->getContents()) != "1") {
                $this->log("Error Code: " . $response['value']->getReasonPhrase() . "-Body:" . $response['value']->getBody()->getContents());
                $error = true;
            }
        }
        return !$error;
    }


    /**
     * Write using a forked cURL process
     * @param $url
     * @param $data
     * @return bool
     */
    protected function _execute_forked($url, $data)
    {

        if ($this->debug()) {
            $this->log("Making forked cURL call to $url");
        }

        $exec = 'curl -X POST -H "Content-Type: application/x-www-form-urlencoded" -d ' . $data . ' "' . $url . '"';

        if (!$this->debug()) {
            $exec .= " >/dev/null 2>&1 &";
        }

        exec($exec, $output, $return_var);

        if ($return_var != 0) {
            $this->handleError($return_var, $output);
        }

        return $return_var == 0;
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @return bool|null
     */
    public function getFork()
    {
        return $this->fork;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }


    /**
     * Number of requests/batches that will be processed in parallel using curl_multi_exec.
     * @return int
     */
    public function getNumThreads()
    {
        return $this->num_threads;
    }
}
