# JefkoSVG

A library for converting an SVG to another image type, or simply replacing placeholders in an SVG for dynamic content.

## Basic Usage ##
```
require_once('./JefkoSVG.php');

$file = realpath('./img/image.svg');
$params = array(
  'data' => array(
    'placeholder1' => 'Filler text', // placeholder_name => placeholder_value
    'random' => 'Filler text' // Replaces {{random}} with "Filler text"
  ),
  'height' => '300', // Optional. In pixels.
  'width' => '300' // Optional. In pixels.
);

JefkoSVG::render($file, $params); // Outputs the image.
```

## Requirements ##
* Image Magick *(required for conversions only)*

### Supported Image Types ###
* PNG
* JPG
* SVG

### Placeholders ###
The SVG file can contain placeholders in the following format: ***{{placeholder_name}}***
