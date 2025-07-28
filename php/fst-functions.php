<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-25, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/**
 * Helper Functions for generating HTML code.
 *
 * These functions are primarily helpers for generating HTML code. Most
 * functions have two varieties; one starting with a single underscore and one
 * starting with two underscores. The former returns HTML code while the
 * latter prints HTML code.
 *
 * The double-underscore functions are intended for use in content and include
 * files for generating output. The single-underscore versions are provided
 * for other cases where a class (for example one derived from TableEngine)
 * or library function might need to return HTML code.
 * 
 * Also included are shortcut functions for calling some static function of
 * class Framework.
 */

/**
 * Print string in HTML format
 *
 * This is a convenience function that applies PHP functions htmlspecialchars
 * and nl2br (in that order) to a string, then prints the result. It is
 * provided simply as a shortcut.
 *
 * Instead of using:
 * ```
 * echo nl2br(htmlspecialchars($s))
 * ```
 * use:
 * ```
 * __($s)
 * ```
 * @param string $s String to be printed
 */
function __ ($s) { print _s($s); }

/**
 * Generate HTML A tag.
 * 
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $text Text to serve as hyperlink
 * @param array $attr Associative array of attributes
 * @return string HTML A tag
 */
function _a ($uri, $text, $attr=[])
	{ return _a_html($uri, htmlspecialchars($text), $attr); }

/**
 * Print HTML A tag.
 * 
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $text Text to serve as hyperlink
 * @param array $attr Associative array of attributes
 */
function __a ($uri, $text, $attr=[]) { print _a($uri, $text, $attr); }

/**
 * Generate HTML A tag with HTML content.
 * 
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $html HTML code to serve as hyperlink
 * @param array $attr Associative array of attributes
 * @return string HTML A tag
 */
function _a_html ($uri, $html, $attr=[]) {
	$attr['href'] = _uri($uri);
	return _tag('a', $html, $attr);
}

/**
 * Print HTML A tag with HTML content.
 * 
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $html HTML code to serve as hyperlink
 * @param array $attr Associative array of attributes
 */
function __a_html ($uri, $html, $attr=[])
	{ print _a_html($uri, $html, $attr); }

/**
 * Generate HTML INPUT button.
 * 
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $text Text to appear on button
 * @param array $attr Associative array of attributes
 * @return string HTML INPUT tag
 */
function _button ($uri, $text, $attr=[])
	{ return _button_html($uri, htmlspecialchars($text), $attr); }

/**
 * Print HTML INPUT button.
 * 
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $text Text to appear on button
 * @param array $attr Associative array of attributes
 */
function __button ($uri, $text, $attr=[])
	{ print _button($uri, $text, $attr); }

/**
 * Generate HTML BUTTON with HTML.
 * 
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $html Text to appear on button
 * @param array $attr Associative array of attributes
 * @return string HTML BUTTON tag
 */
function _button_html ($uri, $html, $attr=[]) {
	return _tag('button', $html, array_merge([ 'type'=>'button', 'data-fst-href'=>_uri($uri), ], $attr));
}

/**
 * Print HTML BUTTON with HTML.
 * 
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $html Text to appear on button
 * @param array $attr Associative array of attributes
 */
function __button_html ($uri, $html, $attr=[])
	{ print _button_html($uri, $html, $attr); }

/**
 * Generate HTML BUTTON with image.
 * 
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $src Relative or absolute URI for SRC attribute for image
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 * @return string HTML BUTTON tag
 */
function _button_img ($uri, $src, $alt, $attr=[])
	{ return _button_html($uri, _img($src, $alt), $attr); }

/**
 * Print HTML BUTTON with image.
 * 
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $src Relative or absolute URI for SRC attribute for image
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 */
function __button_img ($uri, $src, $alt, $attr=[])
	{ print _button_img($uri, $src, $alt, $attr); }

/**
 * Get (or set) FST configuration option.
 *
 * This is a convenience function for calling Framework::config.
 * 
 * @param string $opt Option name
 * @param mixed $value Set value for option (optional)
 * @return mixed Option value
 */
function _config ($opt, $value=null)
	{ return FST\Framework::config($opt, $value); }

/**
 * Get the FST Controller object.
 *
 * This is a convenience function for returning current FST controller.
 * 
 * @return Controller The Controller object for the current request
 */
function _ctrl () { return FST\Framework::ctrl(); }

/**
 * Get name of the current FST Controller.
 * 
 * @return string Controller name
 */
function _ctrlname () { return FST\Framework::ctrl()->ctrl(); }

/**
 * Get environment variable value.
 * 
 * Convenience function, calls Framework::env.
 *
 * @param string $var Environment variable name, or null for all
 * @return string|string[] Environment variable value
 */
function _env ($var=null) { return FST\Framework::env($var); }

/**
 * Get HTML IMG tag.
 * 
 * @param string $uri Relative or absolute URI for SRC attribute
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 * @return string HTML IMG tag
 */
function _img ($uri, $alt, $attr=[]) {
	$attr['src'] = _uri($uri);
	$attr['alt'] = $alt;
	if (!isset($attr['title']))
		$attr['title'] = $attr['alt'];
	return _tag('img', false, $attr);
}

/**
 * Print HTML IMG tag.
 * 
 * @param string $uri Relative or absolute URI for SRC attribute
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 */
function __img ($uri, $alt, $attr=[]) { print _img($uri, $alt, $attr); }

/**
 * Convert string to HTML for printing.
 *
 * This is a convenience function that applies PHP functions htmlspecialchars
 * and nl2br (in that order) to a string. It is provided simply as a shortcut.
 * 
 * @param string $s String to be converted
 * @return string HTML-formatted string
 */
function _s ($s) { return $s ? nl2br(htmlspecialchars($s)) : ''; }

/**
 * Generate an HTML element.
 * 
 * @param string $tag HTML tag
 * @param mixed $html HTML code for element content, or false for short tag
 * @param array $attr Associative array of attributes
 * @return string HTML element
 */
function _tag ($tag, $html, $attr=[]) {
	return "<$tag" . FST\Framework::attr($attr) . ($html === false ? ' />' : ">$html</$tag>");
}

/**
 * Print an HTML element.
 * 
 * @param string $tag HTML tag
 * @param mixed $html HTML code for element content, or false for short tag
 * @param array $attr Associative array of attributes
 */
function __tag ($tag, $html, $attr=[]) { print _tag($tag, $html, $attr); }

/**
 * Generate HTML from class derived from TableEngine.
 *
 * Creates a new object of the given class, calls its __toString method, and
 * returns that function's return value. The given class name must refer to
 * a class that is derived from TableEngine.
 *
 * If the constructor of the given class requires arguments, they are to be
 * passed as an array via $args. If the constructor may take a single non-array
 * argument, it may simply be passed via $args (not as an array).
 *
 * This function is provided as a convenience for avoiding the inability to
 * throw an exception in PHP while casting an object to a string. If, for
 * example, class "MyTable" is derived from TableEngine, one can use
 * @code print _table('MyTable'); @endcode
 * as an alternative to
 * @code print new MyTable(); @endcode
 * If an exception is throw while generating a table during the latter call,
 * PHP will simply exit with a message saying that __toString must not throw
 * an exception. This function calls __toString explicitly then returns its
 * return string, thus allowing exceptions to be thrown.
 * 
 * @param string $classname Class name
 * @param mixed $args Constructor argument or array of constructor arguments
 * @return string Return value from __toString method of given class
 */
function _table ($classname, $args=[]) {
	if (!is_string($classname) || !class_exists($classname))
		throw new FST\UsageException("Parameter 1 must be a class name");
	if (!is_subclass_of($classname, '\FST\TableEngine'))
		throw new FST\UsageException("Class $classname is not derived from FST\\TableEngine");
	$class = new ReflectionClass($classname);
	$obj = $class->newInstanceArgs(is_array($args) ? $args : [ $args ]);
	return $obj->__toString();
}

/**
 * Print HTML from class derived from TableEngine.
 *
 * Prints the output from _table.
 *
 * @param string $classname Class name
 * @param mixed $args Constructor argument or array of constructor arguments
 */
function __table ($classname, $args=[])
	{ print _table($classname, $args); }

/**
 * Get application URI given URI relative to root.
 *
 * This is a convenience function that calls Framework::uri. Note that if an
 * absolute URI is given, or a URI beginning with a non-alphanumeric character,
 * the given URI is simply returned (as is the behavior of Framework::uri).
 *
 * @param string $uri Relative (or absolute) URI
 * @return string Absolute URI
 */
function _uri ($uri) { return FST\Framework::uri($uri); }

/**
 * Print application URI given URI relative to root.
 *
 * This is a convenience function for printing the return value of _uri.
 *
 * @param string $uri Relative URI
 */
function __uri ($uri) { print _uri($uri); }
