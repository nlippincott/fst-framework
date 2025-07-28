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
 * Number input control.
 */
class FormNumberControl extends FormTextControl {

	/** @ignore */
	protected $decimal = false;
	/** @ignore */
	protected $max = null;
	/** @ignore */
	protected $min = null;
	/** @ignore */
	protected $negative = false;

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
		$this->attr('data-fst', 'form-control-number');
		$this->attr('type', 'number');
	}

	/**
	 * Allow decmial number input.
	 *
	 * @return FormNumberControl This FormControl object
	 */
	public function decimal ()
		{ $this->decimal = true; $this->attr('step', 'any'); return $this; }

	/**
	 * Get error message associated with this control.
	 * 
	 * @return string Error message, or empty string if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg) {
			$val = $this->data();
			if ($val) {
				// TODO: allow .1 or -.1
				$regex = $this->decimal ? '/^-?\d+(\.\d*)?$/' : '/^-?\d+$/';
				if (!preg_match($regex, $val))
					$msg = 'Number required';
				else if ($this->min !== null && $val < $this->min)
					$msg = 'Number out of range';
				else if ($this->max !== null && $val > $this->max)
					$msg = 'Number out of range';
				else if (!$this->negative && $val < 0)
					$msg = 'Negative not allowed';
			}
		}
		return $msg;
	}

	/**
	 * Set maximum input value.
	 *
	 * @param mixed $max Maximum value
	 * @return FormNumberControl This FormControl object
	 */
	public function max ($max)
		{ $this->max = $max; $this->attr('max', $max); return $this; }

	/**
	 * Set minimum input value.
	 * 
	 * @param mixed $min Minimum value
	 * @return FormNumberControl This FormControl object
	 */
	public function min ($min)
		{ $this->min = $min; $this->attr('min', $min); return $this; }

	/**
	 * Allow negative number input.
	 *
	 * @return FormNumberControl This FormControl object
	 */
	public function negative () { $this->negative = true; return $this; }

	/**
	 * Set input type as text instead of number.
	 *
	 * This control generates an HTML input tag with type "number". Calling
	 * this method causes the type to be generated as "text".
	 *
	 * @return FormNumberControl This FormControl object
	 */
	public function text () {
		$this->attr('type', 'text');
		$this->attr('inputmode', 'numeric');
		$this->attr('x-inputmode', 'numeric');
		return $this;
	}
}
