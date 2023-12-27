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
 * File upload handler.
 *
 * An object of this type is returned as data from the FormFileControl
 * object. The FormFileControl object will return objects of this type
 * only upon a successful file upload.
 *
 * Properties available for reading are:
 * - name - Original name of the file on the client machine
 * - extension - Extension of original file on client machine (lowercased)
 * - type - Mime type of the file
 * - size - Size of the file
 * - tmp_name - Temporary file name on server
 */
class FormFileUpload {

	/** @ignore */
	protected $fileinfo;

	/**
	 * Constructor.
	 *
	 * Initializes the object based on the PHP fileinfo array that is
	 * created as a result of a file upload.
	 */
	public function __construct ($fileinfo) {
		$this->fileinfo = $fileinfo;
		$tmp = pathinfo($this->fileinfo['name']);
		$this->fileinfo['filename'] = $tmp['filename']; // Name w/o extension
		$this->fileinfo['extension'] = // Extension, lowercased
			isset($tmp['extension']) ? strtolower($tmp['extension']) : '';
	}

	/**
	 * Destructor.
	 *
	 * Removes the uploaded file from temporary storage, if not already done
	 * so by the save method.
	 */
	public function __destruct () {
		if (file_exists($this->fileinfo['tmp_name']))
			unlink($this->fileinfo['tmp_name']);
	}

	/**
	 * Gets file upload field information.
	 *
	 * Properties that may be retrieved using this method are:
	 *	- name, original name of the file on client machine
	 *	- filename, original file name without extension
	 *	- extension, original file name extension (no dot)
	 *	- type, mime type of the file, if provided by browser
	 *	- size, size of uploaded file in bytes
	 *	- tmp_name, temporary filename as stored on the server
	 *	- imagetype, image type (only for image uploads via FormImageUpload)
	 *
	 * @param string $name Information field name
	 * @return mixed Information field value
	 */
	public function __get ($name) {
		if (!isset($this->fileinfo[$name]))
			throw new UsageException(
				get_class($this) . ": Invalid fileinfo property, $name");
		return $this->fileinfo[$name];
	}

	/**
	 * Get upload file name.
	 * 
	 * @return string Upload file name.
	 */
	public function __toString () { return $this->fileinfo['name']; }

	/**
	 * Get base64-encoded file contents.
	 * 
	 * @return string File contents, base64-encoded
	 */
	public function base64 () { return base64_encode($this->read()); }

	/**
	 * Read uploaded file contents into a string.
	 *
	 * @return string File contents
	 */
	public function read () {

		$fp = fopen($this->tmp_name, 'rb');
		$content = fread($fp, $this->size);
		fclose($fp);

		return $content;
	}

	/**
	 * Save uploaded file.
	 *
	 * Saves the uploaded file to the given path. If only a directory is given
	 * for $path, the original file name from the client machine is used as
	 * the name.
	 *
	 * @param string $path Path for saving file
	 * @return int File save success status
	 */
	public function save ($path) {
		if (is_dir($path))
			$path .= "/{$this->fileinfo['name']}";
		return move_uploaded_file($this->fileinfo['tmp_name'], $path);
	}
}
