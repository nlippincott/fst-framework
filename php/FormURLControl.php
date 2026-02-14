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
 * URL control.
 */
class FormURLControl extends FormTextControl {

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
	public function __construct ($form, $name, $label='URL') {
		parent::__construct($form, $name, $label);
		$this->attr('data-fst', 'form-control-url');
		$this->attr('type', 'url');
	}

	/**
	 * Cause input type as text instead of url.
	 *
	 * By default, the HTML input control generated is of type "url".
	 * Call this function to cause the control to be generated with
	 * type "text".
	 *
	 * @return FormURLControl This FormControl object
	 */
	public function text () { $this->attr('type', 'text'); return $this; }
}

