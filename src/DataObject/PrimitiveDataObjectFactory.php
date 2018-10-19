<?php

class PrimitiveDataObjectFactory
{
    protected $index;

    /**
     * Schema objects array
     *
     * @var array
     */
    protected $records = [];

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