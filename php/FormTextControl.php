<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history:
//	v5.3 - Added list member function for autocomplete values

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Text control.
 */
class FormTextControl extends FormInputControl {

	/// @cond
	protected $case = false;
	protected $datalist = false;
	protected $regex = false;
	protected $regex_msg = '';
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
		$this->attr('data-fst', 'form-control-text');
		$this->attr('type', 'text');
	}

	/**
	 * @brief Get HTML code.
	 * @retval string HTML code
	 */
	public function __toString () {
		if (!$this->datalist)
			return parent::__toString();
		$this->attr('list', $this->attr['id'] . '-datalist');
		ob_start();
		print '<datalist id="' . $this->attr['id'] . '-datalist">';
		foreach ($this->datalist as $list_item)
			print '<option' .
				Framework::attr(array('value'=>$list_item)) . ' />';
		print '</datalist>';
		return parent::__toString() . ob_get_clean();
	}

	/**
	 * @brief Get data input from text control
	 * @retval string Text input that was entered into control
	 *
	 * Gets the data that was input for the control. If the lowercase or
	 * uppercase option was used, that effect is applied by this method.
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
	 * @brief Set autocomplete options.
	 * @param array $datalist Autocomplete strings
	 * @retval object This FormControl object
	 */
	public function datalist ($datalist)
		{ $this->datalist = $datalist; return $this; }

	/**
	 * @brief Get error message associated with this control.
	 * @retval string Error message, or empty string if no error
	 */
	public function error () {
		$msg = parent::error();
		if (!$msg && $this->regex && $this->data())
			$msg = preg_match($this->regex, $this->data()) ?
				false : $this->regex_msg;
		return $msg;
	}

	/**
	 * @brief Set maximim input length.
	 * @param int $length Maximim input length
	 * @retval object This FormControl object
	 */
	public function maxlength ($length)
		{ $this->attr['maxlength'] = $length; return $this; }

	/**
	 * @brief Set the control's placeholder text.
	 * @param string $placeholder Placeholder text
	 * @retval object This FormControl object
	 */
	public function placeholder ($placeholder)
		{ $this->attr['placeholder'] = $placeholder; return $this; }

	/**
	 * @brief Set input size.
	 * @param int $size Size of input control
	 * @retval object This FormControl object
	 */
	public function size ($size) { $this->attr['size'] = $size; return $this; }

	/**
	 * @brief Set regular expression for validation.
	 * @param string $regex Regular expression (PERL-compatible)
	 * @param string $regex_msg Error message text
	 * @retval object This FormControl object
	 *
	 * Sets a regular expression to be used when validating this input. If
	 * set, the regular expression is checked during form validation. If the
	 * regular expression is not matched, the error message will be issued
	 * as the error message for this control.
	 */
	public function regex ($regex, $regex_msg) {
		$this->regex = $regex;
		$this->regex_msg = $regex_msg;
		return $this;
	}

	/**
	 * @brief Convert text input to lowercase.
	 * @retval object This FormControl object
	 *
	 * Indicates that the text input should be converted to lowercase.
	 * Conversion is done when retrieving form data.
	 */
	public function tolower () { $this->case = 'lower'; return $this; }

	/**
	 * @brief Convert text input to uppercase
	 * @retval object This FormControl object
	 *
	 * Indicates that the text input should be converted to uppercase.
	 * Conversion is done when retrieving form data.
	 */
	public function toupper () { $this->case = 'upper'; return $this; }
}
