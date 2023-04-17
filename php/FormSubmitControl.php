<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Submit control
 */
class FormSubmitControl extends FormInputControl {

	/// @cond
	protected $cancel=false;
	/// @endcond

	/**
	 * @brief Control constructor.
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $init Text to appear on submit button (default "Submit")
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 */
	public function __construct ($form, $name, $init='Submit') {
		parent::__construct($form, $name, '');
		$this->attr('data-fst', 'form-control-submit');
		$this->attr('type', 'submit');
		$this->informational = true;
		$this->init($init);
	}

	/**
	 * @brief Get HTML code.
	 * @retval string HTML code
	 */
	public function __toString () {

		ob_start();

		print parent::__toString();
		if ($this->cancel)
			print '<button id="' . $this->id() . '-1" ' .
				'data-fst="form-cancel" type="button">' .
				htmlspecialchars($this->cancel) . '</button>';

		return ob_get_clean();
	}

	/**
	 * @brief Include cancel button with control.
	 * @param string $text Text to appear on cancel button
	 * @retval object This FormControl object
	 *
	 * Adds a button element for cancel functionality. It is the responsibility
	 * of the presentation layer to implement the cancel function.
	 */
	public function cancel ($text='Cancel')
		{ $this->cancel = $text; return $this; }

	/**
	 * @brief Get error message for this control.
	 * @retval bool False, to indicate no error
	 *
	 * There are no errors associated with this control.
	 */
	public function error () { return false; }

	/**
	 * @brief Set control to ok/cancel format
	 * @param string $ok Text for OK button
	 * @param string $cancel Text for Cancel button
	 * @retval object This FormControl object
	 *
	 * Sets up the control to include a cancel button. Also labels the submit
	 * button as "OK" (or value supplied) and the cancel button as "Cancel"
	 * (or value supplied).
	 */
	public function okcancel ($ok='OK', $cancel='Cancel')
		{ $this->init($ok); $this->cancel($cancel); return $this; }
}
