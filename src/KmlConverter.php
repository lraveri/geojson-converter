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

        //importante: se il documeto KML non ha un namespace, il metodo xpath non funziona correttamente
        // Verifica se il documento KML ha un namespace
        $namespaces = $xml->getNamespaces(true);

        //Registra il namespace KML se esiste in modo che funzioni l'xpath
        $xml->registerXPathNamespace('kml', reset($namespaces));
        $placemarks = $xml->xpath('//kml:Placemark'); // FUNZIONA

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
            // Estrai ulteriori informazioni, come timestamp o ExtendedData se necessario
        ];

        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => $geometry
        ];
    }

    protected function parseGeometry($placemark): array
    {
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
            $coordinates = $this->parseLineStringCoordinates($placemark->LineString);

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

    protected function parseLineStringCoordinates($lineString): array
    {
        $coordinatesList = explode(' ', trim((string) $lineString->coordinates));
        $coordinates = [];
        foreach ($coordinatesList as $coord) {
            $c = trim($coord);
            if (empty($c)) {
                continue; // Salta le coordinate vuote
            }
            if (strpos($c, ',') === false) {
                continue; // Salta se non contiene una virgola
            }
            $parts = explode(',', $c);
            if (count($parts) < 2) {
                continue; // Salta se non ha almeno due parti
            }
            if (!is_numeric($parts[0]) || !is_numeric($parts[1])) {
                continue; // Salta se le parti non sono numeriche
            }
            $coordinates[] = array_map('floatval', $parts);
        }

        return $coordinates;
    }
}
