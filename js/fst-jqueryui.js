/***********************************************************************
# FST jQueryUI Library

A JavaScript library providing support for integrating jQueryUI with the
FST Application Framework. This module includes support for dialog boxes
that interact with the framework.
***********************************************************************/

// FST Application Framework, Version 6.0.2
// Copyright (c) 2004-25, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/***********************************************************************
## fst.dialog (String name, String title, Object options) {#fst-dialog}

Open a dialog box, populated with content from the framework.

- ***name***: Name of content area (on the server side)
- ***title***: Title of the dialog box
- ***options***: Dialog box options object

Sends an Ajax request to the framework to produce HTML content to appear
in the dialog box. The name argument specifies the content area that the
framework should produce. That content uses the same mechanism on the
server side as template content, but need not be (and is probably not)
part of the page template.
***********************************************************************/
fst.dialog = function (name, title, options) {

	fst.dialog.close();

	options = jQuery.extend(
		{ data: new Object() },
		(fst.dialog._options[name] === undefined ? new Object() : fst.dialog._options[name]),
		(options === undefined ? new Object() : options)
	);

	options.data._content = name;

	jQuery.ajax({
		url: '?_content',
		type: 'POST',
		data: options.data
	}).done(function (resp) {
		fst.dialog.html(resp, title, options);
	});
};

/***********************************************************************
## fst.dialog.alert (String msg, mixed options) {#fst-dialog-alert}

Display message in dialog box.

- ***msg***: Message string to be displayed
- ***options***: Title string or options object

Uses a jQueryUI dialog to display a message. If *options* is a string, the
string is used as the dialog box title. If *options* is an object, the
following options are supported in addition to fst.dialog options:

- *ok*: Text string for the dismiss button (default "OK")
- *title*: Title for the dialog box (default "Alert")
***********************************************************************/
fst.dialog.alert = function (msg, options) {

	if (options === undefined)
		options = { };
	if (jQuery.type(options) === 'string')
		options = { title: options };

	options = jQuery.extend({
		buttons: [
			{
				text: options.ok === undefined ? 'OK' : options.ok,
				click: function () { fst.dialog.close(true); }
			} ],
		title: 'Alert'
	}, options);

	fst.dialog.html(msg, options.title, options);
}

/***********************************************************************
## fst.dialog.cancel () {#fst-dialog-cancel}

Default form cancel action within dialogs.

A function that defines the default behavior taken when the cancel button
is used within FST-generated forms within a dialog box. This function
simply closes the dialog box. The default behavior can be changed by
assigning a custom function to [fst.dialog.cancel](#fst-dialog-cancel).
***********************************************************************/
fst.dialog.cancel = function () {
	fst.dialog.close();
}

/***********************************************************************
## fst.dialog.close (mixed val) {#fst-dialog-close}

Close (and destroy) the FST dialog.

- ***val***: Dialog result value (optional).

Closes the dialog box that was opened via one of the [fst.dialog](#fst-dialog)
functions. If result is passed, that value is then passed to the callback
function (which is called when the dialog is closed), if one is defined.
***********************************************************************/
fst.dialog.close = function (val) {
	if (val !== undefined)
		fst.dialog.val(val);
	jQuery('[data-fst="dialog"]').dialog('close');
};

/***********************************************************************
## fst.dialog.confirm (String msg, mixed options) {#fst-dialog-confirm}

Show jQueryUI-based confirmation dialog.

- ***msg***: Message string to be displayed
- ***options***: Title string or options object

Uses a jQueryUI dialog to display a confirmation message. If *options* is a
string, the string is used as the confirmation box title. If *options* is an
object, the following options are supported:

- *callback*: Function to be called when the dialog is closed
- *cancel*: Text string for the cancel button (default "Cancel")
- *ok*: Text string for the confirm button (default "OK")
- *title*: Title for the confirmation box (default "Confirm")

The given callback function is given one parameter to indicate the user
interface element used to close the dialog. If the ok button was used,
*true* is passed. If the cancel buttin was used, *false* is passed. If the
dialog was closed using the dialog box's close button, the parameter is
passed as *undefined*.
***********************************************************************/
fst.dialog.confirm = function (msg, options) {

	if (options === undefined)
		options = new Object();
	if (jQuery.type(options) === 'string')
		options = { title: options };

	options = jQuery.extend({
		buttons: [
			{
				text: options.ok === undefined ? 'OK' : options.ok,
				click: function () { fst.dialog.close(true); }
			},
			{
				text: options.cancel === undefined ? 'Cancel' : options.cancel,
				click: function () { fst.dialog.close(false); }
			} ],
		title: 'Confirm',
		callback: function (confirmed) { }
	}, options);

	fst.dialog.html(msg, options.title, options);
}

/***********************************************************************
## fst.dialog.form (String name, String title, Object options) {#fst-dialog-form}

Open a dialog box, populated with a form from the framework.

- ***name***: Name of content area containing a form
- ***title***: Title of the dialog box
- ***options***: Dialog box options object

Sends an Ajax request to the framework to produce HTML content to appear
in the dialog box. The HTML content is expected to contain a form.
The *name* argument specifies the content area that the
framework should produce.
The dialog box contains OK and Cancel buttons, where the OK button submits
the form with no further processing. Actions such as closing the dialog
box should be taken as a result of form processing.
The Cancel button simply closes the dialog box.
***********************************************************************/
fst.dialog.form = function (name, title, options) {

	if (options === undefined)
		options = new Object();

	options = jQuery.extend({
		buttons: [
			{
				text: options.ok === undefined ? 'OK' : options.ok,
				click: function ()
					{ jQuery('[data-fst="dialog"] form').submit(); }
			},
			{
				text: options.cancel === undefined ? 'Cancel' : options.cancel,
				click: function () { fst.dialog.cancel(); }
			} ],
		callback: function (confirmed) { }
	}, options);

	// Set up the postprocess function to check for a submit control in the
	// form returned from the framework (which it likely does not have if
	// using this function to produce the dialog box). If that form does not
	// have a submit control, add a hidden submit control to the form. This
	// Allows for the usual behavior of the ENTER key submitting a form when
	// pressed in a text control.
	if (options.postprocess === undefined)
		options.postprocess = function () { }
	options.postprocess = (function () {
		var postprocess = options.postprocess;
		return function () {
			postprocess.apply(this, arguments);
			if (jQuery('[data-fst="dialog"] form').length &&
					!jQuery('[data-fst="dialog"] form input[type="submit"]').
						length)
				jQuery('[data-fst="dialog"] form').append(
					'<input type="submit" style="display: none;" />');
		};
	})();

	fst.dialog(name, title, options);
}

/***********************************************************************
## fst.dialog.html (mixed html, String title, Object options) {#fst-dialog-html}

Open a dialog box, populated with HTML content.

- ***html***: HTML string, or DOM object
- ***title***: Title of the dialog box
- ***options***: Dialog box options object
***********************************************************************/
fst.dialog.html = function (html, title, options) {

	fst.dialog.close();

	options = jQuery.extend(
		new Object(),
		fst.dialog._options._default,
		(options === undefined ? new Object() : options)
	);

	var w_width = $(window).width();
	var width = options.width ? (options.width > w_width ? w_width : options.width) : (w_width < 600 ? 300 : w_width / 2);

	options.preprocess(options);

	fst.dialog._val = null;
	jQuery('<div data-fst="dialog"></div>').html(html).dialog({
		buttons: options.buttons,
		close: function () {
			$(this).dialog().remove();
			options.callback(fst.dialog._val);
		},
		closeOnEscape: options.closeOnEscape,
		dialogClass: options.dialogClass,
		height: options.height,
		hide: options.hide,
		modal: options.modal,
		position: options.position,
		show: options.show,
		title: title,
		width: width
	});

	if (fst._init.datepicker)
		fst.form.datepicker('div[data-fst="dialog"] input');
	if (fst._init.timeselect)
		fst.form.timeselect('div[data-fst="dialog"] input');

	jQuery('div[data-fst="dialog"] ' + 'form[data-fst="form"] button[data-fst="form-cancel"]').attr('data-fst', 'form-dialog-cancel');

	options.postprocess();

	// Call all registered ready functions, if any
	jQuery.each(fst.dialog._ready, function (idx, fcn) {
		fcn(jQuery('div[data-fst="dialog"]'));
	});
}

fst.dialog._options = new Array();
fst.dialog._options._default = {

	callback: function (val) { }, // Called after dialog is closed
	preprocess: function () { }, // Called before dialog is opened
	postprocess: function () { }, // Called after dialog is opened

	// The remaining options are passed through to jQuery.dialog
	buttons: [],
	closeOnEscape: true,
	dialogClass: "",
	height: 'auto',
	hide: null,
	modal: true,
	position: { my: "center", at: "center", of: window },
	show: null,
	width: 450
};

/***********************************************************************
## fst.dialog.options (String name, Object options) {#fst-dialog-options}

Register options for named dialog.

- ***name***: Dialog name (FST Controller content name)
- ***options***: Dialog options

Sets the default options to be used with the named dialog when opened.
Any of the options may be overridden by specifying options to
[fst.dialog](#fst-dialog).
***********************************************************************/
fst.dialog.options = function (name, options) {
	fst.dialog._options[name] = options;
}

/***********************************************************************
## fst.dialog.options_default (Object options) {#fst-dialog-options-default}

Register default dialog options.

- ***options***: Default dialog options

Sets the default options for all dialogs opened using [fst.dialog](#fst-dialog).
Any of the options may be overridden by specifying options to
[fst.dialog](#fst-dialog) or to [fst.dialog.options](#fst-dialog-options).
***********************************************************************/
fst.dialog.options_default = function (options) {
	jQuery.extend(fst.dialog._options._default, options);
}

/***********************************************************************
## fst.dialog.ready (function fcn) {#fst-dialog-ready}

Register ready function for FST dialogs

- ***fcn***: Ready function

Registers a ready function for FST dialog boxes. When a dialog box is
displayed using any of the *fst.dialog*-related functions, all registered ready
functions are called after the dialog box is displayed. If a postprocess
function is defined for the dialog box, it is called before calling any
of the ready functions. The ready functions receive the dialog box element
as a parameter.
***********************************************************************/
fst.dialog.ready = function (fcn) { fst.dialog._ready.push(fcn); }
fst.dialog._ready = new Array();

/***********************************************************************
## fst.dialog.val (mixed val) {#fst-dialog-val}

Set callback parameter value.

- ***val***: Value to be passed to callback function

Sets the value to be passed to the callback function, if one is defined,
when the dialog box is closed. If no parameter value is defined while
the dialog box is active, the callback function receives *null*. Dialog
boxes opened with [fst.dialog.alert](#fst-dialog-alert) and
[fst.dialog.confirm](#fst-dialog-confirm) set specific
values via this function when the dialog interface buttons are used to
close the dialog.
***********************************************************************/
fst.dialog.val = function (val) { fst.dialog._val = val; }
fst.dialog._val = null;

// Set up response for cancel button on fst form inside dialog
jQuery(document).on('click', 'form[data-fst="form"] button[data-fst="form-dialog-cancel"]', null, function (event) {
	var frm = jQuery(this).closest('form');
	var name = fst.form.name(frm);
	var cancel = fst.form._options[name] && fst.form._options[name].cancel ? fst.form._options[name].cancel : fst.dialog.cancel;
	cancel.apply(frm);
});
