<?php
use PHPUnit\Framework\TestCase;
use Lraveri\GeojsonConverter\KmlConverter;
use Lraveri\GeojsonConverter\Exception\InvalidXmlException;

require_once __DIR__ . '/../vendor/autoload.php';

class KmlConverterTest extends TestCase
{
    public function testInstanceCanBeCreated()
    {
        $converter = new KmlConverter('');
        $this->assertInstanceOf(KmlConverter::class, $converter);
    }

    public function testConvertThrowsOnInvalidXml()
    {
        $this->expectException(InvalidXmlException::class);
        $converter = new KmlConverter('<kml><invalid></kml');
        $converter->convert();
    }

    public function testConvertReturnsValidGeoJsonForPoint()
    {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>
        <kml xmlns="http://www.opengis.net/kml/2.2">
            <Document>
                <Placemark>
                    <name>Test Point</name>
                    <description>Descrizione</description>
                    <Point>
                        <coordinates>9.0,45.0,100.0</coordinates>
                    </Point>
                </Placemark>
            </Document>
        </kml>';
        $converter = new KmlConverter($kml);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(1, $data['features']);
        $feature = $data['features'][0];
        $this->assertEquals('Feature', $feature['type']);
        $this->assertEquals('Point', $feature['geometry']['type']);
        $this->assertEquals([9.0, 45.0, 100.0], $feature['geometry']['coordinates']);
        $this->assertEquals('Test Point', $feature['properties']['name']);
        $this->assertEquals('Descrizione', $feature['properties']['description']);
    }

    public function testConvertReturnsValidGeoJsonForLineString()
    {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>
        <kml xmlns="http://www.opengis.net/kml/2.2">
            <Document>
                <Placemark>
                    <name>Test Line</name>
                    <description>Line Desc</description>
                    <LineString>
                        <coordinates>9.0,45.0,100.0 9.1,45.1,110.0</coordinates>
                    </LineString>
                </Placemark>
            </Document>
        </kml>';
        $converter = new KmlConverter($kml);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(1, $data['features']);
        $feature = $data['features'][0];
        $this->assertEquals('LineString', $feature['geometry']['type']);
        $this->assertEquals([[9.0, 45.0, 100.0], [9.1, 45.1, 110.0]], $feature['geometry']['coordinates']);
        $this->assertEquals('Test Line', $feature['properties']['name']);
        $this->assertEquals('Line Desc', $feature['properties']['description']);
    }

    public function testConvertReturnsValidGeoJsonForMultiLineString()
    {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>
        <kml xmlns="http://www.opengis.net/kml/2.2">
            <Document>
                <Placemark>
                    <name>Test MultiLine</name>
                    <description>MultiLine Desc</description>
                    <MultiGeometry>
                        <LineString>
                            <coordinates>9.0,45.0,100.0 9.1,45.1,110.0</coordinates>
                        </LineString>
                        <LineString>
                            <coordinates>10.0,46.0,120.0 10.1,46.1,130.0</coordinates>
                        </LineString>
                    </MultiGeometry>
                </Placemark>
            </Document>
        </kml>';
        $converter = new KmlConverter($kml);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(1, $data['features']);
        $feature = $data['features'][0];
        $this->assertEquals('MultiLineString', $feature['geometry']['type']);
        $this->assertEquals([
            [[9.0, 45.0, 100.0], [9.1, 45.1, 110.0]],
            [[10.0, 46.0, 120.0], [10.1, 46.1, 130.0]]
        ], $feature['geometry']['coordinates']);
        $this->assertEquals('Test MultiLine', $feature['properties']['name']);
        $this->assertEquals('MultiLine Desc', $feature['properties']['description']);
    }

    public function testParseLineStringCoordinatesHandlesEmpty()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        $result = $method->invoke($converter, (object)["coordinates" => ""]);
        $this->assertEquals([], $result);
    }

    public function testParseLineStringCoordinatesHandlesEmptyString()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        $result = $method->invoke($converter, (object)["coordinates" => ""]);
        $this->assertEquals([], $result);
    }

    public function testParseLineStringCoordinatesHandlesSinglePoint()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        $result = $method->invoke($converter, (object)["coordinates" => "9.0,45.0,100.0"]);
        $this->assertEquals([[9.0, 45.0, 100.0]], $result);
    }

    public function testParseLineStringCoordinatesHandlesMultiplePoints()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        $result = $method->invoke($converter, (object)["coordinates" => "9.0,45.0,100.0 9.1,45.1,110.0"]);
        $this->assertEquals([[9.0, 45.0, 100.0], [9.1, 45.1, 110.0]], $result);
    }

    public function testParseLineStringCoordinatesSkipsInvalidPoints()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        $result = $method->invoke($converter, (object)["coordinates" => "9.0,45.0,100.0 invalid 9.1,45.1,110.0 , ,"]);
        $this->assertEquals([[9.0, 45.0, 100.0], [9.1, 45.1, 110.0]], $result);
    }

    public function testParseLineStringCoordinatesHandlesNoComma()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        $result = $method->invoke($converter, (object)["coordinates" => "9.0 45.0 100.0"]);
        $this->assertEquals([], $result);
    }

    public function testParseLineStringCoordinatesHandlesLessThanTwoParts()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        // Only one part, should be skipped
        $result = $method->invoke($converter, (object)["coordinates" => "9.0"]);
        $this->assertEquals([], $result);
        // One valid, one invalid
        $result = $method->invoke($converter, (object)["coordinates" => "9.0,45.0,100.0 9.1"]);
        $this->assertEquals([[9.0, 45.0, 100.0]], $result);
    }

    public function testParseLineStringCoordinatesSkipsSingleValueAndMixedCases()
    {
        $reflection = new \ReflectionClass(KmlConverter::class);
        $method = $reflection->getMethod('parseLineStringCoordinates');
        $method->setAccessible(true);
        $converter = new KmlConverter('');
        // Only a single value, should be skipped
        $result = $method->invoke($converter, (object)["coordinates" => "9.0"]);
        $this->assertEquals([], $result);
        // Mixed: one valid, one with only one part
        $result = $method->invoke($converter, (object)["coordinates" => "9.0,45.0,100.0 9.1"]);
        $this->assertEquals([[9.0, 45.0, 100.0]], $result);
        // Mixed: one valid, one empty, one with only one part
        $result = $method->invoke($converter, (object)["coordinates" => "9.0,45.0,100.0   9.1   "]);
        $this->assertEquals([[9.0, 45.0, 100.0]], $result);
    }
}
