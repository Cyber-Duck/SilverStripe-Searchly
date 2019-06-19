<?php

namespace CyberDuck\Searchly\DataObject;

use CyberDuck\Searchly\Index\SearchIndex;

/**
 * Creates a stdClass representation of a DataObject populated with its fields
 * and its relations.
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
class PrimitiveDataObjectFactory
{
    /**
     * Index instance.
     *
     * @var SearchIndex
     */
    protected $index;

    /**
     * Schema objects array.
     *
     * @var array
     */
    protected $records = [];

    /**
     * Sets the search index instance records.
     *
     * @param SearchIndex $index
     * @param array       $filters
     */
    public function __construct(SearchIndex $index, array $filters = [])
    {
        $this->index = $index;

        array_map(
            function ($namespace) use ($filters) {
                $models = $namespace::get();
                if (!empty($filters)) {
                    if (array_key_exists($namespace, $filters)) {
                        $models = $models->filter($filters[$namespace]);
                    }
                }
                foreach ($models as $model) {
                    $schema = new PrimitiveDataObject($model, $this->index->getSchema());
                    $data = $schema->getData();
                    if (!$model->hasField('ShowInSearch') || $model->ShowInSearch) {
                        $this->records[] = $data;
                    }
                }
            },
            $this->index->getClasses()
        );

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
        foreach ($this->records as $record) {
            $settings = [
                'index' => [
                    '_index' => $this->index->getName(),
                    '_type' => $this->index->getType(),
                    '_id' => $record->ID,
                ],
            ];
            $data[] = json_encode($settings)."\n".json_encode($record);
        }

        return implode("\n", $data)."\n";
    }
}
