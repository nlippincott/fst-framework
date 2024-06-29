<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-24, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Form file upload control.
 */
class FormFileControl extends FormInputControl {

	/** @ignore */
	protected $data = null;
	/** @ignore */
	protected $ext = null;
	/** @ignore */
	protected $extmsg;
	/** @ignore */
	protected $files = array();
	/** @ignore */
	protected $multiple = false;

	/**
	 * Control constructor.
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 *
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $label Control label (or other value, see description)
	 */
	public function __construct ($form, $name, $label='') {
		parent::__construct($form, $name, $label);
		$this->attr('data-fst', 'form-control-file');
		$this->attr('type', 'file');
		$form->attr('enctype', 'multipart/form-data');
		$this->informational = true;

		// Convert $_FILES[$name] to $this->files, if exists. Converted
		//	array has one entry for each successful file upload or file
		//	upload error.
		if (isset($_FILES[$name], $_FILES[$name]['name'])) {
			if (is_array($_FILES[$name]['name'])) {
				for ($i = 0; $i < count($_FILES[$name]['name']); $i++)
					if ($_FILES[$name]['error'][$i] != UPLOAD_ERR_NO_FILE)
						$this->files[] = array(
							'name'=>$_FILES[$name]['name'][$i],
							'type'=>$_FILES[$name]['type'][$i],
							'size'=>$_FILES[$name]['size'][$i],
							'tmp_name'=>$_FILES[$name]['tmp_name'][$i],
							'error'=>$_FILES[$name]['error'][$i],
						);
			}
			else if ($_FILES[$name]['error'] != UPLOAD_ERR_NO_FILE)
				$this->files[] = $_FILES[$name];
		}
	}

	/**
	 * Get the HTML input tag for the control.
	 *
	 * This varies from the parent class in that if this control is designated
	 * as a multiple file input, the name attribute has "[]" appended to it.
	 *
	 * @return string HTML code for input tag
	 */
	public function __toString () {
		if (!$this->multiple)
			return parent::__toString();
		$attr = $this->attr;
		$attr['name'] .= '[]';
		return '<input' . Framework::attr($attr) . ' />';
	}

	/**
	 * Get file(s) submitted through this control.
	 *
	 * This method returns a FormFileUpload object for the uploaded file.
	 * If the multiple option is used, an array of FormFileUpload objects is
	 * returned. If no file is uploaded, this function returns false if the
	 * multiple option is not used, or an empty array if the multiple option
	 * is used.
	 *
	 * This $type parameter is intended for internal use only, and is used
	 * to change the data type of objects returned by this method. Class
	 * FormImageControl (which is derived from this class) returns objects
	 * of type FormImageUpload (which is derived from FormFileUpload).
	 * 
	 * @param string $type Class name for returned object(s)
	 * @retval mixed See description
	 */
	public function data ($type='\FST\FormFileUpload') {

		if (isset($this->data))
			return $this->data;

		if ($this->multiple) {
			// For multiple file control, return array of upload objects.
			$this->data = array();
			foreach ($this->files as $f)
				$this->data[] = new $type($f);
		}
		else
			// For single file control, return upload object or false.
			$this->data = count($this->files) ?
				new $type($this->files[0]) : false;

		return $this->data;
	}

	/**
	 * Get error message for this control.
	 *
	 * @return mixed Error message string, or false if no error
	 */
	public function error () {

		// If no file uploaded, error only if file upload is required.
		if ($this->is_required() && !count($this->files))
			return 'File required';

		// Check for upload error.
		foreach ($this->files as $f)
			if ($f['error'] != UPLOAD_ERR_OK)
				switch ($f['error']) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					return 'Max file size exceeded';
				case UPLOAD_ERR_PARTIAL:
					return 'File upload is incomplete';
				case UPLOAD_ERR_NO_TMP_DIR:
				case UPLOAD_ERR_CANT_WRITE:
				case UPLOAD_ERR_EXTENSION:
					return 'Server-side upload error';
				default:
					return "File upload error: {$f['error']}";
				}

		// Check for valid extension.
		if (isset($this->ext))
			foreach ($this->files as $f)
				if (array_search(
						strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)),
						$this->ext) === false)
					return $this->extmsg;

		return false;
	}

	/**
	 * Allow multipe file uploads.
	 *
	 * Indicates that multiple files may be upload via this control.
	 *
	 * @return object This FormFile object
	 */
	public function multiple () {
		$this->attr('multiple', 'multiple');
		$this->multiple = true;
		return $this;
	}

	/**
	 * Indicate allowable upload file types.
	 *
	 * Sets the allowable types that are permissable for this file upload.
	 * The file type is (blindly) determined by the file name extension. By
	 * default, there are no restrictions on the type/extension. The
	 * extensions provided must not include the dot.
	 *
	 * @param string $ext Comma-separated list of allowable file extensions
	 * @param string $extmsg Error message if upload extension does not match
	 * @return object This FormControl object
	 */
	public function type ($ext, $extmsg=false) {
		$this->attr('data-fst-type', $ext);
		$this->ext = explode(',', strtolower($ext));
		$this->extmsg = $extmsg;
		$this->attr('accept', implode(',',
			array_map(function ($t) { return '.' . $t; }, $this->ext)));
		return $this;
	}
}
