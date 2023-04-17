<?php

// FST Application Framework, Version 5.5
// Copyright (c) 2004-22, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Phone number input control.
 */
class FormPhoneControl extends FormTextControl {

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
		$this->attr('data-fst', 'form-control-phone');
		$this->attr('type', 'tel');
	}

	/**
	 * @brief Get submitted form data for this control.
	 * @retval mixed Phone number, digits only
	 *
	 * Returns the phone number that was submitted. Only the digits of the
	 * phone number is returned; any formatting is removed. If no data was
	 * submitted, return null unless FormControl::notnull was called in which
	 * case an empty string is returned.
	 */
	public function data () {
		$data = parent::data();
		return $data ? preg_replace('/\D/', '', $data) : $data;
	}

	/**
	 * @brief Sets the initial value for the control
	 * @param string $val Initial phone number value
	 * @retval object This FormControl object
	 *
	 * Sets the initial value to be displayed in the control. If the value
	 * given is 10 digits, the value is re-formatted to include parenthesis
	 * around the area code and a hyphen following the exchange. If the given
	 * value is not 10 digits, the value is used as is.
	 */
	public function init ($val) {
		if (preg_match('/^\d{10}$/', $val))
			$val = '(' . substr($val, 0, 3) . ')' . substr($val, 3, 3) .
				'-' . substr($val, 6);
		parent::init($val);
	}
}
