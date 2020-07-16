<?php

namespace CyberDuck\Searchly\Index;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\Core\Injector\Injector;
use CyberDuck\Searchly\DataObject\PrimitiveDataObject;
use CyberDuck\Searchly\DataObject\PrimitiveDataObjectFactory;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

/**
 * Object representation of a Elastic Search index.
 *
 * @category   SilverStripe Searchly
 *
 * @author     Andrew Mc Cormack <andy@cyber-duck.co.uk>
 * @copyright  Copyright (c) 2018, Andrew Mc Cormack
 * @license    https://github.com/cyber-duck/silverstripe-searchly/license
 *
 * @version    4.1.0
 *
 * @see       https://github.com/cyber-duck/silverstripe-searchly
 */
class SearchIndex
{
    use Configurable;

    /**
     * Search index name.
     *
     * @var string
     */
    protected $name;

    /**
     * Search index data.
     *
     * @var string
     */
    protected $data;

    /**
     * Search index type.
     *
     * @var string
     */
    protected $type;

    /**
     * Array of DataObject classes for this index.
     *
     * @var array
     */
    protected $classes = [];

    /**
     * DataObjectSchema instance.
     *
     * @var DataObjectSchema
     */
    protected $schema;

    /**
     * Array of object records for the index.
     *
     * @var array
     */
    protected $records = [];

    /**
     * Sets the required properties and classes.
     *
     * @param string $name
     * @param string $type
     * @param array  $classes
     */
    public function __construct(string $name, string $type, array $classes)
    {
        $this->name = $name;
        $this->type = $type;
        $this->classes = $classes;

        $this->schema = Injector::inst()->get(DataObjectSchema::class);
    }

    /**
     * Returns the search index name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the search index type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the search index classes.
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Returns the DataObjectSchema instance.
     *
     * @return DataObjectSchema
     */
    public function getSchema(): DataObjectSchema
    {
        return $this->schema;
    }

    /**
     * Returns a UUID for a record
     *
     * @param $record
     *
     * @return string
     */
    public function getRecordID($record): string
    {
        $parts = [
            $record->ClassName,
            $record->ID
        ];

        $id = implode("-", $parts);

        $config = $this->config();

        if($config->get('id_hash_enabled')) {
            $algo = $this->config()->get('id_hash_algo');
            $length = $this->config()->get('id_hash_length');
            $id = substr(hash($algo, $id), 0, $length);
        }

        return $id;
    }

    /**
     * Sets the index records.
     *
     * @param array $records
     *
     * @return SearchIndex
     */
    public function setRecords(array $records): SearchIndex
    {
        $this->records = $records;

        return $this;
    }

    /**
     * Returns the index records.
     *
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Creates the search index using the passed mappings and settings.
     *
     * @param array $mappings
     * @param array $settings
     */
    public function createIndex(array $mappings = [], array $settings = [])
    {
        $payload = [
            'settings' => $settings,
            'mappings' => [
                $this->type => [
                    'properties' => $mappings,
                ],
            ],
        ];

        $client = new SearchIndexClient(
            'PUT',
            sprintf('/%s', $this->name),
            json_encode($payload)."\n"
        );
        $client->sendRequest();
    }

    /**
     * Deletes and recreates the index.
     *
     * @param array $mappings
     * @param array $settings
     *
     * @return SearchIndex
     */
    public function resetIndex(array $mappings = [], array $settings = []): SearchIndex
    {
        $this->deleteIndex();
        $this->createIndex($mappings, $settings);

        return $this;
    }

    /**
     * Delete the index through the API client.
     */
    public function deleteIndex(): bool
    {
        try {
            $client = new SearchIndexClient(
                'DELETE',
                sprintf('/%s', $this->name.''),
                ''
            );
            $client->sendRequest();
        } catch (\Exception $e) {
            //index does not exists, return true anyway
            return true;
        }

        return $client->getResponse()->acknowledged;
    }

    /**
     * Updates the index data through the API client.
     *
     * Second argument is an associative array of filters
     * ClassName => [
     *     'filter_column' => 'filter_value'
     *     ...
     * ];
     *
     * @param array $filters
     *
     * @return SearchIndexClient
     */
    public function index(array $filters = []): SearchIndexClient
    {
        $client = new SearchIndexClient(
            'PUT',
            sprintf('/%s/_doc/_bulk?pretty', $this->name),
            (new PrimitiveDataObjectFactory($this, $filters))->getJSON()
        );

        return $client->sendRequest();
    }

    /**
     * Adds a DataObject to the index.
     *
     * @param DataObject $record
     */
    public function indexRecord(DataObject $record)
    {
        if ($this->isIndexableClass($record)) {
            $transformer = new PrimitiveDataObject($record, $this->getSchema());
            $data = $transformer->getData();

            $client = new SearchIndexClient(
                'PUT',
                sprintf('/%s/%s/%s', $this->name, $this->type, $this->getRecordID($record)),
                json_encode($data)."\n"
            );

            return $client->sendRequest();
        }
    }

    /**
     * Removes a DataObject from the index.
     *
     * @param DataObject $record
     */
    public function removeRecord(DataObject $record)
    {
        if ($this->isIndexableClass($record)) {
            try {
                $client = new SearchIndexClient(
                    'DELETE',
                    sprintf('/%s/%s/%s', $this->name, $this->type, $this->getRecordID($record)),
                    ''
                );

                return $client->sendRequest();
            } catch (\Exception $e) {
                return;
            }
        }
    }

    /**
     * Checks if a passed DataObject can be added to the index.
     *
     * @param DataObject $record
     *
     * @return boolean
     */
    protected function isIndexableClass(DataObject $record): bool
    {
        foreach ($this->classes as $class) {
            if ($record instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
