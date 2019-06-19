<?php

namespace CyberDuck\Searchly\DataObject;

use SilverStripe\ORM\DataObject;

/**
 * Builds DataObject hierarchy spec.
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
class DataObjectHierarchy
{
    /**
     * Source model instance.
     *
     * @var DataObject
     */
    protected $source;

    /**
     * Array of hierarchy models.
     *
     * @var array
     */
    protected $hierarchy = [];

    /**
     * Sets up the hierarchy array.
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
     * Returns the hierarchy model array.
     *
     * @return array
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }

    /**
     * Builds the hierarchy array and then removes ViewableData and DataObject
     * from the array.
     */
    private function setup()
    {
        $class = $this->source;
        while ($class = get_parent_class($class)) {
            $this->hierarchy[] = $class::create();
        }
        array_pop($this->hierarchy);
        array_pop($this->hierarchy);
        array_reverse($this->hierarchy);
    }
}
