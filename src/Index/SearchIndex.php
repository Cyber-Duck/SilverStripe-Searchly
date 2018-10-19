<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObjectSchema;

class SearchIndex
{
    protected $name;

    protected $type;

    protected $classes = [];

    protected $schema;

    protected $searchObjects;

    protected $searchMappings;

    protected $searchClient;

    protected $records = [];

    public function __construct(string $name, string $type, array $classes)
    {
        $this->name = $name;
        $this->type = $type;
        $this->classes = $classes;

        $this->schema = Injector::inst()->get(DataObjectSchema::class);

        $this->searchObjects = new PrimitiveDataObjectFactory($this);
        $this->searchMappings = new SearchMappings($this);
        $this->searchClient = new SearchIndexClient(
            new Client([
                'base_uri' => Environment::getEnv('SEARCHLY_API_DOMAIN')
            ])
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getSchema(): DataObjectSchema
    {
        return $this->schema;
    }

    public function getSearchObjects(): PrimitiveDataObjectFactory
    {
        return $this->searchObjects;
    }

    public function getSearchMappings(): SearchMappings
    {
        return $this->searchMappings;
    }

    public function getSearchClient(): SearchIndexClient
    {
        return $this->searchClient;
    }

    public function setRecords(array $records): SearchIndex
    {
        $this->records = $records;
        return $this;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function putData(): void
    {
        $this->searchClient
            ->setIndex($this->name)
            ->setMethod('PUT')
            ->setBody($this->searchObjects->getJSON())
            ->update();
    }

    public function putMap(): void
    {
        $this->searchClient
            ->setIndex($this->name)
            ->setMethod('PUT')
            ->setBody($this->searchMappings->getJSON())
            ->map();

    }
}