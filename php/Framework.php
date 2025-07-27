<?php

// FST Application Framework, Version 6.0.1
// Copyright (c) 2004-25, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Framework initialization and request processing.
 *
 * Initializes and drives the FST Application Framework. An application does
 * not explicitly create an object of this type but rather calls its static
 * method *init* to launch initialization and request processing.
 */
class Framework {

	/** @ignore */
	protected static $_fst = null;	// Framework instance

	/** @ignore */
	protected static $_ctrl;		// Controller object
	/** @ignore */
	protected static $_ctrlname;	// Controller name

	/** @ignore */
	protected static $_args;	// Controller argument string
	/** @ignore */
	protected static $_argv;	// Controller argument array
	/** @ignore */
	protected static $_cfg;		// Controller configuration

	/** @ignore */
	protected static $_action;	// Controller action

	/** @ignore */
	protected static $_env;		// App environment variables

	// FST version constants
	/** FST version number. */
	const VERSION = '6.1-alpha';
	/** FST copyright dates */
	const VERSION_COPYRIGHT = '2004-25';
	/** FST version release date */
	const VERSION_RELEASE = '2025-07-27';

	// For control of FST copyright comment in HTML output
	/** Default FST copyright output location. */
	const COPYRIGHT_STD = 1;
	/** Place FST copyright in HEAD section. */
	const COPYRIGHT_HEAD = 0;
	/** Place FST copyright at end of HTML. */
	const COPYRIGHT_END = 1;
	/** No FST copyright notice. */
	const COPYRIGHT_NONE = 2;
	/** No FST copyright notice or META tag. */
	const COPYRIGHT_STEALTH = 3;

	/**
	 * Get controller action.
	 *
	 * Gets the action for the current request. The "action" is the first
	 * GET parameter passed on the URL (i.e. the name of the first name/value
	 * pair following the "?"). This is typically used during form submissions
	 * and Ajax requests.
	 *
	 * @return string Controller action
	 */
	public static function action () { return self::$_action; }

	/**
	 * Get controller argument by index.
	 *
	 * FST controller arguments are given in the URL and are separated by
	 * slashes. For example, if the requested resource is "/account/14/edit",
	 * argument 0 is "account", argument 1 is "14", and argument 2 is "edit".
	 * The given index determines which argument value is returned. If no
	 * argument exists for the given index, an empty string is returned.
	 *
	 * @param int $idx Zero-based index
	 * @return string Controller argument value
	 */
	public static function arg ($idx)
		{ return isset(self::$_argv[$idx]) ? self::$_argv[$idx] : ''; }

	/**
	 * Get controller argument count.
	 *
	 * @return int Number of controller arguments
	 */
	public static function argc () { return count(self::$_argv); }

	/**
	 * Get controller argument string.
	 *
	 * Returns a string containing all controller arguments separated by
	 * slashes. The string does not contain a leading slash. The string
	 * represents the URI, less $_GET parameters, relative to the application
	 * root as specified during FST initialization. For example, if the
	 * application root is specified as "/myapp/", and the URI given is
	 * "/myapp/account/15/edit?_form=account_form", this function returns
	 * "account/15/edit". The return value of this function is suitable
	 * for usage in redirects initiated by Controller::redirect.
	 *
	 * @return string Argument URI (relative to application root)
	 */
	public static function args () { return implode('/', self::$_argv); }

	/**
	 * Get controller argument array.
	 *
	 * Returns an array of the controller arguments, relative to the
	 * application root.
	 *
	 * @return string[] Controller arguments
	 */
	public static function argv () { return self::$_argv; }

	/**
	 * Get (or set) framework configuration option.
	 *
	 * If $value is not given, returns a configuration option. If $value is
	 * given, sets the given configuration option. Typically, this function
	 * is used to query options given at the time the framework was
	 * initialized via Framework::init. However, this function may be called
	 * by a Controller class to override an option that was set during
	 * framework initialization.
	 * 
	 * @param string $opt Option name
	 * @param mixed $value Set value for option (optional)
	 * @return mixed Option value
	 */
	public static function config ($opt, $value=null) {
		if (isset($value))
			self::$_cfg[$opt] = $value;
		return isset(self::$_cfg[$opt]) ? self::$_cfg[$opt] : null;
	}

	/**
	 * Output content from content file.
	 *
	 * Produces content from a named content file. Public member variables of
	 * the Controller class handling the request are set prior to executing
	 * the contents of the given file; i.e. all such member variables are
	 * available as variables in the content file.
	 *
	 * The file name must include relative path from the root.
	 * 
	 * @param string $fname File name
	 */
	public static function content ($fname) {
		extract(get_object_vars(self::ctrl()));
		require $fname;
	}

	/**
	 * Get controller object.
	 *
	 * Gets the current controller object. This is an object that is derived
	 * from class Controller and represents the controller object that is
	 * handling the current request.
	 * 
	 * @return Controller Current Controller object
	 */
	public static function ctrl () { return self::$_ctrl; }

	/**
	 * Get controller name.
	 *
	 * Gets the name of the current controller. For example, if the
	 * Controller derived class is named "account_controller", this function
	 * returns "account". (Note that all controller classes used by the
	 * framework have names ending in "_controller".)
	 *
	 * @return string Current controller name
	 */
	public static function ctrlname () { return self::$_ctrlname; }

	/**
	 * Get environment variable value.
	 * 
	 * Gets the value associated with the given environment variable.
	 * Environment variables may be those defined by the shell, or those
	 * defined for the application via an environment file.
	 * 
	 * If $var is not supplied, an array of environment variables is
	 * returned.
	 *
	 * @param string $var Environment variable name (optional)
	 * @return string|string[] Environment variable value
	 */
	public static function env ($var=null) {
		return $var ? (isset(self::$_env[$var]) ? self::$_env[$var] : null) : self::$_env;
	}

	// FST modules will call this function for usage errors. It is not
	//	intended to be user-callable.
	// This function attempts to report the file and line number where an FST
	//	user error occurred. It does this by going through the backtrace to
	//	find the first occurrence of a source file that is not part of the
	//	FST system directory (or the same directory as this source file).
	/** @ignore */
	public static function error ($msg) {
		if (self::config('debug')) {
			$fstdir = dirname(__FILE__);
			foreach (debug_backtrace() as $trace) {
				if (isset($trace['file']) &&
						dirname($trace['file']) != $fstdir) {
					$file = $trace['file'];
					$line = $trace['line'];
					break;
				}
			}
		}
		$msg = isset($file, $line) ?
			"FST Error in $file at line $line: $msg" :
			"FST Error: $msg";
		error_log($msg);
		if (self::config('debug'))
			print $msg;
		exit;
	}

	/**
	 * Send 404 Not Found header.
	 *
	 * Sends a 404 Not Found header to the client and exits.
	 */
	public static function header_404 () {
		function_exists('http_response_code') ?
			http_response_code(404) :
			header('HTTP/1.0 404 Not Found', true, 404);
			print '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
			print '<html><head>';
			print '<title>404 Not Found</title>';
			print '</head><body>';
			print '<h1>Not Found</h1>';
			print '<p>The requested URL /' . self::args() .
				' was not found on this server.</p>';
			print '<hr>';
			print '<address>Server at ' . $_SERVER['SERVER_NAME'] .
				' Port ' . $_SERVER['SERVER_PORT'] . '</address>';
			print '</body></html>';
		exit;
	}

	/** 
	 * Convert relative URI to absolute URI.
	 *
	 * Converts a relative URL to an absoute URI, considering the base URI
	 * of the application. For example, if the application root is specified
	 * as "/myapp/" and this function is given "account/15/edit", this
	 * function returns "/myapp/account/15/edit".
	 *
	 * Note that if this function is given a URL that includes the protocol
	 * or leads with a slash, the given URL is considered to be an absolute
	 * URL and is simply returned.
	 *
	 * @param string $uri Relative URI
	 * @return string Absolute URI
	 */
	public static function uri ($uri=null) {
		if (!$uri)
			return self::config('root');
		return preg_match('"^(\w+:|/|\?|#)"', $uri) ?
			$uri : self::config('root') . $uri;
	}

	/**
	 * Initialize the framework and carry out the controller action.
	 *
	 * Initializes the framework and carries out the action appropriate to
	 * the request. Such action may involve page generation, form processing,
	 * or an Ajax request handler. An associative array may be given to set
	 * configuration settings, thus overriding the default settings.
	 *
	 * Settings are provided as an associative array. Default options are
	 * as follows:
	 *	- ajax: "true" Handle Ajax requests
	 *	- class: "class" Relative path or array of relative paths to the
	 *		class directory
	 *	- content: "content" Relative path to the content directory
	 *	- controllers: "array()" Controller map
	 *	- copyright: "COPYRIGHT_STD" Control of FST copyright comments
	 *	- debug: "false" FST Debug Mode
	 *	- default: "true" Enable default controller selection
	 *	- env: ".env" Environment file, or array of files, or false
	 *	- home: "home" Controller name for home page
	 *	- inc: "inc" Relative path to the include directory
	 *	- lib: "lib" Relative path or array of paths to the library directory
	 *	- root: "/" The application root (include leading and trailing slash)
	 *	- session: "false" Session name, or false if no session
	 *	- template: "template" Relative path to the template directory
	 *
	 * @param array $cfg Configuration settings
	 */
	public static function init ($cfg = array()) {

		try {

			if (!is_null(self::$_fst))
				throw new UsageException('Framework is already initialized');
			if (!is_array($cfg))
				throw new UsageException(
					'Framework configuration parameter is not an array');

			self::$_cfg = array_merge(
				array(

					'root'=>'/', // Application root, including trailing slash

					// Application directories, no trailing slash
					'app'=>'app', // Controller directory
					'class'=>'class',
						// Directory or array of directories for autoload
						//	classes, or false
					'content'=>'content', // Directory for content includes
					'inc'=>'inc', // Directory for template includes
					'lib'=>'lib',
						// Directory or array of directories for library
						//	includes, or false
					'template'=>'template', // Directory for templates

					// Application options
					'ajax'=>true, // Handle Ajax requests
					'controllers'=>array(), // Controller map
					'copyright'=>self::COPYRIGHT_STD, // FST Copyright comments
					'default'=>true, // Enable default controller selection
					'debug'=>null, // From .env file by default
					'debug_error_reporting'=>E_ALL,
						// Error reporting level, when debug is set
					'env'=>false, // Environment file, array of files, or false
					'helpers'=>true, // Define helper functions
					'home'=>'home', // Controller name for home page
					'meta-content-type'=>true,
						// Generate default meta content-type tag
					'meta-viewport'=>true,
						// Generate default meta viewport tag
					'timezone'=>false, // Timezone, or false for system default
					'session'=>false, // session name, false for no session
				),
				$cfg);

			self::$_fst = new self();
		}
		catch (UsageException $e) {
			if (self::config('debug')) {
				$fstdir = dirname(__FILE__);
				foreach ($e->getTrace() as $trace) {
					if (isset($trace['file']) &&
							dirname($trace['file']) != $fstdir) {
						$file = $trace['file'];
						$line = $trace['line'];
						break;
					}
				}
			}
			$msg = isset($file, $line) ?
				"FST Error in $file at line $line: {$e->getMessage()}" :
				"FST Error: {$e->getMessage()}";
			error_log($msg);
			if (self::config('debug'))
				print $msg;
		}
	}

	/**
	 * Convert name/value pairs to HTML attribute string.
	 *
	 * Converts an associative array into a string that may be output in
	 * HTML attribute form. This string may be output immediately after
	 * the HTML tag (the string returned includes a leading space, except
	 * if the string is empty).
	 * 
	 * @param array $attr Attribute name/value pairs
	 * @return string HTML-formatted attribute string
	 */
	public static function attr ($attr) {
		$str = '';
		foreach ($attr as $k=>$v)
			$str .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
		return $str;
	}

	// Constructor, framework driver.
	/** @ignore */
	protected function __construct () {

		// Load environment variables
		self::$_env = $_ENV;
		if (self::config('env'))
			foreach (is_array(self::config('env')) ? self::config('env') : [ self::config('env') ] as $env) {
				if (($env_settings = parse_ini_file($env, true)) === false)
					throw new UsageException("Error processing environment file $env");
				self::$_env = array_merge(self::$_env, $env_settings);
			}

		// If 'debug' option not set in FST config, use DEBUG from .env file if set
		if (self::config('debug') === null && isset(self::$_env['DEBUG']))
			self::config('debug', self::$_env['DEBUG']);

		// Set error reporting, if debug mode is set
		if (self::config('debug')) {
			error_reporting(self::config('debug_error_reporting'));
			ini_set('display_errors', 1);
		}

		// Register form control methods
		require 'fst-framework.php';

		// Define helper functions if configured
		if (self::config('helpers'))
			require 'fst-functions.php';

		// Set timezone, if configured
		if (self::config('timezone'))
			date_default_timezone_set(self::config('timezone'));

		// Start session, if required
		if (self::config('session')) {
			session_name(self::config('session'));
			session_start();
		}

		// Set up application autoload function
		spl_autoload_register(function ($cls) {
			if (substr($cls, -11) == '_controller') {
				$ctrl_fname = self::config('app') . '/' . $cls . '.php';
				if (file_exists($ctrl_fname)) {
					require $ctrl_fname;
					return;
				}
			}
			static $autoload_directories = null;
			if ($autoload_directories === null)
				$autoload_directories =
					($autoload_config = self::config('class')) ?
						(is_array($autoload_config) ?
							$autoload_config : array($autoload_config)) :
						array();
			foreach ($autoload_directories as $d)
				if (file_exists("$d/$cls.php")) {
					require "$d/$cls.php";
					return;
				}
		});

		// Application include libraries
		if (self::config('lib'))
			foreach (is_array(self::config('lib')) ?
					self::config('lib') : array(self::config('lib')) as $d)
				foreach (glob("$d/*.php") as $f)
					require $f;

		// Get controller arguments from URI
		self::$_args = preg_replace(
			array("'^" . self::config('root') . "'", '/\?.*$/'),
			'', $_SERVER['REQUEST_URI']);
		self::$_argv = explode('/', self::$_args);

		// Get controller action, if specified (from QUERY_STRING)
		list(self::$_action) = count($_GET) ? array_keys($_GET) : array(false);

		// Determine the controller name (first match in controllers config)
		self::$_ctrlname = false;
		foreach (self::config('controllers') as $k=>$v)
			if (!self::$_ctrlname && preg_match("'$v'", self::args()))
				self::$_ctrlname = $k;

		// If no controller match, check home and default options
		if (!self::$_ctrlname) {
			if (!self::arg(0) && self::config('home'))
				self::$_ctrlname = self::config('home');
			else if (self::config('default') &&
					preg_match('/^[a-z][\w\-]*$/i', self::arg(0)))
				self::$_ctrlname = lcfirst(implode('', array_map('ucfirst',
					explode('-', strtolower(self::arg(0))))));
		}

		// If still no match, no controller is configured
		if (!self::$_ctrlname) {
			if (self::config('debug'))
				throw new UsageException(
					"No controller configured for '" . self::args() . "'");
			self::header_404();
		}

		// Instantiate the controller
		$ctrlclass = self::$_ctrlname . '_controller';
		if (!class_exists($ctrlclass)) {
			if (self::config('debug'))
				throw new UsageException(
					"Controller class not found: $ctrlclass");
			self::header_404();
		}
		$rc = new \ReflectionClass($ctrlclass);
		if ($rc->isAbstract()) {
			if (self::config('debug'))
				throw new UsageException(
					"Controller class is abstract: $ctrlclass");
			self::header_404();
		}
		self::$_ctrl = new $ctrlclass();
		if (!is_a(self::$_ctrl, 'FST\Controller'))
			throw new UsageException(
				"Class $ctrlclass is not derived from FST\\Controller");

		// Build list of traits used by this controller and any of its
		//	parent controllers.
		$class = $ctrlclass;
		$traits = array();
		while ($class) {
			$traits = array_merge(class_uses($class), $traits);
			$class = get_parent_class($class);
		}

		// Initialize any traits used by the controller. Trait initialization
		//	methods are the same as the trait followed by '_init'.
		foreach ($traits as $trait) {
			$method = "{$trait}_init";
			if (method_exists(self::ctrl(), $method))
				self::ctrl()->$method();
		}

		// Initialize the controller. First, call the controller's init
		//	method (which virtual abstract in FST\Controller). Then, call
		//	post data initialization methods for all post names that are
		//	given (post names beginning with an underscore are ignored).
		self::ctrl()->init();
		foreach ($_POST as $k=>$v) {
			$method = "init_$k";
			if ($k[0] != '_' && method_exists(self::ctrl(), $method))
				self::ctrl()->$method($v);
		}

		// If an Ajax request, invoke the Ajax handler and exit.
		if (self::config('ajax') &&
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			self::ctrl()->_invoke_ajax_handler();
			exit;
		} 

		// If a (non-Ajax) POST request, invoke the POST handler (no exit).
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
			self::ctrl()->_invoke_post_handler();

		// Invoke the page pre-processor.
		self::ctrl()->_invoke_page_preprocessor();

		// Generate the page.
		if (self::ctrl()->template)
			$this->generate_page();

		exit;
	}

	/** @ignore */
	protected $parser;

	/** @ignore */
	protected $stack = array();		// Parser element stack
	/** @ignore */
	protected $head = false;		// HEAD element encountered
	/** @ignore */
	protected $body = false;		// BODY element encountered

	/** @ignore */
	protected function generate_page () {

		// Get the template.
		$fname = self::config('template') . '/' . self::ctrl()->template;
		if (file_exists("$fname.xml.php")) {
			ob_start();
			$fname = "$fname.xml.php";
			self::content($fname);
			$template = explode("\n", ob_get_clean());
		}
		else if (file_exists("$fname.php")) { // DEPRECATED
			// Logic same as filename format above. Extension .xml.php is
			//	now preferred for PHP-generated templates.
			ob_start();
			$fname = "$fname.php";
			self::content($fname);
			$template = explode("\n", ob_get_clean());
		}
		else if (file_exists("$fname.xml")) {
			$fname = "$fname.xml";
			$template = file($fname, FILE_IGNORE_NEW_LINES);
		}
		else
			throw new UsageException(
				"Template '" . self::ctrl()->template . "' not found");

		// Output HTML5 DOCTYPE
		print "<!DOCTYPE HTML>\n";
		if (self::config('debug')) {
			print '<!-- Controller: ' . self::ctrlname() . " -->\n";
			print "<!-- FST Config: ";
			print_r(self::$_cfg);
			print "-->\n";
			print "<!-- Template: $fname\n";
			$ln = 1;
			foreach ($template as $line)
				printf("%4d: %s\n", $ln++, str_replace(
					array('<!--', '-->'), array('<! -', '- >'), $line));
			print "-->\n";
		}

		// Parse the template.
		$this->parser = xml_parser_create();
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_character_data_handler($this->parser, array(&$this, 'data'));
		xml_set_element_handler($this->parser,
			array(&$this, 'element'), array(&$this, 'element_end'));

		try {
			$ln = 0;
			$cnt = count($template);
			foreach ($template as $line)
				if (!xml_parse($this->parser, "$line\n", ++$ln == $cnt))
					throw new TemplateException(
						xml_error_string(xml_get_error_code($this->parser)));
		}
		catch (TemplateException $e) {
			$line = xml_get_current_line_number($this->parser);
			$msg = "FST Error in " .
				realpath($fname) . " at line $line: {$e->getMessage()}";
			error_log($msg);
			if (self::config('debug'))
				print $msg;
			exit;
		}

		xml_parser_free($this->parser);
	}

	/** @ignore */
	protected function data ($p, $data) {
		$tag = end($this->stack);
		switch ($tag) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				print $data;
				break;
			case 'script':
				if (!ctype_space($data))
					throw new TemplateException(
						'Embedded scripts not allowed');
			default:
				if (!ctype_space($data))
					throw new TemplateException(
						'Element data not allowed in <' .
							end($this->stack) . '>');
		}
	}

	/** @ignore */
	protected function element ($p, $tag, $attr) {

		if (!count($this->stack) && $tag != 'html')
			throw new TagException($tag);

		array_push($this->stack, $tag);

		switch ($tag) {

		case 'html':
			if (count($this->stack) != 1)
				throw new TagException($tag);

			// If PRE attribute, include named file prior to HTML tag
			if (array_key_exists('pre-inc', $attr)) {
				self::content(
					self::config('inc') . '/' . $attr['pre-inc'] . '.php');
				unset($attr['pre-inc']);
			}

			print '<html' . self::attr($attr) . ">\n";
			break;

		case 'head':
			if (count($this->stack) != 2 || $this->head || $this->body)
				throw new TagException($tag);
			$this->head = true;

			print "<head" . self::attr($attr) . ">\n";
			if (self::config('copyright') == self::COPYRIGHT_HEAD)
				$this->copyright();
			print "<title>" . htmlentities(self::ctrl()->title) . "</title>\n";
			if (self::config('copyright') != self::COPYRIGHT_STEALTH)
				print '<meta name="generator" ' .
					'content="FST Application Framework, Ver. ' .
					self::VERSION . ' (' . self::VERSION_RELEASE .
					')" />' . "\n";
			if (self::config('meta-content-type'))
				print '<meta http-equiv="Content-type" ' .
					'content="text/html;charset=UTF-8" />' . "\n";
			if (self::config('meta-viewport'))
				print '<meta name="viewport" ' .
					'content="width=device-width, initial-scale=1.0" />' .
					"\n";
			print '<script>var _approot = "' .
				self::config('root') . '";</script>' . "\n";
			break;

		case 'body':
			if (count($this->stack) != 2 || $this->body)
				throw new TagException($tag);
			if (!$this->head) {
				array_pop($this->stack);
				$this->element($p, 'head', array());
				$this->element_end($p, 'head');
				array_push($this->stack, 'body');
			}
			$this->body = true;
			$attr['data-fst'] = 'ctrl-' . self::ctrlname();
			print "<body" . self::attr($attr) . '>';
			break;

		case 'content':
			if (count($this->stack) < 3 || $this->stack[1] != 'body')
				throw new TagException($tag);

			// Optional 'tag' specified tag for wrapper element
			if (isset($attr['tag'])) {
				$tag = $attr['tag'];
				unset($attr['tag']);
			}
			else
				$tag = 'div';

			// Optional 'name' determines content area id and default file
			if (isset($attr['name'])) {
				$fname = self::ctrlname() . "_{$attr['name']}.php";
				$attr['data-fst'] = "content-{$attr['name']}";
			}
			else {
				$fname = self::ctrlname() . '.php';
				$attr['data-fst'] = "content";
			}

			// Invoke content pre-processor
			if (isset($attr['name'])) {
				if (self::config('debug')) $name = $attr['name'];
				$content = self::ctrl()
					->_invoke_content_preprocessor($attr['name']);
				unset($attr['name']);
			}
			else {
				if (self::config('debug')) $name = '[default]';
				$content = self::ctrl()->_invoke_content_preprocessor();
			}

			if (self::config('debug'))
				print "<!-- Content begin: $name -->";

			// Produce content based on return from pre-processor
			print "<$tag" . self::attr($attr) . '>';
			if ($content === false) { // No content
				if (self::config('debug'))
					print '<!-- No content -->';
			}
			else if ($content === null || $content === true) {
				// Default content file
				if (self::config('debug'))
					print "<!-- File: $fname -->";
				$fname = realpath(self::config('content')) . '/' . $fname;
				if (!file_exists($fname))
					throw new ContentException($fname);
				self::content($fname);
			}
			else if (is_object($content)) { // Object to string
				if (!method_exists($content, '__toString'))
					throw new TemplateException(
						'Content object does not implement __toString');
				if (self::config('debug'))
					print "<!-- Object -->";
				print $content->__toString();
			}
			else if (!is_string($content)) // Invalid return type
				throw new TemplateException(
					'Content type ' . gettype($content) . ' is not valid');
			else if (substr($content, 0, 1) == '<') { // HTML string
				if (self::config('debug'))
					print "<!-- HTML -->";
				print $content;
			}
			else { // Named content file
				if (self::config('debug'))
					print "<!-- File: $content.php -->";
				$fname = realpath(self::config('content')) .
					'/' . $content . '.php';
				if (!file_exists($fname))
					throw new ContentException($fname);
				self::content($fname);
			}

			print "</$tag>";
			if (self::config('debug'))
				print "<!-- Content end: $name -->";
			break;

		case 'inc':
			if (count($this->stack) < 3)
				throw new TagException($tag);
			if ($this->stack[1] == 'head') { // In 'head' section?
				if (count($this->stack) > 3)
					throw new TagException($tag);
				foreach ($attr as $k=>$v)
					if (array_search($k, array(
							'custom', 'name', 'required', 'tag')) === false)
						throw new TagException($tag, $k);
			}
			if (!isset($attr['name']))
				throw new TagAttReqException($tag, 'name');

			// Determine tag for wrapper element
			if (isset($attr['tag'])) {
				$tag = $attr['tag'];
				unset($attr['tag']);
				// Only 'script' and 'style' allowed if in HEAD element
				if ($this->stack[1] == 'head')
					if ($tag && $tag != 'script' && $tag != 'style')
						throw new TagException('inc', 'tag', $tag);
			}
			else
				$tag = $this->stack[1] == 'head' ? false : 'div';

			// Determine include file names
			$ext = '.php';
			if ($tag == 'script') $ext = '.js';
			if ($tag == 'style') $ext = '.css';
			$fname_default = 
				realpath(self::config('inc')) . "/{$attr['name']}$ext";
			$fname_custom =
				realpath(self::config('inc')) . "/{$attr['name']}_" .
					self::ctrlname() . $ext;
			unset($attr['name']);

			// Determine which include files exist
			$fname_default_exist = file_exists($fname_default);
			$fname_custom_exist = file_exists($fname_custom);

			// Get custom option
			if (isset($attr['custom'])) {
				$custom = $attr['custom'];
				if (array_search($custom, array(
						'after', 'before', 'ignore', 'replace')) === false)
					throw new TagException('inc', 'custom', $custom);
				unset($attr['custom']);
			}
			else
				$custom = 'replace';

			// Get required option
			if (isset($attr['required'])) {
				$required = (bool)$attr['required'];
				unset($attr['required']);
			}
			else
				$required = false;

			// Check for required file
			if ($required)
				if ($custom == 'ignore') {
					if (!$fname_default_exist)
						throw new IncludeException($fname_default);
				}
				else if (!($fname_default_exist || $fname_custom_exist))
					throw new IncludeException($fname_default, $fname_custom);

			// Get the content
			ob_start();
			switch ($custom) {
				case 'after':
					if ($fname_default_exist)
						self::content($fname_default);
					if ($fname_custom_exist)
						self::content($fname_custom);
					break;
				case 'before':
					if ($fname_custom_exist)
						self::content($fname_custom);
					if ($fname_default_exist)
						self::content($fname_default);
					break;
				case 'ignore':
					if ($fname_default_exist)
						self::content($fname_default);
					break;
				case 'replace':
					if ($fname_custom_exist)
						self::content($fname_custom);
					else if ($fname_default_exist)
						self::content($fname_default);
					break;
			}
			$content = ob_get_clean();

			// Produce the content, if any
			if ($content) {
				if ($tag)
					print "<$tag" . self::attr($attr) . '>';
				print $content;
				if ($tag)
					print "</$tag>";
			}

			break;

		case 'script':
			if (count($this->stack) < 3)
				throw new TagException($tag);
			if (!isset($attr['src']))
				throw new TagAttReqException($tag, 'src');
			if (isset($attr['path']) && preg_match('"/$"', $attr['path']))
				$attr['path'] .= basename($attr['src']);
			if (isset($attr['path']) && file_exists($attr['path'])) {
				$attr['src'] .= '?' . md5(filemtime($attr['path']));
				unset($attr['path']);
			}
			else if (!preg_match('"^(\w+:|/)"', $attr['src']) &&
					file_exists($attr['src']))
				$attr['src'] .= '?' . md5(filemtime($attr['src']));

			$attr['src'] = self::uri($attr['src']);
			print "<script" . self::attr($attr) . "></script>\n";
			break;

		case 'link':
			if (count($this->stack) < 3 || $this->stack[1] != 'head')
				throw new TagException($tag);
			if (!isset($attr['href']))
				throw new TagAttReqException($tag, 'href');
			if (!isset($attr['rel']) &&
					preg_match('/\.(css|less)(\?.*)?$/', $attr['href'])) {
				$attr['rel'] = 'stylesheet';
				if (preg_match('/\.less(\?.*)?$/', $attr['href']))
					$attr['rel'] .= '/less';
				if (!isset($attr['type']))
					$attr['type'] = 'text/css';
				if (isset($attr['path']) && preg_match('"/$"', $attr['path']))
					$attr['path'] .= basename($attr['href']);
				if (isset($attr['path']) && file_exists($attr['path'])) {
					$attr['href'] .= '?' . md5(filemtime($attr['path']));
					unset($attr['path']);
				}
				else if (!preg_match('"^(\w+:|/)"', $attr['href']) &&
						file_exists($attr['href']))
					$attr['href'] .= '?' . md5(filemtime($attr['href']));
			}
			$attr['href'] = self::uri($attr['href']);
			print "<link" . self::attr($attr) . " />\n";
			break;

		case 'title':
			if (count($this->stack) < 4 || !preg_match(
					'/^h[1-6]$/', $this->stack[count($this->stack)-2]))
				throw new TagException($tag);
			print htmlspecialchars(self::ctrl()->title);
			break;

		case 'div':
		case 'article':
		case 'nav':
		case 'aside':
		case 'header':
		case 'footer':
		case 'main':
		case 'section':

		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
			if (count($this->stack) < 3 || $this->stack[1] != 'body')
				throw new TagException($tag);
			print "\n<$tag" . self::attr($attr) . '>';
			break;

		default:
			//throw new TemplateException("<$tag> is invalid");
			throw new TemplateException("<$tag> is invalid");
		}
	}

	/** @ignore */
	protected function element_end ($p, $tag) {
		switch ($tag) {

		case 'html':
			if (self::config('copyright') == self::COPYRIGHT_END)
				$this->copyright();
			print "</html>\n";
			break;

		case 'head':
		case 'body':
			print "</$tag>\n";
			break;

		case 'div';
		case 'article':
		case 'nav':
		case 'aside':
		case 'header':
		case 'footer':
		case 'main':
		case 'section':

		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
			print "</$tag>\n";
		}

		array_pop($this->stack);
	}

	/** @ignore */
	protected function copyright () {
		print "<!--\n";
		print "  Generated by FST Application Framework\n\n";
		print '  FST Application Framework, Ver. ' . self::VERSION .
			' (' . self::VERSION_RELEASE . ')' . "\n";
		print '  Copyright (c) ' . self::VERSION_COPYRIGHT .
			", Norman Lippincott Jr, Saylorsburg PA USA\n";
		print "  All Rights Reserved\n";
		print "-->\n";
	}

	/** @ignore */
	protected function __clone () { }
}

/** @ignore */
class TemplateException extends \Exception {
	public function __construct ($msg)
		{ parent::__construct(htmlspecialchars($msg)); }
}

/** @ignore */
class UsageException extends \Exception {
	public function __construct ($msg)
		{ parent::__construct(htmlspecialchars($msg)); }
}

/** @ignore */
class ContentException extends TemplateException {
	public function __construct ($fname)
		{ parent::__construct("Content file $fname not found"); }
}

/** @ignore */
class IncludeException extends TemplateException {
	public function __construct ($fname, $fname2=false) {
		parent::__construct($fname2 ?
			"Include file $fname or $fname2 not found" :
			"Include file $fname not found");
	}
}

/** @ignore */
class TagException extends TemplateException {
	public function __construct ($tag, $attr=false, $value=false) {
		parent::__construct($attr ?
			($value ?
				"<$tag $attr=\"$value\"> is not valid" :
				"<$tag $attr> is not valid") :
			"<$tag> not allowed here");
	}
}

/** @ignore */
class TagAttReqException extends TemplateException {
	public function __construct ($tag, $attr)
		{ parent::__construct("<$tag> requires $attr attribute"); }
}

/** @ignore */
class DatabaseException extends UsageException {
	public function __construct ($msg) { parent::__construct($msg); }
}

/** @ignore */
class NotFoundException extends DatabaseException {
	public function __construct ($msg) { parent::__construct($msg); }
}

/// @endcond
