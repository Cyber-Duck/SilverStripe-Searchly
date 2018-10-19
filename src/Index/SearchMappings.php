<?php

namespace CyberDuck\Searchly\Index;

class SearchMappings
{
    /**
     * Index name
     *
     * @var string
     */
    protected $index;

    public function __construct(SearchIndex $index)
    {
        $this->index = $index;
    }

    /**
     * Returns the JSON search mappings
     *
     * @return string
     */
    public function getJSON(): string
    {
        return json_encode([
            $this->index->getName() => [
                'mappings' => [
                    $this->index->getType() => [
                        'properties' => $this->map($this->index->getRecords())
                    ]
                ]
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Recursively iterates over an array of object records merging column and 
     * relation definitions to construct the JSON search mappings
     *
     * @param array $records
     * @return array
     */
    private function map(array $records): array
    {
        $data = [];
        foreach($records as $record) {
            foreach(get_object_vars($record) as $name => $value) {
                if(!is_array($value) && !is_object($value)) {
                    $data[$name] = ['type' => $name == 'ID' ? 'long' : 'text'];
                }
                if(is_array($value)) {
                    $merge = [];
                    if(array_key_exists($name, $data)) {
                        $merge = $data[$name];
                        if(array_key_exists('properties', $merge)) {
                            $merge = $merge['properties'];
                        }
                    }
                    $data[$name]['type'] = 'nested';
                    $data[$name]['properties'] = array_merge($merge, $this->map($value));
                }
            }
        }
        return $data;
    }
}