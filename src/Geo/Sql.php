<?php

namespace BootPress\Geo;

/**
 * Get SQL query strings that will return the desired geo information from your database records.  All results are in reference to where you set ``$geo->from()``.
 */
class Sql
{
    private $geo;
    private $fields;

    public function __construct(Component $parent)
    {
        $this->geo = $parent;
        $this->fields();
    }

    /**
     * Set the names of the **$latitude** and **$longitude** columns in your query.
     *
     * @param string $latitude
     * @param string $longitude
     */
    public function fields($latitude = 'latitude', $longitude = 'longitude')
    {
        $this->fields = array(
            'lat' => $latitude,
            'long' => $longitude,
            'radlat' => 'RADIANS('.$latitude.')',
            'radlong' => 'RADIANS('.$longitude.')',
        );
    }

    /**
     * Get the great circle distance of each record from a central latitude and longitude using the spherical law of cosines, which gives results that are identical to the haversine formula.
     *
     * @return string
     *
     * @link http://stackoverflow.com/questions/574691/mysql-great-circle-distance-haversine-formula
     * @link http://www.movable-type.co.uk/scripts/latlong.html#cosine-law
     */
    public function distance()
    {
        return implode('', array(
            '('.$this->geo->radius.' * ACOS(',
                'COS('.$this->geo->from['radlat'].') * ',
                'COS('.$this->fields['radlat'].') * ',
                'COS('.$this->fields['radlong'].' - '.$this->geo->from['radlong'].') + ',
                'SIN('.$this->geo->from['radlat'].') * ',
                'SIN('.$this->fields['radlat'].')',
            ')) AS distance',
        ));
    }

    /**
     * Return the initial (great circle) bearing of each record from a central latitude and longitude.
     *
     * @return string
     *
     * @link http://stackoverflow.com/questions/24099740/can-mysql-determine-bearing-between-two-records-with-latitude-and-longitude
     * @link http://www.movable-type.co.uk/scripts/latlong.html#bearing
     */
    public function bearing()
    {
        return implode('', array(
            '((DEGREES(ATAN2(',
                'SIN('.$this->fields['radlong'].' - '.$this->geo->from['radlong'].') * COS('.$this->fields['radlat'].'), ',
                'COS('.$this->geo->from['radlat'].') * ',
                'SIN('.$this->fields['radlat'].') - ',
                'SIN('.$this->geo->from['radlat'].') * ',
                'COS('.$this->fields['radlat'].') * ',
                'COS('.$this->fields['radlong'].' - '.$this->geo->from['radlong'].')',
            ')) + 360) % 360) AS bearing',
        ));
    }

    /**
     * Establish a bounding box within a given **$distance** to pull records from.
     * 
     * @param float $distance
     * 
     * @return string
     *
     * @link http://www.movable-type.co.uk/scripts/latlong-db.html
     */
    public function within($distance)
    {
        $maxLat = $this->geo->from['lat'] + rad2deg($distance / $this->geo->radius);
        $minLat = $this->geo->from['lat'] - rad2deg($distance / $this->geo->radius);
        $maxLong = $this->geo->from['long'] + rad2deg($distance / $this->geo->radius / cos($this->geo->from['radlat']));
        $minLong = $this->geo->from['long'] - rad2deg($distance / $this->geo->radius / cos($this->geo->from['radlat']));

        return $this->fields['lat'].' BETWEEN '.$minLat.' AND '.$maxLat.' AND '.$this->fields['long'].' BETWEEN '.$minLong.' AND '.$maxLong;
    }

    /**
     * Order records by the amount of distance from a central latitude and longitude.
     *
     * @return string
     *
     * @link http://stackoverflow.com/questions/3695224/sqlite-getting-nearest-locations-with-latitude-and-longitude/7472230#7472230
     */
    public function order()
    {
        return implode('', array(
            '(',
                '('.$this->geo->from['lat'].' - '.$this->fields['lat'].') * ('.$this->geo->from['lat'].' - '.$this->fields['lat'].') + ',
                '('.$this->geo->from['long'].' - '.$this->fields['long'].') * ('.$this->geo->from['long'].' - '.$this->fields['long'].') * ',
                pow(cos($this->geo->from['radlat']), 2), // A scaling factor which is 0 at the poles and 1 at the equator
            ')',
        ));
    }
}
