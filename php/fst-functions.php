<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-23, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revisions, ver 5.1
//	- Added functions _table and __table
// Revisions, ver 6.0
//	- Added function _env

/**
 * @file
 * @brief Helper Functions for generating HTML code.
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
 * @brief Print string in HTML format
 * @param string $s String to be printed
 *
 * This is a convenience function that applies PHP functions htmlspecialchars
 * and nl2br (in that order) to a string, then prints the result. It is
 * provided simply as a shortcut.
 *
 * Instead of using:
 *
 * <code>echo nl2br(htmlspecialchars($s))</code>
 *
 * use:
 *
 * <code>__($s)</code>
 */
function __ ($s) { print _s($s); }

/**
 * @brief Generate HTML A tag.
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $text Text to serve as hyperlink
 * @param array $attr Associative array of attributes
 * @retval string HTML A tag
 */
function _a ($uri, $text, $attr=array())
	{ return _a_html($uri, htmlspecialchars($text), $attr); }

/**
 * @brief Print HTML A tag.
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $text Text to serve as hyperlink
 * @param array $attr Associative array of attributes
 */
function __a ($uri, $text, $attr=array()) { print _a($uri, $text, $attr); }

/**
 * @brief Generate HTML A tag with HTML content.
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $html HTML code to serve as hyperlink
 * @param array $attr Associative array of attributes
 * @retval string HTML A tag
 */
function _a_html ($uri, $html, $attr=array()) {
	$attr['href'] = _uri($uri);
	return _tag('a', $html, $attr);
}

/**
 * @brief Print HTML A tag with HTML content.
 * @param string $uri Relative or absolute URI for HREF attribute
 * @param string $html HTML code to serve as hyperlink
 * @param array $attr Associative array of attributes
 */
function __a_html ($uri, $html, $attr=array())
	{ print _a_html($uri, $html, $attr); }

/**
 * @brief Generate HTML INPUT button.
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $text Text to appear on button
 * @param array $attr Associative array of attributes
 * @retval string HTML INPUT tag
 */
function _button ($uri, $text, $attr=array())
	{ return _button_html($uri, htmlspecialchars($text), $attr); }

/**
 * @brief Print HTML INPUT button.
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $text Text to appear on button
 * @param array $attr Associative array of attributes
 */
function __button ($uri, $text, $attr=array())
	{ print _button($uri, $text, $attr); }

/**
 * @brief Generate HTML BUTTON with HTML.
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $html Text to appear on button
 * @param array $attr Associative array of attributes
 * @retval string HTML BUTTON tag
 */
function _button_html ($uri, $html, $attr=array()) {
	return _tag('button', $html,
			array_merge(array(
					'type'=>'button',
					'data-fst-href'=>_uri($uri),
				), $attr));
}

/**
 * @brief Print HTML BUTTON with HTML.
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $html Text to appear on button
 * @param array $attr Associative array of attributes
 */
function __button_html ($uri, $html, $attr=array())
	{ print _button_html($uri, $html, $attr); }

/**
 * @brief Generate HTML BUTTON with image.
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $src Relative or absolute URI for SRC attribute for image
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 * @retval string HTML BUTTON tag
 */
function _button_img ($uri, $src, $alt, $attr=array())
	{ return _button_html($uri, _img($src, $alt), $attr); }

/**
 * @brief Print HTML BUTTON with image.
 * @param string $uri Relative or absolute URI for A:HREF behavior
 * @param string $src Relative or absolute URI for SRC attribute for image
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 */
function __button_img ($uri, $src, $alt, $attr=array())
	{ print _button_img($uri, $src, $alt, $attr); }

/**
 * @brief Get (or set) FST configuration option.
 * @param string $opt Option name
 * @param mixed $value Set value for option (optional)
 * @retval mixed Option value
 *
 * This is a convenience function for calling Framework::config.
 */
function _config ($opt, $value=null)
	{ return FST\Framework::config($opt, $value); }

/**
 * @brief Get the FST Controller object.
 * @retval object The Controller object for the current request
 *
 * This is a convenience function for returning current FST controller.
 */
function _ctrl () { return FST\Framework::ctrl(); }

/**
 * @brief Get name of the current FST Controller.
 * @retval string Controller name
 */
function _ctrlname () { return FST\Framework::ctrl()->ctrl(); }

/**
 * @brief Get environment variable value.
 * @param string $var Environment variable name, or null for all
 * @retval mixed Environment variable value
 * 
 * Convenience function, calls Framework::env.
 */
function _env ($var=null) { return FST\Framework::env($var); }

/**
 * @brief Get HTML IMG tag.
 * @param string $uri Relative or absolute URI for SRC attribute
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 * @retval string HTML IMG tag
 */
function _img ($uri, $alt, $attr=array()) {
	$attr['src'] = _uri($uri);
	$attr['alt'] = $alt;
	if (!isset($attr['title']))
		$attr['title'] = $attr['alt'];
	return _tag('img', false, $attr);
}

/**
 * @brief Print HTML IMG tag.
 * @param string $uri Relative or absolute URI for SRC attribute
 * @param string $alt Alternate text
 * @param array $attr Associative array of attributes
 */
function __img ($uri, $alt, $attr=array()) { print _img($uri, $alt, $attr); }

/**
 * @brief Convert string to HTML for printing
 * @param string $s String to be converted
 * @retval string HTML-formatted string
 *
 * This is a convenience function that applies PHP functions htmlspecialchars
 * and nl2br (in that order) to a string. It is provided simply as a shortcut.
 */
function _s ($s) { return $s ? nl2br(htmlspecialchars($s)) : ''; }

/**
 * @brief Generate an HTML element.
 * @param string $tag HTML tag
 * @param mixed $html HTML code for element content, or false for short tag
 * @param array $attr Associative array of attributes
 * @retval string HTML element
 */
function _tag ($tag, $html, $attr=array()) {
	return "<$tag" . FST\Framework::attr($attr) .
		($html === false ? ' />' : ">$html</$tag>");
}

/**
 * @brief Print an HTML element.
 * @param string $tag HTML tag
 * @param mixed $html HTML code for element content, or false for short tag
 * @param array $attr Associative array of attributes
 */
function __tag ($tag, $html, $attr=array()) { print _tag($tag, $html, $attr); }

/**
 * @brief Generate HTML from class derived from TableEngine
 * @param string $classname Class name
 * @param mixed $args Constructor argument or array of constructor arguments
 * @retval string Return value from __toString method of given class
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
 * Added in FST version 5.1.
 */
function _table ($classname, $args=array()) {
	if (!is_string($classname) || !class_exists($classname))
		throw new FST\UsageException("Parameter 1 must be a class name");
	if (!is_subclass_of($classname, '\FST\TableEngine'))
		throw new FST\UsageException(
			"Class $classname is not derived from FST\\TableEngine");
	$class = new ReflectionClass($classname);
	$obj = $class->newInstanceArgs(is_array($args) ? $args : array($args));
	return $obj->__toString();
}

/**
 * @brief Print HTML from class derived from TableEngine
 * @param string $classname Class name
 * @param mixed $args Constructor argument or array of constructor arguments
 *
 * Prints the output from _table.
 *
 * Added in FST version 5.1.
 */
function __table ($classname, $args=array())
	{ print _table($classname, $args); }

/**
 * @brief Get application URI given URI relative to root.
 * @param string $uri Relative (or absolute) URI
 * @retval string Absolute URI
 *
 * This is a convenience function that calls Framework::uri. Note that if an
 * absolute URI is given, or a URI beginning with a non-alphanumeric character,
 * the given URI is simply returned (as is the behavior of Framework::uri).
 */
function _uri ($uri) { return FST\Framework::uri($uri); }

/**
 * @brief Print application URI given URI relative to root.
 * @param string $uri Relative URI
 *
 * This is a convenience function for printing the return value of _uri.
 */
function __uri ($uri) { print _uri($uri); }
