<?php
/**
 * A class for converting an SVG to another image type.
 *
 * @category Library
 * @package SVG
 * @author Daniel Jeffery
 * @copyright Copyright (C) 2015 Starlite Dezigns. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * @version 1.0.0
 * @link https://github.com/jeffda/JefkoSVG
 *
 * This file is part of Jefko SVG.
 *
 * Jefko SVG is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jefko SVG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Jefko SVG.  If not, see <http://www.gnu.org/licenses/>.
 */

class JefkoSVG
{
	const ACCEPTED_CONVERSIONS = 'jpg;png';
	const ACCEPTED_MIMES = 'image/svg+xml';
	const ACCEPTED_TYPES = 'svg';
	const PLACEHOLDER_REGEX = '/\{\{(\w+)\}\}/i';
	
	public static function convert($file, $params = array(), &$to = 'png')
	{
		static::validateConversionType( $to );
		
		static::validateFile( $file );
		
		$filename = static::getFilename( $file, $params );
		$imagick = class_exists( 'Imagick' );
		
		if (!$imagick)
			$to = 'svg';
		
		$output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename . '.' . $to;
		
		if (file_exists( $output ))
			return $output;
		
		$svg = file_get_contents( $file );
		if (isset( $params['data'] ))
			static::fillPlaceholders( $svg, $params['data'] );
		
		if (!$imagick)
		{
			$handle = fopen( $output, 'w' );
			fwrite( $handle, $svg );
			fclose( $handle );
			
			return $output;
		}
		
		$im = new Imagick();

		$im->readImageBlob( $svg );
		$im->setImageCompressionQuality( 100 );

		switch ($to)
		{
			case 'jpg':
				$im->setImageFormat( 'jpeg' );
				break;
			case 'png':
				$imPx = new ImagickPixel( 'transparent' );
				$im->setBackgroundColor( $imPx );
				$im->setImageFormat( 'png32' );
				break;
			default:
				throw new Exception( 'JefkoSVG: The file type, ' . $to . ', is not fully supported.' );
		}
		
		$height = ((isset( $params['height'] )) ? $params['height'] : false);
		$width = ((isset( $params['width'] )) ? $params['width'] : false);
		
		if ($height || $width)
		{
			$origHeight = $im->getImageHeight();
			$origWidth = $im->getImageWidth();
			
			if (!$height)
				$height = (($width / $origWidth) * $origHeight);
			
			if (!$width)
				$width = (($height / $origHeight) * $origWidth);
			
			$height = min( $height, $origHeight );
			$width = min( $width, $origWidth );
			
			$im->resizeImage( $width, $height, imagick::FILTER_LANCZOS, 1 );
		}

		$im->writeImage( $output );
		$im->clear();
		$im->destroy();
		
		return $output;
	}
	
	protected static function fillPlaceholders(&$svg, $data)
	{
		if (!is_array( $data )) return;
		
		foreach ($data as $placeholder => $value)
			$svg = str_replace( '{{' . $placeholder . '}}', $value, $svg );
	}
	
	protected static function getFilename($file, $params)
	{
		$info = pathinfo( $file );
		
		$filename = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '?' . json_encode( $params );
		
		return md5( base64_encode( $filename ) );
	}
	
	protected static function getMime($file)
	{
		$resource = finfo_open( FILEINFO_MIME_TYPE );
		$mime = finfo_file( $resource, $file );
		finfo_close( $resource );
		
		return $mime;
	}
	
	public static function isValidFile($file)
	{
		try {
			static::validateFile( $file );
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	
	public static function render($file, $params = array(), $to = 'png')
	{
		try {
			$output = file_get_contents( static::convert( $file, $params, $to ) );
		} catch (Exception $e) {
			header( 'HTTP/1.0 404 Not Found' );
			exit(0);
		}
		
		switch ($to)
		{
			case 'jpg':
				$mime = 'image/jpeg';
				break;
			case 'png':
				$mime = 'image/png';
				break;
			default:
				$mime = 'image/svg+xml';
		}
		
		header( 'Content-Type: ' . $mime );
		echo $output;
		exit(0);
	}
	
	public static function validateConversionType(&$type)
	{
		$acceptableTypes = explode( ';', strtolower( static::ACCEPTED_CONVERSIONS ) );
		
		if (!is_array( $acceptableTypes ))
			throw new Exception( 'JefkoSVG: Could not load acceptable conversion types.' );
		
		if (!in_array( strtolower( $type ), $acceptableTypes ))
			$type = 'png';
	}
	
	protected static function validateFile($file)
	{
		if (!file_exists( $file ))
			throw new Exception( 'JefkoSVG: Could not locate the file: ' . $file );
		
		$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		
		if (!in_array( $extension, explode( ';', static::ACCEPTED_TYPES ) ))
			throw new Exception( 'JefkoSVG: Invalid file type.' );
		
		if (!in_array( static::getMime( $file ), explode( ';', static::ACCEPTED_MIMES ) ))
			throw new Exception( 'JefkoSVG: Invalid file.' );
	}
}
