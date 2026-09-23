/***********************************************************************
# FST JavaScript Library

A JavaScript library providing client-side support for FST Framework
applications. This module includes support for Ajax calls and form
submissions to the framework, as well as convenience functions for
modal dialogs.

This library is intended to replace fst-jquery.js and fst-jqueryui.js.
It is written in pure JavaScript; there is no need to include jQuery.

This is considered experimental in FST version 6.2.
***********************************************************************/

// FST Application Framework, Version 6.2
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// If browser back button was used, flush cache
(() => { window.onpageshow = evt => { if (evt.persisted) window.location.reload(); }; })();

const fst = {

	/***********************************************************************
	## fst.ajax (String name, Object opts) {#fst-ajax}

	Initiates an Ajax call to the FST Controller

	- ***name***: Ajax handler name
	- ***options***: Ajax options:
		- *callback*: Callback function following the Ajax call. Receives the server response as its argument.
		- *confirm*: Optional confirmation message. Ajax call proceeds only if user responds in the affirmative.
		- *data*: Data to be passed to the server.
		- *postprocess*: Function to be called after the callback function or user action to cancel upon confirmation. No arguments are passed.
		- *preprocess*: Function to be called prior to confirmation message and Ajax call. Options are passed as the argument. Function may set up and/or modify confirm option.
	***********************************************************************/
	ajax: Object.assign(
		async function (name, options) {

			// Type checks
			if (!(typeof name == 'string' || name instanceof String))
				throw new TypeError("fst.ajax(): 'name' must be a string");
			if (options && !(typeof options == 'object'))
				throw new TypeError("fst.ajax(): 'options' must be an object");

			// Set up options
			const opts = {
				callback: resp => { },
				confirm: null,
				data: { },
				postprocess: () => { },
				preprocess: opts => { },
				...fst.ajax._options['_default'],
				...(fst.ajax._options[name] ?? { }),
				...(options ?? { })
			};

			// Type checks on options
			try { fst.ajax.options._validate(opts); }
			catch (err) { throw new TypeError(`fst.ajax(): ${err.message}`); }

			// Call preprocess funciton passing options, which may be modified
			if (opts.preprocess.call(this, opts) === false)
				return false;

			// Type checks on options again, after preprocess
			try { fst.ajax.options._validate(opts); }
			catch (err) { throw new TypeError(`fst.ajax(): ${err.message}`); }

			// Handle confirmation if confirm option present
			if (opts.confirm) {
				fst.dialog.confirm(opts.confirm, confirmed => {
					if (confirmed)
						fst.ajax.call(this, name, {
							callback: opts.callback,
							confirm: null,
							data: opts.data,
							postprocess: opts.postprocess,
							preprocess: opts => { }
						});
					else
						opts.postprocess.call(this);
				});
				return;
			}

			try {
				// Send to server
				const response = await fetch(`?${name}`, {
					method: 'POST',
					headers: { 'Content-type': 'application/json' },
					body: JSON.stringify(opts.data)
				});

				// Error check
				if (!response.ok)
					throw new Error(`Ajax request failed, server status: ${response.status}`);

				// Copy of response stream (for error checking)
				const response_clone = response.clone();

				// Parse the response and call the callback function
				const ct = response.headers.get('content-type');
				if (ct && ct.includes('application/json')) {
					try {
						const data = await response.json();
						opts.callback.call(this, data);
					}
					catch (jsonError) {
						// Failed to parse JSON response, log the response to the console
						const text = await response_clone.text();
						console.warn("Server did not return JSON. Raw output logged below:");
						console.error(text);
					}
				}
				else {
					const text = await response.text();
					if (ct && ct.includes('text/plain'))
						opts.callback.call(this, text);
					else {
						// In event of server-side error, content type is 'text/html'
						console.warn("Server returned unexpected response. Raw output logged below:");
						console.error(text);
					}
				}
			}
			catch (err) {
				console.error('Failed to communication with server:', err);
			}

			// Call the postprocess function
			opts.postprocess.call(this);
		}, {
			/***********************************************************************
			## fst.ajax.options (String name, Object opts) {#fst-ajax-callback}

			Register options for an Ajax handler.

			- ***name***: Ajax handler name
			- ***options***: Handler options

			Registers default options to be associated with the given named
			Ajax handler. These options override the default options for Ajax
			calls specifically for the named handler, and may be overridden
			when calling fst.ajax.

			If opts is passed as null, options for the named Ajax handler are deleted.
			***********************************************************************/
			_options: { _default: { } },
			options: Object.assign(
				(name, options) => {

					// Type checks
					if (!(typeof name == 'string' || name instanceof String))
						throw new TypeError("fst.ajax.options(): 'name' must be a string");
					if (name == '_default')
						throw new TypeError("fst.ajax.options(): cannot set default options, use fst.ajax.options.default()");
					if (!(options === null || typeof options == 'object'))
						throw new TypeError("fst.ajax.options(): 'options' must be an object or must be specified as null");

					try { fst.ajax.options._validate(options); }
					catch (err) { throw new TypeError(`fst.ajax.options(): ${err.message}`); }

					if (options === null)
						delete fst.ajax._options[name];
					else
						fst.ajax._options[name] = options;
				}, {
					/***********************************************************************
					## fst.ajax.options.default (Object options) {#fst-ajax-callback}

					Register options for an Ajax handler.

					- ***options***: Default Ajax handler options

					Registers default options to be associated with Ajax calls. Upon
					initiating an Ajax call, any options specified by the named Ajax
					action and/or by call to fst.ajax() will extend the default options.
					***********************************************************************/
					default: options => {

						// Type checks
						try { fst.ajax.options._validate(options); }
						catch (err) { throw new TypeError(`fst.form.options.default(): ${err.message}`); }

						if (options === null)
							fst.ajax._options['_default'] = { };
						else
							fst.ajax._options['_default'] = options;
					},

					_validate: options => {
						// Validate Ajax options object
						if (options.callback && !(typeof options.callback == 'function'))
							throw new TypeError("'callback' option must be a function");
						if (options.confirm && !(typeof options.confirm == 'string' || options.confirm instanceof String))
							throw new TypeError("'confirm' option must be a string");
						if (options.preprocess && !(typeof options.preprocess == 'function'))
							throw new TypeError("'preprocess' option must be a function");
						if (options.postprocess && !(typeof options.postprocess == 'function'))
							throw new TypeError("'postprocess' option must be a function");
					}
				}
			)
		}
	),
	
	/***********************************************************************
	## fst.content (String area) {#fst-content}

	Populate framework content area via Ajax

	- ***name***: Name of content area (from template), optional

	Sends an Ajax request to the framework, which produces HTML content to
	replace the content of the named content area in the page. The content
	area is (usually) a DIV element that was generated when the framework
	was initially producing the page. If name is not given, replaces the
	content of the main content area.
	***********************************************************************/
	content: Object.assign(
		(name, options) => {

			// Type checks
			if (!(typeof name == 'string' || name instanceof String))
				throw new TypeError("fst.content(): 'name' must be a string");
			if (options && !(typeof options == 'object'))
				throw new TypeError("fst.content(): 'options' must be an object");

			// Set up options
			const opts = {
				confirm: null,
				data: { },
				postprocess: () => { },
				preprocess: () => { },
				...fst.content._options['_default'],
				...(fst.content._options[name] ?? { }),
				...(options ?? { })
			};

			// Type checks on options
			try { fst.content.options._validate(opts); }
			catch (err) { throw new TypeError(`fst.content(): ${err.message}`); }

			// Check for valid content area
			if (!fst.content.element(name))
				throw new TypeError(`fst.content(): content area "${name}" is not valid`);

			// Call preprocess function passing options, which may be modified
			if (opts.preprocess.call(this, opts) === false)
				return;

			// Type checks on options again, after preprocess
			try { fst.content.options._validate(opts); }
			catch (err) { throw new TypeError(`fst.content(): ${err.message}`); }

			// Handle confirmation if confirm option present
			if (opts.confirm) {
				fst.dialog.confirm(opts.confirm, confirmed => {
					if (confirmed)
						fst.content.call(this, name, {
							confirm: null,
							data: opts.data(),
							postprocess: opts.postprocess,
							preprocess: opts => { }
						});
					else
						opts.postprocess.call(this);
				});
				return;
			}

			// Get content via ajax
			fst.ajax('_content', {
				callback: resp => fst.content.element(name).innerHTML = resp,
				data: { _content: name }
			});
		}, {
			/***********************************************************************
			## fst.content.element (String name) {#fst-content-element}

			Get DOM element for FST content area

			- ***name***: Name of content area (from template), optional

			Returns the DOM element for the named FST content area. If name
			is not given, returns the DOM element of the main content area.
			***********************************************************************/
			element: name => {

				// Type check
				if (!(typeof name == 'string' || name instanceof String))
					throw new TypeError("fst.content.element(): 'name' must be a string");

				return document.querySelector(name ? `[data-fst="content-${name}"]` : '[data-fst="content"');
			},

			_options: { _default: { }},
			options: Object.assign(
				(name, options) => {
					fst.content._options[name] = options;

					// type checks
					if (!(typeof name == 'string' || name instanceof String))
						throw new TypeError("fst.content.options(): 'name' must be a string");
					if (name == '_default')
						throw new TypeError("fst.content.options(): cannot set default options, use fst.content.options.default()");
					if (!(options === null || typeof options == 'object'))
						throw new TypeError("fst.content.options(): 'options' must be an object")

					try { fst.content.options._validate(options); }
					catch (err) { throw new TypeError(`fst.content.options(): ${err.message}`); }

					if (options === null)
						delete fst.content._options[name];
					else
						fst.ajax._options[name] = options;
				} , {
					default: options => {

						// Type checks
						if (!(options === null || typeof options == 'object'))
							throw new TypeError("fst.content.options(): 'options' must be an object")
						try { fst.content.options._validate(options); }
						catch (err) { throw new TypeError(`fst.content.options(): ${err.message}`); }

						if (options === null)
							fst.ajax._options['_default'] = { };
						else
							fst.content._options[_default] = options;
					},

					_validate: options => {
						// Validate Ajax options object
						if (options.confirm && !(typeof options.confirm == 'string' || options.confirm instanceof String))
							throw new TypeError("'confirm' option must be a string");
						if (options.preprocess && !(typeof options.preprocess == 'function'))
							throw new TypeError("'preprocess' option must be a function");
						if (options.postprocess && !(typeof options.postprocess == 'function'))
							throw new TypeError("'postprocess' option must be a function");
					}
				}
			)
		}
	),

	/***********************************************************************
	## fst.dialog (String html, Options options) {#fst-dialog}

	Opens a modal dialog

	- ***html***: String, plain text or HTML, message to appear in dialog
	- ***callback***: Function, called upon dialog close
	- ***buttons***: Array, Strings for dialog buttons
	- ***options***: Object, options:
		- *escape*: Boolean, allow Escape key to close, default true

	Opens a modal dialog box, populating content with the provided HTML code,
	with buttons to close the dialog.
	
	The *buttons* options is to be supplied as an array of Strings, one for
	each button to appear. This may be explicitly given as null in which case
	no buttons will appear. If passed as null, the dialog can only be closed
	programatically.

	The *callback* function is called upon dialog close. Function receives
	one String argument, the text of the button used to close the dialog. If
	the dialog is closed using the Escape key, an empty string is passed.
	***********************************************************************/
	dialog: Object.assign(
		(html, callback, buttons, options) => {

			// Ensure no dialog is currently open
			if (document.querySelector('dialog[data-fst="dialog"]'))
				return;

			// Type checks
			if (!(typeof html == 'string' || html instanceof String))
				throw new TypeError("fst.dialog(): 'html' must be a string");
			if (!(typeof callback == 'function'))
				throw new TypeError("fst.dialog(): 'callback' must be a function");
			if (!(buttons === null || buttons instanceof Array))
				throw new TypeError("fst.dialog(): 'buttons' must be an array of strings");
			if (buttons)
				buttons.forEach(btn => {
					if (!(typeof btn == 'string' || btn instanceof String))
						throw new TypeError("fst.dialog(): 'buttons' must be an array of strings");
				});
			if (options && !(typeof options == 'object'))
				throw new TypeError("fst.dialog(): 'options' must be an object");

			// Apply default options
			const opts = { escape: true, ...(options ?? { }) }

			// Create the dialog element
			const dlg = document.createElement('dialog');
			dlg.dataset.fst = 'dialog';

			// Create the dialog content
			dlg.innerHTML = buttons ? `<div>${html}</div><hr /><form method="dialog"></form>` : `<div>${html}</div>`;

			// Add the buttons
			const frm = dlg.querySelector('form');
			buttons.forEach(text => {
				const btn = document.createElement('button');
				btn.value = text;
				btn.textContent = text;
				frm.appendChild(btn);
			});

			// When the dialog closes...
			dlg.addEventListener('close', () => {
				callback(dlg.returnValue)
				dlg.remove();
			});

			// Prevent escape key?
			if (!opts.escape) {
				dlg.addEventListener('cancel', event => event.preventDefault());
				dlg.addEventListener('keydown', event => {
					if (event.key == 'Escape')
						event.preventDefault();
				});
			}

			// Add dialog to the DOM
			document.body.appendChild(dlg);

			// Show the dialog
			dlg.showModal();

		}, {
			/***********************************************************************
			## fst.dialog.alert (String html, Object options) {#fst-dialog-alert}

			Opens a modal alert/informational dialog box

			- ***html***: String, plain text or HTML, message to appear in dialog
			- ***options***: Object, options:
				- *button*: String, text to appear on close button, default "Close"
				- *callback*: Function, called upon dialog close
				- *escape*: Boolean, allow Escape key to close, default true

			Opens a modal dialog box, populating content with the provided message.
			Dialog box includes one button for closing the dialog.
			
			If *callback* is provided, it is called when the dialog box is closed.
			The button text is passed as an argument, or an empty string if closed
			using the Escape key.
			***********************************************************************/
			alert: (html, options) => {

				// Type checks
				if (!(typeof html == 'string' || html instanceof String))
					throw new TypeError("fst.dialog.alert(): 'html' must be a string");
				if (options && !(typeof options == 'object'))
					throw new TypeError("fst.dialog.alert(): 'options' must be an object");

				// Apply  default options
				const opts = {
					button: "Close",
					callback: () => { },
					escape: true,
					...(options ?? { })
				}

				// Type checks on options
				if (!(typeof opts.button == 'string' || opts.button instanceof String))
					throw new TypeError("fst.dialog.alert(): option 'button' must be a string");
				if (!(typeof opts.callback == 'function'))
					throw new TypeError("fst.dialog.alert(): option 'callback' must be a function");

				// Present the alert using fst.dialog
				fst.dialog(html, opts.callback, [ opts.button ], { escape: opts.escape });
			},

			/***********************************************************************
			## fst.dialog.close () {#fst-dialog-close}

			Closes the dialog box
			***********************************************************************/
			close: () => {
				const dlg = document.querySelector('dialog[data-fst="dialog"]');
				if (dlg)
					dlg.close();
			},

			/***********************************************************************
			## fst.dialog.confirm (String message, Function callback, Object options) {#fst-dialog-confirm}

			Opens a modal confirmation dialog box

			- ***message***: String (text or HTML), message to appear in dialog
			- ***callback***: Function, called upon dialog confirmation
			- ***options***: Object, options:
				- button: String, text to appear on confirmation button, default "OK"
				- button_cancel: String, text to appear on cancel button, default "Cancel"
				- escape: Boolean, allow Escape key to close, default true

			Function *callback* is called when the dialog is closed. It is passed a
			single Boolean argument, true if dialog is closed withthe confirmation
			("OK") button, or false if closed with the cancel button or the Escape
			key.
			***********************************************************************/
			confirm: (html, callback, options) => {

				// Type checks
				if (!(typeof html == 'string' || html instanceof String))
					throw new TypeError("fst.dialog.confirm(): 'html' must be a string");
				if (!(typeof callback == 'function'))
					throw new TypeError("fst.dialog.confirm(): 'callback' must be a function");
				if (options && !(typeof options == 'object'))
					throw new TypeError("fst.dialog.confirm(): 'options' must be an object");

				// Apply default options
				const opts = {
					button: "OK",
					button_cancel: "Cancel",
					escape: true,
					...(options ?? { })
				};

				// Type checks on options
				if (!(typeof opts.button == 'string' || opts.button instanceof String))
					throw new TypeError("fst.dialog.confirm(): option 'button' must be a string");
				if (!(typeof opts.button_cancel == 'string' || opts.button_cancel instanceof String))
					throw new TypeError("fst.dialog.confirm(): option 'button_cancel' must be a string");

				// Present the confirmation using fst.dialog
				fst.dialog(html, btn => { callback(btn == opts.button); }, [ opts.button, opts.button_cancel ], { escape: opts.escape });
			},

			/***********************************************************************
			## fst.dialog.content (String name, Object options) {#fst-dialog-alert}

			Opens a modal informational dialog box with Controller-provided content

			- ***name***: String, name of the content area
			- ***options***: Object, options:
				- *buttons*: Array of Strings for buttons, default ['Close']
				- *callback*: Function, called upon dialog close
				- *escape*: Boolean, allow Escape key to close, default true

			Opens a modal dialog box, populating content with HTML provided by
			the Controller.
			
			If *callback* is provided, it is called when the dialog box is closed.
			The button text is passed as an argument, or an empty string if closed
			using the Escape key.
			***********************************************************************/
			content: (name, options) => {

				// Type checks
				if (!(typeof name == 'string' || name instanceof String))
					throw new TypeError("fst.dialog.content(): 'name' must be a string");
				if (options && !(typeof options == 'object'))
					throw new TypeError("fst.dialog.content(): 'options' must be an object");

				const opts = {
					buttons: [ 'Close' ],
					callback: resp => { },
					data: { },
					escape: true,
					...(options ?? { })
				};

				// Type checks on options
				if (!(opts.buttons instanceof Array))
					throw new TypeError("fst.dialog.alert(): option 'buttons' must be an array");
				if (!(typeof opts.callback == 'function'))
					throw new TypeError("fst.dialog.alert(): option 'callback' must be a function");

				fst.ajax('_content', {
					data: {...opts.data, _content: name },
					callback: resp => {
						fst.dialog(resp, opts.callback, opts.buttons, opts);
					}
				});
			}
		}
	),
	
	/***********************************************************************
	## fst.form (String|object form) {#fst-form}

	Get FST form element

	- ***form***: FST form name or DOM element

	If a *form* is given as a string, return the FST form element with the
	given name. If an object, searches the DOM for the FST form element to
	which the element belongs (which may be *form* itself). If no such element
	exists, returns *null*.

	Note that a *form* element is returned only if such form was created by
	the FST Controller.
	***********************************************************************/
	form: Object.assign(
		form => {

			// If 'form' given as a string, search the DOM for FST form with the given name
			if (typeof form === 'string' || form instanceof String)
				return document.querySelector(`form[data-fst="form"][action="?_form=${form}"]`);

			// If 'form' given as an Element, search DOM upward for FST form element
			if (form instanceof Element) // Note: does not work across iframe's
				return form.closest('form[data-fst="form"]'); // Note: may return itself

			// No FST form found
			return null;
		}, {
			/***********************************************************************
			## fst.form.options (String name, Object opts) {#fst-form-options}

			Register options for an FST form.

			- ***name***: FST form name
			- ***options***: Processing options

			Registers default options to be associated with the given named
			FST form. These options override the default options for form
			submits specifically for the named form, and may be overridden.

			If opts is passed as null, options for the named form are deleted.
			***********************************************************************/
			_options: { _default: { } },
			options: Object.assign(
				(name, options) => {

					// Type checks
					if (!(typeof name == 'string' || name instanceof String))
						throw new TypeError("fst.form.options(): 'name' must be a string");
					if (!(options === null || typeof options == 'object'))
						throw new TypeError("fst.form.options(): 'options' must be an object or must be specified as null");

					try { fst.form.options._validate(options); }
					catch (err) { throw new TypeError(`fst.form.options(): ${err.message}`) }

					if (options === null)
						delete fst.form._options[name];
					else
						fst.form._options[name] = options;
				}, {
					/***********************************************************************
					## fst.form.options.default (Object options) {#fst-ajax-callback}

					Register default form options.

					- ***options***: Default form options

					Registers default options to be associated with forms. Upon
					submitting a form, any options specified for the named form
					action and/or by call to fst.form.submit() will extend the default
					options.
					***********************************************************************/
					default: options => {
						
						try { fst.form.options._validate(options); }
						catch (err) { throw new TypeError(`fst.form.options.default(): ${err.message}`) }
						
						if (options === null)
							fst.form._options['_default'] = { };
						else
							fst.form._options['_default'] = options;
					},

					_validate: options => {
						// Type checks on form options object
						if (options.callback && !(typeof options.callback == 'function'))
							throw new TypeError("'callback' option must be a function");
						if (options.callback_fail && !(typeof options.callback_fail == 'function'))
							throw new TypeError("'callback_fail' option must be a function");
						if (options.confirm && !(typeof options.confirm == 'string' || options.confirm instanceof String))
							throw new TypeError("'callback' option must be a function");
						if (options.preprocess && !(typeof options.preprocess == 'function'))
							throw new TypeError("'preprocess' option must be a function");
						if (options.postprocess && !(typeof options.postprocess == 'function'))
							throw new TypeError("'postprocess' option must be a function");
					}
				}
			),

			/***********************************************************************
			## fst.form.error (String|Element form, String name, String message) {#fst-form-error}

			Error message for form field

			- ***form***: FST form name or element
			- ***name***: Field name
			- ***message***: Error message

			Populates the error message area for the given form field. This is
			the message area designated by the default FST formatting for a
			form. If the form is not generated using default formatting, the
			message area might not exist, in which no DOM updates are done.
			***********************************************************************/
			error: (form, name, message) => {

				// Type checks
				const frm = fst.form(form);
				if (!frm)
					throw new TypeError("fst.form.error(): 'form' must be a valid FST form");
				if (!(typeof name == 'string' || name instanceof String))
					throw new TypeError("fst.form.error(), 'name' must be a string");
				if (!(typeof message == 'string' || message instanceof String))
					throw new TypeError("fst.form.error(), 'message' must be a string");

				// Get error message area for the named field
				// Note that the error message might not be found if for is not default FST form formatting
				const err = frm.querySelector(`div[data-fst="form-controls-row"]:has([name="${name}"]) + div[data-fst="form-error"]`);

				// Add message to message area, if found
				if (err) {
					const div = document.createElement('div');
					div.textContent = message;
					err.append(div);
				}
			},

			/***********************************************************************
			## fst.form.errors (String|Element form, Object errors) {#fst-form-errors}

			Error message for form field

			- ***form***: FST form name or element
			- ***errors***: Object of error messages

			Populates the error message area for each name/value pair given
			in *errors*. The name given is interpreted as the name of an FST
			form field and the value is the error message.
			***********************************************************************/
			errors: Object.assign(
				(form, errors) => {

					// Type checks
					const frm = fst.form(form);
					if (!frm)
						throw new TypeError("fst.form.errors(): 'form' must be a valid FST form");
					if (!(typeof errors == 'object'))
						throw new TypeError("fst.form.errors(), 'errors' must be an object");

					Object.entries(errors).forEach(([key, value]) => {
						fst.form.error(frm, key, value);
					});
				}, {
					/***********************************************************************
					## fst.form.errors.clear (String|Element form) {#fst-form-errors-clear}

					Clear FST form error messages

					- ***form***: FST form name or element

					Clears the error message areas for all fields in an FST-generated form.
					***********************************************************************/
					clear: (form) => {

						// Type checks
						const frm = fst.form(form);
						if (!frm)
							throw new TypeError("fst.form.errors(): 'form' must be a valid FST form");

						// Clear errors
						fst.form(form).querySelectorAll('div[data-fst="form-error"]').forEach((div, idx) => {
							div.replaceChildren();
						});
					}
				}
			),

			/***********************************************************************
			## fst.form.fname (Object obj) {#fst-form-fname}

			Return the name of the FST form containing the given object

			- ***obj***: A DOM element

			Given a DOM element, search the DOM upward to find the nearest FST form
			element, then returns the name of the form. If no such form element it
			found, returns null.
			***********************************************************************/
			fname: obj => {

				// Type check
				if (!(obj instanceof Element))
					throw new TypeError("fst.form.name(): 'obj' must be a DOM element");

				const frm = fst.form(obj);
				const matches = frm.action.match(/\?_form=(\w+)/);
				return matches[1];
			},

			/***********************************************************************
			## fst.form.submit (String name, Object options) {#fst-form-submit}

			Submits the FST form with the given name

			- ***form***: FST form name or DOM element
			- ***options***: Form options

			Submits the FST *form* element with the given name. Name is the name
			as assigned when created by the FST controller.
			***********************************************************************/
			submit: (form, options) => {

				// Type checks
				const frm = fst.form(form);
				if (!frm)
					throw new TypeError("fst.form.submit(): 'form' must be a valid FST form");
				if (options && !(typeof options == 'object'))
					throw new TypeError("fst.form.submit(): 'options' must be an object");

				// Get FST form name
				// const matches = frm.action.match(/\?_form=(\w+)/);
				// const fname = matches[1];
				const fname = fst.form.fname(frm);

				// Set up form options
				const opts = {
					preprocess: opts => { },
					callback: resp => { fst.redirect.back(); },
					callback_fail: resp => { fst.form.errors(frm, resp.errors); },
					confirm: null,
					postprocess: () => { },
					progress: frm.querySelector('input[type="file"]') !== null, // Form has a file input
					...fst.form._options['_default'],
					...(fst.form._options[fname] ?? { }),
					...(options ?? { })
				};

				// Type checks on options
				try { fst.form.options._validate(opts); }
				catch (err) { throw new TypeError(`fst.form.submit(): ${err.message}`); }

				// Call preprocessor function (if returns false, quit)
				if (opts.preprocess.call(frm, opts) === false)
					return;

				// Type checks on options, again after preprocess
				try { fst.form.options._validate(opts); }
				catch (err) { throw new TypeError(`fst.form.submit(): ${err.message}`); }

				// If confirm option, get confirmation response
				if (opts.confirm) {
					fst.dialog(opts.confirm, ret => {
							// If confirmed, re-submit w/o preprocess or confirm
							if (ret == 'OK')
								fst.form.submit(form, {
									callback: opts.callback,
									callback_fail: opts.callback_fail,
									confirm: null,
									postprocess: opts.postprocess,
									preprocess: () => { },
									progress: opts.progress
								});
							// Not confirmed, call postprocess
							// TODO: re-think postprocess after cancelling submit
							else
								opts.postprocess.call(frm);
						}, [ 'OK', 'Cancel' ]);
					return;
				}

				// Clear form errors
				fst.form.errors.clear(frm);

				// Create request object
				const xhr = new XMLHttpRequest();

				// If progress option, set up progress indicator
				if (opts.progress) {

					// Show progress overlay
					fst.progress();

					// Monitor upload progress
					xhr.upload.addEventListener('progress', evt => {
						if (evt.lengthComputable)
							fst.progress(Math.round((evt.loaded / evt.total) * 100));
					});
				}

				// Handle server response (when upload completes)
				xhr.addEventListener('load', () => {
					if (xhr.status >= 200 && xhr.status < 300) {
						try {
							// Get FST form submit response
							const resp = JSON.parse(xhr.responseText);
							// Call callback function if validation success, or callback_fail if errors
							resp.valid ? opts.callback.call(frm, resp) : opts.callback_fail.call(frm, resp);
						}
						catch (err) {
							// TODO: show alert box w/ error???
							console.error('Could not parse JSON response:', err);
							console.error(xhr.responseText);
						}
						finally {
							// Hide progress (if requested) and call postprocess function, even if errors
							if (opts.progress)
								fst.progress.off();
							opts.postprocess.call(frm);
						}
					}
					else {
						console.error('Server error status:', xhr.status);
					}
				});

				// Handle network failures
				xhr.addEventListener('error', () => {
					// TODO: show alert box w/ error???
					console.error('Network transaction failed.');
				});

				// Get the form data and send the request
				const data = new FormData(frm);
				xhr.open('POST', frm.action);
				xhr.send(data);
			}
		}
	),
	
	/***********************************************************************
	## fst.progress (int percentage) {#fst-progress}

	Show/update progress overlay

	- ***percentage***: Percentage of progress, default 0
	
	Displays an overlay for showing progress (typically for form file uploads)
	and provides a visual of the percentage given.
	***********************************************************************/
	progress: Object.assign(
		percentage => {
			const prog = document.querySelector('div[data-fst="progress"');
			prog.style.display = 'flex';
			const prog_bar = prog.querySelector('.progress-bar');
			const prog_pct = percentage ?? 0;
			prog_bar.innerHTML = `${prog_pct}%`;
			prog_bar.style.background = `linear-gradient(to right, #3498db ${prog_pct}%, white ${prog_pct}%)`;
		}, {
			/***********************************************************************
			## fst.progress.off () {#fst-progress-off}

			Hide the progress overlay
			***********************************************************************/
			off: () => {
				const prog = document.querySelector('div[data-fst="progress"');
				prog.style.display = 'none';
			}
		}
	),
	
	/***********************************************************************
	## fst.redirect (String uri) {#fst-redirect}

	Redirects to the given location.

	- ***uri***: Relative or absolute URI

	Redirect the browser to the given location. The location may be absolute,
	in which case it is used as-is, or relative, in which case it is to be
	given relative to the application root (thus it corresponds to the FST
	controller arguments).
	***********************************************************************/
	redirect: Object.assign(
		uri => {
				// Type checks
				if (!(typeof uri == 'string' || uri instanceof String))
					throw new TypeError("fst.redirect(): 'html' must be a string");

				window.location = fst.uri(uri);
			}, {

			/***********************************************************************
			## fst.redirect.back (int n) {#fst-redirect-back}

			Redirect to a browser history location.

			- ***n***: Number of pages to go back (default is 1)

			Redirect the browser to some number of pages in the browser history. If the
			number of pages is not given, redirects to the previous page.
			***********************************************************************/
			back: n => {
				// Type checks
				if (n && !(typeof uri == 'string' || uri instanceof String))
					throw new TypeError("fst.redirect(): 'html' must be a string");

				n === undefined ? window.history.back() : window.history.go(-n);
			},

			/***********************************************************************
			## fst.redirect.home () {#fst-redirect-home}

			Redirects to the application home page.
			***********************************************************************/
			home: () => fst.redirect('')
		}
	),

	/***********************************************************************
	## fst.reload () {#fst-reload}

	Reload the current page.
	***********************************************************************/
	reload: () => window.location.reload(),
	
	/***********************************************************************
	## fst.trigger (String name, function fcn) {#fst-trigger}

	Registers a trigger function.

	- ***name***: Trigger name
	- ***fcn***: Trigger function

	Associates the given function with the given trigger name. Once a trigger
	is registered, following a link with href ?NAME cause the trigger function
	to be called (by default, triggers initiate an Ajax call). Passing null
	as the function deletes the previously registered trigger.
	***********************************************************************/
	_triggers: [],
	trigger: (name, fcn) => {

		// Type checks
		if (!(typeof name == 'string' || name instanceof String))
			throw new TypeError("fst.trigger(): 'name' must be a string");
		if (!(typeof fcn == 'function'))
			throw new TypeError("fst.trigger(): 'fcn' must be a function");

		if (fcn === null)
			delete fst._triggers[name];
		else
			fst._triggers[name] = fcn;
	},
	
	/***********************************************************************
	## fst.uri (String uri) {#fst-uri}

	Convert relative URI to absolute URI.

	- ***uri***: URI string (absolute or relative)
	- **Return**: An absolute URI string

	Converts relative URI strings to an absolute URI. Relative URI strings
	given to this function are assumed to be relative to the application's
	root, in which case the correct absolute URI is returned. If an absolute
	URI is given, the URI is returned unmodified.
	***********************************************************************/
	uri: uri => {

		// Type checks
		if (!(typeof uri == 'string' || uri instanceof String))
			throw new TypeError("fst.uri(): 'uri' must be a string");

		var absuri = /^(\w+:|\/|\?)/; // begins with protocol, '/', or '?'
		return absuri.test(uri) ? uri : (_approot ? _approot : '/') + uri;
	},

	/***********************************************************************
	## fst.wait () {#fst-wait}

	Displays a wait overlay with spinner

	Shows a window overlay with a spinner preventing user interaction with
	page elements. Call wait.off to remove the overlay.
	***********************************************************************/
	wait: Object.assign(
		msg => {
			const wait = document.querySelector('div[data-fst="wait"]');
			wait.style.display = 'flex';
		}, {
			/***********************************************************************
			## fst.wait.off () {#fst-wait-off}

			Removes the wait overlay

			Removes the wait overlay created by fst.wait.
			***********************************************************************/
			off: () => {
				const wait = document.querySelector('div[data-fst="wait"]');
				wait.style.display = 'none';
			}
		}
	)
}

// window.addEventListener('DOMContentLoaded', () => {
// 	console.log('DOM is fully built.');

// 	document.querySelectorAll('form').forEach((frm, idx) => {
// 		frm.addEventListener('input', () => {
// 			fst.dirty = true;
// 		});
// 		frm.addEventListener('submit', () => {
// 			fst.dirty = false;
// 		});
// 	});
// });

// Once DOM is loaded, add event handlers
window.addEventListener('DOMContentLoaded', () => {

	// Event handler for FST button click
	document.addEventListener('click', evt => {

		// Check target for button, if no button then no action
		const btn = evt.target.closest('button');
		if (!btn)
			return;

		// Check if an FST form cancel button
		if ((btn.dataset.fst ?? '') == 'form-cancel') {
			evt.preventDefault();

			// Get form name
			const fname = fst.form.fname(btn);

			// If a cancel handler is defined, call it
			if (fst.form._options[fname] && fst.form._options[fname].cancel)
				fst.form._options[fname].cancel();
			// Else redirect back
			else
				fst.redirect.back();
		
			return;
		}

		// Check for FST button href
		const href = btn.dataset.fstHref ?? false;
		if (href) {
			evt.preventDefault();

			// If href begins with '?', process FST action
			if (href.substr(0, 1) == '?') {

				// Determine the action
				const action = href.substr(1);

				// If a defined trigger, call the trigger function
				if (fst._triggers[action])
					fst._triggers[action].call(evt.target);

				// Else initiate an Ajax call
				else {
					// Gather any data, removing any FST-related attributes
					const data = { ...evt.target.dataset };
					const re = /^fst([A-Z]|$)/;
					Object.keys(data).forEach(key => {
						if (re.test(key))
							delete data[key];
					});
					// Ajax call
					fst.ajax(href, { data: data });
				}
			}

			// Else assume a URI and redirect
			else
				fst.redirect(href);
		}
	});

	// Event handler for action A links
	document.addEventListener('click', evt => {

		// Search for A tag w/ action link
		const a = evt.target.closest('a[href^="?"]');
		if (!a)
			return;

		evt.preventDefault();

		// Get the action (follows "?")
		const action = a.getAttribute('href').substr(1);

		// If a defined trigger, call trigger function passing A element as context
		if (fst._triggers[action]) {
			fst._triggers[action].call(a);
			return;
		}

		// Get dataset from target, removing FST-specific attributes
		const data = { ...a.dataset };
		const re = /^fst([A-Z]|$)/;
		Object.keys(data).forEach(key => {
			if (re.test(key))
				delete data[key];
		});

		// Initiate Ajax call
		fst.ajax(action, { data: data });
	});
});

// document.addEventListener('input', evt => {
// 	const frm = fst.form(evt.target);
// 	if (frm.dataset.fst == 'form')
// 		frm.dataset.fstModified = 'modified';
// });

document.addEventListener('submit', async evt => {

	// If an FST-generated form, handle form submission via fst.form.submit
	if (evt.target.dataset.fst && evt.target.action && evt.target.dataset.fst == 'form' && evt.target.action.match(/\?_form=\w+$/)) {
		evt.preventDefault();
		fst.form.submit(evt.target);
	}
});

window.addEventListener('load', () => {

	// Create progress overlay
	const prog = document.createElement('div');
	prog.dataset.fst = 'progress';
	prog.style.display = 'none';
	prog.innerHTML = '<div class="progress-bar"></div>';
	document.body.appendChild(prog);

	// Create wait overlay
	const wait = document.createElement('div');
	wait.dataset.fst = 'wait';
	wait.style.display = 'none';
	wait.innerHTML = '<div class="spinner"></div>';
	document.body.appendChild(wait);

	// TODO: Create styles for overlays???
});

// window.addEventListener('beforeunload', (evt) => {
// 	// if (fst.form._modified) {
// 		evt.preventDefault();
// 		evt.returnValue = ''; // Legacy browser compatibility
// 	// }
// });
