<?php

namespace BootPress\Geo;

/**
 * Geo formulae for determining the distance and bearings among latitude and longitude coordinates.
 *
 * @link https://packagist.org/packages/jstayton/google-maps-geocoder
 * @link http://geoservices.tamu.edu/Services/Geocode/OtherGeocoders/
 * @link https://developers.google.com/maps/documentation/geocoding/
 */
class Component
{
    /** @var object An on-demand BootPress\Geo\Sql instance. */
    private $sql = null;

    /** @var object An on-demand BootPress\Geo\Rhumb instance. */
    private $rhumb = null;

    /** @var array Latitude and longitude data. */
    private $from = array();

    /** @var array Latitude and longitude data. */
    private $to = array();

    /** @var string Either *'Kilometers'*, *'Miles'*, or *'Nautical Miles'*. */
    private $unit = 'Miles';

    /** @var string The mean radius of the earth for ``$this->unit``. */
    private $radius = 3959; // the mean radius of the Earth

    /** @var string The points of a compass. */
    private $cardinals = array('N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'N');

    /**
     * Magic getter for our private properties.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if ($property == 'sql' && is_null($this->sql)) {
            $this->sql = new Sql($this);
        } elseif ($property == 'rhumb' && is_null($this->rhumb)) {
            $this->rhumb = new Rhumb($this);
        }

        return (isset($this->$property)) ? $this->$property : null;
    }

    /**
     * The base latitude and longitude that all calculations are based on.
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return object This for method chaining.
     */
    public function from($latitude, $longitude)
    {
        $this->from = array(
            'lat' => $latitude,
            'long' => $longitude,
            'radlat' => deg2rad($latitude),
            'radlong' => deg2rad($longitude),
        );

        return $this;
    }

    /**
     * The latitude and longitude for which we determine distance and bearing.
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return object This for method chaining.
     */
    public function to($latitude, $longitude)
    {
        $this->to = array(
            'lat' => $latitude,
            'long' => $longitude,
            'radlat' => deg2rad($latitude),
            'radlong' => deg2rad($longitude),
        );

        return $this;
    }

    /**
     * Swap ``$this->from()`` and ``$this->to()`` values;.
     *
     * @return object This for method chaining.
     */
    public function swap()
    {
        $swap = $this->to;
        $this->to = $this->from;
        $this->from = $swap;

        return $this;
    }

    /**
     * Establish the unit of measure for all calculations.  The default is *'miles'*.
     *
     * @param float $value Either *'km'*, *'mi'*, or *'nm'*.
     *
     * @return object This for method chaining.
     */
    public function unit($value)
    {
        switch (strtoupper(substr($value, 0, 1))) {
            case 'K':
                $this->unit = 'Kilometers';
                $this->radius = 6371;
                break;
            case 'M':
                $this->unit = 'Miles';
                $this->radius = 3959;
                break;
            case 'N':
                $this->unit = 'NauticalMiles';
                $this->radius = 3440;
                break;
        }

        return $this;
    }

    /**
     * Convert among kilometers, miles, and nautical miles based on ``$this->unit()``.
     *
     * @param float  $value The distance you want to convert.
     * @param string $unit  Either *'km'*, *'mi'*, or *'nm'*.  If not specified, then we will give you all three.
     *
     * @return float|array
     */
    public function convert($value, $unit = null)
    {
        if (is_null($unit)) {
            return array(
                'km' => $this->convert($value, 'km'),
                'mi' => $this->convert($value, 'mi'),
                'nm' => $this->convert($value, 'nm'),
            );
        }
        switch (strtoupper(substr($unit, 0, 1))) {
            case 'K': $convert = 'Kilometers'; break;
            case 'M': $convert = 'Miles'; break;
            case 'N': $convert = 'NauticalMiles'; break;
            default: return; break;
        }
        if ($this->unit == $convert) {
            return $value;
        } elseif ($this->unit == 'Kilometers') {
            return ($convert == 'Miles') ? $value * 0.621371 : $value * 0.539957; // else Nautical Miles
        } elseif ($this->unit == 'Miles') {
            return ($convert == 'Kilometers') ? $value * 1.60934 : $value * 0.868976; // else Nautical Miles
        } else { // $this->unit == 'NauticalMiles'
            return ($convert == 'Kilometers') ? $value * 1.852 : $value * 1.15078; // else Miles
        }
    }

    /**
     * Get the great circle distance using the spherical law of cosines, which gives results that are identical to the haversine formula.
     *
     * @return float
     *
     * @link http://www.movable-type.co.uk/scripts/latlong.html#cosine-law
     */
    public function distance()
    {
        return $this->radius * acos(
            cos($this->from['radlat']) *
            cos($this->to['radlat']) *
            cos($this->to['radlong'] - $this->from['radlong']) +
            sin($this->from['radlat']) *
            sin($this->to['radlat'])
        );
    }

    /**
     * Get the initial (great circle) bearing.
     *
     * @return float
     *
     * @link https://www.dougv.com/2009/07/13/calculating-the-bearing-and-compass-rose-direction-between-two-latitude-longitude-coordinates-in-php/
     */
    public function bearing()
    {
        return (rad2deg(atan2(
            sin($this->to['radlong'] - $this->from['radlong']) * cos($this->to['radlat']),
            cos($this->from['radlat']) *
            sin($this->to['radlat']) -
            sin($this->from['radlat']) *
            cos($this->to['radlat']) *
            cos($this->to['radlong'] - $this->from['radlong'])
        )) + 360) % 360;
    }

    /**
     * Get the cardinal compass direction from a **$bearing**.
     *
     * @param float $bearing
     *
     * @return string
     */
    public function compass($bearing)
    {
        $bearing = round($bearing / 45);

        return isset($this->cardinals[$bearing]) ? $this->cardinals[$bearing] : null;
    }
}
