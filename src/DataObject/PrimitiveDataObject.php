<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

class PrimitiveDataObject
{
    /**
     * Source model instance
     *
     * @var DataObject
     */
    protected $source;

    /**
     * DataObject schema instance
     *
     * @var DataObjectSchema
     */
    protected $schema;

    /**
     * DataObjectHierarchy instance
     *
     * @var DataObjectHierarchy
     */
    protected $hierarchy;

    /**
     * Constructed model schema data instance
     *
     * @var stdClass
     */
    protected $data;

    /**
     * Ignores these relations when building the search schema
     *
     * @var array
     */
    protected $ignoreRelations = [];

    /**
     * Ignores these classes when building the search schema
     *
     * @var array
     */
    protected $ignoreClasses = [];

    /**
     * Sets the required instances
     *
     * @param DataObject $source
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
     * Sets a relation name to ignore when building the schema
     *
     * @param string $relation
     * @return PrimitiveDataObject
     */
    public function setIgnoreRelation(string $relation): PrimitiveDataObject
    {
        $this->ignoreRelations[] = $relation;
        return $this;
    }

    /**
     * Sets a class name to ignore when building the schema
     *
     * @param string $class
     * @return PrimitiveDataObject
     */
    public function setIgnoreClass(string $class): PrimitiveDataObject
    {
        $this->ignoreClasses[] = $class;
        return $this;
    }

    /**
     * Returns the constructed data instance
     *
     * @return stdClass
     */
    public function getData(): stdClass
    {
        // set columns
        $this->data->ID = $this->source->ID;
        $this->data->ClassName = $this->source->ClassName;
        array_map(function($column) {
            if($this->source->{$column}) {
                $this->data->{$column} = $this->source->{$column};
            }
        }, (array) $this->source::config()->get('searchable_db'));

        // ignore loop backs to current class name
        array_map(function($model) {
            $this->setIgnoreClass(get_class($model));
        }, $this->hierarchy->getHierarchy());

        // set has one
        $this->setRelation(
            (array) $this->source::config()->get('searchable_has_one'), 
            (array) $this->source::config()->get('has_one'), 
            $this->getHasOneMethod()
        );
        // set has many
        $this->setRelation(
            (array) $this->source::config()->get('searchable_has_many'), 
            (array) $this->source::config()->get('has_many'), 
            $this->getHasManyMethod()
        );
        // set many many
        $this->setRelation(
            (array) $this->source::config()->get('searchable_many_many'), 
            (array) $this->source::config()->get('many_many'), 
            $this->getManyManyMethod()
        );
        return $this->data;
    }

    /**
     * Sets a has one, has many, or many many relation by validating the
     * passed values and running the relation specific closure
     *
     * @param array $relations
     * @param array $schema
     * @param Closure $closure
     * @return void
     */
    private function setRelation(array $relations, array $schema, Closure $closure): void
    {
        array_map(function($relation) use ($schema, $closure) {
            if(!array_key_exists($relation, $schema)) {
                throw new Exception(sprintf(
                    'Search Index error: Cannot find %s relation on %s',
                    $relation,
                    get_class($this->source)
                ));
            }
            if(in_array($relation, $this->ignoreRelations) 
            || in_array($schema[$relation], $this->ignoreClasses)) {
                return;
            }
            $closure($relation);
        }, $relations);
    }

    /**
     * Returns the has one relation closure to execute and build the relation
     *
     * @return Closure
     */
    private function getHasOneMethod(): Closure
    {
        return (function($relation) {
            $schema = new PrimitiveDataObject($this->source->$relation(), $this->schema);
            $this->data->{$relation} = $schema->getData();
        });
    }

    /**
     * Returns the has many relation closure to execute and build the relation
     *
     * @return Closure
     */
    private function getHasManyMethod(): Closure
    {
        return (function($relation) {
            if($this->source->$relation()->Count() == 0) {
                return;
            }
            $this->data->{$relation} = [];
            foreach($this->source->$relation() as $many) {
                $schema = new PrimitiveDataObject($many, $this->schema);
                $inverse = $this->schema->getRemoteJoinField($this->source, $relation);
                // ignore back references to the current class hierarchy
                $schema->setIgnoreRelation(substr($inverse, 0, -2));
                $this->data->{$relation}[] = $schema->getData();
            }
        });
    }

    /**
     * Returns the many many relation closure to execute and build the relation
     *
     * @return Closure
     */
    private function getManyManyMethod(): Closure
    {
        return (function($relation) {
            if($this->source->$relation()->Count() == 0) {
                return;
            }
            $this->data->{$relation} = [];
            foreach($this->source->$relation() as $many) {
                $schema = new PrimitiveDataObject($many, $this->schema);
                $this->data->{$relation}[] = $schema->getData();
            }
        });
    }
}