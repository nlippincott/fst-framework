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
 * FST Form class.
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

	/** @ignore */
	static protected $controls = array(); // Registered controls

	/**
	 * Register a Form control class.
	 *
	 * A Form object includes form controls, each of which is defined by
	 * a class that is derived from FormControl. This function registers
	 * a control creation function with a control class.
	 *
	 * @param string $function_name Name of control creation function
	 * @param string $class_name Name of class used to create the control
	 */
	static function register ($function_name, $class_name) {
		// Note: May be called a second time for a given function name, so
		//	as to support override of default class name by an application.
		if (!is_subclass_of($class_name, 'FST\FormControl'))
			throw new UsageException(
				"Class $class_name is not derived from FST\\FormControl");
		self::$controls[$function_name] = $class_name;
	}

	/** @ignore */
	protected $attr = array(); // Additional attributes for form tag
	/** @ignore */
	protected $ajax = true; // Submit via Ajax, if fst-jquery installed
	/** @ignore */
	protected $error = array(); // Error messages for controls
	/** @ignore */
	protected $fld = array(); // Form fields

	/**
	 * Form object constructor.
	 *
	 * Creates a form. A form id is required. It is the programmer's
	 * responsibility that the id given does not conflict with any other
	 * elements in the HTML DOM.
	 *
	 * @param string $id Form id, for HTML DOM
	 * @param string $action Form action
	 */
	public function __construct ($id, $action='') {
		$this->attr('id', $id);
		$this->attr('action',
			preg_match('"^(\w+:/)?/"', $action) ? $action : "?$action");
		$this->attr('method', 'post');
	}

	/**
	 * Constructs a FormControl object.
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
	 * 
	 * The following function names and their corresponding FormControl classes
	 * are automatically registered by the Framework:
	 *
	 * |-----------|----------------------|
	 * | date      | FormDateControl      |
	 * | email     | FormEmailControl     |
	 * | file      | FormFileControl      |
	 * | hidden    | FormHiddenControl    |
	 * | image     | FormImageControl     |
	 * | money     | FormMoneyControl     |
	 * | multiple  | FormMultipleControl  |
	 * | note      | FormNoteControl      |
	 * | number    | FormNumberControl    |
	 * | option    | FormOptionControl    |
	 * | password  | FormPasswordControl  |
	 * | search    | FormSearchControl    |
	 * | selection | FormSelectionControl |
	 * | submit    | FormSubmitControl    |
	 * | text      | FormTextControl      |
	 * | textarea  | FormTextareaControl  |
	 * | time      | FormTimeControl      |
	 * | url       | FormURLControl       |
	 * | username  | FormUsernameControl  |
	 * |-----------|----------------------|
	 *
	 * @param string $fcn Method name
	 * @param array $args Method arguments
	 * @return object A FormControl object
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
	 * Get submitted form value.
	 *
	 * @param string $name Field name
	 * @return string Submitted field value (from $_POST), trimmed
	 */
	public function __get ($name) {
		if (!isset($this->fld[$name]))
			throw new UsageException("Invalid field name: $name");
		return $this->fld[$name]->data();
	}

	/**
	 * Produce HTML code with class attribute.
	 *
	 * Produces the HTML code for the form with the class attribute specified
	 * on the FORM tag. Provided for convience, this simply calls Form::html
	 * to produce the code.
	 *
	 * @param string $class Class name
	 * @return string HTML code
	 */
	public function __invoke ($class='') { return $this->html($class); }

	/**
	 * Produce HTML code for the form.
	 *
	 * Called when the object is printed, produces the HTML code for the
	 * form. This function simply calls Form::html.
	 *
	 * @return string HTML code
	 */
	public function __toString () { return $this->html(); }

	/**
	 * Get named form control object.
	 *
	 * Retrieves the form control object with the given name. If no control
	 * with the given name exists, returns false.
	 *
	 * @param string $name Field name
	 * @return mixed FormControl object or false
	 */
	public function ctrl ($name)
		{ return isset($this->fld[$name]) ? $this->fld[$name] : false; }

	/**
	 * Produce HTML code for the form.
	 *
	 * Produces the HTML code for the form, with an optional class to be
	 * added to the FORM tag.
	 *
	 * @param string $class Class name, optional
	 * @return string HTML code
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
	 * Produce HTML code for form tag.
	 *
	 * Produces the HTML code for the opening form tag, with an optional
	 * class to be added.
	 *
	 * Typically called by an application when it is undesirable to use the
	 * default form HTML code. Content generators that need full control over
	 * how the form is produced should use this function to generate the
	 * form tag.
	 *
	 * @param string $class Class name, optional
	 * @return string HTML code
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
	 * Produce HTML code for form end tag.
	 *
	 * Produce the HTML FORM end tag, for use in conjunction with function
	 * Form::html_form.
	 *
	 * @return string HTML code
	 */
	public function html_form_end () { return '</form>'; }

	// Set or clear form to submit via Ajax (deprecated)
	//
	// Indicates whether or not form is submitted via Ajax. Default is to
	// submit via Ajax, if FST jQuery module is used. Default value for $ajax
	// is true for compatibility with FST version 4.
	// @param bool $ajax Submit form via Ajax flag (default true)
	/** @ignore */
	public function ajax ($ajax=true) { $this->ajax = $ajax; }

	/**
	 * Set form attribute.
	 *
	 * Sets an attribute to be included in the HTML form tag. Care must be
	 * taken so as to not inadvertently override attributes that
	 * are managed by the Form class itself.
	 *
	 * @param string $name Attribute name
	 * @param string $value Attribute value
	 */
	public function attr ($name, $value) { $this->attr[$name] = $value; }

	/**
	 * Get submitted form data.
	 *
	 * Returns an associative array of name/value pairs of the submitted form
	 * data. If $fields is supplied, it is expected to be an array of field
	 * names, and the returned array will contain only fields that are named
	 * in that array. If $fields is not supplied, the returned array will
	 * contain all fields for which controls are defined except any that
	 * are indicated as informational.
	 *
	 * @param array $fields Array of field names (optional, or false)
	 * @return array Name/value pairs of submitted form values
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
	 * Set error message for the given field.
	 *
	 * Sets an error message for a given field. This is to be called
	 * by the application when performing form validation.
	 *
	 * @param string $name Field name
	 * @param string $message Error message text
	 */
	public function error ($name, $message) {
		if (!isset($this->fld[$name]))
			throw new UsageException("Invalid field name: $name");
		$this->error[$name] = $message;
	}

	/**
	 * Get number of errors in form.
	 *
	 * Returns the number of fields that have error messages associated with
	 * them. Fields do not have errors associated with them until after
	 * form validation occurs.
	 *
	 * @return int Error count
	 */
	public function error_count () { return count($this->error); }

	/**
	 * Get form errors.
	 *
	 * Returns an associative array of error messages for the form. The
	 * array is indexed by field name.
	 *
	 * @return array Name/value pairs of field names and error messages
	 */
	public function errors () { return $this->error; }

	/**
	 * Get form id.
	 *
	 * @return string Form id
	 */
	public function id () { return $this->attr['id']; }

	/**
	 * Set initial form values.
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
	 *
	 * @param mixed $data Array or object for initializing form values
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

	/**
	 * Sets all form elements to read-only.
	 */
	public function readonly () { foreach ($this->fld as $f) $f->readonly(); }

	/**
	 * Perform validation on submitted form data.
	 *
	 * This calls the error function for each form control. Each form control
	 * should return a string if there is a validation error for the given
	 * control. If the form submission was canceled, this function performs
	 * error validation only on the submit control.
	 *
	 * @return bool Validation success flag
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
