<?php
use PHPUnit\Framework\TestCase;
use Lraveri\GeojsonConverter\GpxConverter;
use Lraveri\GeojsonConverter\Exception\InvalidXmlException;

require_once __DIR__ . '/../vendor/autoload.php';

class GpxConverterTest extends TestCase
{
    public function testInstanceCanBeCreated()
    {
        $converter = new GpxConverter('');
        $this->assertInstanceOf(GpxConverter::class, $converter);
    }

    public function testConvertThrowsOnInvalidXml()
    {
        $this->expectException(InvalidXmlException::class);
        $converter = new GpxConverter('<gpx><invalid></gpx');
        $converter->convert();
    }

    public function testConvertReturnsValidGeoJsonForWaypoint()
    {
        $gpx = '<?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="test">
            <wpt lat="45.0" lon="9.0">
                <name>Test Point</name>
                <desc>Descrizione</desc>
                <ele>100</ele>
                <time>2024-01-01T12:00:00Z</time>
                <cmt>Commento</cmt>
            </wpt>
        </gpx>';
        $converter = new GpxConverter($gpx);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(1, $data['features']);
        $feature = $data['features'][0];
        $this->assertEquals('Feature', $feature['type']);
        $this->assertEquals('Point', $feature['geometry']['type']);
        $this->assertEquals([9.0, 45.0], $feature['geometry']['coordinates']);
        $this->assertEquals('Test Point', $feature['properties']['name']);
        $this->assertEquals('Descrizione', $feature['properties']['description']);
        $this->assertEquals(100.0, $feature['properties']['ele']);
        $this->assertEquals('2024-01-01T12:00:00Z', $feature['properties']['time']);
        $this->assertEquals('Commento', $feature['properties']['comment']);
    }

    public function testConvertReturnsValidGeoJsonForRoute()
    {
        $gpx = '<?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="test">
            <rte>
                <name>Test Route</name>
                <desc>Route Desc</desc>
                <rtept lat="45.0" lon="9.0"><ele>100</ele></rtept>
                <rtept lat="45.1" lon="9.1"><ele>110</ele></rtept>
            </rte>
        </gpx>';
        $converter = new GpxConverter($gpx);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(1, $data['features']);
        $feature = $data['features'][0];
        $this->assertEquals('LineString', $feature['geometry']['type']);
        $this->assertEquals([[9.0, 45.0, 100.0], [9.1, 45.1, 110.0]], $feature['geometry']['coordinates']);
        $this->assertEquals('Test Route', $feature['properties']['name']);
        $this->assertEquals('Route Desc', $feature['properties']['description']);
    }

    public function testConvertReturnsValidGeoJsonForTrack()
    {
        $gpx = '<?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="test">
            <trk>
                <name>Test Track</name>
                <desc>Track Desc</desc>
                <trkseg>
                    <trkpt lat="45.0" lon="9.0"><ele>100</ele></trkpt>
                    <trkpt lat="45.1" lon="9.1"><ele>110</ele></trkpt>
                </trkseg>
            </trk>
        </gpx>';
        $converter = new GpxConverter($gpx);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(1, $data['features']);
        $feature = $data['features'][0];
        $this->assertEquals('MultiLineString', $feature['geometry']['type']);
        $this->assertEquals([[[9.0, 45.0, 100.0], [9.1, 45.1, 110.0]]], $feature['geometry']['coordinates']);
        $this->assertEquals('Test Track', $feature['properties']['name']);
        $this->assertEquals('Track Desc', $feature['properties']['description']);
    }

    public function testConvertWithMetadataProperties()
    {
        $gpx = '<?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="test">
            <metadata>
                <name>Test GPX</name>
                <desc>Test Description</desc>
                <author>
                    <name>Test Author</name>
                    <email id="info" domain="example.com"/>
                </author>
                <time>2024-01-01T12:00:00Z</time>
            </metadata>
        </gpx>';
        $converter = new GpxConverter($gpx);
        $geojson = $converter->convert();
        $data = json_decode($geojson, true);
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertEquals('Test GPX', $data['properties']['name']);
        $this->assertEquals('Test Description', $data['properties']['description']);
        $this->assertEquals('Test Author', $data['properties']['author']);
        $this->assertEquals('info@example.com', $data['properties']['email']);
        $this->assertEquals('2024-01-01T12:00:00Z', $data['properties']['time']);
    }

    public function testParseWaypointsHandlesEmpty()
    {
        $reflection = new \ReflectionClass(GpxConverter::class);
        $method = $reflection->getMethod('parseWaypoints');
        $method->setAccessible(true);
        $converter = new GpxConverter('');
        $result = $method->invoke($converter, []);
        $this->assertEquals([], $result);
    }

    public function testParseRoutesHandlesEmpty()
    {
        $reflection = new \ReflectionClass(GpxConverter::class);
        $method = $reflection->getMethod('parseRoutes');
        $method->setAccessible(true);
        $converter = new GpxConverter('');
        $result = $method->invoke($converter, []);
        $this->assertEquals([], $result);
    }

    public function testParseTracksHandlesEmpty()
    {
        $reflection = new \ReflectionClass(GpxConverter::class);
        $method = $reflection->getMethod('parseTracks');
        $method->setAccessible(true);
        $converter = new GpxConverter('');
        $result = $method->invoke($converter, []);
        $this->assertEquals([], $result);
    }
}
