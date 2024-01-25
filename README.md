# Convert KML and GPX to GeoJSON in PHP

## Description
`geojson-converter` is a PHP library for converting GPX and KML files into GeoJSON format. This library is particularly useful for projects that require a quick and reliable conversion of mapping file formats.

## Installation
To install `geojson-converter`, use the following command in your project:

```bash
composer require lraveri/geojson-converter
```

## Usage

### Converting GPX Files to GeoJSON

To convert a GPX file to GeoJSON:

```php
`$content = file_get_contents('/path/to/file.gpx');
$gpxConverter = new GpxConverter($content);
$geoJson = $gpxConverter->convert();` 
```

### Converting KML Files to GeoJSON

To convert a KML file to GeoJSON:

```php
`$content = file_get_contents('/path/to/file.kml');
$kmlConverter = new KmlConverter($content);
$geoJson = $kmlConverter->convert();` 
```

## Requirements

-   PHP 7.1 or higher.

## Contributing

Interested in contributing? Fantastic! You can open a pull request or an issue on the [GitHub repository](https://github.com/lraveri/geojson-converter).

## License

This project is released under the MIT License. See the `LICENSE` file for more details.

## Support

If you have any questions or issues, feel free to create an issue on the GitHub repository.
