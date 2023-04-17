<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history, version 5.2.1
//	- Correction to HTML5 boolean attribute "checked"
//	- Correction to HTML5 boolean attribute "disabled"
// Revision history, version 5.3
//	- Generate HTML with INPUT element before LABEL element, not embedded in it
//	- Generate HTML LABEL element, even if no text for control (for styling)

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Option (checkbox) control.
 */
class FormOptionControl extends FormInputControl {

	/// @cond
	protected $text; // Option text
	/// @endcond

	/**
	 * @brief Control constructor.
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $text Option text
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 */
	public function __construct ($form, $name, $text='') {
		parent::__construct($form, $name);
		$this->attr('data-fst', 'form-control-option');
		$this->attr('type', 'checkbox');
		$this->attr('value', 1);
		$this->text($text);
	}

	/**
	 * @brief Get HTML code.
	 * @retval string HTML code
	 */
	public function __toString () {
		return '<input' . Framework::attr($this->attr) . ' />' .
			'<label for="' . $this->attr['id'] . '">' .
			($this->text ? htmlspecialchars($this->text) : '') .
			'</label>';
	}

	/**
	 * @brief Get data entered in this control.
	 * @retval int 1 or 0, indicating yes/no or checked/unchecked
	 */
	public function data () { return parent::data() ? 1 : 0; }

	/**
	 * @brief Set initial control value.
	 * @param bool $val Control-is-checked flag
	 */
	public function init ($val)
		{ $this->attr('checked', $val ? 'checked' : null); return $this; }

	/**
	 * @brief Set control as read-only.
	 * @retval object This FormControl object
	 */
	public function readonly ()
		{ $this->attr('disabled', 'disabled'); return $this; }

	/**
	 * @brief Set control option text.
	 * @param string $text Option text
	 * @retval object This FormControl object
	 */
	public function text ($text) { $this->text = $text; return $this; }
}
