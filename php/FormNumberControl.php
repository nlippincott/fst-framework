<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history:
//	v5.3 - Modified text method to add inputmode attribute (set to numeric)

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Number input control.
 */
class FormNumberControl extends FormTextControl {

	/// @cond
	protected $decimal = false;
	protected $max = null;
	protected $min = null;
	protected $negative = false;
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
		$this->attr('data-fst', 'form-control-number');
		$this->attr('type', 'number');
		//$this->size(8);
	}

	/**
	 * @brief Allow decmial number input.
	 * @retval object This FormControl object
	 */
	public function decimal ()
		{ $this->decimal = true; $this->attr('step', 'any'); return $this; }

	/**
	 * @brief Get error message associated with this control
	 * @retval string Error message, or empty string if no error
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
	 * @brief Set maximum input value.
	 * @param mixed $max Maximum value
	 * @retval object This FormControl object
	 */
	public function max ($max)
		{ $this->max = $max; $this->attr('max', $max); return $this; }

	/**
	 * @brief Set minimum input value.
	 * @param mixed $min Minimum value
	 * @retval object This FormControl object
	 */
	public function min ($min)
		{ $this->min = $min; $this->attr('min', $min); return $this; }

	/**
	 * Allow negative number input.
	 * @retval object This FormControl object
	 */
	public function negative () { $this->negative = true; return $this; }

	/**
	 * @brief Set input type as text instead of number.
	 * @retval object This FormControl object
	 *
	 * This control generates an HTML input tag with type "number". Calling
	 * this method causes the type to be generated as "text".
	 */
	public function text () {
		$this->attr('type', 'text');
		$this->attr('inputmode', 'numeric');
		$this->attr('x-inputmode', 'numeric');
		return $this;
	}
}
