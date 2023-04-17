<?php

// FST Application Framework, Version 5.5
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
 * @brief Form note control.
 *
 * This is not an actual input control, but rather includes a text- or
 * HTML-based note into a form. This control must have a unique name just
 * as any other form control.
 */

class FormNoteControl extends FormControl {

	/// @cond
	protected $html = false;
	protected $name;
	protected $note;
	/// @endcond

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
		$this->attr('data-fst', 'form-control-note');
		$this->informational = true;
		$this->name = $name;
		unset($this->attr['name']);
	}

	/**
	 * @brief Get HTML code.
	 * @retval string HTML code
	 */
	public function __toString () {
		return '<span' . Framework::attr($this->attr) . '>' .
			($this->html ? $this->note : htmlspecialchars($this->note)) .
			'</span>';
	}

	/**
	 * @brief Sets the control text or HTML.
	 * @param string $val Text or HTML for the note
	 * @retval object This FormControl object
	 */
	public function init ($val) { $this->note = $val; return $this; }

	/**
	 * @brief Set control value format as HTML.
	 * @return This FormNoteControl object
	 *
	 * Call this method to indicate that the form data provided to
	 * FormNoteControl::init was HTML-formatted.
	 */
	public function html () { $this->html = true; return $this; }

	/**
	 * @brief Get control name.
	 * @retval string Control name
	 */
	public function name () { return $this->name; }

	/**
	 * @brief Set control as read-only.
	 * @retval control This FormControl object
	 *
	 * This method overrides the base class method, and simply returns
	 * itself. The base class behavior of this method, and its intended
	 * function, is not appropriate to this control.
	 */
	public function readonly () { return $this; }
}
