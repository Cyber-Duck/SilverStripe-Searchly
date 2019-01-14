<?php

namespace CyberDuck\Searchly\Index;

use CyberDuck\Searchly\DataObject\PrimitiveDataObjectFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObjectSchema;

/**
 * Object representation of a Searchly index
 *
 * @category   SilverStripe Searchly
 * @author     Andrew Mc Cormack <andy@cyber-duck.co.uk>
 * @copyright  Copyright (c) 2018, Andrew Mc Cormack
 * @license    https://github.com/cyber-duck/silverstripe-searchly/license
 * @version    1.0.0
 * @link       https://github.com/cyber-duck/silverstripe-searchly
 * @since      1.0.0
 */
class SearchIndex
{
    /**
     * Search index name
     *
     * @var string
     */
    protected $name;

    /**
     * Search index type
     *
     * @var string
     */
    protected $type;

    /**
     * Array of DataObject classes for this index
     *
     * @var array
     */
    protected $classes = [];

    /**
     * DataObjectSchema instance
     *
     * @var DataObjectSchema
     */
    protected $schema;

    /**
     * Factory instance to create primitive data objects
     *
     * @var PrimitiveDataObjectFactory
     */
    protected $searchObjects;

    /**
     * Array of object records for the index
     *
     * @var array
     */
    protected $records = [];

    /**
     * Sets the required properties and classes
     *
     * @param string $name
     * @param string $type
     * @param array $classes
     */
    public function __construct(string $name, string $type, array $classes)
    {
        $this->name = $name;
        $this->type = $type;
        $this->classes = $classes;

        $this->schema = Injector::inst()->get(DataObjectSchema::class);

        $this->searchObjects = new PrimitiveDataObjectFactory($this);
    }

    /**
     * Returns the search index name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the search index type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the search index classes
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Returns the DataObjectSchema instance
     *
     * @return DataObjectSchema
     */
    public function getSchema(): DataObjectSchema
    {
        return $this->schema;
    }

    /**
     * Returns the index objects factory instance
     *
     * @return PrimitiveDataObjectFactory
     */
    public function getSearchObjects(): PrimitiveDataObjectFactory
    {
        return $this->searchObjects;
    }

    /**
     * Sets the index records
     *
     * @param array $records
     * @return SearchIndex
     */
    public function setRecords(array $records): SearchIndex
    {
        $this->records = $records;

        return $this;
    }

    /**
     * Returns the index records
     *
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Updates the index data through the API client
     *
     * @return SearchIndexClient
     */
    public function putData(): SearchIndexClient
    {
        $client = new SearchIndexClient(
            'PUT',
            sprintf('/%s/_doc/_bulk?pretty', $this->name),
            $this->searchObjects->getJSON()
        );

        return $client->sendRequest();
    }

    /**
     * Delete all data from the index
     *
     * @return SearchIndexClient
     */
    public function clearIndex(): SearchIndexClient
    {
        $client = new SearchIndexClient(
            'DELETE',
            sprintf('/%s', $this->name),
            ''
        );

        try {
            // Delete would fail if index does not exists
            return $client->sendRequest();
        } catch (ClientException $e) {
            return $client;
        }
    }

    /**
     * Create a mapping for the index
     *
     * @param  array $map
     * @return SearchIndexClient
     */
    public function mapIndex(array $map = []): SearchIndexClient
    {
        $client = new SearchIndexClient(
            'PUT',
            sprintf('/%s', $this->name),
            json_encode($map)
        );

        return $client->sendRequest();
    }

    /**
     * Delete a record from the index
     *
     * @param  int $id
     * @return SearchIndexClient
     */
    public function deleteData(int $id): SearchIndexClient
    {
        $client = new SearchIndexClient(
            'DELETE',
            sprintf('/%s/_doc/%i', $this->name, $id),
            json_encode($map)
        );

        return $client->sendRequest();
    }
}