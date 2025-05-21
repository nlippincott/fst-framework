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
 * Time input control.
 */
class FormTimeControl extends FormInputControl {

	/** @ignore */
	protected $data = false;

	/** @ignore */
	protected $max = null;
	/** @ignore */
	protected $min = null;
	/** @ignore */
	protected $step = null; // Step value in minutes

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
		$this->attr('data-fst', 'form-control-time');
		$this->attr('type', 'time');
	}

	/**
	 * Get submitted form data.
	 *
	 * @return string Time in HH:MM format, or empty string if no input
	 */
	public function data () {
		if ($this->data === false) {
			$this->data = parent::data();
			if ($this->data) {
				try {
					$dtm = new \DateTime($this->data);
					$this->data = $dtm->format('H:i');
				}
				catch (\Exception $e) { }
			}
		}
		return $this->data;
	}

	/**
	 * Get error message.
	 * 
	 * @return string Error message, or empty string if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg && $this->data()) {
			if (!preg_match('/^([0-1]\d|2[0-3]):[0-5]\d$/', $this->data))
				$msg = 'Invalid time';
			else if (($this->min && $this->data < $this->min) ||
					($this->max && $this->data > $this->max))
				$msg = 'Time is out of range';
		}
		return $msg;
	}

	/**
	 * Set initial time value.
	 * 
	 * @param mixed $val DateTime object, or time string
	 * @return FormTimeControl This FormControl object
	 */
	public function init ($val) {
		if ($val) {
			if (!is_a($val, '\DateTime')) {
				try {
					$val = new \DateTime($val);
				}
				catch (\Exception $e) {
					$val = false;
				}
			}
			if ($val)
				$this->attr('value', $val->format('H:i'));
		}
		return $this;
	}

	/**
	 * Set maximum time input value.
	 * 
	 * @param mixed $val DateTime object, or time string
	 * @return FormTimeControl This FormControl object
	 */
	public function max ($val) {
		if (!is_a($val, 'DateTime'))
			$val = new \DateTime("$val");
		$this->max = $val->format('H:i');
		$this->attr('max', $this->max);
		return $this;
	}

	/**
	 * Set minimum time input value.
	 * 
	 * @param mixed $val DateTime object, or time string
	 * @return FormTimeControl This FormControl object
	 */
	public function min ($val) {
		if (!is_a($val, 'DateTime'))
			$val = new \DateTime("$val");
		$this->min = $val->format('H:i');
		$this->attr('min', $this->min);
		return $this;
	}

	/**
	 * Set the step value.
	 *
	 * @param int $step Step value, in minutes
	 * @return FormTimeControl This FormControl object
	 */
	public function step ($step) {
		if ((int)$step > 0) {
			$this->step = (int)$step;
			$this->attr('step', $step * 60); return $this;
		}
		else
			throw new UsageException('Step value must be positive integer');
	}

	/**
	 * Cause input type as text instead of time.
	 *
	 * By default, the HTML input control generated is of type "time".
	 * Call this function to cause the control to be generated with
	 * type "text".
	 *
	 * @return FormTimeControl This FormControl object
	 */
	public function text () { $this->attr('type', 'text'); return $this; }
}
