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
 * FST Controller, base class.
 *
 * A page controller must be derived from this class and must override
 * the *init* method. A controller may also set member variables *template*
 * and *title* to indicate the page template and page title respectively.
 */
abstract class Controller {

	/**
	 * Template name.
	 *
	 * This is used by the Template Engine during page generation. Controllers
	 * derived from this class should set this variable as appropriate if
	 * a template other than "default" should be used.
	 */
	public $template = "default";

	/**
	 * Page title.
	 *
	 * This is used by the Tempalte Engine for the page title. Also, content
	 * areas may use this in output. Controllers derived from this class
	 * should set this variable as appropriate.
	 */
	public $title = "FST Framework Application";

	// Registered forms for use with the form processor.
	// This is used by the Controller class for processing forms. Forms are
	// registered using the "form" method. The Controller class provides
	// automatically-called methods for initializing, validating, and
	// processing forms.
	/** @ignore */
	private $_forms = array();

	/**
	 * Perform controller initialization.
	 *
	 * This is called by the framework to initialize the controller after
	 * its instantiation. Derived classes must override this method.
	 */
	abstract public function init ();

	/**
	 * Get the controller action.
	 *
	 * The action is passed on the URI as the first $_GET argument name.
	 *
	 * @return string Action name, or false if none defined
	 */
	final protected function action () { return Framework::action(); }

	/**
	 * Get positional controller argument.
	 *
	 * Controller arguments are passed in the URI, preceeding $_GET arguments
	 * if any are defined. Controller arguments are separated by slashes.
	 * If the framework configuration parameter 'shift' is set, this vector
	 * is adjusted accordingly.
	 *
	 * @todo Fix the description, not correct for position argument
	 * @param int $idx Index of the controller argument
	 * @return string Argument value, or null if no argument
	 */
	final protected function arg ($idx) { return Framework::arg($idx); }

	/**
	 * @brief Get controller argument string.
	 *
	 * The controller argument string is the path portion of the URI
	 * without the leading slash, and compensated for shifting.
	 *
	 * @return string Argument value
	 */
	final protected function args () { return Framework::args(); }

	/**
	 * Get controller argument vector.
	 *
	 * Gets the controller argument string split out into its individual
	 * components. If arguments have been shifted, the number of shifted
	 * arguments are removed from the vector.
	 * 
	 * @return string[] Array of controller argument strings
	 */
	final protected function argv () { return Framework::argv(); }

	/**
	 * @brief Get controller name.
	 * 
	 * @return string Name of the current controller
	 */
	final public function ctrl () { return Framework::ctrlname(); }

	/**
	 * Creates a form and registers it for use with the form handler.
	 *
	 * Creates a form to be used with the controller's form handler. The
	 * handler name is used to determine controller methods that will be
	 * automatically called by the form handler. The ID is used as both the
	 * ID in the HTML DOM (when output as content) and as the member variable
	 * name within the controller. If an ID is not supplied, the handler name
	 * is used.
	 * 
	 * @param string $name Form handler name
	 * @param string $id Form ID
	 * @param string $class Form class
	 * @return Form The Form object
	 */
	final protected function form ($name, $id=false, $class='FST\Form') {

		if (!preg_match('/^[a-z]\w*$/i', $name))
			throw new UsageException("Invalid form name: $name");
		if ($id && !preg_match('/^[a-z]\w*$/i', $id))
			throw new UsageException("Invalid form id: $id");
		if (!class_exists($class))
			throw new UsageException("Form class not found: $class");

		if (!$id) $id = $name;

		$this->$id = $this->_forms[$name] = new $class($id, "_form=$name");
		if (!is_a($this->$id, 'FST\Form'))
			throw new UsageException(
				"Class $class is not derived from FST\\Form");

		$method = "form_$name";
		if (method_exists($this, $method))
			$this->$method($this->$id);

		return $this->$id;
	}

	/**
	 * Get the server's host name.
	 * 
	 * @return string Host name
	 */
	final protected function host () { return $_SERVER['SERVER_NAME']; }

	/**
	 * Test if controller running as an Ajax call.
	 * 
	 * @return bool Is running as Ajax flag
	 */
	final protected function is_ajax () {
		return Framework::config('ajax') &&
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * Test if controller is processing (non-Ajax) POST data.
	 * 
	 * @return bool Is processing POST data flag
	 */
	final protected function is_post ()
		{ return !$this->is_ajax() && $_SERVER['REQUEST_METHOD'] == 'POST'; }

	/**
	 * Test if running on the SSL port.
	 * 
	 * @return bool Is running on port 443 (standard SSL port) flag
	 * 
	 * @deprecated
	 */
	final protected function is_ssl ()
		{ return $_SERVER['SERVER_PORT'] == 443; }

	/**
	 * Exit with a 404 Not Found header.
	 *
	 * Sends a 404 Not Found header to the client and exits. This may be
	 * called from the controller's init function to indicate that the
	 * resource requested was not found. The end user will experience
	 * a 404 error, and no output from the application.
	 */
	protected function notfound () { Framework::header_404(); }

	/**
	 * Redirect to another controller or web page.
	 *
	 * Sends header to change the location to the given URI. If the URI is
	 * absolute or includes a protocol, it is used as-is. Otherwise, it is
	 * converted to an absolute URI via the uri function.
	 *
	 * The controller exits immediately after sending the header.
	 *
	 * If an Ajax call, no redirection is done (as that is the responsibility
	 * of the client-side code). The controller, however, still exits.
	 * 
	 * @param string $uri Controller argument string or URI
	 */
	protected function redirect ($uri='')
	{
		if (!$this->is_ajax())
			header('Location: ' . Framework::uri($uri));
		exit;
	}

	/**
	 * Reloads the current controller.
	 *
	 * Issues a redirect to reload the current controller. The controller
	 * is reloaded with its current arguments, but without any GET or POST
	 * data.
	 *
	 * Note that if an Ajax call is in progress, the controller exits upon
	 * calling this function (as is the behavior of redirect).
	 */
	protected function reload () { $this->redirect($this->args()); }

	// Invoke the Ajax handler specified by the action (final).
	// This is called by the framework when the controller is instantiated
	// via an Ajax call. This invokes the appropriate Ajax handler as
	// determined by the action.
	/** @ignore */
	final public function _invoke_ajax_handler () {
		$handler = method_exists($this, "ajax_{$this->action()}") ?
			"ajax_{$this->action()}" :
			(method_exists($this, 'ajax') ? 'ajax' : null);
		if ($handler) {
			header('Content-type: application/json');
			print json_encode($this->$handler());
			exit;
		}
	}

	// Invoke the content preprocessor (final).
	// This is called by the framework just prior to rendering content in
	// a template. This may be called in page generation or Ajax context.
	/** @ignore */
	final public function _invoke_content_preprocessor ($content=null) {
		$method = $content ? "content_$content" : 'content';
		return method_exists($this, $method) ? $this->$method() : null;
	}

	// Invoke the form handler.
	//
	// This is called by the framework when a form is submitted via Ajax or
	// POST using "_form" as its action.
	/** @ignore */
	final public function _invoke_form_handler () {

		// Get name of form handler
		$name = $_GET['_form'];

		// Get the form
		if (!isset($this->_forms[$name]))
			throw new UsageException("Form not defined: $name");
		$form = $this->_forms[$name];

		// Validate form. First, calls validate method on form which validates
		//	according to control rules. Second, if valid according to default
		//	control rules and the controller defines a validation method for
		//	the form, call it. If that method returns a value, the form is
		//	valid that value is true. If, however, that method does not return
		//	a value (return value is null), successful validation is determined
		//	by the error count on the form (this behavior is new in version
		//	5.1).
		$method = "form_{$name}_validate";
//		$valid = $form->validate() &&
//			(!method_exists($this, $method) || $this->$method($form));
		$valid = $form->validate() ?
			(method_exists($this, $method) ? $this->$method($form) : true) :
			false;
		if ($valid === null)
			$valid = !$form->error_count();

		// Successful form submission...
		if ($valid) {
			$method = "form_{$name}_submit";
			return array(
					'name'=>$name,
					'valid'=>true,
					'errors'=>false,
					'data'=>method_exists($this, $method) ?
						$this->$method($form) : null
				);
		}

		// Failed form submission...
		$method = "form_{$name}_submit_fail";
		return array(
				'name'=>$name,
				'valid'=>false,
				'errors'=>$form->errors(),
				'data'=>method_exists($this, $method) ?
					$this->$method($form) : null
			);
	}

	// Invoke the page preprocessor (final).
	// This is called by the framework just prior to generating a page. This
	// will only be called in page generation context.
	/** @ignore */
	final public function _invoke_page_preprocessor () {
		//{ if (method_exists($this, 'page')) $this->page(); }
		$preprocessor = method_exists($this, 'page') ? 'page' : null;
		if ($preprocessor) $this->$preprocessor();
	}

	// @brief Invoke the POST handler.
	//
	// This calls the appropriate POST handler when data is sent to the
	// controller in a non-Ajax call.
	/** @ignore */
	final public function _invoke_post_handler () {
		$handler = method_exists($this, "post_{$this->action()}") ?
			"post_{$this->action()}" :
			(method_exists($this, 'post') ? 'post' : null);
		if ($handler) $this->$handler();
	}

	// @brief Ajax handler for producing dynamic content (final).
	/** @ignore */
	final public function ajax__content () {

		// Get name of content area
		$name = isset($_POST['_content']) ? $_POST['_content'] : null;

		// Invoke content preprocessor
		$pre = $this->_invoke_content_preprocessor($name);

		// Produce requested content based on preprocessor result
		if ($pre === null) { // Default content file
			$fname = Framework::config('content') . '/' .
				Framework::ctrlname() . ($name ? "_$name.php" : '.php');
			ob_start();
			Framework::content($fname);
			$content = ob_get_clean();
		}
		else if (is_string($pre)) { // Named content file
			$fname = Framework::config('content') . '/' . "$pre.php";
			ob_start();
			Framework::content($fname);
			$content = ob_get_clean();
		}
		else if (is_object($pre)) // Object
			$content = "$pre";
		else // No content
			$content = null;

		return "$content";
	}

	// Ajax handler for processing registered forms (final).
	/** @ignore */
	final public function ajax__form ()
		{ return $this->_invoke_form_handler(); }

	// Post handler for processing registered forms (final).
	/** @ignore */
	final public function post__form () { $this->_invoke_form_handler(); }
}
