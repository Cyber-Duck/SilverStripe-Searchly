<?php

namespace CyberDuck\Searchly\Index;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use SilverStripe\Core\Environment;

/**
 * Client to interact with the Searchly API
 * 
 * @category   SilverStripe Searchly
 * @category   SilverStripe Searchly
 * @author     Andrew Mc Cormack <andy@cyber-duck.co.uk>
 * @copyright  Copyright (c) 2018, Andrew Mc Cormack
 * @license    https://github.com/cyber-duck/silverstripe-searchly/license
 * @version    1.0.0
 * @link       https://github.com/cyber-duck/silverstripe-searchly
 * @since      1.0.0
 */
class SearchIndexClient
{
    /**
     * HTTP client instance
     *
     * @var Client
     */
    protected $client;

    /**
     * HTTP request method
     *
     * @var string
     */
    protected $method;

    /**
     * HTTP request endpoint
     *
     * @var string
     */
    protected $endpoint;
    
    /**
     * HTTP request body
     *
     * @var string
     */
    protected $body;

    /**
     * HTTP request headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * HTTP response
     *
     * @var mixed
     */
    protected $response;

    /**
     * Sets the required properties and client instance
     *
     * @param string $method
     * @param string $endpoint
     * @param string $body
     */
    public function __construct(string $method, string $endpoint, string $body, array $headers = [])
    {
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->body = $body;
        $this->headers = $headers;

        $this->client = new Client([
            'base_uri' => Environment::getEnv('SEARCHLY_BASE_URI')
        ]);
        return $this;
    }

    /**
     * Sends the API request
     *
     * @return SearchIndexClient
     */
    public function sendRequest(): SearchIndexClient
    {
        $options = [
            'headers' => $this->headers,
            'body'    => $this->body
        ];
        $this->response = $this->client->request(
            $this->method, $this->endpoint, $options
        );
        $this->response = json_decode((string) $this->response->getBody());
        return $this;
    }

    /**
     * Returns the API response
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}