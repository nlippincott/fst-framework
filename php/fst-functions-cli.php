<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/**
 * Helper Functions applicable to both CLI and Web contexts.
 * 
 * Included are shortcut functions for calling some static function of
 * class Framework.
 */

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
 * Get environment variable value.
 * 
 * Convenience function, calls Framework::env.
 *
 * @param string $var Environment variable name, or null for all
 * @return string|string[] Environment variable value
 */
function _env ($var=null) { return FST\Framework::env($var); }
