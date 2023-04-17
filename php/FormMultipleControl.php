<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history, ver 5.2.1
//	- Correction to HTML5 boolean attribute "disabled"
//	- Correction to HTML5 boolean attribute "multiple"
// Revision history, ver 5.4
//	- Removed reference to this->prompt (not applicable)
//	- Added method checkbox to allow generating control as multiple
//		checkboxes instead of the native multiple selection control.
//	- For native multiple selection control, corrected selected attribute
//	- Added implode option to return values as single string, imploded with
//		newlines, instead of an array.
//	- Changed init method to explode value given when value given is not
//		an array.

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Form control for multiple selection from a number of options.
 *
 * This control is used to select from a number of pre-defined options. The
 * control allows for multiple selections. Data value returned from this
 * control is, by default, an array of selected values.
 * Optionally, a string may be returned with values separated
 * by newline characters.
 */
class FormMultipleControl extends FormControl {

	/// @cond
	private $assoc = null;
	private $check = false;
	private $implode = false;
	private $options = array();
	private $value = array();
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
		$this->attr('data-fst', 'form-control-multiple');
		$this->attr('multiple', 'multiple');
		$this->attr('name', $name . '[]');
	}

	/**
	 * @brief Get HTML code.
	 * @retval string HTML code
	 */
	public function __toString () {

		ob_start();

		if ($this->check) {
			print '<div' . Framework::attr(array(
				'id'=>$this->attr['id'],
				'data-fst'=>'form-control-multiple',
				'data-fst-name'=>$this->attr['name'])) . '>';

			$attr = array(
				'data-fst'=>'form-control-multiple-option',
				'name'=>$this->attr['name'],
				'type'=>'checkbox');

			$cnt = 0;
			foreach ($this->options as $k=>$v) {
				print '<div>';
				$attr['id'] = $this->attr['id'] . '-' . ++$cnt;
				$attr['value'] = $this->assoc ? $k : $v;
				if (array_search($v, $this->value) !== false)
					$attr['checked'] = 'checked';
				else if (isset($attr['checked']))
					unset($attr['checked']);
				print '<input' . Framework::attr($attr) . ' />';
				print '<label for="' . $attr['id'] . '">' .
					htmlspecialchars($v) . '</label>';
				print '</div>';
			}

			print '</div>';
		}
		else {
			// Generate native multiple selection control
			print '<select' . Framework::attr($this->attr) . '>';

			foreach ($this->options as $k=>$v) {

				$label = $v;
				$value = $this->assoc ? $k : $v;

				print '<option value="' . htmlspecialchars($value) . '"';
				if (array_search($value, $this->value) !== false)
					print ' selected="selected"';
				print '>' . htmlspecialchars($label) . '</option>';
			}
			print '</select>';
		}

		return ob_get_clean();
	}

	/**
	 * @brief Generate control as multiple checkboxes
	 * @param bool $check Generate as checkboxes (default true)
	 * @retval object This FormControl object
	 *
	 * If $check is true, causes the control to be generated as multiple
	 * checkbox controls rather than the native multiple selection control.
	 */
	public function checkbox ($check=true)
		{ $this->check = $check; return $this; }

	/**
	 * @brief Get value of selected option.
	 * @retval mixed Array or string containing the selected values
	 *
	 * By default, returns an array of the selected values. If the implode
	 * option is set, returns a string of the selected values separated by
	 * newlines.
	 */
	public function data () {
		$val = isset($_POST[$this->name()]) ? $_POST[$this->name()] : array();
		return $this->implode ? implode("\n", $val) : $val;
	}

	/**
	 * @brief Get error message associated with this control.
	 * @return Error message string, or false if no error
	 */
	public function error () {
		return $this->is_required() && !count($this->data()) ?
			'Value required' : false;
	}

	/**
	 * @brief Set initial value for the control.
	 * @param mixed $val Initial value, or array of initial values
	 * @retval object This FormControl object
	 *
	 * This function accepts a scalar value or an array. If a scalar value,
	 * that value supplied becomes an initially selected value in the control.
	 * This function may be called multiple times with a scalar value to
	 * initially select multiple values. If, however, an array is passed,
	 * the values in the array are used as the initially selected values, and
	 * the values supplied by any previous calls are discarded.
	 */
	public function init ($val) {
		if (is_array($val))
			$this->value = $val;
		else
			$this->value[] = $val;
		$this->value = is_array($val) ?
			$val : array_map('trim', explode("\n", trim($val)));
		return $this;
	}

	/**
	 * @brief Cause control value to be returned as string.
	 * @param bool $implode Set data method to return imploded values
	 * @retval object This FormControl object
	 *
	 * This function controls the behavior of the return value of the data
	 * method. By default, the data method returns an array of values
	 * selected. If this function is called passing true (default), then
	 * the values selected will be imploded (PHP implode function) with
	 * newline characters and returned as a string.
	 */
	public function implode ($implode=true)
		{ $this->implode = $implode; return $this; }

	/**
	 * @brief Get control name.
	 * @retval string Control name
	 *
	 * This overrides the default function by trimming '[]' off the name
	 * attribute.
	 */
	public function name () { return substr($this->attr['name'], 0, -2); }

	/**
	 * @brief Set available options for the control.
	 * @param array $opt Array of control options
	 * @param mixed $assoc Define interpretation of $opt parameter (optional)
	 * @retval object This FormControl object
	 *
	 * Defines the options that are available for the control. Parameter $opt
	 * contains the data to be used as the values and descriptions for the
	 * options. The interpretation of this parameter depends on parameter
	 * $type.
	 *
	 * Available values for $assoc are as follows:
	 *	- true - An associative array is given. The array key values are
	 *		used for the options values and the array values are used for the
	 *		option descriptions.
	 *	- false - An array is given, and values in the array are to be used
	 *		as both the option values and descriptions.
	 *	- null (default) - Same as either true or false based upon
	 *		examination of array key values in $opt.
	 *	- "[KEY:]DESC" - An array of associative arrays or objects is given.
	 *		DESC specifies the index or property of each element in $opt
	 *		to be used as the option description. KEY (if supplied) specifies
	 *		the index or property to be used as the option value. If KEY is
	 * 		not provided, DESC is also used as the option value.
	 */
	public function options ($opt, $assoc=null) {

		if (!is_array($opt))
			throw new UsageException('Parameter 1 is not an array');

		// If $assoc not given, set to true or false based on $opt key values
		if ($assoc === null)
			$assoc = array_keys($opt) !== range(0, count($opt) - 1);

		// If $assoc is boolean, use options as given.
		if (is_bool($assoc)) {
			$this->options = $opt;
			$this->assoc = $assoc;
			return $this;
		}

		// $assoc must be a string
		if (!is_string($assoc))
			throw new UsageException('Parameter 2 must be bool or string');

		// For compatibility with a development version, check $assoc for
		//	leading characters 'arrays:' or 'objects:'. If so, remove.
		if (preg_match('/^(arrays|objects)\:/', $assoc))
			$assoc = substr($assoc, strpos($assoc, ':') + 1);

		// Determine KEY and DESC names
		list($key, $desc) = explode(':', "$assoc:");
		if (!$desc)
			$desc = $key;

		// Build an associative array of options.
		$this->options = array();
		$this->assoc = true;
		if (is_object($opt[0]))
			foreach ($opt as $row)
				$this->options[$row->$key] = $row->$desc;
		else
			foreach ($opt as $row)
				$this->options[$row[$key]] = $row[$desc];

		return $this;
	}

	/**
	 * @brief Set control as read-only.
	 * @retval object This FormControl object
	 */
	public function readonly ()
		{ $this->attr('disabled', 'disabled'); return $this; }

	/**
	 * @brief Set control size.
	 * @param int $size Size of the control
	 * @return This FormControl object.
	 */
	public function size ($size) { $this->attr('size', $size); return $this; }
}
