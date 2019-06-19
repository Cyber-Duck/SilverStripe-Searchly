<?php

namespace CyberDuck\Searchly\DataObject;

use Closure;
use stdClass;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\Subsites\Model\Subsite;

/**
 * Creates a stdClass representation of a DataObject populated with fields and
 * its relations.
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
class PrimitiveDataObject
{
    /**
     * Source model instance.
     *
     * @var DataObject
     */
    protected $source;

    /**
     * DataObject schema instance.
     *
     * @var DataObjectSchema
     */
    protected $schema;

    /**
     * DataObjectHierarchy instance.
     *
     * @var DataObjectHierarchy
     */
    protected $hierarchy;

    /**
     * Constructed model schema data instance.
     *
     * @var stdClass
     */
    protected $data;

    /**
     * Ignores these relations when building the search schema.
     *
     * @var array
     */
    protected $ignoreRelations = [];

    /**
     * Ignores these classes when building the search schema.
     *
     * @var array
     */
    protected $UserDefinedForm = [];

    /**
     * Sets the required instances.
     *
     * @param DataObject       $source
     * @param DataObjectSchema $schema
     */
    public function __construct(DataObject $source, DataObjectSchema $schema)
    {
        $this->source = $source;
        $this->schema = $schema;
        $this->hierarchy = new DataObjectHierarchy($this->source);
        $this->data = new stdClass();
    }

    /**
     * Sets a relation name to ignore when building the schema.
     *
     * @param string $relation
     *
     * @return PrimitiveDataObject
     */
    public function setIgnoreRelation(string $relation): PrimitiveDataObject
    {
        $this->ignoreRelations[] = $relation;

        return $this;
    }

    /**
     * Sets a class name to ignore when building the schema.
     *
     * @param string $class
     *
     * @return PrimitiveDataObject
     */
    public function setIgnoreClass(string $class): PrimitiveDataObject
    {
        $this->ignoreClasses[] = $class;

        return $this;
    }

    /**
     * Returns the constructed data instance.
     *
     * @return stdClass
     */
    public function getData(): stdClass
    {
        // set required columns
        $this->data->ID = $this->source->ID;
        $this->data->ClassName = $this->source->ClassName;

        if ($this->hasLink()) {
            $this->data->Link = $this->getLink();
        }
        if (class_exists(Subsite::class)) {
            $this->data->SubsiteID = $this->getSubsiteID();
        }

        // set columns
        array_map(
            function ($column) {
                if ($this->source->{$column}) {
                    $this->setColumnContent($column);
                }
            },
            (array) $this->source::config()->get('searchable_db')
        );

        // ignore loop backs to current class name
        array_map(
            function ($model) {
                $this->setIgnoreClass(get_class($model));
            },
            $this->hierarchy->getHierarchy()
        );

        $config = $this->source::config();

        // set has one
        $this->setRelation(
            (array) $config->get('searchable_has_one'),
            (array) $config->get('has_one'),
            $this->getHasOneMethod()
        );
        // set has many
        $this->setRelation(
            (array) $config->get('searchable_has_many'),
            (array) $config->get('has_many'),
            $this->getHasManyMethod()
        );
        // set many many
        $this->setRelation(
            (array) $config->get('searchable_many_many'),
            (array) $config->get('many_many'),
            $this->getManyManyMethod()
        );

        return $this->data;
    }

    /**
     * Checks if a link exists on the source DataObject.
     *
     * @return boolean
     */
    protected function hasLink(): bool
    {
        return property_exists($this->source, 'Link')
        || method_exists($this->source, 'Link')
        || method_exists($this->source, 'getLink');
    }

    /**
     * Returns the Link property.
     *
     * @param DataObject $object
     *
     * @return string|null
     */
    protected function getLink()
    {
        if ($this->source instanceof SiteTree) {
            return DataObject::get_by_id(SiteTree::class, $this->source->ID)->AbsoluteLink();
        }
        if ($this->source instanceof File) {
            return DataObject::get_by_id(File::class, $this->source->ID)->AbsoluteLink();
        }
        if (method_exists('Link', $this->source)) {
            return Director::absoluteURL($this->source->Link());
        }
        if (method_exists('getLink', $this->source)) {
            return Director::absoluteURL($this->source->getLink());
        }

        return Director::absoluteURL($this->source->Link);
    }

    /**
     * Returns the record subsite ID.
     *
     * @return int
     */
    protected function getSubsiteID(): int
    {
        return $this->source->hasField('SubsiteID') ? (int) $this->source->SubsiteID : 0;
    }

    /**
     * Normalises HTML field content.
     *
     * @param string $column
     */
    protected function setColumnContent(string $column)
    {
        $content = $this->source->{$column};

        if ('HTMLText' === $this->schema->fieldSpec($this->source->ClassName, $column)) {
            $lines = array_filter(explode('>', $content));
            $lines = array_map(function ($line) {
                return strip_tags($line.'>');
            }, $lines);

            $this->data->{$column} = implode(' ', array_filter($lines));
        } else {
            $this->data->{$column} = strip_tags($content);
        }
    }

    /**
     * Sets a has one, has many, or many many relation by validating the
     * passed values and running the relation specific closure.
     *
     * @param array   $relations
     * @param array   $schema
     * @param Closure $closure
     */
    private function setRelation(array $relations, array $schema, Closure $closure)
    {
        array_map(
            function ($relation) use ($schema, $closure) {
                if (!array_key_exists($relation, $schema)) {
                    return;
                }
                if (in_array($relation, $this->ignoreRelations)
                || in_array($schema[$relation], $this->ignoreClasses)) {
                    return;
                }
                $closure($relation);
            },
            $relations
        );
    }

    /**
     * Returns the has one relation closure to execute and build the relation.
     *
     * @return Closure
     */
    private function getHasOneMethod(): Closure
    {
        return function ($relation) {
            if ('has_one' == $this->source->getRelationType($relation)) {
                $schema = new PrimitiveDataObject($this->source->$relation(), $this->schema);
                $this->data->{$relation} = $schema->getData();
            }
        };
    }

    /**
     * Returns the has many relation closure to execute and build the relation.
     *
     * @return Closure
     */
    private function getHasManyMethod(): Closure
    {
        return function ($relation) {
            if ('has_many' == $this->source->getRelationType($relation)) {
                if (0 == $this->source->$relation()->Count()) {
                    return;
                }
                $this->data->{$relation} = [];
                foreach ($this->source->$relation() as $many) {
                    $schema = new PrimitiveDataObject($many, $this->schema);
                    $inverse = $this->schema->getRemoteJoinField($this->source, $relation);
                    // ignore back references to the current class hierarchy
                    $schema->setIgnoreRelation(substr($inverse, 0, -2));
                    $this->data->{$relation}[] = $schema->getData();
                }
            }
        };
    }

    /**
     * Returns the many many relation closure to execute and build the relation.
     *
     * @return Closure
     */
    private function getManyManyMethod(): Closure
    {
        return function ($relation) {
            if ('many_many' == $this->source->getRelationType($relation)) {
                if (0 == $this->source->$relation()->Count()) {
                    return;
                }
                $this->data->{$relation} = [];
                foreach ($this->source->$relation() as $many) {
                    $schema = new PrimitiveDataObject($many, $this->schema);
                    $this->data->{$relation}[] = $schema->getData();
                }
            }
        };
    }
}
