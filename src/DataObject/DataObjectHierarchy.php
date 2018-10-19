<?php

use SilverStripe\ORM\DataObject;

class DataObjectHierarchy
{
    /**
     * Source model instance
     *
     * @var DataObject
     */
    protected $source;

    /**
     * Array of hierarchy models
     *
     * @var array
     */
    protected $hierarchy = [];

    /**
     * Sets up the hierarchy array
     *
     * @param DataObject $source
     */
    public function __construct(DataObject $source)
    {
        $this->source = $source;
        $this->hierarchy[] = $this->source;
        $this->setup();
    }

    /**
     * Returns the hierarchy model array
     *
     * @return array
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }

    /**
     * Builds the hierarch array and then removes ViewableData and DataObject 
     * from the array
     *
     * @return void
     */
    private function setup(): void
    {
        $class = $this->source;
        while($class = get_parent_class($class)) {
            $this->hierarchy[] = $class::create();
        }
        array_pop($this->hierarchy);
        array_pop($this->hierarchy);
        array_reverse($this->hierarchy);
    }
}