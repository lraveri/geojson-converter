<?php

namespace App\GeojsonConverter;

use App\GeojsonConverter\Exception\InvalidXmlException;

class KmlConverter implements GeojsonConverterInterface
{
    protected $fileContent;

    public function __construct(string $fileContent) {
        $this->fileContent = $fileContent;
    }

    public function convert(): string
    {
        $xml = simplexml_load_string($this->fileContent);
        if ($xml === false) {
            throw new InvalidXmlException("Error parsing the KML file.");
        }

        $features = [];
        foreach ($xml->Document->Placemark as $placemark) {
            $features[] = $this->parsePlacemark($placemark);
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    protected function parsePlacemark($placemark): array {
        $geometry = $this->parseGeometry($placemark);

        $properties = [
            'name' => (string) $placemark->name,
            'description' => (string) $placemark->description,
            // Estrai ulteriori informazioni, come timestamp o ExtendedData se necessario
        ];

        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => $geometry
        ];
    }

    protected function parseGeometry($placemark): array {
        // Gestione Point
        if (isset($placemark->Point)) {
            $coordinates = explode(',', trim((string) $placemark->Point->coordinates));
            return [
                'type' => 'Point',
                'coordinates' => array_map('floatval', $coordinates)
            ];
        }

        // Gestione LineString
        if (isset($placemark->LineString)) {
            $coordinatesList = explode(' ', trim((string) $placemark->LineString->coordinates));
            $coordinates = array_map(function($coord) {
                $parts = explode(',', $coord);
                return array_map('floatval', $parts);
            }, $coordinatesList);

            return [
                'type' => 'LineString',
                'coordinates' => $coordinates
            ];
        }

        // Gestione MultiGeometry
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

    protected function parseLineStringCoordinates($lineString): array {
        $coordinatesList = explode(' ', trim((string) $lineString->coordinates));
        return array_map(function($coord) {
            $parts = explode(',', trim($coord));
            return array_map('floatval', $parts);
        }, $coordinatesList);
    }

}
