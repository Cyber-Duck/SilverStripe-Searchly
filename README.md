# SilverStripe Elastic Search

This package adds the ability to index DataObjects on any Elastic Search instance running on your local environment, on a server such as AWS, or an Elastic Search service such as Searchly.

[![Latest Stable Version](https://poser.pugx.org/cyber-duck/silverstripe-searchly/v/stable)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)
[![Latest Unstable Version](https://poser.pugx.org/cyber-duck/silverstripe-searchly/v/unstable)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)
[![Total Downloads](https://poser.pugx.org/cyber-duck/silverstripe-searchly/downloads)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)
[![License](https://poser.pugx.org/cyber-duck/silverstripe-searchly/license)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)

Author: [Andrew Mc Cormack](https://github.com/Andrew-Mc-Cormack)

__For SilverStripe 4.*__

* [Installation](#installation)
* [Setting Your Elastic Search Endpoint](#setting-your-elastic-search-endpoint)
* [Setting DataObject Indexable Fields and Relations](#setting-dataobject-indexable-fields-and-relations)
* [Performing Actions on a Search Index](#performing-actions-on-a-search-index)
    * [createIndex()](#createindexmappings---settings--)
    * [deleteIndex()](#deleteindex)
    * [resetIndex()](#resetindexmappings---settings--)
    * [index()](#indexarray-filters--)
    * [indexRecord()](#indexrecorddataobject-record)
    * [removeRecord()](#removerecorddataobject-record)
    * [Creating an Index Task](#creating-an-index-task)
* [Performing a Search](#performing-a-search)
    * [Configuring Your Search Query](#configuring-your-search-query)
    * [Getting Search Results](#getting-search-results)

## Installation

Add the following to your composer.json file and run /dev/build?flush=all

```json
{  
    "require": {  
        "cyber-duck/silverstripe-searchly": "4.1.*"
    }
}
```

## Setting Your Elastic Search Endpoint

Add a SEARCHLY_BASE_URI var to your .env file with any valid ES endpoint (AWS, searchly etc)

```
SEARCHLY_BASE_URI="https://site:{api-key}@xyz.searchly.com" - 
```

Or for a local docker ES instance or similar

```
SEARCHLY_BASE_URI="http://es:9200"
```

## Setting DataObject Indexable Fields and Relations

Models and their relations can be indexed together as one searchable object. To index fields and their relations you can use searchable_* config arrays on your DataObject.

In the example below the relations Tags & ContentBlocks would need their own searchable_* config. When indexing, these relationships will be traversed and a nested objects created.

```php
private static $searchable_db = [
    'Title',
    'Content'
];

private static $searchable_has_many = [
    'Tags'
];

private static $searchable_many_many = [
    'ContentBlocks'
];

```

## Performing Actions on a Search Index

Creating a search index instance allow you access to functions to change and manipulate a search index such as creating a new one, adding / removing records etc

```php
$index = new SearchIndex(
    'pages', // the index name, can be hard coded or better to pull from a .env var
    'pages', // the searchly index _type
    [Page::class] // an array of models to index, can be pages or non pages
);
```

### createIndex($mappings = [], $settings = [])

Calling this method on your search index instance will build an ES endpoint index. For full configuration for mappings and settings please see the Elastic Search documentation.

```php
$mappings = [
    'Created' => [
        'type' => 'date',
    ],
    'LastEdited' => [
        'type' => 'date',
    ],
    'ClassName' => [
        'type' => 'keyword',
    ],
    'Title' => [
        'type' => 'text',
        'boost' => 100,
    ],
    'Link' => [
        'type' => 'text',
        'index' => false,
    ]
];

$settings = [
    'analysis' => [
        'analyzer' => [
            'default' => [
                'type' => 'english',
            ]
        ]
    ]
];
            
$index->createIndex($mappings, $settings);
```

### deleteIndex()

This method will completely remove the search index from your ES endpoint

```php
$index->deleteIndex();
```

### resetIndex($mappings = [], $settings = [])

This method will call deleteIndex() and then call createIndex(). Make sure to pass your mappings and settings configuration as you would for createIndex();

```php
$index->resetIndex($mappings, $settings);
```

### index(array $filters = [])

This method will push all the models to the ES endpoint index you specified when creating your search index instance.

In the example below calling index will traverse through all Page and File models, create a JSON representation of them and push them to the ES endpoint index.

```php
$index = new SearchIndex(
    'models', // the index name, can be hard coded or better to pull from a .env var
    'models', // the searchly index _type
    [
        Page::class,
        File::class
    ]
);
$index->index();
```

You can also apply filters in case you wish to exclude certain model from the index.

```php
$index->index([
    Page::class => [
        'ClassName:not' => ErrorPage::class,
    ],
    File::class => [
        'ClassName:not' => Folder::class,
    ],
]);
```

You can also see all the data sent to the ES endpoint index by calling index() then getRecords(). This will return an array of JSON objects.

```php
$index->index([...])->getRecords();
```

### indexRecord(DataObject $record)

Adds a single data object to the ES endpoint index

```php
$index->indexRecord(
    Page::get()->find('ID', 1)
);
```

### removeRecord(DataObject $record)

Removes a single data object from the ES endpoint index

```php
$index->removeRecord(
    Page::get()->find('ID', 1)
);
```

### Creating an Index Task

The easiest way to create your indexes is to create a SilverStripe task and run it to create / rebuild all your indexes

```php
use CyberDuck\Searchly\Index\SearchIndex;
use SilverStripe\Assets\File;
use SilverStripe\Dev\BuildTask;

class SearchIndexTask extends BuildTask 
{
    protected $enabled = true;

    protected $title = "Searchly Pages index task";

    protected $description = "Indexes all site pages for use in searchly";

    public function run($request)
    {
        $mappings = [
            ... // your configuration
        ];

        $settings = [
            ... // your configuration
        ];

        $index = new SearchIndex(
            'pages', // the index name, can be hard coded or better to pull from a .env var
            'pages', // the searchly index _type
            [Page::class] // an array of models to index, can be pages or non pages
        );
        $index->resetIndex($mappings, $settings);
        $index->index();

        $index = new SearchIndex(
            'files', // the index name, can be hard coded or better to pull from a .env var
            'files', // the searchly index _type
            [File::class] // an array of models to index, can be pages or non pages
        );
        $index->resetIndex($mappings, $settings);
        $index->index();
    }
}
```

If you run into PHP time outs with indexing large numbers of models, you can try to increase the execution time

```php
    public function run($request)
    {
        ini_set('max_execution_time', 300);

        $index = new SearchIndex(...
```

## Performing a Search

To perform a search query create a new SearchQuery instance and inject the search term and index name into the constructor.

```php
use CyberDuck\Searchly\Index\SearchQuery;

$query = new SearchQuery(
    $term, // the search term
    'pages' // the index name, can be hard coded or better to pull from a .env var
);
```

### Configuring Your Search Query

You can control the amount of results returned. Useful for very large data sets.

```php
$query->setSize(50);
```

You can also control AND / OR matching

```php
$query->setOperator('OR'); 
```

There is also a custom method for setting analyze_wildcard config to true / false

```php
$query->setWildcard(true);
```

Or you can build your own highly complex configurations for any situation.

```php
$query->setConfig('sort', [['Created' => 'desc']]);
$query->setConfig('query', [
    'bool' => [
        'must' => [
            [
                'query_string' => [
                    'query' => '*'.$escapedterm.'*',
                    'analyze_wildcard' => true,
                    'default_operator' => 'OR',
                ]
            ]
        ]
    ]
]);
```

### Getting Search Results

To return an array of matched model IDs you can call getIDs()

```php
$ids = $query->getIDs();
```

To return an array of matched objects you can call getHits()

```php
$objects = $query->getHits();
```

Highlights / matched text can also be returns by calling setHighlight() on the SearchQuery instance and calling getHighlights()

```php
$query->setHighlight(true);

$highlights = $query->getHighlights();
```

To return the full ES endpoint response object you can call getResponse() on your query object. Useful for debugging.

```php
$response = $query->getResponse();
```