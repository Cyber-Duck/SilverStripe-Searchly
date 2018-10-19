<?php


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class SearchIndexClient
{
    protected $client;

    protected $method;

    protected $index;

    protected $body;

    protected $headers = [];

    protected $response;

    public function __construct(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    public function setMethod(string $method): SearchIndexClient
    {
        $this->method = $method;
        return $this;
    }

    public function setIndex(string $index): SearchIndexClient
    {
        $this->index = $index;
        return $this;
    }

    public function setBody(string $body): SearchIndexClient
    {
        $this->body = $body;
        return $this;
    }

    public function setHeaders(array $headers): SearchIndexClient
    {
        $this->headers = $headers;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function update()
    {
        $this->endpoint = sprintf('/%s/_doc/_bulk?pretty', $this->index);
        $this->sendRequest();
    }

    public function map()
    {
        $this->endpoint = sprintf('/%s/_mapping/_doc', $this->index);
        $this->sendRequest();
    }

    private function sendRequest(): bool
    {
        $options = [
            'headers' => $this->headers,
            'body'    => $this->body
        ];
        $this->response = $this->client->request(
            $this->method, $this->endpoint, $options
        );
        $this->response = json_decode((string) $this->response->getBody());

        return !empty($response) ? true : false;
    }
}