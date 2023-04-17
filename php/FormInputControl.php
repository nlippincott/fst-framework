<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history, ver 5.2.1
//	- Correction to HTML5 boolean attribute "autofocus"
//	- Correction to HTML5 boolean attribute "readonly"

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Abstract base class for all controls using an HTML input tag.
 *
 * All form control classes that are rendered as HTML input tags are derived
 * from this class which, in turn, is derived from FormControl.
 */
abstract class FormInputControl extends FormControl {

	/**
	 * @brief Get the HTML input tag for the control.
	 * @return string HTML input tag
	 */
	public function __toString ()
		{ return '<input' . Framework::attr($this->attr) . ' />'; }

	/**
	 * @brief Sets the autofocus attribute.
	 * @retval object This FormControl object
	 */
	public function autofocus ()
		{ $this->attr('autofocus', 'autofocus'); return $this; }

	/**
	 * @brief Sets the initial value for the control
	 * @param string $val Initial value
	 * @retval object This FormControl object
	 */
	public function init ($val) { $this->attr('value', $val); return $this; }

	/**
	 * @brief Set control as read-only
	 * @retval object This FormControl object
	 */
	public function readonly ()
		{ $this->attr('readonly', 'readonly'); return $this; }
}
