<?php

namespace CyberDuck\Searchly\Index;

use CyberDuck\Searchly\DataObject\PrimitiveDataObjectFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObjectSchema;

/**
 * Object representation of a Searchly index
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
     * Search index mappings instance
     *
     * @var SearchMappings
     */
    protected $searchMappings;

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
        $this->searchMappings = new SearchMappings($this);
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
     * Returns the index mappings instance
     *
     * @return SearchMappings
     */
    public function getSearchMappings(): SearchMappings
    {
        return $this->searchMappings;
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
     * Updates the index mappings through the API client
     *
     * @return SearchIndexClient
     */
    public function putMap(): SearchIndexClient
    {
        $client = new SearchIndexClient(
            'PUT',
            sprintf('/%s/_mapping/_doc', $this->name),
            $this->searchMappings->getJSON()
        );
        return $client->sendRequest();
    }
}