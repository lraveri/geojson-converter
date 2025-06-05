<?php

namespace Lraveri\GeojsonConverter;

use Lraveri\GeojsonConverter\Exception\InvalidXmlException;

class KmlConverter implements GeojsonConverterInterface
{
    protected $fileContent;

    public function __construct(string $fileContent)
    {
        $this->fileContent = $fileContent;
    }

    public function convert(): string
    {
        $xml = simplexml_load_string($this->fileContent);
        if ($xml === false) {
            throw new InvalidXmlException("Error parsing the KML file.");
        }

        $namespaces = $xml->getNamespaces(true);

        $xml->registerXPathNamespace('kml', reset($namespaces));
        $placemarks = $xml->xpath('//kml:Placemark');

        $features = [];
        foreach ($placemarks as $placemark) {
            $features[] = $this->parsePlacemark($placemark);
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    protected function parsePlacemark($placemark): array
    {
        $geometry = $this->parseGeometry($placemark);

        $properties = [
            'name' => (string) $placemark->name,
            'description' => (string) $placemark->description,
        ];

        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => $geometry
        ];
    }

    protected function parseGeometry($placemark): array
    {
        if (isset($placemark->Point)) {
            $coordinates = explode(',', trim((string) $placemark->Point->coordinates));
            return [
                'type' => 'Point',
                'coordinates' => array_map('floatval', $coordinates)
            ];
        }

        if (isset($placemark->LineString)) {
            $coordinates = $this->parseLineStringCoordinates($placemark->LineString);

            return [
                'type' => 'LineString',
                'coordinates' => $coordinates
            ];
        }

        if (isset($placemark->MultiGeometry)) {
            $multiLineCoordinates = [];
            foreach ($placemark->MultiGeometry->children() as $childGeometry) {
                if ($childGeometry->getName() == 'LineString') {
                    $lineCoordinates = $this->parseLineStringCoordinates($childGeometry);
                    if (!empty($lineCoordinates)) {
                        $multiLineCoordinates[] = $lineCoordinates;
                    }
                }
            }
            return [
                'type' => 'MultiLineString',
                'coordinates' => $multiLineCoordinates
            ];
        }

        return [];
    }

    protected function parseLineStringCoordinates($lineString): array
    {
        $coordinatesList = explode(' ', trim((string) $lineString->coordinates));
        $coordinates = [];
        foreach ($coordinatesList as $coord) {
            $c = trim($coord);
            if (empty($c)) {
                continue;
            }
            if (strpos($c, ',') === false) {
                continue;
            }
            $parts = explode(',', $c);
            if (count($parts) < 2) {
                continue;
            }
            if (!is_numeric($parts[0]) || !is_numeric($parts[1])) {
                continue;
            }
            $coordinates[] = array_map('floatval', $parts);
        }

        return $coordinates;
    }
}
