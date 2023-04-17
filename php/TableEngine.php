<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revisions, ver 5.1
//	- Added key method
// Revisions, ver 5.3
//	- Added cell_attr, head_attr, and head_class methods
//	- Added foot_attr and foot_class methods
//	- Removed the cellspacing method

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Table Generation Engine, abstract base class.
 *
 * Abstract base class for the Table Generation Engine. To use the engine,
 * one must derive a class from TableEngine, and override, at a minimum, the
 * following protected methods: 'cell', 'columns', and 'row'. The following
 * protected methods may be overridden to affect the engine's behavior:
 * 'table_attr', 'table_class', 'table_id', 'col_class', 'row_class', 'head',
 * 'head_attr', 'head_class', 'head_colspan', 'cell_attr', 'cell_class',
 * 'cell_colspan', 'cell_id', 'foot', 'foot_attr', 'foot_class',
 * 'foot_colspan'.
 */
abstract class TableEngine {

	/// @cond
	private $_key = null;
	/// @endcond

	/**
	 * @brief Returns rows for table generation.
	 * @retval array Array of row items
	 *
	 * Derived classes must override this function to return array of items
	 * for table generation. One table row is generated for each element of
	 * the returned array.
	 */
	abstract protected function rows ();

	/**
	 * @brief Return columns for table generation.
	 * @retval array Array of column items
	 *
	 * Derived classes must override this function to return an array of items
	 * for table generation. One table column is generated for each element of
	 * the returned array.
	 */
	abstract protected function columns ();

	/**
	 * @brief Get content for cell at the given row and column.
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @retval string HTML code to serve as the contents of the cell
	 *
	 * Derived classes must override this function to return the content for
	 * the cell at the given row and column. The value returned will be sent
	 * directly to the HTML output as the cell contents.
	 */
	abstract protected function cell ($row, $col);

	/**
	 * @brief Get table tag attributes
	 * @retval array Associative of attribute/value pairs
	 *
	 * Derived classes may override this function to return attributes to
	 * be included in the table tag (TABLE). If class is included among
	 * the attributes, the value returned from this function overrides any
	 * value returned from table_class.
	 */
	protected function table_attr () { return array(); }

	/**
	 * @brief Get table class string
	 * @retval string Class name(s) for table
	 *
	 * Derived classes may override this function to return the class string
	 * to be included in the output table tag.
	 */
	protected function table_class () { return ''; }

	/**
	 * @brief Get table id string
	 * @retval string ID for table
	 *
	 * Derived classes may override this function to return the id to be used
	 * for the output table tag.
	 */
	protected function table_id () { return ''; }

	/**
	 * @brief Get table caption
	 * @retval string Caption
	 *
	 * Derived classes may override this function to return the caption for
	 * the table.
	 */
	protected function caption () { return ''; }

	/**
	 * @brief Get content for header cell.
	 * @param mixed $col One item from the columns array
	 * @retval string HTML code for the header cell content
	 *
	 * Derived classes may override this function to return content for header
	 * cells in the table.
	 */
	protected function head ($col) { return ''; }

	/**
	 * @brief Get header cell attributes
	 * @param mixed $col One item from the columns array
	 * @retval array Associative of attribute/value pairs
	 *
	 * Derived classes may override this function to return attributes to
	 * be included in the header cell tag (TH). If class is included among
	 * the attributes, the value returned from this function overrides any
	 * value returned from head_class.
	 */
	protected function head_attr ($col) { return array(); }

	/**
	 * @brief Get header cell class string
	 * @param mixed $col One item from the columns array
	 * @retval string Class name(s) for header cell
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to header cell. If a string is returned, that string will
	 * be applied to the th element of the header cell.
	 */
	protected function head_class ($col) { return ''; }

	/**
	 * @brief Get column span value for header cell
	 * @param mixed $col One item from the columns array
	 * @retval int Number >= 1 indicating number of columns to span
	 *
	 * Derived classes may override this function to return the colspan value
	 * for a header cell. A number should be returned. If a number greater
	 * than 1 is returned, the colspan attribute is added to the th element
	 * and the appropriate number of column entries are skipped in the table
	 * generation. The default behavior (if not overridden) is to return 1
	 * for every cell.
	 */
	protected function head_colspan ($col) { return 1; }

	/**
	 * @brief Get column class string
	 * @param mixed $col One item from the columns array
	 * @retval string Class name(s) for column
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to a column. If a string is returned, that string will
	 * be applied to all cells (both header and data) in the column.
	 */
	protected function col_class ($col) { return ''; }

	/**
	 * @brief Get row class string
	 * @param mixed $row One item from the rows array
	 * @retval string Class name(s) for row
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to a row. If a string is returned, that string will
	 * be applied to the tr element of the row (and not to individual data
	 * cells in the row).
	 */
	protected function row_class ($row) { return ''; }

	/**
	 * @brief Get row ID string
	 * @param mixed $row One item from the rows array
	 * @retval string ID for row
	 *
	 * Derived classes may override this function to return the ID string
	 * to be applied to a row. If a string is returned, that string will
	 * be used as the ID attribute of the tr element of the row.
	 */
	protected function row_id ($row) { return ''; }

	/**
	 * @brief Get table cell attributes
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @retval array Associative array of attribute/value pairs
	 *
	 * Derived classes may override this function return attributes to be
	 * applied to the table cell. If class is included among the array
	 * keys, the value overrides any value returned by the cell_class
	 * method.
	 */
	protected function cell_attr ($row, $col) { return array(); }

	/**
	 * @brief Get cell class string
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @retval string Class name(s) for cell
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to a row. If a string is returned, that string will
	 * be applied to the tr element of the row (and not to individual data
	 * cells in the row).
	 */
	protected function cell_class ($row, $col) { return ''; }

	/**
	 * @brief Get cell ID string
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @retval string ID for cell
	 *
	 * Derived classes may override this function to return the ID string
	 * to be applied to a cell. If a string is returned, that string will
	 * be used as the ID attribute of the td element.
	 */
	protected function cell_id ($row, $col) { return ''; }

	/**
	 * @brief Get column span value for a cell
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @retval int Number >= 1 indicating number of columns to span
	 *
	 * Derived classes may override this function to return the colspan value
	 * for a data cell. A number should be returned. If a number greater
	 * than 1 is returned, the colspan attribute is added to the td element
	 * and the appropriate number of column entries are skipped in the table
	 * generation. The default behavior (if not overridden) is to return 1
	 * for every cell.
	 */
	protected function cell_colspan ($row, $col) { return 1; }

	/**
	 * @brief Get content for footer cell
	 * @param mixed $col One item from the columns array
	 * @retval string HTML code for the footer cell content
	 *
	 * Derived classes may override this function to return content for footer
	 * cells in the table.
	 */
	protected function foot ($col) { return ''; }

	/**
	 * @brief Get footer cell attributes
	 * @param mixed $col One item from the columns array
	 * @retval array Associative of attribute/value pairs
	 *
	 * Derived classes may override this function to return attributes to
	 * be included in the footer cell tag (TD). If class is included among
	 * the attributes, the value returned from this function overrides any
	 * value returned from foot_class.
	 */
	protected function foot_attr ($col) { return array(); }

	/**
	 * @brief Get footer cell class string
	 * @param mixed $col One item from the columns array
	 * @retval string Class name(s) for footer cell
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to footer cell. If a string is returned, that string will
	 * be applied to the td element of the footer cell.
	 */
	protected function foot_class ($col) { return ''; }

	/**
	 * @brief Get column span value for footer cell
	 * @param mixed $col One item from the columns array
	 * @retval int Number >= 1 indicating number of columns to span
	 *
	 * Derived classes may override this function to return the colspan value
	 * for a footer cell. A number should be returned. If a number greater
	 * than 1 is returned, the colspan attribute is added to the td element
	 * and the appropriate number of column entries are skipped in the table
	 * generation. The default behavior (if not overridden) is to return 1
	 * for every cell.
	 */
	protected function foot_colspan ($col) { return 1; }

	/**
	 * @brief Get key value of rows array for current row
	 * @retval mixed Key value
	 *
	 * Gets the key value of the rows array for the current row being
	 * generated during table generation.
	 *
	 * Added in FST version 5.1.
	 */
	final protected function key () { return $this->_key; }

	/**
	 * @brief Generates the HTML code for the table
	 * @retval string HTML code generated for table
	 */
	public function __toString () {

		// Get row and column data
		$rows = $this->rows();
		$columns = $this->columns();
		if (!is_array($rows))
			$rows = array($rows);
		if (!is_array($columns))
			$columns = array($columns);

		// If no rows or no columns, return an empty string.
		if (!count($rows) || !count($columns))
			return '';

		// Produce TABLE tag
		$attr = $this->table_attr();
		$class = $this->table_class();
		if ($class && !isset($attr['class']))
			$attr['class'] = $class;
		$id = $this->table_id();
		if ($id)
			$attr['id'] = $id;
		$html = '<table' . Framework::attr($attr) . '>';

		// Get caption (optional)
		$caption = $this->caption();
		if ($caption)
			$html .= "<caption>$caption</caption>";

		// Get content for header cells
		$content = array();
		$count = 0;
		$idx = 0;
		foreach ($columns as $col)
			if ($content[$idx++] = $this->head($col))
				$count++;

		// Produce header row, if any header content
		if ($count) {
			$html .= "\n<thead><tr>";
			$span = 0;
			$idx = 0;
			foreach ($columns as $col) {
				if ($span)
					$span--;
				else {
					$attr = $this->head_attr($col);
					if (!is_array($attr)) $attr = array();
					$class = trim($this->col_class($col) . ' ' .
						$this->head_class($col));
					if ($class && !isset($attr['class']))
						$attr['class'] = $class;
					$colspan = (int)$this->head_colspan($col);
					$span = $colspan > 1 ? $colspan - 1 : 0;
					if ($colspan > 1)
						$attr['colspan'] = $colspan;
					$html .= '<th' . Framework::attr($attr) . '>' .
						$content[$idx] . '</th>';
				}
				$idx++;
			}
			$html .= '</tr></thead>';
		}

		// Produce table body, if any data rows
		if (count($rows)) {
			$html .= "\n<tbody>";
			foreach ($rows as $this->_key=>$row) {
				$class = $this->row_class($row);
				if ($class)
					$class = ' class="' . $class . '"';
				$id = $this->row_id($row);
				if ($id)
					$id = ' id="' . $id . '"';
				$html .= "\n<tr$id$class>";

				$span = 0;
				foreach ($columns as $col) {
					if ($span)
						$span--;
					else {
						$attr = $this->cell_attr($row, $col);
						if (!is_array($attr)) $attr = array();
						$class = trim($this->col_class($col) . ' ' .
							$this->cell_class($row, $col));
						if ($class && !isset($attr['class']))
							$attr['class'] = $class;
						$id = $this->cell_id($row, $col);
						if ($id)
							$attr['id'] = $id;
						$colspan = (int)$this->cell_colspan($row, $col);
						$span = $colspan > 1 ? $colspan - 1 : 0;
						if ($colspan > 1)
							$attr['colspan'] = $colspan;
						$html .= '<td' . Framework::attr($attr) . '>' .
							$this->cell($row, $col) . '</td>';
					}
				}

				$html .= '</tr>';
			}
			$this->_key = null;
			$html .= "\n</tbody>";
		}

		// Get content for footer cells
		$content = array();
		$count = 0;
		$idx = 0;
		foreach ($columns as $col)
			if ($content[$idx++] = $this->foot($col))
				$count++;

		// Produce footer row, if any footer content
		if ($count) {
			$html .= "\n<tfoot><tr>";
			$span = 0;
			$idx = 0;
			foreach ($columns as $col) {
				if ($span)
					$span--;
				else {
					$attr = $this->foot_attr($col);
					if (!is_array($attr)) $attr = array();
					$class = trim($this->col_class($col) . ' ' .
						$this->foot_class($col));
					if ($class && !isset($attr['class']))
						$attr['class'] = $class;
					$colspan = (int)$this->foot_colspan($col);
					$span = $colspan > 1 ? $colspan - 1 : 0;
					if ($colspan > 1)
						$attr['colspan'] = $colspan;
					$html .= '<td' . Framework::attr($attr) . '>' .
						$content[$idx] . '</td>';
				}
				$idx++;
			}
			$html .= '</tr></tfoot>';
		}

		// End of table
		$html .= "\n</table>";

		return $html;
	}
}
