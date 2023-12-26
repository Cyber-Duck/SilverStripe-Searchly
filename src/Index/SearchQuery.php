<?php

namespace CyberDuck\Searchly\Index;

/**
 * Object representing a search query and its results.
 *
 * @category   SilverStripe Elastic Search
 *
 * @author     Andrew Mc Cormack <andy@cyber-duck.co.uk>
 * @copyright  Copyright (c) 2018, Andrew Mc Cormack
 * @license    https://github.com/cyber-duck/silverstripe-searchly/license
 *
 * @version    4.1.0
 *
 * @see       https://github.com/cyber-duck/silverstripe-searchly
 */
class SearchQuery
{
    /**
     * Settings dictionary.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Query text string.
     *
     * @var string
     */
    protected $query;

    /**
     * Query index name.
     *
     * @var string
     */
    protected $index;

    /**
     * Query result limit.
     *
     * @var int
     */
    protected $size = 50;

    /**
     * Query result _source.
     *
     * @var int
     */
    protected $source;

    /**
     * Query match operator.
     *
     * @var string
     */
    protected $operator = 'AND';

    /**
     * Enable query wildcard matching.
     *
     * @var boolean
     */
    protected $wildcard = true;

    /**
     * Enable query matched highlights.
     *
     * @var boolean
     */
    protected $highlight = false;

    /**
     * Whether the query request has been executed.
     *
     * @var boolean
     */
    protected $executed = false;

    /**
     * Search index client instance.
     *
     * @var SearchIndexClient
     */
    protected $client;

    /**
     * HTTP response instance.
     *
     * @var mixed
     */
    protected $response;

    /**
     * Sets the search text and index name.
     *
     * @param string $query
     * @param string $index
     */
    public function __construct(string $query, string $index)
    {
        $this->query = $query;
        $this->index = $index;
    }

    /**
     * Sets the result limit.
     *
     * @param int $size
     *
     * @return SearchQuery
     */
    public function setSize(int $size): SearchQuery
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Sets the match operator.
     *
     * @param string $operator
     *
     * @return SearchQuery
     */
    public function setOperator(string $operator): SearchQuery
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Enables wildcard matching.
     *
     * @param bool $wildcard
     *
     * @return SearchQuery
     */
    public function setWildcard(bool $wildcard): SearchQuery
    {
        $this->wildcard = $wildcard;

        return $this;
    }

    /**
     * Enables highlights.
     *
     * @param bool $highlight
     *
     * @return SearchQuery
     */
    public function setHighlight(bool $highlight): SearchQuery
    {
        $this->highlight = $highlight;

        return $this;
    }

    /**
     * Sets a query configuration option.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return SearchQuery
     */
    public function setConfig(string $name, $value): SearchQuery
    {
        $this->config[$name] = $value;

        return $this;
    }

    /**
     * Returns the search client instance.
     *
     * @return SearchIndexClient
     */
    public function getClient(): SearchIndexClient
    {
        return $this->client;
    }

    /**
     * Returns the search query configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Returns the query HTTP response.
     *
     * @return mixed
     */
    public function getResponse()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->response;
    }

    /**
     * Returns the matched IDs.
     */
    public function getIDs()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return array_map(function ($hit) {
            return $hit->_id;
        }, $this->response->hits->hits);
    }

    /**
     * Returns all matched objects from the index.
     *
     * @return array
     */
    public function getHits(): array
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->response->hits->hits;
    }

    /**
     * Returns an array of query highlights in $id => $highlight format.
     */
    public function getHighlights()
    {
        if (!$this->executed) {
            $this->execute();
        }
        $highlights = [];
        array_map(function ($hit) use (&$highlights) {
            $data = (array) $hit->highlight;

            $highlights[$hit->_id] = implode('',
                array_map(function ($key, $value) {
                    return implode('', $value);
                }, array_keys($data), $data)
            );
        }, $this->response->hits->hits);

        return $highlights;
    }

    /**
     * Executes the search query request.
     */
    protected function execute()
    {
        if ($this->source) {
            $this->setConfig('_source', $this->source);
        }
        $this->setConfig('size', $this->size);
        $this->setConfig('query', [
            'query_string' => [
                'query' => $this->getEscapedQuery(),
                'analyze_wildcard' => $this->wildcard,
                'default_operator' => $this->operator,
            ],
        ]);
        if (true === $this->highlight) {
            $this->setConfig('highlight', [
                'pre_tags' => [
                    '<strong>',
                ],
                'post_tags' => [
                    '</strong>',
                ],
                'fields' => [
                    '*' => (object) null,
                ],
                'require_field_match' => false,
                'fragment_size' => 500,
            ]);
        }
        $this->client = new SearchIndexClient(
            'GET',
            sprintf('/%s/_search', $this->index),
            json_encode($this->getConfig(), JSON_PRETTY_PRINT)
        );
        $this->response = $this->client->sendRequest()->getResponse();
        $this->executed = true;
    }

    /**
     * Returns the escaped search query string.
     *
     * @return string
     */
    protected function getEscapedQuery(): string
    {
        $regex = '/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/';

        return preg_replace($regex, addslashes('\\$0'), $this->query);
    }
}
