<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history, version 5.2.1
//	- Correction to HTML5 boolean attribute "readonly"
// Revision history, version 5.2.2
//	- Added placeholder function
// Revision history, version 5.5
//	- __toString, fixed deprecated parameter to htmlspecialchars

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Textarea control
 */
class FormTextareaControl extends FormControl {

	/// @cond
	protected $value;
	/// @endcond

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
		$this->attr('data-fst', 'form-control-textarea');
	}

	/**
	 * @brief Get HTML code.
	 * @retval string HTML code
	 */
	public function __toString () {
		return '<textarea' . Framework::attr($this->attr) . '>' .
			($this->value ? htmlspecialchars($this->value) : '') .
			'</textarea>';
	}

	/**
	 * @brief Set number of columns for control.
	 * @param int $cols Number of columns
	 * @retval object This FormControl object
	 */
	public function cols ($cols) { $this->attr['cols'] = $cols; return $this; }

	/**
	 * @brief Set initial value for control.
	 * @param string $val Initial value
	 * @retval object This FormControl object
	 */
	public function init ($val) { $this->value = $val; return $this; }

	/**
	 * @brief Set the control's placeholder text.
	 * @param string $placeholder Placeholder text
	 * @retval object This FormControl object
	 */
	public function placeholder ($placeholder)
		{ $this->attr['placeholder'] = $placeholder; return $this; }

	/**
	 * Set control as read-only.
	 * @retval object This FormControl object
	 */
	public function readonly ()
		{ $this->attr('readonly', 'readonly'); return $this; }

	/**
	 * @brief Set number of rows for control.
	 * @param int $rows Number of rows
	 * @retval object This FormControl object
	 */
	public function rows ($rows) { $this->attr['rows'] = $rows; return $this; }
}
