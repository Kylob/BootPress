<?php

use BootPress\Geo\Component as Geo;

class GeoTest extends \PHPUnit_Framework_TestCase
{
    private static $geo;
    private static $sydney;
    private static $anchorage;
    private static $fairbanks;

    public static function setUpBeforeClass()
    {
        self::$geo = new Geo();
        self::$sydney = array(
            'lat' => -33.9399228,
            'long' => 151.17527640000003,
        );
        self::$anchorage = array(
            'lat' => 61.174400329589801117,
            'long' => -149.99600219726599448,
        );
        self::$fairbanks = array(
            'lat' => 64.815101619999992977,
            'long' => -147.85600279999999884,
        );
    }

    public function testGetterProperties()
    {
        $this->assertInstanceOf('BootPress\Geo\Sql', self::$geo->sql);
        $this->assertInstanceOf('BootPress\Geo\Rhumb', self::$geo->rhumb);
        $this->assertEquals(array(), self::$geo->from);
        $this->assertEquals(array(), self::$geo->to);
        $this->assertEquals('Miles', self::$geo->unit);
        $this->assertEquals(3959, self::$geo->radius);
        $this->assertEquals(array('N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'N'), self::$geo->cardinals);
    }

    public function testFromMethod()
    {
        extract(self::$anchorage);
        $this->assertInstanceOf('BootPress\Geo\Component', self::$geo->from($lat, $long));
        $this->assertEquals(array(
            'lat' => $lat,
            'long' => $long,
            'radlat' => deg2rad($lat),
            'radlong' => deg2rad($long),
        ), self::$geo->from);
    }

    public function testToMethod()
    {
        extract(self::$fairbanks);
        $this->assertInstanceOf('BootPress\Geo\Component', self::$geo->to($lat, $long));
        $this->assertEquals(array(
            'lat' => $lat,
            'long' => $long,
            'radlat' => deg2rad($lat),
            'radlong' => deg2rad($long),
        ), self::$geo->to);
    }

    public function testSwapMethod()
    {
        $to = self::$geo->to;
        $from = self::$geo->from;
        $this->assertInstanceOf('BootPress\Geo\Component', self::$geo->swap());
        $this->assertEquals($from, self::$geo->to);
        $this->assertEquals($to, self::$geo->from);
    }

    public function testUnitMethod()
    {
        $this->assertInstanceOf('BootPress\Geo\Component', self::$geo->unit('ki'));
        $this->assertEquals('Kilometers', self::$geo->unit);
        $this->assertEquals(6371, self::$geo->radius);
        $this->assertInstanceOf('BootPress\Geo\Component', self::$geo->unit('mi'));
        $this->assertEquals('Miles', self::$geo->unit);
        $this->assertEquals(3959, self::$geo->radius);
        $this->assertInstanceOf('BootPress\Geo\Component', self::$geo->unit('nm'));
        $this->assertEquals('NauticalMiles', self::$geo->unit);
        $this->assertEquals(3440, self::$geo->radius);
    }

    public function testConvertMethod()
    {
        self::$geo->unit('Kilometers');
        $this->assertEquals(array(
            'km' => 100,
            'mi' => 62,
            'nm' => 54,
        ), array_map('round', self::$geo->convert(100)));
        self::$geo->unit('Miles');
        $this->assertEquals(array(
            'km' => 161,
            'mi' => 100,
            'nm' => 87,
        ), array_map('round', self::$geo->convert(100)));
        self::$geo->unit('NauticalMiles');
        $this->assertEquals(array(
            'km' => 185,
            'mi' => 115,
            'nm' => 100,
        ), array_map('round', self::$geo->convert(100)));
        $this->assertNull(self::$geo->convert(100, 'feet'));
    }

    public function testDistanceMethod()
    {
        $this->setSydneyToAnchorage();
        $this->assertEquals(11829.56, round(self::$geo->unit('kilometers')->distance(), 2));
        $this->assertEquals(7351.00, round(self::$geo->unit('miles')->distance(), 2));
        $this->assertEquals(6387.33, round(self::$geo->unit('nautical')->distance(), 2));
        $this->setAnchorageToFairbanks();
        $this->assertEquals(418.94, round(self::$geo->unit('kilometers')->distance(), 2));
        $this->assertEquals(260.33, round(self::$geo->unit('miles')->distance(), 2));
        $this->assertEquals(226.20, round(self::$geo->unit('nautical')->distance(), 2));
        $this->setFairbanksToAnchorage();
        $this->assertEquals(418.94, round(self::$geo->unit('kilometers')->distance(), 2));
        $this->assertEquals(260.33, round(self::$geo->unit('miles')->distance(), 2));
        $this->assertEquals(226.20, round(self::$geo->unit('nautical')->distance(), 2));
    }

    public function testRhumbDistanceMethod()
    {
        $this->setSydneyToAnchorage();
        $this->assertEquals(11902.16, round(self::$geo->unit('kilometers')->rhumb->distance(), 2));
        $this->assertEquals(7396.11, round(self::$geo->unit('miles')->rhumb->distance(), 2));
        $this->assertEquals(6426.53, round(self::$geo->unit('nautical')->rhumb->distance(), 2));
        $this->setAnchorageToFairbanks();
        $this->assertEquals(418.96, round(self::$geo->unit('kilometers')->rhumb->distance(), 2));
        $this->assertEquals(260.34, round(self::$geo->unit('miles')->rhumb->distance(), 2));
        $this->assertEquals(226.21, round(self::$geo->unit('nautical')->rhumb->distance(), 2));
        $this->setFairbanksToAnchorage();
        $this->assertEquals(418.96, round(self::$geo->unit('kilometers')->rhumb->distance(), 2));
        $this->assertEquals(260.34, round(self::$geo->unit('miles')->rhumb->distance(), 2));
        $this->assertEquals(226.21, round(self::$geo->unit('nautical')->rhumb->distance(), 2));
    }

    public function testRhumbBearingMethod()
    {
        $this->setSydneyToAnchorage();
        $this->assertEquals(27, self::$geo->rhumb->bearing());
        $this->setAnchorageToFairbanks();
        $this->assertEquals(14, self::$geo->rhumb->bearing());
        $this->setFairbanksToAnchorage();
        $this->assertEquals(194, self::$geo->rhumb->bearing());
    }

    public function testBearingMethod()
    {
        $this->setSydneyToAnchorage();
        $this->assertEquals(25, self::$geo->bearing());
        $this->setAnchorageToFairbanks();
        $this->assertEquals(13, self::$geo->bearing());
        $this->setFairbanksToAnchorage();
        $this->assertEquals(195, self::$geo->bearing());
    }

    public function testCompassMethod()
    {
        foreach (range(-50, -23) as $bearing) {
            $this->assertNull(self::$geo->compass($bearing));
        }
        foreach (range(-22, 22) as $bearing) {
            $this->assertEquals('N', self::$geo->compass($bearing));
        }
        foreach (range(23, 67) as $bearing) {
            $this->assertEquals('NE', self::$geo->compass($bearing));
        }
        foreach (range(68, 112) as $bearing) {
            $this->assertEquals('E', self::$geo->compass($bearing));
        }
        foreach (range(113, 157) as $bearing) {
            $this->assertEquals('SE', self::$geo->compass($bearing));
        }
        foreach (range(158, 202) as $bearing) {
            $this->assertEquals('S', self::$geo->compass($bearing));
        }
        foreach (range(203, 247) as $bearing) {
            $this->assertEquals('SW', self::$geo->compass($bearing));
        }
        foreach (range(248, 292) as $bearing) {
            $this->assertEquals('W', self::$geo->compass($bearing));
        }
        foreach (range(293, 337) as $bearing) {
            $this->assertEquals('NW', self::$geo->compass($bearing));
        }
        foreach (range(338, 382) as $bearing) {
            $this->assertEquals('N', self::$geo->compass($bearing));
        }
        foreach (range(383, 410) as $bearing) {
            $this->assertNull(self::$geo->compass($bearing));
        }
    }

    public function testSqlFieldsMethod()
    {
        $this->assertNull(self::$geo->sql->fields('g.lat', 'g.long'));
        $this->assertAttributeEquals(array(
            'lat' => 'g.lat',
            'long' => 'g.long',
            'radlat' => 'RADIANS(g.lat)',
            'radlong' => 'RADIANS(g.long)',
        ), 'fields', self::$geo->sql);
    }

    public function testSqlDistanceMethod()
    {
        $this->setAnchorageToFairbanks();
        $anchorage = self::$geo->from;
        extract($anchorage);
        $formula = 'ACOS(COS('.$radlat.') * COS(RADIANS(g.lat)) * COS(RADIANS(g.long) - '.$radlong.') + SIN('.$radlat.') * SIN(RADIANS(g.lat))))';
        $this->assertEquals('(6371 * '.$formula.' AS distance', self::$geo->unit('kilometers')->sql->distance());
        $this->assertEquals('(3959 * '.$formula.' AS distance', self::$geo->unit('miles')->sql->distance());
        $this->assertEquals('(3440 * '.$formula.' AS distance', self::$geo->unit('nautical')->sql->distance());
    }

    public function testSqlBearingMethod()
    {
        $this->setAnchorageToFairbanks();
        $anchorage = self::$geo->from;
        extract($anchorage);
        $this->assertEquals('((DEGREES(ATAN2(SIN(RADIANS(g.long) - '.$radlong.') * COS(RADIANS(g.lat)), COS('.$radlat.') * SIN(RADIANS(g.lat)) - SIN('.$radlat.') * COS(RADIANS(g.lat)) * COS(RADIANS(g.long) - '.$radlong.'))) + 360) % 360) AS bearing', self::$geo->sql->bearing());
    }

    public function testSqlWithinMethod()
    {
        $this->setAnchorageToFairbanks();
        $anchorage = self::$geo->from;
        extract($anchorage);
        $this->assertEquals('g.lat BETWEEN 60.275078723671 AND 62.073721935509 AND g.long BETWEEN -151.861252921 AND -148.13075147353', self::$geo->unit('kilometers')->sql->within(100));
        $this->assertEquals('g.lat BETWEEN 59.72717174881 AND 62.62162891037 AND g.long BETWEEN -152.99764714824 AND -146.9943572463', self::$geo->unit('miles')->sql->within(100));
        $this->assertEquals('g.lat BETWEEN 59.508825343744 AND 62.839975315435 AND g.long BETWEEN -153.4505116045 AND -146.54149279003', self::$geo->unit('nautical')->sql->within(100));
    }

    public function testSqlOrderMethod()
    {
        $this->setAnchorageToFairbanks();
        $anchorage = self::$geo->from;
        extract($anchorage);
        $this->assertEquals('(('.$lat.' - g.lat) * ('.$lat.' - g.lat) + ('.$long.' - g.long) * ('.$long.' - g.long) * '.pow(cos($radlat), 2).')', self::$geo->sql->order());
    }

    private function setSydneyToAnchorage()
    {
        extract(self::$sydney, EXTR_PREFIX_ALL, 'syd');
        extract(self::$anchorage, EXTR_PREFIX_ALL, 'anc');
        self::$geo->from($syd_lat, $syd_long)->to($anc_lat, $anc_long);
    }

    private function setAnchorageToFairbanks()
    {
        extract(self::$anchorage, EXTR_PREFIX_ALL, 'anc');
        extract(self::$fairbanks, EXTR_PREFIX_ALL, 'fai');
        self::$geo->from($anc_lat, $anc_long)->to($fai_lat, $fai_long);
    }

    private function setFairbanksToAnchorage()
    {
        extract(self::$fairbanks, EXTR_PREFIX_ALL, 'fai');
        extract(self::$anchorage, EXTR_PREFIX_ALL, 'anc');
        self::$geo->from($fai_lat, $fai_long)->to($anc_lat, $anc_long);
    }
}
