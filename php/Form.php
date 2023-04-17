<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history:
//	v5.3 - Method init allows object parameter in addition to array.
//	v5.5 - Method init considers object properties provided by magic methods.

/// @cond
namespace FST;
/// @endcond

/**
 * @brief FST Form class
 *
 * Form class for facilitating management of HTML forms within FST
 * Controllers. Once a Form object is created, various FormControl objects
 * may be added to the Form object using its various control creation methods.
 *
 * When a Form object is printed, an HTML representation of the form is
 * generated. When the form is submitted, the form elements are accessable
 * via properties of the Form object.
 *
 * Used in conjunction with the FST Controller object, form submission and
 * validation is handled automatically.
 */
class Form {

	/// @cond
	static protected $controls = array(); // Registered controls
	/// @endcond

	/**
	 * @brief Register a Form control class.
	 * @param string $function_name Name of control creation function
	 * @param string $class_name Name of class used to create the control
	 *
	 * A Form object includes form controls, each of which is defined by
	 * a class that is derived from FormControl. This function registers
	 * a control creation function with a control class.
	 */
	static function register ($function_name, $class_name) {
		// Note: May be called a second time for a given function name, so
		//	as to support override of default class name by an application.
		if (!is_subclass_of($class_name, 'FST\FormControl'))
			throw new UsageException(
				"Class $class_name is not derived from FST\\FormControl");
		self::$controls[$function_name] = $class_name;
	}

	/// @cond
	protected $attr = array(); // Additional attributes for form tag
	protected $ajax = true; // Submit via Ajax, if fst-jquery installed
	protected $error = array(); // Error messages for controls
	protected $fld = array(); // Form fields
	/// @endcond

	/**
	 * @brief Form object constructor.
	 * @param string $id Form id, for HTML DOM
	 * @param string $action Form action
	 *
	 * Creates a form. A form id is required. It is the programmer's
	 * responsibility that the id given does not conflict with any other
	 * elements in the HTML DOM.
	 */
	public function __construct ($id, $action='') {
		$this->attr('id', $id);
		$this->attr('action',
			preg_match('"^(\w+:/)?/"', $action) ? $action : "?$action");
		$this->attr('method', 'post');
	}

	/**
	 * @brief Constructs a FormControl object.
	 * @param string $fcn Method name
	 * @param array $args Method arguments
	 * @retval object A FormControl object
	 *
	 * When an undefined method is called, this magic method checks to
	 * see if the method name is associated with a registered form class.
	 * If so, it creates a new object and associates it with the current
	 * form.
	 *
	 * Controls are added to a form by calling the form's corresponding
	 * registered function. These functions take two parameters. The first
	 * is the name of the control and must be unique with respect to the
	 * form. The second is optional and is typically the label, but may
	 * be used for another purpose, depending on the control.
	 */
	public function __call ($fcn, $args) {
		if (array_key_exists($fcn, self::$controls) === false)
			throw new UsageException("Unknown Form method: $fcn");
		$ctrlclass = self::$controls[$fcn];
		if (!count($args))
			throw new UsageException("Too few arguments: Form::$fcn");
		$name = $args[0];
		$label = isset($args[1]) ? $args[1] : false;
		if (isset($this->fld[$name]))
			throw new UsageException("Duplicate form field: $name");
//		if (!preg_match('/^[a-z_]\w*$/i', $name))
//			throw new UsageException("Invalid form field name: $name");
		$this->fld[$name] = $label === false ?
			new $ctrlclass($this, $name) :
			new $ctrlclass($this, $name, $label);
		return $this->fld[$name];
	}

	/**
	 * @brief Get submitted form value.
	 * @param string $name Field name
	 * @retval string Submitted field value (from $_POST), trimmed
	 */
	public function __get ($name) {
		if (!isset($this->fld[$name]))
			throw new UsageException("Invalid field name: $name");
		return $this->fld[$name]->data();
	}

	/**
	 * @brief Produce HTML code with class attribute.
	 * @param string $class Class name
	 * @retval string HTML code
	 *
	 * Produces the HTML code for the form with the class attribute specified
	 * on the FORM tag. Provided for convience, this simply calls Form::html
	 * to produce the code.
	 */
	public function __invoke ($class='') { return $this->html($class); }

	/**
	 * @brief Produce HTML code for the form.
	 * @retval string HTML code
	 *
	 * Called when the object is printed, produces the HTML code for the
	 * form. This function simply calls Form::html.
	 */
	public function __toString () { return $this->html(); }

	/**
	 * @brief Get named form control object.
	 * @param string $name Field name
	 * @retval mixed FormControl object or false
	 *
	 * Retrieves the form control object with the given name. If no control
	 * with the given name exists, returns false.
	 */
	public function ctrl ($name)
		{ return isset($this->fld[$name]) ? $this->fld[$name] : false; }

	/**
	 * @brief Produce HTML code for the form.
	 * @param string $class Class name, optional
	 * @retval string HTML code
	 *
	 * Produces the HTML code for the form, with an optional class to be
	 * added to the FORM tag.
	 */
	public function html ($class='') {

		ob_start();

		print $this->html_form($class);

		// Output all hidden fields, build array of non-hidden fields
		$flds = array();
		foreach ($this->fld as $fld) {
			if (is_a($fld, 'FST\FormHiddenControl'))
				print $fld;
			else
				$flds[] = $fld;
		}

		// Output all non-hidden fields in form rows
		$count = 0;
		reset($flds);
		while (current($flds)) { // for each row

			$errors = array();
			if (isset($this->error[current($flds)->name()]))
				$errors[] = $this->error[current($flds)->name()];

			print '<div data-fst="form-row">';

			print '<div data-fst="form-label">';
			print current($flds)->label();
			print '</div>';

			print '<div data-fst="form-controls">';
			print '<div data-fst="form-controls-row">';

			print current($flds);
			next($flds);

			while (current($flds) && current($flds)->is_grouped()) {

				if (isset($this->error[current($flds)->name()]))
					$errors[] = $this->error[current($flds)->name()];

				if (!current($flds)->is_glued())
					print '</div><div data-fst="form-controls-row">';
				print current($flds)->label();
				print current($flds);

				next($flds);
			}

			print "</div>\n"; // data-fst=form-controls-row

			print '<div data-fst="form-error">'; // data-fst=form-error
			foreach ($errors as $err)
				print '<div>' . htmlspecialchars($err) . '</div>';
			print '</div>';

			print "</div>\n"; // data-fst=form-controls
			print "</div>\n"; // data-fst=form-row
		}

		print $this->html_form_end() . "\n";

		return ob_get_clean();
	}

	/**
	 * @brief Produce HTML code for form tag.
	 * @param string $class Class name, optional
	 * @retval string HTML code
	 *
	 * Produces the HTML code for the opening form tag, with an optional
	 * class to be added.
	 *
	 * Produce the HTML FORM tag.
	 * Typically called by an application when it is undesirable to use the
	 * default form HTML code. Content generators that need full control over
	 * how the form is produced should use this function to generate the
	 * form tag.
	 */
	public function html_form ($class='') {
		$attr = $this->attr;
		if (!isset($attr['data-fst']))
			$attr['data-fst'] = $this->ajax ? 'form' : 'form-post';
		if ($class)
			$attr['class'] = isset($attr['class']) ?
				"{$attr['class']} $class" : $class;
		return '<form' . Framework::attr($attr) . '>';
	}

	/**
	 * @brief Produce HTML code for form end tag.
	 * @retval string HTML code
	 *
	 * Produce the HTML FORM end tag, for use in conjunction with function
	 * Form::html_form.
	 */
	public function html_form_end () { return '</form>'; }

	/// @cond
	/*
	 * Set or clear form to submit via Ajax (deprecated)
	 *
	 * Indicates whether or not form is submitted via Ajax. Default is to
	 * submit via Ajax, if FST jQuery module is used. Default value for $ajax
	 * is true for compatibility with FST version 4.
	 * @param bool $ajax Submit form via Ajax flag (default true)
	 */
	public function ajax ($ajax=true) { $this->ajax = $ajax; }
	/// @endcond

	/**
	 * @brief Set form attribute.
	 * @param string $name Attribute name
	 * @param string $value Attribute value
	 *
	 * Sets an attribute to be included in the HTML form tag. Care must be
	 * taken so as to not inadvertently override attributes that
	 * are managed by the Form class itself.
	 */
	public function attr ($name, $value) { $this->attr[$name] = $value; }

	/**
	 * @brief Get submitted form data.
	 * @param array $fields Array of field names (optional, or false)
	 * @retval array Name/value pairs of submitted form values
	 *
	 * Returns an associative array of name/value pairs of the submitted form
	 * data. If $fields is supplied, it is expected to be an array of field
	 * names, and the returned array will contain only fields that are named
	 * in that array. If $fields is not supplied, the returned array will
	 * contain all fields for which controls are defined except any that
	 * are indicated as informational.
	 */
	public function data ($fields=false) {
		$data = array();
		foreach ($this->fld as $name=>$fld)
			if (($fields === false ||
						array_search($name, $fields) !== false) &&
					!$fld->is_informational())
				$data[$name] = $fld->data();
		return $data;
	}

	/**
	 * @brief Set error message for the given field.
	 * @param string $name Field name
	 * @param string $message Error message text
	 *
	 * Sets an error message for a given field. This is to be called
	 * by the application when performing form validation.
	 */
	public function error ($name, $message) {
		if (!isset($this->fld[$name]))
			throw new UsageException("Invalid field name: $name");
		$this->error[$name] = $message;
	}

	/**
	 * @brief Get number of errors in form.
	 * @retval int Error count
	 *
	 * Returns the number of fields that have error messages associated with
	 * them. Fields do not have errors associated with them until after
	 * form validation occurs.
	 */
	public function error_count () { return count($this->error); }

	/**
	 * @brief Get form errors.
	 * @retval array Name/value pairs of field names and error messages
	 *
	 * Returns an associative array of error messages for the form. The
	 * array is indexed by field name.
	 */
	public function errors () { return $this->error; }

	/**
	 * @brief Get form id.
	 * @retval string Form id
	 */
	public function id () { return $this->attr['id']; }

	/**
	 * @brief Set initial form values
	 * @param mixed $data Array or object for initializing form values
	 *
	 * Sets initial value of all form elements that have been defined. If
	 * $data is an associative array, key/value pairs are used to initialize
	 * form elements with name corresponding to the key with the associated
	 * value. If $data is an object, form elements with name corresponding to
	 * a public property of the object are initialized with that property's
	 * value. If the object has virtual properties (provided via __get and
	 * and __isset), those properties are also used.
	 *
	 * This does not have any effect on form elements created after
	 * this method is called.
	 */
	public function init ($data) {
		if (!is_array($data) && !is_object($data))
			throw new UsageException('Parameter 1 is not an array or object');
//		foreach (is_object($data) ? get_object_vars($data) : $data
//				as $name=>$value)
//			if (isset($this->fld[$name]))
//				$this->fld[$name]->init($value);
		if (is_array($data)) {
			foreach ($data as $name=>$value)
				if (isset($this->fld[$name]))
					$this->fld[$name]->init($value);
		}
		else /* is_object($data) */ {
			foreach ($this->fld as $name=>$obj)
				if (property_exists($data, $name) || isset($data->$name))
					$this->fld[$name]->init($data->$name);
		}
	}

	/// @cond
	/*
	 * Get HTML code for FORM begin tag (deprecated)
	 *
	 * This function is deprecated in favor of function html_form.
	 * @return HTML code
	 */
	public function begin () { return $this->html_form(); }

	/*
	 * Get HTML code for FORM end tag (deprecated)
	 *
	 * This function is deprecated in favor of function html_form_end.
	 * @return HTML code
	 */
	public function end () { return $this->html_form_end(); }
	/// @endcond

	/**
	 * @brief Sets all form elements to read-only.
	 */
	public function readonly () { foreach ($this->fld as $f) $f->readonly(); }

	/**
	 * @brief Perform validation on submitted form data.
	 * @retval bool Validation success flag
	 *
	 * This calls the error function for each form control. Each form control
	 * should return a string if there is a validation error for the given
	 * control. If the form submission was canceled, this function performs
	 * error validation only on the submit control.
	 */
	public function validate () {
		$this->error = array();
		$this->init($_POST);
		foreach ($this->fld as $f) {
			$msg = $f->error();
			if ($msg)
				$this->error[$f->name()] = $msg;
		}
		return count($this->error) == 0;
	}
}
