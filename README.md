# SilverStripe-Searchly
Elastic Search integration for SilverStripe

[![Latest Stable Version](https://poser.pugx.org/cyber-duck/silverstripe-searchly/v/stable)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)
[![Latest Unstable Version](https://poser.pugx.org/cyber-duck/silverstripe-searchly/v/unstable)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)
[![Total Downloads](https://poser.pugx.org/cyber-duck/silverstripe-searchly/downloads)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)
[![License](https://poser.pugx.org/cyber-duck/silverstripe-searchly/license)](https://packagist.org/packages/cyber-duck/silverstripe-searchly)

Author: [Andrew Mc Cormack](https://github.com/Andrew-Mc-Cormack)

__For SilverStripe 4.*__

## Installation

Add the following to your composer.json file and run /dev/build?flush=all

```json
{  
    "require": {  
        "cyber-duck/silverstripe-searchly": "1.0.*"
    }
}
```

## Configuration

Add a SEARCHLY_BASE_URI var to your .env file.

```
SEARCHLY_BASE_URI="https://site:{api-key}@xyz.searchly.com" - Any valid ES endpoint (AWS, searchly etc)
```

Add a value for your index name

```
SEARCHLY_PAGES_INDEX="pages"
```

## Making Models Indexable

Both models and their relations can be indexed. To index fields from a model you can use searchable_* config arrays


```php
private static $searchable_db = [
    'Title',
    'Content'
];

private static $searchable_has_many = [
    'Quotes'
];

private static $searchable_many_many = [
    'Items'
];

```

In the example above the relations Quotes & Items would need their own searchable_* config. When indexing, these relationships will be traveresed and a nested object created for the index.

## Creating an Index

The easiest way to create an index is to create a SilverStripe task. In the example below the task indexes all "Page" models.

```php

use CyberDuck\Searchly\Index\SearchIndex;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;

class SearchIndexTask extends BuildTask 
{
    protected $enabled = true;

    protected $title = "Searchly Pages index task";

    protected $description = "Indexes all site pages for use in searchly";

    public function run($request)
    {
        $index = new SearchIndex(
            Environment::getEnv('SEARCHLY_PAGES_INDEX'), // the index name, can be hard coded or better to pull from a .env var
            'pages', // the searchly index _type
            [\Page::class] // an array of models to index
        );
        $index->putData();
    }
}
```

## Performing a Search

To perform a search query create a new SearchQuery instance and inject the search term and index name into the constructor.

```php

use CyberDuck\Searchly\Index\SearchQuery;

$query = new SearchQuery(
    'Your search term', 
    Environment::getEnv('SEARCHLY_PAGES_INDEX') // the index name, can be hard coded or better to pull from a .env var
);

```

To return the full searchly response object you can call getResponse()

```php
$results = $query->getResponse();
```

To return an array of matched model IDs you can call getIDs()

```php
$results = $query->getIDs();
```

Highlights / matched text can also be returns by calling setHighlight() on the SearchQuery instance and calling getHighlights()

```php
$query->setHighlight(true);

$results = $query->getHighlights();
```

## Handling Large Data Sets

If you run into PHP timeouts with indexing large numbers of models, you can try to increase the execution time


```php

    public function run($request)
    {
        ini_set('max_execution_time', 300);

        $index = new SearchIndex(...
```