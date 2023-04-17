<?php

// FST Application Framework, Version 5.5
// Copyright (c) 2004-22, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revisions, ver 5.1
//	- For default controller selection, do not consider abstract classes
//	- Added meta-viewport option configuration option
//	- Added post data initialization methods
//	- Output comment for controller name when in debug mode
//	- For object return from content preprocessor, call __toString explicitly
//		rather than implicit conversion to string
//	- For object return from content preprocessor, throw an exception
//		when object does not implement __toString
//	- Throw an exception if content preprocessor return type is other than
//		boolean, null, object, or a string
//	- When content preprocessor returns true, output default content file
//		(same behavior as null)
//	- When content preprocessor returns a string with first character '<',
//		output the string directly as HTML content
// Revisions, ver 5.2
//	- Function attr, apply htmlspecialchars to attribute values
// Revisions, ver 5.2.1
//	- For form controls, correction to values for HTML5 boolean attributes
// Revisions, ver 5.2.2
//	- For textarea control, added placeholder function
// Revisions, ver 5.3
//	- Configuration option 'class' may be specified as an array of directories
//	- Call _init methods of traits used by controller
// Revisions, ver 5.4
//	- Configuration option 'lib' may be specified as an array of directories
//	- Added DatabaseException and NotFoundException classes
// Revisions, ver 5.4.3
//	- Correction of converstion of application URI's, those leading with '#'
//		are no longer converted using absolute URI logic

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Framework initialization and page generation.
 *
 * Initializes and drives the FST Application Framework.
 *
 * <code>Framework::init($config_options);</code>
 */
class Framework {

	/// @cond

	protected static $_fst = null;	// Framework instance

	protected static $_ctrl;		// Controller object
	protected static $_ctrlname;	// Controller name

	protected static $_args;	// Controller argument string
	protected static $_argv;	// Controller argument array
	protected static $_cfg;		// Controller configuration

	protected static $_action;	// Controller action

	/// @endcond

	// FST version constants
	const VERSION = '5.5-rc1';				///< FST version number
	const VERSION_COPYRIGHT = '2004-23';	///< FST coypright dates
	const VERSION_RELEASE = '2022-04-04';	///< FST version release date

	// For control of FST copyright comment in HTML output
	const COPYRIGHT_STD = 1;		///< Default FST copyright output location
	const COPYRIGHT_HEAD = 0;		///< Place FST copyright in HEAD section
	const COPYRIGHT_END = 1;		///< Place FST copyright at end of HTML
	const COPYRIGHT_NONE = 2;		///< No FST copyright notice
	const COPYRIGHT_STEALTH = 3;	///< No FST copyright notice or META tag

	/**
	 * @brief Get controller action.
	 * @retval string Controller action
	 *
	 * Gets the action for the current request. The "action" is the first
	 * GET parameter passed on the URL (i.e. the name of the first name/value
	 * pair following the "?"). This is typically used during form submissions
	 * and Ajax requests.
	 */
	public static function action () { return self::$_action; }

	/**
	 * @brief Get controller argument by index.
	 * @param int $idx Zero-based index
	 * @retval string Controller argument value
	 *
	 * FST controller arguments are given in the URL and are separated by
	 * slashes. For example, if the requested resource is "/account/14/edit",
	 * argument 0 is "account", argument 1 is "14", and argument 2 is "edit".
	 */
	public static function arg ($idx)
		{ return isset(self::$_argv[$idx]) ? self::$_argv[$idx] : ''; }

	/**
	 * @brief Get controller argument count.
	 * @retval int Number of controller arguments
	 */
	public static function argc () { return count(self::$_argv); }

	/**
	 * @brief Get controller argument string.
	 * @retval string Argument URI (relative to application root)
	 *
	 * Returns a string containing all controller arguments separated by
	 * slashes. The string does not contain a leading slash. The string
	 * represents the URI, less $_GET parameters, relative to the application
	 * root as specified during FST initialization. For example, if the
	 * application root is specified as "/myapp/", and the URI given is
	 * "/myapp/account/15/edit?_form=account_form", this function returns
	 * "account/15/edit". The return value of this function is suitable
	 * for usage in redirects initiated by Controller::redirect.
	 */
	public static function args () { return implode('/', self::$_argv); }

	/**
	 * @brief Get controller argument array.
	 * @retval array Controller arguments
	 *
	 * Returns an array of the controller arguments, relative to the
	 * application root.
	 */
	public static function argv () { return self::$_argv; }

	/**
	 * @brief Get (or set) framework configuration option.
	 * @param string $opt Option name
	 * @param mixed $value Set value for option (optional)
	 * @retval string Option value
	 *
	 * If $value is not given, returns a configuration option. If $value is
	 * given, sets the given configuration option. Typically, this function
	 * is used to query options given at the time the framework was
	 * initialized via Framework::init. However, this function may be called
	 * by a Controller class to override an option set during framework
	 * initialization.
	 */
	public static function config ($opt, $value=null) {
		if (isset($value))
			self::$_cfg[$opt] = $value;
		return self::$_cfg[$opt];
	}

	/**
	 * @brief Output content from content file.
	 * @param string $fname File name
	 *
	 * Produces content from a named content file. Public member variables of
	 * the Controller class handling the request are set prior to executing
	 * the contents of the given file; i.e. all such member variables are
	 * available as variables in the content file.
	 *
	 * The file name must include relative path from the root.
	 */
	public static function content ($fname) {
//		foreach (get_object_vars(self::ctrl()) as $_k=>$_v)
//			$$_k = $_v;
//		unset($_k, $_v);
		extract(get_object_vars(self::ctrl()));
		require $fname;
	}

	/**
	 * @brief Get controller object.
	 * @retval object Current Controller object
	 *
	 * Gets the current controller object. This is an object that is derived
	 * from class Controller, and represents the controller object that is
	 * handling the current request.
	 */
	public static function ctrl () { return self::$_ctrl; }

	/**
	 * @brief Get controller name.
	 * @retval string Current controller name
	 *
	 * Gets the name of the current controller. For example, if the
	 * Controller derived class is named "account_controller", this function
	 * returns "account". (Note that all controller classes used by the
	 * framework have names ending in "_controller".)
	 */
	public static function ctrlname () { return self::$_ctrlname; }

	/// @cond
	// FST modules will call this function for usage errors. It is not
	//	intended to be user-callable.
	// This function attempts to report the file and line number where an FST
	//	user error occurred. It does this by going through the backtrace to
	//	find the first occurrence of a source file that is not part of the
	//	FST system directory (or the same directory as this source file).
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
	/// @endcond

	/**
	 * @brief Send 404 Not Found header.
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
	 * @brief Convert relative URI to absolute URI.
	 * @param string $uri Relative URI
	 * @retval string Absolute URI
	 *
	 * Converts a relative URL to an absoute URI, considering the base URI
	 * of the application. For example, if the application root is specified
	 * as "/myapp/" and this function is given "account/15/edit", this
	 * function returns "/myapp/account/15/edit".
	 *
	 * Note that if this function is given a URL that includes the protocol
	 * or leads with a slash, the given URL is considered to be an absolute
	 * URL and is simply returned.
	 */
	public static function uri ($uri='') {
		return preg_match('"^(\w+:|/|\?|#)"', $uri) ?
			$uri : self::config('root') . $uri;
	}

	/**
	 * @brief Initialize the framework and carry out the controller action.
	 * @param array $cfg Configuration settings
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
	 *	- home: "home" Controller name for home page
	 *	- inc: "inc" Relative path to the include directory
	 *	- lib: "lib" Relative path or array of paths to the library directory
	 *	- root: "/" The application root (include leading and trailing slash)
	 *	- session: "false" Session name, or false if no session
	 *	- template: "template" Relative path to the template directory
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

					// Application options (may also be overridden in
					//	config.ini)
					'ajax'=>true, // Handle Ajax requests
					'controllers'=>array(), // Controller map
					'copyright'=>self::COPYRIGHT_STD, // FST Copyright comments
					'default'=>true, // Enable default controller selection
					'debug'=>false, // Use false for production
					'debug_error_reporting'=>E_ALL,
						// Error reporting level, when debug is set
					'home'=>'home', // Controller name for home page
					'meta-content-type'=>true,
						// Generate default meta content-type tag
					'meta-viewport'=>true,
						// Generate default meta viewport tag
					'session'=>false, // session name, false for no session
				),
				$cfg);

			// If app/config.ini exists, load options
			$cfg2_fname = self::config('app') . '/config.ini';
			if (file_exists($cfg2_fname)) {
				$cfg2 = parse_ini_file($cfg2_fname, true);
				$cfg2_map = array(
						// legacy-option=>current-option
						'Ajax'=>'ajax',
						'Controller'=>'controllers',
						'Copyright'=>'copyright',
						'Debug'=>'debug',
						'Session'=>'session',
					);
				foreach ($cfg2_map as $legacy=>$option) {
					if (array_key_exists($legacy, $cfg2))
						self::$_cfg[$option] = $cfg2[$legacy];
					if (array_key_exists($option, $cfg2))
						self::$_cfg[$option] = $cfg2[$option];
				}
				// Application root may also be deifned in config file
				if (array_key_exists('root', $cfg2))
					self::$_cfg['root'] = $cfg2['root'];
			}

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
	 * @brief Convert name/value pairs to HTML attribute string.
	 * @param array $attr Attribute name/value pairs
	 * @retval string HTML-formatted attribute string
	 *
	 * Converts an associative array into a string that may be output in
	 * HTML attribute form. This string may be output immediately after
	 * the HTML tag (the string returned includes a leading space, except
	 * if the string is empty).
	 */
	public static function attr ($attr) {
		$str = '';
		foreach ($attr as $k=>$v)
			$str .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
		return $str;
	}

	/**
	 * @brief Constructor, framework driver.
	 */
	protected function __construct () {

		// Set error reporting, if debug mode is set
		if (self::config('debug')) {
			error_reporting(self::config('debug_error_reporting'));
			ini_set('display_errors', 1);
		}

		// Start session, if required
		if (self::config('session')) {
			session_name(self::config('session'));
			session_start();
		}

		// Set up application autoload function
		//	TODO: Include autoload forms? *_form in app directory?
		/*
		if (self::config('class'))
			spl_autoload_register(function ($cls) {
				//$f = preg_match('/_(controller|form)$/', $cls) ?
				$f = preg_match('/_controller$/', $cls) ?
					Framework::config('app') . "/$cls.php" :
					Framework::config('class') . "/$cls.php";
				if (file_exists($f))
					require $f;
			});
		else
			spl_autoload_register(function ($cls) {
				if (preg_match('/_controller$/', $cls)) {
					$f = Framework::config('app') . "/$cls.php";
					if (file_exists($f))
						require $f;
				}
			});
		*/
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

	/// @cond

	protected $parser;

	protected $stack = array();		// Parser element stack
	protected $head = false;		// HEAD element encountered
	protected $body = false;		// BODY element encountered

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

	protected function __clone () { }

	/// @endcond
}

/// @cond

class TemplateException extends \Exception {
	public function __construct ($msg)
		{ parent::__construct(htmlspecialchars($msg)); }
}

class UsageException extends \Exception {
	public function __construct ($msg)
		{ parent::__construct(htmlspecialchars($msg)); }
}

class ContentException extends TemplateException {
	public function __construct ($fname)
		{ parent::__construct("Content file $fname not found"); }
}

class IncludeException extends TemplateException {
	public function __construct ($fname, $fname2=false) {
		parent::__construct($fname2 ?
			"Include file $fname or $fname2 not found" :
			"Include file $fname not found");
	}
}

class TagException extends TemplateException {
	public function __construct ($tag, $attr=false, $value=false) {
		parent::__construct($attr ?
			($value ?
				"<$tag $attr=\"$value\"> is not valid" :
				"<$tag $attr> is not valid") :
			"<$tag> not allowed here");
	}
}

class TagAttReqException extends TemplateException {
	public function __construct ($tag, $attr)
		{ parent::__construct("<$tag> requires $attr attribute"); }
}

class DatabaseException extends UsageException {
	public function __construct ($msg) { parent::__construct($msg); }
}

class NotFoundException extends DatabaseException {
	public function __construct ($msg) { parent::__construct($msg); }
}

/// @endcond
