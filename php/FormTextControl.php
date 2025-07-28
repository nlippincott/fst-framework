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
 * Text control.
 */
class FormTextControl extends FormInputControl {

	/** @ignore */
	protected $case = null;
	/** @ignore */
	protected $datalist = null;
	/** @ignore */
	protected $regex = null;
	/** @ignore */
	protected $regex_msg = '';

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
		$this->attr('data-fst', 'form-control-text');
		$this->attr('type', 'text');
	}

	/**
	 * Get HTML code.
	 * 
	 * @return string HTML code
	 */
	public function __toString () {
		if (!$this->datalist)
			return parent::__toString();
		$this->attr('list', $this->attr['id'] . '-datalist');
		ob_start();
		print '<datalist id="' . $this->attr['id'] . '-datalist">';
		foreach ($this->datalist as $list_item)
			print '<option' . Framework::attr(array('value'=>$list_item)) . ' />';
		print '</datalist>';
		return parent::__toString() . ob_get_clean();
	}

	/**
	 * Get data input from text control
	 *
	 * Gets the data that was input for the control. If the lowercase or
	 * uppercase option was used, that effect is applied by this method.
	 *
	 * @return string Text input that was entered into control
	 */
	public function data () {
		if (parent::data())
			switch ($this->case) {
				case 'lower': return strtolower(parent::data());
				case 'upper': return strtoupper(parent::data());
			}
		return parent::data();
	}

	/**
	 * Set autocomplete options.
	 * 
	 * @param array $datalist Autocomplete strings
	 * @return FormTextControl This FormControl object
	 */
	public function datalist ($datalist)
		{ $this->datalist = $datalist; return $this; }

	/**
	 * Get error message associated with this control.
	 * 
	 * @return string Error message, or empty string if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg && $this->regex && $this->data())
			$msg = preg_match($this->regex, $this->data()) ? false : $this->regex_msg;
		return $msg;
	}

	/**
	 * Set maximim input length.
	 * 
	 * @param int $length Maximim input length
	 * @return FormTextControl This FormControl object
	 */
	public function maxlength ($length)
		{ $this->attr['maxlength'] = $length; return $this; }

	/**
	 * Set the control's placeholder text.
	 * 
	 * @param string $placeholder Placeholder text
	 * @return FormTextControl This FormControl object
	 */
	public function placeholder ($placeholder)
		{ $this->attr['placeholder'] = $placeholder; return $this; }

	/**
	 * Set input size.
	 * 
	 * @param int $size Size of input control
	 * @return FormTextControl This FormControl object
	 */
	public function size ($size) { $this->attr['size'] = $size; return $this; }

	/**
	 * Set regular expression for validation.
	 *
	 * Sets a regular expression to be used when validating this input. If
	 * set, the regular expression is checked during form validation. If the
	 * regular expression is not matched, the error message will be issued
	 * as the error message for this control.
	 *
	 * @param string $regex Regular expression (PERL-compatible)
	 * @param string $regex_msg Error message text
	 * @return FormTextControl This FormControl object
	 */
	public function regex ($regex, $regex_msg) {
		$this->regex = $regex;
		$this->regex_msg = $regex_msg;
		return $this;
	}

	/**
	 * Convert text input to lowercase.
	 *
	 * Indicates that the text input should be converted to lowercase.
	 * Conversion is done when retrieving form data.
	 *
	 * @return FormTextControl This FormControl object
	 */
	public function tolower () { $this->case = 'lower'; return $this; }

	/**
	 * Convert text input to uppercase
	 *
	 * Indicates that the text input should be converted to uppercase.
	 * Conversion is done when retrieving form data.
	 *
	 * @return FormTextControl This FormControl object
	 */
	public function toupper () { $this->case = 'upper'; return $this; }
}
