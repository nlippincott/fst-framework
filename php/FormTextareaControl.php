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
 * Textarea control.
 */
class FormTextareaControl extends FormControl {

	/** @ignore */
	protected $value;

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
		$this->attr('data-fst', 'form-control-textarea');
	}

	/**
	 * Get HTML code.
	 *
	 * @return string HTML code
	 */
	public function __toString () {
		return '<textarea' . Framework::attr($this->attr) . '>' .
			($this->value ? htmlspecialchars($this->value) : '') .
			'</textarea>';
	}

	/**
	 * Set number of columns for control.
	 * 
	 * @param int $cols Number of columns
	 * @return object This FormControl object
	 */
	public function cols ($cols) { $this->attr['cols'] = $cols; return $this; }

	/**
	 * Set initial value for control.
	 *
	 * @param string $val Initial value
	 * @return object This FormControl object
	 */
	public function init ($val) { $this->value = $val; return $this; }

	/**
	 * Set the control's placeholder text.
	 * 
	 * @param string $placeholder Placeholder text
	 * @return object This FormControl object
	 */
	public function placeholder ($placeholder)
		{ $this->attr['placeholder'] = $placeholder; return $this; }

	/**
	 * Set control as read-only.
	 * 
	 * @return object This FormControl object
	 */
	public function readonly ()
		{ $this->attr('readonly', 'readonly'); return $this; }

	/**
	 * Set number of rows for control.
	 * 
	 * @param int $rows Number of rows
	 * @return object This FormControl object
	 */
	public function rows ($rows) { $this->attr['rows'] = $rows; return $this; }
}
