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
 * Option (checkbox) control.
 */
class FormOptionControl extends FormInputControl {

	/** @ignore */
	protected $text; // Option text

	/**
	 * Control constructor.
	 *
	 * Calls the base class constructor and
	 * performs additional required initialization.
	 *
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $text Option text
	 */
	public function __construct ($form, $name, $text='') {
		parent::__construct($form, $name);
		$this->attr('data-fst', 'form-control-option');
		$this->attr('type', 'checkbox');
		$this->attr('value', 1);
		$this->text($text);
	}

	/**
	 * Get HTML code.
	 *
	 * @return string HTML code
	 */
	public function __toString () {
		return '<input' . Framework::attr($this->attr) . ' />' .
			'<label for="' . $this->attr['id'] . '">' .
			($this->text ? htmlspecialchars($this->text) : '') .
			'</label>';
	}

	/**
	 * Get data entered in this control.
	 * 
	 * @return int 1 or 0, indicating yes/no or checked/unchecked
	 */
	public function data () { return parent::data() ? 1 : 0; }

	/**
	 * Set initial control value.
	 *
	 * @param bool $val Control-is-checked flag
	 */
	public function init ($val)
		{ $this->attr('checked', $val ? 'checked' : null); return $this; }

	/**
	 * Set control as read-only.
	 *
	 * @return object This FormControl object
	 */
	public function readonly ()
		{ $this->attr('disabled', 'disabled'); return $this; }

	/**
	 * Set control option text.
	 * 
	 * @param string $text Option text
	 * @return object This FormControl object
	 */
	public function text ($text) { $this->text = $text; return $this; }
}
