<?php

namespace CyberDuck\Searchly\DataObject;

namespace CyberDuck\Searchly\Index\SearchIndex;

/**
 * Creates a stdClass representation of a DataObject populated with fields and 
 * its relations.
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
class PrimitiveDataObjectFactory
{
    /**
     * Index instance
     *
     * @var SearchIndex
     */
    protected $index;

    /**
     * Schema objects array
     *
     * @var array
     */
    protected $records = [];

    /**
     * Sets the search index instance records
     *
     * @param SearchIndex $index
     */
    public function __construct(SearchIndex $index)
    {
        $this->index = $index;
        
        array_map(function($namespace) {
            foreach($namespace::get()->limit(5) as $model) {
                $schema = new PrimitiveDataObject($model, $this->index->getSchema());
                $this->records[] = $schema->getData();
            }
        }, $this->index->getClasses());
        
        $this->index->setRecords($this->records);
    }

    /**
     * Returns the search index spec in JSON format.
     * Used to populate the searchly API index record data.
     *
     * @return string
     */
    public function getJSON(): string
    {
        $data = [];
        foreach($this->records as $record) {
            $data[] = json_encode([
                "index" => [
                    "_index" => $this->index->getName(), 
                    "_type"  => $this->index->getType(), 
                    "_id"    => $record->ID
                ]
            ])."\n".json_encode($record);
        }
        return implode("\n", $data);
    }
}