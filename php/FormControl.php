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
 * Abstract base class for all form controls
 *
 * All form controls managed by a Form object are derived from this class.
 * Further, the Form::register static method is used to register a function
 * name that the Form class will use to create and add a control to a Form
 * object.
 *
 * FormControl methods that are used for setting control options will return
 * the object used to invoke the method ($this), so as to support
 * method chaining.
 */
abstract class FormControl {

	/** @ignore */
	protected $attr = array();

	/** @ignore */
	protected $glued = false;
	/** @ignore */
	protected $grouped = false;
	/** @ignore */
	protected $label = null;
	/** @ignore */
	protected $informational = false;
	/** @ignore */
	protected $nodata = null;
	/** @ignore */
	protected $required = false;

	/**
	 * Control constructor.
	 *
	 * Sets the control's 'id' and 'name' attributes based on the control
	 * name. Also, sets the control's label, which may be an empty string,
	 * the default value. Derived classes may override the constructor for
	 * additional control initialization, but must maintain the parameter
	 * list.
	 *
	 * The third parameter, $label, is typically the label associated with
	 * the control. However, particular controls may use this field for a
	 * different purpose (such as in FormSubmitControl).
	 * If that is the case, FormControl::label may be
	 * used to set the label value if desired.
	 *
	 * @param object $form Form object to which control is attached
	 * @param string $name Control name ("name" attribute in HTML)
	 * @param string $label Control label (or other value, see description)
	 */
	public function __construct ($form, $name, $label='') {
		$this->attr['data-fst'] = 'form-control';
		$this->attr['id'] = $form->id() . "_$name";
		$this->attr['name'] = $name;
		$this->label($label);
	}

	/**
	 * Get HTML code for the control.
	 *
	 * Derived classes must override this method such that the HTML code
	 * for the control is returned.
	 *
	 * @return string HTML code
	 */
	abstract public function __toString ();

	/**
	 * Add class name to HTML class attribute.
	 *
	 * @param string $classname Class name
	 * @return object This FormControl object
	 */
	public function addClass ($classname) {
		if (!isset($this->attr['class']))
			$this->attr['class'] = '';
		$this->attr['class'] = trim($this->attr['class'] . ' ' . $classname);
	}

	/**
	 * Set control attribute.
	 *
	 * Sets an attribute to be included in the control's top-level HTML
	 * element. It is the responsibility of derived classes to output
	 * the attributes as part of the top-level element. Care must be taken
	 * by the user so as to not inadvertently override attributes that are
	 * managed by the control itself. If $value is passed as null, the
	 * given attribute is removed from the element.
	 *
	 * @param string $name Attribute name
	 * @param string $value Attribute value
	 */
	public function attr ($name, $value) {
		if ($value === null)
			unset($this->attr[$name]);
		else
			$this->attr[$name] = $value;
		return $this;
	}

	/**
	 * Get submitted form data for this control.
	 *
	 * Returns for data that was submitted within the form for this control.
	 * If data was provided for the control, a string is returned and that
	 * string is trimmed (leading and trailing spaces removed). If not data
	 * was submitted, return null unless FormControl::notnull was called in
	 * which case an empty string is returned.
	 *
	 * @return mixed Form data value (trimmed)
	 */
	public function data () {
//		return isset($_POST[$this->name()]) && $_POST[$this->name()] ?
//			trim($_POST[$this->name()]) : $this->nodata;
		return isset($_POST[$this->name()]) && $_POST[$this->name()] !== '' ?
			trim($_POST[$this->name()]) : $this->nodata;
	}

	/**
	 * Get error message for this control.
	 *
	 * Returns either an error message or false according to the control's
	 * validation rules. This base implementation returns an error message
	 * if a form value is required and no value has been submitted.
	 *
	 * @return mixed Error message as a string, or false if no error
	 */
	public function error () {
		return $this->is_required() && $this->no_post_data() ?
			'Value is required' : false;
	}

	/**
	 * Set control to be glued with previous control.
	 *
	 * By default, each form control is rendered by Form::html in its own row.
	 * Calling this function (passing true for $glued) causes this element
	 * to be generated inline with the previous form control.
	 *
	 * @param bool $glued Indicates if control is to be glued
	 * @return object This FormControl object
	 */
	public function glue ($glued=true) { $this->glued = $glued; return $this; }

	/**
	 * Set control to be grouped with previous control.
	 *
	 * By default, each form control is rendered in its own row. Calling this
	 * function causes this element to be generated in its own DIV element but
	 * in the same row and with the same label as the previous control.
	 *
	 * @param bool $grouped Indicates if control is to be grouped
	 * @return object This FormControl object
	 */
	public function group ($grouped = true)
		{ $this->grouped = $grouped; return $this; }

	/**
	 * Get ID attribute for this control.
	 * 
	 * @return string Control id (HTML ID attribute)
	 */
	public function id () { return $this->attr['id']; }

	/**
	 * Sets the control as an informational control.
	 *
	 * An informational control is one that is provided to the user for
	 * informational purposes, but its value is not included in the form's
	 * input data.
	 *
	 * A control may be designated as informational by calling this function.
	 * However, controls derived from this class may be informational by
	 * their nature, in which case the control's constructor will designate
	 * the control as informational (e.g. FormNoteControl).
	 *
	 * Data may be retrieved from an informational control (by calling
	 * FormControl::data), but that data is not included when Form::data
	 * is called to retrieve all data values submitted for a form.
	 *
	 * @return object This FormControl object
	 */
	public function info () { $this->informational = true; return $this; }

	/**
	 * Sets the initial value for the control.
	 *
	 * Derived classes must override this function to set up the control's
	 * value as the given value. The derived function must return the current
	 * FormControl object.
	 *
	 * @param mixed $val Initial control value
	 * @return object This FormControl object
	 */
	abstract public function init ($val);

	/**
	 * Get glued property of this control.
	 *
	 * @return bool Control-is-glued flag
	 */
	public function is_glued () { return $this->glued; }

	/**
	 * Get grouped property of this control.
	 *
	 * @return bool Control-is-grouped flag
	 */
	public function is_grouped () { return $this->glued || $this->grouped; }

	/**
	 * Get informational property of this control.
	 * 
	 * @return bool Control-is-informational flag
	 */
	public function is_informational () { return $this->informational; }

	/**
	 * Get required property of this control.
	 *
	 * @return bool Control-value-is-required flag
	 */
	public function is_required () { return $this->required; }

	/**
	 * Get or set label associated with this control.
	 *
	 * If parameter $label is provided, this method sets the text to be
	 * associated with the control's label. When called for this purpose,
	 * this method returns the FormControl object used to invoke this
	 * method, so as to facilitate method chaining.
	 *
	 * If parameter $label is not provided, this method returns the FormLabel
	 * object associated with this control, or null if no
	 * label is defined.
	 *
	 * Most controls use the third parameter of the constructor (and the
	 * second parameter of the Form class registered function) as the
	 * control's label text. However, some controls will use that parameter
	 * for another purpose (in which case the label will be an empty string).
	 * When that is the case, this method may be used to set the label
	 * for the control.
	 *
	 * @param string $label Label text, if called to set the label (optional)
	 * @return mixed FormLabel object, empty string, or this FormControl object
	 */
	public function label ($label=null) {
		if ($label !== null) {
			$this->label = new FormLabel($label, $this);
			return $this;
		}
		return $this->label;
	}

	/**
	 * Get name associated with this control.
	 *
	 * @return string Control name
	 */
	public function name () { return $this->attr['name']; }

	// Determine if no data entry
	/** @ignore */
	protected function no_post_data ()
		{ return $this->data() === $this->nodata; }

	/**
	 * Indicate control should not return null if no input.
	 *
	 * By default, most controls will present their data value as null if no
	 * data was entered into the control. Calling this function will cause
	 * the control to return an empty string instead of null in such cases.
	 *
	 * @return object This FormControl object
	 */
	public function notnull () { $this->nodata = ''; return $this; }

	/**
	 * Set control as read-only.
	 *
	 * Classes derived from FormControl must override this method to indicate
	 * that the control should be generated as read-only.
	 *
	 * @return object This FormControl object
	 */
	abstract public function readonly ();

	/**
	 * Set control value as required.
	 *
	 * Indicates whether or not a value for this control is required. If set,
	 * the validation function will indicate an error if a value is not
	 * supplied for the control.
	 *
	 * @param bool $required Control value required flag
	 * @return object This FormControl object
	 */
	public function required ($required=true) {
		$this->required = $required;
		$this->attr('data-fst-required', $required ? '1' : null);
		return $this;
	}

	/**
	 * Remove class name from HTML class attribute.
	 *
	 * @param string $classname Class name
	 * @return object This FormControl object
	 */
	public function removeClass ($classname) {
		if (isset($this->attr['class'])) {
			$classes = array();
			$idx = array_search($classname, $classes);
			if ($idx !== false)
				array_splice($classes, $idx, 1);
			$this->attr['class'] = implode(' ', $classes);
		}
		return $this;
	}

	// Sets the initial value for the control (synonym for 'init') (deprecated)
	//
	// Calls 'init' to set the intial value for the control.
	//
	// This function is provided for two reasons:
	// - maintain compatibility with FST version 4
	// - provide consistency with HTML value attribute
	//
	// @param mixed $val Initial control value
	// @return This FormControl object
	/** @ignore */
	final public function value ($val) { return $this->init($val); }
}
