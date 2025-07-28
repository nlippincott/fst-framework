<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-25, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/*
 * Number range control (experimental).
 */
class FormRangeControl extends FormInputControl {

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
		$this->attr('data-fst', 'form-control-range');
		$this->attr('type', 'range');
		$this->attr('min', 0);
		$this->attr('max', 10);
		$this->attr('step', 1);
	}

	/**
	 * Get submitted form data for this control.
	 *
	 * @return int Form data value
	 */
	public function data ()
		{ return parent::data() === null ? null : (int)parent::data(); }

	/**
	 * Get error message associated with this control.
	 * 
	 * @return string Error message, or empty string if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg) {
			$val = $this->data();
			if (!preg_match('/^\d+$/', $val))
				$msg = 'Number required';
			else if ($val < $this->attr['min'])
				$msg = 'Number out of range';
			else if ($val > $this->attr['max'])
				$msg = 'Number out of range';
		}
		return $msg;
	}

	/**
	 * Set maximum value for range.
	 * 
	 * @param int $max Maximum value
	 * @return FormRangeControl This FormControl object
	 */
	public function max ($max) { $this->attr('max', (int)$max); return $this; }

	/**
	 * Set minimum value for range.
	 * 
	 * @param int $min Minimum value
	 * @return FormRangeControl This FormControl object
	 */
	public function min ($min) { $this->attr('min', (int)$min); return $this; }

	/**
	 * Set step attribute.
	 * 
	 * @param int $step Step value
	 * @return FormRangeControl This FormControl object
	 */
	public function step ($step)
		{ $this->attr('step', (int)$step); return $this; }
}
