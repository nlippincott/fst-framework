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
 * Submit control.
 */
class FormSubmitControl extends FormInputControl {

	/** @ignore */
	protected $cancel=false;

	/**
	 * Control constructor.
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 *
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $init Text to appear on submit button (default "Submit")
	 */
	public function __construct ($form, $name, $init='Submit') {
		parent::__construct($form, $name, '');
		$this->attr('data-fst', 'form-control-submit');
		$this->attr('type', 'submit');
		$this->informational = true;
		$this->init($init);
	}

	/**
	 * Get HTML code.
	 * 
	 * @return string HTML code
	 */
	public function __toString () {

		ob_start();

		print parent::__toString();
		if ($this->cancel)
			print '<button id="' . $this->id() . '-1" ' . 'data-fst="form-cancel" type="button">' . htmlspecialchars($this->cancel) . '</button>';

		return ob_get_clean();
	}

	/**
	 * Include cancel button with control.
	 *
	 * Adds a button element for cancel functionality. It is the responsibility
	 * of the presentation layer to implement the cancel function.
	 *
	 * @param string $text Text to appear on cancel button
	 * @return FormSubmitControl This FormControl object
	 */
	public function cancel ($text='Cancel')
		{ $this->cancel = $text; return $this; }

	/**
	 * Get error message for this control.
	 *
	 * There are no errors associated with this control.
	 *
	 * @return bool False, to indicate no error
	 */
	public function error () { return false; }

	/**
	 * Set control to ok/cancel format
	 *
	 * Sets up the control to include a cancel button. Also labels the submit
	 * button as "OK" (or value supplied) and the cancel button as "Cancel"
	 * (or value supplied).
	 *
	 * @param string $ok Text for OK button
	 * @param string $cancel Text for Cancel button
	 * @return FormSubmitControl This FormControl object
	 */
	public function okcancel ($ok='OK', $cancel='Cancel')
		{ $this->init($ok); $this->cancel($cancel); return $this; }
}
