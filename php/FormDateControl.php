<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Date input control.
 */
class FormDateControl extends FormInputControl {

	/** @ignore */
	protected $data = false;

	/** @ignore */
	protected $max = null;
	/** @ignore */
	protected $min = null;

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
		$this->attr('data-fst', 'form-control-date');
		$this->attr('type', 'date');
	}

	/**
	 * Get submitted form data.
	 *
	 * Returns the date in YYYY-MM-DD format. If no input is provided,
	 * returns null or an empty string, depending on setting of Form::notnull.
	 *
	 * @return string Date in YYYY-MM-DD format (or empty string if no input)
	 */
	public function data () {
		if ($this->data === false) {
			$this->data = parent::data();
			if ($this->data) {
				try {
					$dtm = new \DateTime($this->data);
					$this->data = $dtm->format('Y-m-d');
				}
				catch (\Exception $e) { }
			}
		}
		return $this->data;
	}

	/**
	 * Get error message.
	 *
	 * @return string|false Error message as a string, or false if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg && $this->data()) {
			if (!preg_match('/^\d{4}-\d\d-\d\d$/', $this->data))
				$msg = 'Invalid date';
			else if (($this->min && $this->data < $this->min) ||
					($this->max && $this->data > $this->max))
				$msg = 'Date is out of range';
		}
		return $msg;
	}

	/**
	 * Set initial date value.
	 *
	 * @param mixed $val DateTime object, or date string
	 * @return FormDateControl This FormControl object
	 */
	public function init ($val) {
		if ($val) {
			if (!is_a($val, 'DateTime')) {
				try {
					$val = date_create("$val");
				}
				catch (\Exception $e) {
					throw new UsageException(
						'Invalid init value for date control.');
				}
			}
			$this->attr('value', $val->format('Y-m-d'));
		}
		return $this;
	}

	/**
	 * Set maximum date input value.
	 *
	 * @param mixed $val DateTime object, or date string
	 * @return FormDateControl This FormControl object
	 */
	public function max ($val) {
		if (!is_a($val, 'DateTime'))
			$val = new \DateTime("$val");
		$this->max = $val->format('Y-m-d');
		$this->attr('max', $this->max);
		return $this;
	}

	/**
	 * Set minimum date input value.
	 *
	 * @param mixed $val DateTime object, or date string
	 * @return FormDateControl This FormControl object
	 */
	public function min ($val) {
		if (!is_a($val, 'DateTime'))
			$val = new \DateTime("$val");
		$this->min = $val->format('Y-m-d');
		$this->attr('min', $this->min);
		return $this;
	}

	/**
	 * Cause input type as text instead of search.
	 *
	 * By default, the HTML input control generated is of type "date".
	 * Call this function to cause the control to be generated with
	 * type "text".
	 *
	 * @return FormDateControl This FormControl object
	 */
	public function text () { $this->attr('type', 'text'); return $this; }
}
