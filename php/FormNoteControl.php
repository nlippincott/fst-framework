<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-24, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Form note control.
 *
 * This is not an actual input control, but rather includes a text- or
 * HTML-based note into a form. This control must have a unique name just
 * as any other form control.
 */

class FormNoteControl extends FormControl {

	/** @ignore */
	protected $html = false;
	/** @ignore */
	protected $name;
	/** @ignore */
	protected $note;

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
	public function __construct ($form, $name, $label='') {
		parent::__construct($form, $name, $label);
		$this->attr('data-fst', 'form-control-note');
		$this->informational = true;
		$this->name = $name;
		unset($this->attr['name']);
	}

	/**
	 * Get HTML code.
	 *
	 * @return string HTML code
	 */
	public function __toString () {
		$note = $this->note === null ? '' : $this->note;
		return '<span' . Framework::attr($this->attr) . '>' .
			($this->html ? $note : htmlspecialchars($note)) .
			'</span>';
	}

	/**
	 * Sets the control text or HTML.
	 * 
	 * @param string $val Text or HTML for the note
	 * @return object This FormControl object
	 */
	public function init ($val) { $this->note = $val; return $this; }

	/**
	 * Set control value format as HTML.
	 *
	 * Call this method to indicate that the form data provided to
	 * FormNoteControl::init was HTML-formatted.
	 *
	 * @return object This FormNoteControl object
	 */
	public function html () { $this->html = true; return $this; }

	/**
	 * Get control name.
	 * 
	 * @retval string Control name
	 */
	public function name () { return $this->name; }

	/**
	 * Set control as read-only.
	 *
	 * This method overrides the base class method, and simply returns
	 * itself. The base class behavior of this method, and its intended
	 * function, is not appropriate to this control.
	 *
	 * @return control This FormControl object
	 */
	public function readonly () { return $this; }
}
