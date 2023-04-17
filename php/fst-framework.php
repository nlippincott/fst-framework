<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/**
 * @file
 * @brief Defines classes and functions for the FST Application Framework.
 *
 * This file is to be the first file included in the application's index.php
 * file, and defines the various classes and function to support the
 * framework.
 *
 * In addition, this file registers all the standard form controls included
 * with the framework so that they are available with the Form class
 * @see <a href="fst-form-controls.html">FST Form Controls</a>
 */

/// @cond

// Requires PHP version 5.3 or higher
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die('FST Application Framework requires PHP 5.3 or higher');

require 'Framework.php';
require 'Controller.php';

require 'TableEngine.php';
require 'ListEngine.php';
require 'CalendarEngine.php';
require 'NameValueTable.php';

require 'Form.php';
require 'FormLabel.php';
require 'FormControl.php';
require 'FormInputControl.php';

require 'FormTextControl.php';
FST\Form::register('text', 'FST\FormTextControl');

require 'FormEmailControl.php';
FST\Form::register('email', 'FST\FormEmailControl');

require 'FormNumberControl.php';
FST\Form::register('number', 'FST\FormNumberControl');

require 'FormMoneyControl.php';
FST\Form::register('money', 'FST\FormMoneyControl');

require 'FormSearchControl.php';
FST\Form::register('search', 'FST\FormSearchControl');

require 'FormURLControl.php';
FST\Form::register('url', 'FST\FormURLControl');

require 'FormHiddenControl.php';
FST\Form::register('hidden', 'FST\FormHiddenControl');

require 'FormDateControl.php';
FST\Form::register('date', 'FST\FormDateControl');

require 'FormTimeControl.php';
FST\Form::register('time', 'FST\FormTimeControl');

require 'FormUsernameControl.php';
FST\Form::register('username', 'FST\FormUsernameControl');

require 'FormPasswordControl.php';
FST\Form::register('password', 'FST\FormPasswordControl');

require 'FormOptionControl.php';
FST\Form::register('option', 'FST\FormOptionControl');

require 'FormSelectionControl.php';
FST\Form::register('selection', 'FST\FormSelectionControl');

require 'FormMultipleControl.php';
FST\Form::register('multiple', 'FST\FormMultipleControl');

require 'FormTextareaControl.php';
FST\Form::register('textarea', 'FST\FormTextareaControl');

require 'FormNoteControl.php';
FST\Form::register('note', 'FST\FormNoteControl');

require 'FormSubmitControl.php';
FST\Form::register('submit', 'FST\FormSubmitControl');

//require 'FormRangeControl.php'; // Experimental
//Form::register('range', 'FormRangeControl');

require 'FormFileControl.php';
require 'FormFileUpload.php';
FST\Form::register('file', 'FST\FormFileControl');

require 'FormImageControl.php';
require 'FormImageUpload.php';
FST\Form::register('image', 'FST\FormImageControl');

if (PHP_VERSION_ID >= 70000) {
	// The Mongo and MySQL data models *may* use features that require PHP 7,
	// so are only included only if PHP 7 or higher.
	require 'Mongo.php';
	require 'MySQL.php';
}

/// @endcond
