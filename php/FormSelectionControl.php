<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Form control for selection from a number of options.
 *
 * This control is used to select from a number of pre-defined options. Then
 * control may be rendered as a drop-down selection (default) or a set of
 * radio buttons (by calling the radio function).
 */
class FormSelectionControl extends FormControl {

	/** @ignore */
	private $options = [];
	/** @ignore */
	private $options_group = [];
	/** @ignore */
	private $prompt = null;
	/** @ignore */
	private $radio = false;
	/** @ignore */
	private $radio_glued = false;
	/** @ignore */
	private $radio_group = false;
	/** @ignore */
	private $value = '';

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
		$this->attr('data-fst', 'form-control-selection');
	}

	/**
	 * Get HTML code
	 *
	 * @return string HTML code
	 */
	public function __toString () {

		ob_start();

		if ($this->radio) {

			print '<span id="' . $this->id() . '">';

			$cnt = 0;
			foreach ($this->options as $value=>$label) {

				$attr = $this->attr;
				$attr['id'] .= '-' . ++$cnt;
				$attr['type'] = 'radio';
				$attr['value'] = $value;

				if ($this->radio_group)
					$attr['name'] = $this->radio_group;

				if ("$value" == "{$this->value}")
					$attr['checked'] = 'checked';

				if ($cnt > 1) print $this->radio_glued ? ' ' : '<br />';

				print '<input' . Framework::attr($attr) . ' />';
				print '<label for="' . $attr['id'] . '">' . htmlspecialchars($label) . '</label>';
			}

			print '</span>';
		}
		else {
			print '<select' . Framework::attr($this->attr) . '>';

			if ($this->prompt)
				print '<option value="">' . htmlspecialchars($this->prompt) . '</option>';

			foreach ($this->options as $value=>$label) {
				print '<option value="' . htmlspecialchars($value) . '"';
				if ("$value" === "{$this->value}")
					print ' selected="1"';
				print '>' . htmlspecialchars($label) . '</option>';
			}

			foreach ($this->options_group as $grp=>$opts) {
				print '<optgroup label="' . htmlspecialchars($grp) . '">';
				foreach ($opts as $value=>$label) {
					print '<option value="' . htmlspecialchars($value) . '"';
					if ("$value" === "{$this->value}")
						print ' selected="1"';
					print '>' . htmlspecialchars($label) . '</option>';
				}
				print '</optgroup>';
			}

			print '</select>';
		}

		return ob_get_clean();
	}

	/**
	 * Get value of selected option.
	 *
	 * @return string Selected option value
	 */
	public function data () {
		if ($this->radio && $this->radio_group)
			return isset($_POST[$this->radio_group]) ? $_POST[$this->radio_group] : $this->nodata;
		return parent::data();
	}

	/**
	 * Set initial value for the control.
	 * 
	 * @param mixed $val Initial value
	 * @return FormSelectionControl This FormControl object
	 */
	public function init ($val) { $this->value = $val; return $this; }

	/**
	 * Set available options for the control.
	 *
	 * Defines the options that are available for the control. Parameter $opt
	 * contains the data to be used as the values and descriptions for the
	 * options. The interpretation of this parameter depends on parameter
	 * $assoc.
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
	 * 
	 * Optional parameter $group defines an options group. If this parameter
	 * is provided (must be a string), the options given will become a group
	 * of options under the given title of the group parameter. If called
	 * more than once with the same value for $group, the last call defines
	 * the options for the defined group.
	 * 
	 * When parameter $group is not given, the options supplied becmore the
	 * main options (no group title) for the control. If called more than once
	 * with no $group option, the last call defines the options that are used.
	 * 
	 * If this control is defined as a radio control (by calling the radio
	 * method), all option groups are ignored.
	 *
	 * @param array $opt Array of control options
	 * @param mixed $assoc Define interpretation of $opt parameter (optional)
	 * @param string $group Title for options group (optional)
	 * @return FormSelectionControl This FormControl object
	 */
	public function options ($opt, $assoc=null, $group=null) {

		if (!is_array($opt))
			throw new UsageException('Parameter 1 is not an array');
		if (isset($group) && !is_string($group))
			throw new UsageException('Parameter 3 must be a string');

		// If $assoc not given, set to true or false based on $opt key values
		if ($assoc === null)
			$assoc = array_keys($opt) !== range(0, count($opt) - 1);

		// If $assoc is boolean, set options accordingly.
		if (is_bool($assoc)) {
			if ($assoc)
				$opts = $opt;
			else
				foreach ($opt as $o)
					$opts[$o] = $o;
			if ($group)
				$this->options_group[$group] = $opts;
			else
				$this->options = $opts;
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
		$opts = [];
		if (is_object($opt[0]))
			foreach ($opt as $row)
				$opts[$row->$key] = $row->$desc;
		else
			foreach ($opt as $row)
				$opts[$row[$key]] = $row[$desc];
		if ($group)
			$this->options_group[$group] = $opts;
		else
			$this->options = $opts;

		return $this;
	}

	/**
	 * Sets the prompt text for the control.
	 *
	 * Sets the prompt text when the control is presented as a drop-down
	 * selection box. This is ignored if the control is rendered as radio
	 * buttons.
	 *
	 * @param string $text Prompt text
	 * @return FormSelectionControl This FormControl object
	 */
	public function prompt ($text) { $this->prompt = $text; return $this; }

	/**
	 * Set control to radio presentation.
	 *
	 * Causes the control to be displayed as radio buttons rather than a
	 * drop-down selection box. By default, radio buttons are displayed
	 * vertically (i.e. within their own DIV elements). Set $glued to true
	 * to "glue" them horizontally in the same form row.
	 *
	 * @param bool $glued Display-options-horizontally flag
	 * @return FormSelectionControl This FormControl object
	 */
	public function radio ($glued=false)
		{ $this->radio = true; $this->radio_glued = $glued; return $this; }

	/**
	 * Set the radio group for the control.
	 *
	 * Sets the name of the radio group. This can be used to generate multiple
	 * radio controls for the same selection group. Typically this is used
	 * when more then one FormSelectionControl objects are defined and are
	 * needed to work together.
	 * Calling this function for a drop-down selection box has no effect.
	 *
	 * @param string $name Radio group name
	 * @return FormSelectionControl This FormControl object
	 */
	public function radiogroup ($name)
		{ $this->radio_group = $name; return $this; }

	/**
	 * Set control as read-only.
	 *
	 * @return FormSelectionControl This FormControl object
	 */
	public function readonly ()
		{ $this->attr('disabled', 'disabled'); return $this; }
}
