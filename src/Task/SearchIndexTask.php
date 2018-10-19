<?php

namespace CyberDuck\Searchly\Task;

use SilverStripe\Dev\BuildTask;

class SearchIndexTask extends BuildTask 
{
    /**
     * Tasks status
     *
     * @var boolean
     */
	protected $enabled = true;

    /**
     * Task title
     *
     * @var string
     */
	protected $title = "Search index task";

    /**
     * Task description
     *
     * @var string
     */
    protected $description = "Indexes all site content for use in searchly";

    /**
     * Array of search indexes defined in YAML
     *
     * @var array
     */
    protected $indexes = [];

    /**
     * Runs the index task
     *
     * @param HTTPRequest $request
     * @return void
     */
    public function run($request)
    {
        $this->indexes = (array) $this::config()->get('indexes');

        if($this->validateIndexes()) {
            array_map(function($name, $config) {
                if($this->validateConfiguration($name, $config)) {
                    $index = new SearchIndex(
                        $name, 
                        $config['type'], 
                        $config['classes']
                    );
                    $index->putData();
                    $index->putMap();
                }
            }, array_keys($this->indexes), $this->indexes);
        }
    }

    private function validateIndexes(): bool
    {
        if(empty($this->indexes)) {
            throw new Exception(sprintf(
                'No indexes array defined in YML for %s',
                static::class
            ));
        }
        return true;
    }

    private function validateConfiguration(string $name, array $config): bool
    {
        if(!is_string($name)) {
            throw new Exception('Index name must be a string');
        }
        if(!array_key_exists('type', $config)) {
            throw new Exception('Index type key is required');
        }
        if(!array_key_exists('classes', $config)) {
            throw new Exception('Index classes key is required');
        }
        if(!is_array($config['classes'])) {
            throw new Exception('Index classes must be an array');
        }
        return true;
    }
}