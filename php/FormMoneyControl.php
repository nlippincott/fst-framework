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
 * Money input control.
 *
 * This control is derived from FormNumberControl, and includes additional
 * validation to ensure values are appropriate money values.
 */
class FormMoneyControl extends FormNumberControl {

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
		$this->attr('data-fst', 'form-control-money');
		$this->decimal();
		$this->text();
	}

	/**
	 * Get submitted form data for this control.
	 *
	 * Value may be entered into this control with an optional leading
	 * dollar sign, and optionally with comma separators. This function
	 * strips those characters from the input data so long as the comma
	 * separators are in the correct location.
	 *
	 * @return string Money value (in decimal form)
	 */
	public function data () {
		$data = parent::data();
		if ($data && preg_match('/^\$?\d{1,3}(,\d{3})*(.\d\d)?$/', $data))
			return str_replace(array('$', ','), '', $data);
		return $data;
	}

	/**
	 * Get error message associated with this control
	 *
	 * @return string Error message, or empty string if no error
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
	 * Sets initial value for the control.
	 *
	 * A number is expected for initialization of this control. That number
	 * is formatted with two decimal places and comma separators.
	 *
	 * @param string $val Initial value
	 * @return object This FormControl object
	 */
	public function init ($val) {
		return parent::init($val === false || $val === null || $val === "" ?
			"" : number_format((float)$val, 2));
	}
}
