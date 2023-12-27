<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-24, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revisions, ver 5.2
//	- Function save, if second parameter is int now represents maximum
//		dimension for resize
// Revisions, ver 5.3
//	- Added additional cases for jpeg rotations
//	- Moved scaling operations to image method
//	- Added read method, overrides parent::read, includes scaling option
//	- Added base64 method
//	- Added src method
//	- Added support for SVG upload

namespace FST;

/**
 * Image file upload handler.
 *
 * An object of this type is returned as data from the FormImageControl
 * object. The FormImageControl object will return objects of this type
 * only upon a successful file upload.
 */
class FormImageUpload extends FormFileUpload {

	/**
	 * Constructor.
	 *
	 * @param string $name Name of the file upload control
	 */
	public function __construct ($name) {

		parent::__construct($name);

		// Determine image type
		if ($this->type == 'image/svg+xml')
			$this->fileinfo['imagetype'] = 'svg';
		else
			switch (exif_imagetype($this->tmp_name)) {
			case IMAGETYPE_GIF:
				$this->fileinfo['imagetype'] = 'gif';
				break;
			case IMAGETYPE_JPEG:
				$this->fileinfo['imagetype'] = 'jpeg';
				break;
			case IMAGETYPE_PNG:
				$this->fileinfo['imagetype'] = 'png';
				break;
			default:
				$this->fileinfo['imagetype'] = 'unknown';
			}
	}

	/**
	 * Get image string base64-encoded
	 *
	 * Gets a base64-encoded string of the image file, optionally scaled
	 * according to the scaling dimensions. See FileImageUpload::image for
	 * dimension options.
	 *
	 * @param mixed $dim Max scaling dimension, or dimension options array
	 * @return string Image file contents, base64-encoded
	 */
	public function base64 ($dim=false)
		{ return base64_encode($this->read($dim)); }

	/**
	 * Get image resource.
	 *
	 * Creates an image resource from the uploaded file, optionally scaled
	 * according to the given dimension option. This method is not applicable
	 * to SVG image uploads.
	 *
	 * Dimension options may be
	 * specified to scale the uploaded image. Dimension option may be an
	 * integer or an associative array of options. If an integer, is used
	 * as the maximum dimension (width or height) in pixels. If given as an
	 * associative array, valid options are 'height', 'width', and 'upsize'
	 * for scaling. If only one of width or height are specified,
	 * the other dimension is scaled proportionately. If upsize is not given
	 * or is false and the original image is smaller (in both dimensions) as
	 * the scaling size options, no image resizing is done.
	 *
	 * Returns false if called for an SVG image upload.
	 *
	 * @param mixed $dim Max scaling dimension, or dimension options array
	 * @return resource Image resource
	 */
	public function image ($dim=false) {
		switch ($this->imagetype) {
		case 'gif':
			$img = imagecreatefromgif($this->tmp_name);
			break;
		case 'jpeg':
			$img = imagecreatefromjpeg($this->tmp_name);
			// Using '@' due to 'illegal IFD size' warning in exif_read_data
			$exif = @exif_read_data($this->tmp_name);
			if (isset($exif['Orientation'])) {
				switch ($exif['Orientation']) {
					case 2:
						imageflip($img, IMG_FLIP_HORIZONTAL);
						break;
					case 3:
						$img = imagerotate($img, 180, 0);
						break;
					case 5:
						$img = imagerotate($img, -90, 0);
						imageflip($img, IMG_FLIP_HORIZONTAL);
						break;
					case 6:
						$img = imagerotate($img, -90, 0);
						break;
					case 7:
						$img = imagerotate($img, 90, 0);
						imageflip($img, IMG_FLIP_HORIZONTAL);
						break;
					case 8:
						$img = imagerotate($img, 90, 0);
				}
			}
			break;
		case 'png':
			$img = imagecreatefrompng($this->tmp_name);
			break;
		default:
			return false;
		}

		// If no scaling option, return the image resource.
		if (!$dim)
			return $img;

		// Get width and height of uploaded image
		$width1 = imagesx($img);
		$height1 = imagesy($img);

		// Determine width and height for resizing
		if (is_array($dim)) {
			$width = isset($options['width']) ?
				(int)$options['width'] : false;
			$height = isset($options['height']) ?
				(int)$options['height'] : false;
			$upsize = isset($options['upsize']) ?
				(bool)$options['upsize'] : false;
		}
		else {
			if ($width1 > $height1) {
				$width = (int)$dim;
				$height = false;
			}
			else {
				$height = (int)$dim;
				$width = false;
			}
			$upsize = false;
		}

		// Determine width and height for scaled image
		if (!$height) // only width provided, determine height
			$height = (int)($height1 * $width / $width1);
		else if (!$width) // only height provided, determine width
			$width = (int)($width1 * $height / $height1);

		// If image smaller than resize dimensions and upsize option is false,
		//	return original image.
		if ($width1 <= $width && $height1 <= $height)
			return $img;

		// Create image resource for scaled image
		$img2 = imagecreatetruecolor($width, $height);
		if ($this->imagetype == 'png') {
			// Preserve transparency in png's
			imagealphablending($img2, false);
			imagesavealpha($img2, true);
		}
		imagecopyresampled($img2, $img, 0, 0, 0, 0,
			$width, $height, $width1, $height1);

		// Destroy original image resource and return scaled resource.
		imagedestroy($img);
		return $img2;
	}

	/**
	 * Get the image file contents.
	 *
	 * Gets the uploaded image file contents, optionally scaled
	 * according to the scaling dimensions. See FileImageUpload::image for
	 * dimension options. Scaling dimensions are ignored for SVG image
	 * uploads.
	 *
	 * @param mixed $dim Max scaling dimension, or dimension options array
	 * @return string Image file contents
	 */
	public function read ($dim=false) {
		// If not scaled or if an svg, just get uploaded file contents
		if (!$dim || $this->imagetype == 'svg')
			return parent::read();
		// Get scaled image resource and generate image contents
		ob_start();
		$img = $this->image($dim);
		switch ($this->imagetype) {
		case 'gif':
			imagegif($this->image($dim));
			break;
		case 'jpeg':
			//imagejpeg($this->image($dim, null, 90);
			imagejpeg($this->image($dim));
			break;
		default: // 'png' or 'unknown'
			imagepng($this->image($dim));
		}
		return ob_get_clean();
	}

	/**
	 * Save uploaded image.
	 *
	 * Saves the uploaded image to the given path, optionally scaled
	 * according to the scaling dimensions. See FileImageUpload::image for
	 * dimension options. Scaling dimensions are ignored for SVG image
	 * uploads.
	 *
	 * @param string $path Path for saving file
	 * @param mixed $dim Max scaling dimension, or dimension options array
	 * @return int Image creation status
	 */
	public function save ($path, $dim=false) {
		// If not scaled or if an svg, just copy the uploaded file
		if (!$dim || $this->imagetype == 'svg')
			$ret = copy($this->tmp_name, $path);
		else
			switch ($this->imagetype) {
			case 'gif':
				$ret = imagegif($this->image($dim), $path);
				break;
			case 'jpeg':
				//$ret = imagejpeg($this->image($dim), $path, 90);
				$ret = imagejpeg($this->image($dim), $path);
				break;
			default: // 'png' or 'unknown'
				$ret = imagepng($this->image($dim), $path);
			}

		// Return image file creation status
		return $ret;
	}

	/**
	 * Get HTML IMG tag source.
	 *
	 * Gets a string that may be used as the SRC attribute of an HTML IMG
	 * tag with the image data embedded directly in the tag.
	 * The image may be optionally scaled
	 * according to the scaling dimensions (but is ignored if the image type
	 * is SVG). See FileImageUpload::image for
	 * dimension options.
	 *
	 * @param mixed $dim Max scaling dimension, or dimension options array
	 * @return string Image source string
	 */
	public function src ($dim=false) {
		return "data:image/{$this->type};base64," . $this->base64($dim);
	}
}
