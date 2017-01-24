<?php

namespace BootPress\Geo;

/**
 * Get rhumb line information for a path which follows a constant bearing.  Easier to navigate than the great circle, but covers a greater distance.
 *
 * @link https://www.dougv.com/2009/07/13/calculating-the-bearing-and-compass-rose-direction-between-two-latitude-longitude-coordinates-in-php/
 * @link http://www.movable-type.co.uk/scripts/latlong.html#rhumblines
 */
class Rhumb
{
    private $geo;

    public function __construct(Component $parent)
    {
        $this->geo = $parent;
    }

    /**
     * Get the rhumb distance.
     *
     * @return float
     */
    public function distance()
    {
        list($dLat, $dLong, $dPhi) = $this->dLatLongPhi();
        $q = (abs($dPhi) > 0.000000000001) ? $dLat / $dPhi : cos($this->geo->from['radlat']);

        return sqrt(pow($dLat, 2) + pow($q, 2) * pow($dLong, 2)) * $this->geo->radius;
    }

    /**
     * Get the rhumb bearing.
     *
     * @return float
     */
    public function bearing()
    {
        list($dLat, $dLong, $dPhi) = $this->dLatLongPhi();

        return (rad2deg(atan2($dLong, $dPhi)) + 360) % 360;
    }

    private function dLatLongPhi()
    {
        $dLat = $this->geo->to['radlat'] - $this->geo->from['radlat'];
        $dLong = $this->geo->to['radlong'] - $this->geo->from['radlong'];
        if (abs($dLong) > pi()) {
            $dLong = ($dLong > 0) ? -(2 * pi() - $dLong) : (2 * pi() + $dLong);
        }
        $dPhi = log(tan(pi() / 4 + $this->geo->to['radlat'] / 2) / tan(pi() / 4 + $this->geo->from['radlat'] / 2));

        return array($dLat, $dLong, $dPhi);
    }
}
