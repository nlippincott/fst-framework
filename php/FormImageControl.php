<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history, ver 5.1:
//	- Complete re-write of error detection.
// Revision history, ver 5.3:
//	- Corrected default value for parameter on save method.
//	- Allow svg extension on upload

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Form image file upload control.
 *
 * An specialization of FormFileControl that handles image uploads. File types
 * are automatically restricted to jpg, jpeg, gif, png, and svg (but can call
 * FormFileControl::type to further restrict). Includes a method for saving
 * the uploaded image, and for specifying maximum dimensions when saving.
 */
class FormImageControl extends FormFileControl {

	/**
	 * @brief Control constructor.
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $label Control label (or other value, see description)
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 */
	public function __construct ($form, $name, $label='') {
		parent::__construct($form, $name, $label);
		$this->attr('accept', 'image/*');
		$this->attr('data-fst', 'form-control-file-image');
		$this->type('jpg,jpeg,gif,png,svg',
			'Image file (jpeg, png, svg, gif) required');
	}

	/**
	 * @brief Get file(s) submitted through this control
	 * @param string $type Class name for returned object(s)
	 * @retval mixed FormImageUpload object or array of FormImageUpload objects
	 *
	 * This function returns a FormImageUpload object for the uploaded file.
	 * If the multiple option is used, an array of FormImageUpload objects is
	 * returned. If no file is uploaded, this function returns false if the
	 * multiple option is not used, or an empty array if the multiple option
	 * is used.
	 *
	 * The $type parameter is passed to FormFileControl::data to indicate
	 * the type of objects returned by this method, which is set to
	 * FormImageUpload. This parameter is intended for internal use only.
	 */
	public function data ($type='\FST\FormImageUpload')
		{ return parent::data($type); }

	/**
	 * @brief Get error message associated with this control.
	 * @retval string Error message, or false if no error
	 *
	 * This function extends the error handling provided by FormFileControl.
	 * It ensures that, if a file was uploaded, the files does actually
	 * contain an image.
	 */
	public function error () {
		if ($err = parent::error())
			return $err;

		// Check that each file uploaded is an image file.
		foreach ($this->files as $f)
//			switch (exif_imagetype($f['tmp_name'])) {
//			case IMAGETYPE_GIF:
//			case IMAGETYPE_JPEG:
//			case IMAGETYPE_PNG:
//				break;
//			default:
//				return 'Image file is required';
//			}
			switch ($f['type']) {
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/svg+xml':
				break;
			default:
				return 'Image file (jpeg, png, svg, gif) required';
			}

		return false;
	}
}
