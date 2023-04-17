<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

/// @cond
namespace FST;
/// @endcond

/**
 * @brief List Generation Engine, abstract base class
 *
 * Abstract base class for the List Generation Engine. To use the engine,
 * one must derive a class from ListEngine, and override, at a minimum, the
 * following protected methods: 'item', and 'items'. The following
 * protected methods may be overridden to affect the engine's behavior:
 * 'list_id', 'list_class', 'item_class', and 'item_id'.
 */
abstract class ListEngine {

	/**
	 * @brief Returns items for list generation.
	 * @retval array Array of list items
	 *
	 * Derived classes must override this function to return an array of
	 * items for list generation. One list item is generated for each element
	 * in the returned array.
	 */
	abstract protected function items ();

	/**
	 * @brief Get content for the given item.
	 * @param mixed $item One item from the items array
	 * @retval string HTML code to serve as the content of the list item
	 *
	 * Derived classes must override this function to return the content to
	 * be produces for the given item. The value returned will be used as-is
	 * for the list item content.
	 */
	abstract protected function item ($item);

	/**
	 * @brief Get additional attributes for top-level list element.
	 * @retval array Associative array of name/value pairs
	 *
	 * Derived classes may override this function to provide name/value
	 * pairs of attributes to be included in the top-level UL or OL tag
	 * that is generated when list output is produced.
	 */
	protected function list_attr () { return array(); }

	/**
	 * @brief Get class for top-level list element.
	 * @retval string Class name
	 *
	 * Derived classes may override this function to provide a class name
	 * to be assigned to the top-level UL or OL tag that is generated when
	 * list output is produced.
	 */
	protected function list_class () { return ''; }

	/**
	 * @brief Get ID for top-level list element.
	 * @retval string ID value
	 *
	 * Derived classes may override this function to provide an ID
	 * to be assigned to the top-level UL or OL tag that is generated when
	 * list output is produced.
	 */
	protected function list_id () { return ''; }

	/**
	 * @brief Get additional attributes for the given list item.
	 * @retval array Associative array of name/value pairs
	 *
	 * Derived classes may override this function to provide name/value
	 * pairs of attributes to be included in the LI tag for the given item.
	 */
	protected function item_attr ($item) { return array(); }

	/**
	 * @brief Get class for the given list item.
	 * @retval string Class name
	 *
	 * Derived classes may override this function to provide a class name
	 * to be assigned to the LI element for the given list item.
	 */
	protected function item_class ($item) { return ''; }

	/**
	 * @brief Get ID for the given list item.
	 * @retval string ID value
	 *
	 * Derived classes may override this function to provide an ID
	 * to be assigned to the LI element for the given list item.
	 */
	protected function item_id ($item) { return ''; }

	/**
	 * @brief Get list-is-ordered flag.
	 * @retval bool List-is-ordered flag
	 *
	 * Derived classes may override this function to indicate whether or not
	 * the generated list is to be ordered. The default implementation returns
	 * false, thus indicating that an unordered list is to be generated.
	 */
	protected function ordered () { return false; }

	/**
	 * @brief Generates the HTML code for the list.
	 * @retval string HTML code generated for the list
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
