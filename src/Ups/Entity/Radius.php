<?php
namespace Ups\Entity;

class Radius
{
    const UNIT_KILOMETERS = 'KM';
    const UNIT_MILES = 'MI';

    private $radius;
    private $unit;

    /**
     * @return integer
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param integer $radius
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        if(!in_array($unit, [self::UNIT_KILOMETERS, self::UNIT_MILES])) {
            throw new \LogicException ('Unknown range unit');
        }

        $this->unit = $unit;

        return $this;
    }
}
