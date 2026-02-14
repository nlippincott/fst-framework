<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Abstract base class for all controls using an HTML input tag.
 *
 * All form control classes that are rendered as HTML input tags are derived
 * from this class which, in turn, is derived from FormControl.
 */
abstract class FormInputControl extends FormControl {

	/**
	 * Get the HTML input tag for the control.
	 *
	 * @return string HTML input tag
	 */
	public function __toString ()
		{ return '<input' . Framework::attr($this->attr) . ' />'; }

	/**
	 * Sets the autofocus attribute.
	 * 
	 * @return FormInputControl This FormControl object
	 */
	public function autofocus ()
		{ $this->attr('autofocus', 'autofocus'); return $this; }

	/**
	 * Sets the initial value for the control.
	 * 
	 * @param string $val Initial value
	 * @return FormInputControl This FormControl object
	 */
	public function init ($val) { $this->attr('value', $val); return $this; }

	/**
	 * Set control as read-only.
	 * 
	 * @return FormInputControl This FormControl object
	 */
	public function readonly ()
		{ $this->attr('readonly', 'readonly'); return $this; }
}
