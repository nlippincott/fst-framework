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
 * Hidden input control.
 */
class FormHiddenControl extends FormInputControl {

	/**
	 * Control constructor.
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 *
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $init Initialization value for control
	 */
	public function __construct ($form, $name, $init=null) {
		parent::__construct($form, $name);
		$this->attr('data-fst', 'form-control-hidden');
		$this->attr('type', 'hidden');
		if ($init !== null)
			$this->init($init);
	}
}
