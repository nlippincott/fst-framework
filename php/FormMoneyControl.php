<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history:
//	v5.3 - Uses text version of parent control, to use number inputmode
//	v5.3 - Removed step attribute on input tag (since no longer number input)
//	v5.3 - Fixed initialization by null value

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Money input control.
 *
 * This control is derived from FormNumberControl, and includes additional
 * validation to ensure values are appropriate money values.
 */
class FormMoneyControl extends FormNumberControl {

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
		$this->attr('data-fst', 'form-control-money');
		$this->decimal();
		$this->text();
	}

	/**
	 * @brief Get submitted form data for this control
	 * @retval string Money value (in decimal form)
	 *
	 * Value may be entered into this control with an optional leading
	 * dollar sign, and optionally with comma separators. This function
	 * strips those characters from the input data so long as the comma
	 * separators are in the correct location.
	 */
	public function data () {
		$data = parent::data();
		if ($data && preg_match('/^\$?\d{1,3}(,\d{3})*(.\d\d)?$/', $data))
			return str_replace(array('$', ','), '', $data);
		return $data;
	}

	/**
	 * @brief Get error message associated with this control
	 * @retval string Error message, or empty string if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg) {
			$val = $this->data();
			if ($val) {
				if (!preg_match('/^-?(\d+\.?|\d*\.\d\d)$/', $this->data()))
					$msg = 'Money value required';
			}
		}
		return str_replace('Number', 'Money value', $msg);
	}

	/**
	 * @brief Sets initial value for the control
	 * @param string $val Initial value
	 * @retval object This FormControl object
	 *
	 * A number is expected for initialization of this control. That number
	 * is formatted with two decimal places and comma separators.
	 */
	public function init ($val) {
		return parent::init($val === false || $val === null || $val === "" ?
			"" : number_format((float)$val, 2));
	}
}
