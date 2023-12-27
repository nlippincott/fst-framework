<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * List Generation Engine, abstract base class.
 *
 * Abstract base class for the List Generation Engine. To use the engine,
 * one must derive a class from ListEngine, and override, at a minimum, the
 * following protected methods: 'item', and 'items'. The following
 * protected methods may be overridden to affect the engine's behavior:
 * 'list_id', 'list_class', 'item_class', and 'item_id'.
 */
abstract class ListEngine {

	/**
	 * Returns items for list generation.
	 *
	 * Derived classes must override this function to return an array of
	 * items for list generation. One list item is generated for each element
	 * in the returned array.
	 *
	 * @return array Array of list items
	 */
	abstract protected function items ();

	/**
	 * Get content for the given item.
	 *
	 * Derived classes must override this function to return the content to
	 * be produces for the given item. The value returned will be used as-is
	 * for the list item content.
	 *
	 * @param mixed $item One item from the items array
	 * @return string HTML code to serve as the content of the list item
	 */
	abstract protected function item ($item);

	/**
	 * Get additional attributes for top-level list element.
	 *
	 * Derived classes may override this function to provide name/value
	 * pairs of attributes to be included in the top-level UL or OL tag
	 * that is generated when list output is produced.
	 *
	 * @return array Associative array of name/value pairs
	 */
	protected function list_attr () { return array(); }

	/**
	 * Get class for top-level list element.
	 *
	 * Derived classes may override this function to provide a class name
	 * to be assigned to the top-level UL or OL tag that is generated when
	 * list output is produced.
	 *
	 * @return string Class name
	 */
	protected function list_class () { return ''; }

	/**
	 * Get ID for top-level list element.
	 *
	 * Derived classes may override this function to provide an ID
	 * to be assigned to the top-level UL or OL tag that is generated when
	 * list output is produced.
	 *
	 * @return string ID value
	 */
	protected function list_id () { return ''; }

	/**
	 * Get additional attributes for the given list item.
	 *
	 * Derived classes may override this function to provide name/value
	 * pairs of attributes to be included in the LI tag for the given item.
	 *
	 * @return array Associative array of name/value pairs
	 */
	protected function item_attr ($item) { return array(); }

	/**
	 * Get class for the given list item.
	 *
	 * Derived classes may override this function to provide a class name
	 * to be assigned to the LI element for the given list item.
	 *
	 * @return string Class name
	 */
	protected function item_class ($item) { return ''; }

	/**
	 * Get ID for the given list item.
	 *
	 * Derived classes may override this function to provide an ID
	 * to be assigned to the LI element for the given list item.
	 *
	 * @return string ID value
	 */
	protected function item_id ($item) { return ''; }

	/**
	 * Get list-is-ordered flag.
	 *
	 * Derived classes may override this function to indicate whether or not
	 * the generated list is to be ordered. The default implementation returns
	 * false, thus indicating that an unordered list is to be generated.
	 *
	 * @return bool List-is-ordered flag
	 */
	protected function ordered () { return false; }

	/**
	 * Generates the HTML code for the list.
	 * 
	 * @return string HTML code generated for the list
	 */
	public function __toString () {

		$tag = $this->ordered() ? 'ol' : 'ul';
		$attr = $this->list_attr();
		$class = $this->list_class();
		$id = $this->list_id();

		if ($class) $attr['class'] = $class;
		if ($id) $attr['id'] = $id;

		ob_start();
		print "<$tag" . Framework::attr($attr) . '>';

		foreach ($this->items() as $item) {
			$attr = $this->item_attr($item);
			$class = $this->item_class($item);
			$id = $this->item_id($item);

			if ($class) $attr['class'] = $class;
			if ($id) $attr['id'] = $id;

			print '<li' . Framework::attr($attr) . '>';
			print $this->item($item);
			print '</li>';
		}

		print "</$tag>";

		return ob_get_clean();
	}
}
