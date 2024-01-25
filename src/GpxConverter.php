<?php

namespace Lraveri\GeojsonConverter;

use Lraveri\GeojsonConverter\Exception\InvalidXmlException;

class GpxConverter implements GeojsonConverterInterface
{
    protected $fileContent;

    public function __construct(string $fileContent) {
        $this->fileContent = $fileContent;
    }

    public function convert(): string
    {
        $xml = simplexml_load_string($this->fileContent);
        if ($xml === false) {
            throw new InvalidXmlException("Error parsing the GPX file.");
        }

        $features = [];
        if (isset($xml->wpt)) {
            $features = array_merge($features, $this->parseWaypoints($xml->wpt));
        }
        if (isset($xml->rte)) {
            $features = array_merge($features, $this->parseRoutes($xml->rte));
        }
        if (isset($xml->trk)) {
            $features = array_merge($features, $this->parseTracks($xml->trk));
        }

        $metaProperties = [];
        if (isset($xml->metadata)) {
            $metaProperties['name'] = (string) $xml->metadata->name;
            $metaProperties['description'] = (string) $xml->metadata->desc;
            $metaProperties['author'] = isset($xml->metadata->author) ? (string) $xml->metadata->author->name : '';
            $metaProperties['email'] = isset($xml->metadata->author)
                ? $xml->metadata->author->email['id'] . '@' . $xml->metadata->author->email['domain'] : '';
            $metaProperties['time'] = (string) $xml->metadata->time;
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'properties' => $metaProperties,
            'features' => $features
        ]);
    }

    protected function parseWaypoints($waypoints): array {
        $features = [];
        foreach ($waypoints as $wpt) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'name' => (string) $wpt->name,
                    'description' => (string) $wpt->desc,
                    'ele' => (float) $wpt->ele,
                    'time' => (string) $wpt->time,
                    'comment' => (string) $wpt->cmt
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $wpt['lon'], (float) $wpt['lat']]
                ]
            ];
        }
        return $features;
    }

    protected function parseRoutes($routes): array {
        $features = [];
        foreach ($routes as $rte) {
            $routePoints = [];
            foreach ($rte->rtept as $rtept) {
                $point = [(float) $rtept['lon'], (float) $rtept['lat']];
                if (isset($rtept->ele)) {
                    $point[] = (float) $rtept->ele;
                }
                if (isset($rtept->time)) {
                    $point[] = (string) $rtept->time;
                }
                $routePoints[] = $point;
            }

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'name' => (string) $rte->name,
                    'description' => (string) $rte->desc,
                ],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $routePoints
                ]
            ];
        }
        return $features;
    }

    protected function parseTracks($tracks): array {
        $features = [];
        foreach ($tracks as $trk) {
            $trackSegments = [];
            foreach ($trk->trkseg as $trkseg) {
                $segmentPoints = [];
                foreach ($trkseg->trkpt as $trkpt) {
                    $point = [(float) $trkpt['lon'], (float) $trkpt['lat']];
                    if (isset($trkpt->ele)) {
                        $point[] = (float) $trkpt->ele;
                    }
                    if (isset($trkpt->time)) {
                        $point[] = (string) $trkpt->time;
                    }
                    $segmentPoints[] = $point;
                }
                $trackSegments[] = $segmentPoints;
            }

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'name' => (string) $trk->name,
                    'description' => (string) $trk->desc,
                ],
                'geometry' => [
                    'type' => 'MultiLineString',
                    'coordinates' => $trackSegments
                ]
            ];
        }
        return $features;
    }
}
