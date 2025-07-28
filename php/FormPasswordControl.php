<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-25, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Password input control.
 */
class FormPasswordControl extends FormTextControl {

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
	public function __construct ($form, $name, $label='Password') {
		parent::__construct($form, $name, $label);
		$this->attr('data-fst', 'form-control-password');
		$this->attr('type', 'password');
		$this->required();
	}
}
