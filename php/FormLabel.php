<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/// @cond
namespace FST;
/// @endcond

/**
 * @brief A form label.
 *
 * The Form class, when generating its HTML code, will generate a label for
 * each control row. Objects of this class are returned from the
 * FormControl::label method.
 *
 * Generally, there is not need to create objects from this class, as it is
 * used internally by the Form and FormControl classes.
 */
class FormLabel {

	/// @cond
	protected $control;
	protected $html = false;
	protected $label;
	/// @endcond

	/**
	 * @brief Constructor.
	 * @param string $label Label text
	 * @param object $control FormControl object with which label is associated
	 *
	 * Objects of this class are returned by FormControl::label, and are
	 * used to print the HTML code for the label.
	 *
	 * The HTML code will include the "for" attribute to indicate the ID of
	 * the control with which the label is associated. If $control is passed
	 * as false, no "for" attribute is generated.
	 */
	public function __construct ($label, $control) {
		$this->control = $control;
		$this->label = $label;
	}

	/**
	 * @brief Get HTML code for the label.
	 * @retval string HTML code
	 */
	public function __toString () {
		$label = $this->html ? $this->label : htmlspecialchars($this->label);
		return $this->control ?
			('<label for="' . $this->control->id() . '">' . $label . '</label>') :
			('<label>' . $label . '</label>');
	}

	/**
	 * @brief Indicate label is given as HTML code
	 * @retval The FormControl object associated with this label (for chaining)
	 * 
	 * Call this method to indicate that the label text was given as HTML
	 * code as opposed to plain text. Since FormLabel objects are typically
	 * not instantiated directly, call FormControl::label to retrieve
	 * FormLabel object, then call this method.
	 */
	public function html () {
		$this->html = true;
		return $this->control;
	}

	/**
	 * @brief Get the label text.
	 * @retval string Label text string
	 */
	public function label () { return $this->label; }
}
