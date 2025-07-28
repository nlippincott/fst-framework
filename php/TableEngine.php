<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-25, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Table Generation Engine, abstract base class.
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

	/** @ignore */
	private $_key = null;

	/**
	 * Returns rows for table generation.
	 *
	 * Derived classes must override this function to return array of items
	 * for table generation. One table row is generated for each element of
	 * the returned array.
	 *
	 * @return mixed[] Array of row items
	 */
	abstract protected function rows ();

	/**
	 * Return columns for table generation.
	 *
	 * Derived classes must override this function to return an array of items
	 * for table generation. One table column is generated for each element of
	 * the returned array.
	 *
	 * @return mixed[] Array of column items
	 */
	abstract protected function columns ();

	/**
	 * Get content for cell at the given row and column.
	 *
	 * Derived classes must override this function to return the content for
	 * the cell at the given row and column. The value returned will be sent
	 * directly to the HTML output as the cell contents.
	 *
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @return string HTML code to serve as the contents of the cell
	 */
	abstract protected function cell ($row, $col);

	/**
	 * Get table tag attributes.
	 *
	 * Derived classes may override this function to return attributes to
	 * be included in the table tag (TABLE). If class is included among
	 * the attributes, the value returned from this function overrides any
	 * value returned from table_class.
	 * 
	 * @return string[] Associative of attribute/value pairs
	 */
	protected function table_attr () { return []; }

	/**
	 * Get table class string.
	 *
	 * Derived classes may override this function to return the class string
	 * to be included in the output table tag.
	 *
	 * @return string Class name(s) for table
	 */
	protected function table_class () { return ''; }

	/**
	 * Get table id string.
	 *
	 * Derived classes may override this function to return the id to be used
	 * for the output table tag.
	 *
	 * @return string ID for table
	 */
	protected function table_id () { return ''; }

	/**
	 * Get table caption.
	 *
	 * Derived classes may override this function to return the caption for
	 * the table.
	 *
	 * @return string Caption
	 */
	protected function caption () { return ''; }

	/**
	 * Get content for header cell.
	 *
	 * Derived classes may override this function to return content for header
	 * cells in the table.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string HTML code for the header cell content
	 */
	protected function head ($col) { return ''; }

	/**
	 * Get header cell attributes.
	 *
	 * Derived classes may override this function to return attributes to
	 * be included in the header cell tag (TH). If class is included among
	 * the attributes, the value returned from this function overrides any
	 * value returned from head_class.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string[] Associative of attribute/value pairs
	 */
	protected function head_attr ($col) { return []; }

	/**
	 * Get header cell class string.
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to header cell. If a string is returned, that string will
	 * be applied to the th element of the header cell.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string Class name(s) for header cell
	 */
	protected function head_class ($col) { return ''; }

	/**
	 * Get column span value for header cell.
	 *
	 * Derived classes may override this function to return the colspan value
	 * for a header cell. A number should be returned. If a number greater
	 * than 1 is returned, the colspan attribute is added to the th element
	 * and the appropriate number of column entries are skipped in the table
	 * generation. The default behavior (if not overridden) is to return 1
	 * for every cell.
	 *
	 * @param mixed $col One item from the columns array
	 * @return int Number >= 1 indicating number of columns to span
	 */
	protected function head_colspan ($col) { return 1; }

	/**
	 * Get column class string.
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to a column. If a string is returned, that string will
	 * be applied to all cells (both header and data) in the column.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string Class name(s) for column
	 */
	protected function col_class ($col) { return ''; }

	/**
	 * Get row class string.
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to a row. If a string is returned, that string will
	 * be applied to the tr element of the row (and not to individual data
	 * cells in the row).
	 *
	 * @param mixed $row One item from the rows array
	 * @return string Class name(s) for row
	 */
	protected function row_class ($row) { return ''; }

	/**
	 * Get row ID string.
	 *
	 * Derived classes may override this function to return the ID string
	 * to be applied to a row. If a string is returned, that string will
	 * be used as the ID attribute of the tr element of the row.
	 *
	 * @param mixed $row One item from the rows array
	 * @return string ID for row
	 */
	protected function row_id ($row) { return ''; }

	/**
	 * Get table cell attributes.
	 *
	 * Derived classes may override this function return attributes to be
	 * applied to the table cell. If class is included among the array
	 * keys, the value overrides any value returned by the cell_class
	 * method.
	 *
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @return string[] Associative array of attribute/value pairs
	 */
	protected function cell_attr ($row, $col) { return []; }

	/**
	 * Get cell class string.
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to a row. If a string is returned, that string will
	 * be applied to the tr element of the row (and not to individual data
	 * cells in the row).
	 *
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @return string Class name(s) for cell
	 */
	protected function cell_class ($row, $col) { return ''; }

	/**
	 * Get cell ID string.
	 *
	 * Derived classes may override this function to return the ID string
	 * to be applied to a cell. If a string is returned, that string will
	 * be used as the ID attribute of the td element.
	 *
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @return string ID for cell
	 */
	protected function cell_id ($row, $col) { return ''; }

	/**
	 * Get column span value for a cell.
	 *
	 * Derived classes may override this function to return the colspan value
	 * for a data cell. A number should be returned. If a number greater
	 * than 1 is returned, the colspan attribute is added to the td element
	 * and the appropriate number of column entries are skipped in the table
	 * generation. The default behavior (if not overridden) is to return 1
	 * for every cell.
	 *
	 * @param mixed $row One item from the rows array
	 * @param mixed $col One item from the columns array
	 * @return int Number >= 1 indicating number of columns to span
	 */
	protected function cell_colspan ($row, $col) { return 1; }

	/**
	 * Get content for footer cell.
	 *
	 * Derived classes may override this function to return content for footer
	 * cells in the table.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string HTML code for the footer cell content
	 */
	protected function foot ($col) { return ''; }

	/**
	 * Get footer cell attributes.
	 *
	 * Derived classes may override this function to return attributes to
	 * be included in the footer cell tag (TD). If class is included among
	 * the attributes, the value returned from this function overrides any
	 * value returned from foot_class.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string[] Associative of attribute/value pairs
	 */
	protected function foot_attr ($col) { return []; }

	/**
	 * Get footer cell class string.
	 *
	 * Derived classes may override this function to return the class string
	 * to be applied to footer cell. If a string is returned, that string will
	 * be applied to the td element of the footer cell.
	 *
	 * @param mixed $col One item from the columns array
	 * @return string Class name(s) for footer cell
	 */
	protected function foot_class ($col) { return ''; }

	/**
	 * Get column span value for footer cell.
	 *
	 * Derived classes may override this function to return the colspan value
	 * for a footer cell. A number should be returned. If a number greater
	 * than 1 is returned, the colspan attribute is added to the td element
	 * and the appropriate number of column entries are skipped in the table
	 * generation. The default behavior (if not overridden) is to return 1
	 * for every cell.
	 *
	 * @param mixed $col One item from the columns array
	 * @return int Number >= 1 indicating number of columns to span
	 */
	protected function foot_colspan ($col) { return 1; }

	/**
	 * Get key value of rows array for current row.
	 *
	 * Gets the key value of the rows array for the current row being
	 * generated during table generation.
	 *
	 * @return mixed Key value
	 */
	final protected function key () { return $this->_key; }

	/**
	 * Generates the HTML code for the table
	 *
	 * @return string HTML code generated for table
	 */
	public function __toString () {

		// Get row and column data
		$rows = $this->rows();
		$columns = $this->columns();
		if (!is_array($rows))
			$rows = [ $rows ];
		if (!is_array($columns))
			$columns = [ $columns ];

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
		$content = [];
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
					if (!is_array($attr))
						$attr = [];
					$class = trim($this->col_class($col) . ' ' .
						$this->head_class($col));
					if ($class && !isset($attr['class']))
						$attr['class'] = $class;
					$colspan = (int)$this->head_colspan($col);
					$span = $colspan > 1 ? $colspan - 1 : 0;
					if ($colspan > 1)
						$attr['colspan'] = $colspan;
					$html .= '<th' . Framework::attr($attr) . '>' . $content[$idx] . '</th>';
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
						if (!is_array($attr)) $attr = [];
						$class = trim($this->col_class($col) . ' ' . $this->cell_class($row, $col));
						if ($class && !isset($attr['class']))
							$attr['class'] = $class;
						$id = $this->cell_id($row, $col);
						if ($id)
							$attr['id'] = $id;
						$colspan = (int)$this->cell_colspan($row, $col);
						$span = $colspan > 1 ? $colspan - 1 : 0;
						if ($colspan > 1)
							$attr['colspan'] = $colspan;
						$html .= '<td' . Framework::attr($attr) . '>' . $this->cell($row, $col) . '</td>';
					}
				}

				$html .= '</tr>';
			}
			$this->_key = null;
			$html .= "\n</tbody>";
		}

		// Get content for footer cells
		$content = [];
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
					if (!is_array($attr))
						$attr = [];
					$class = trim($this->col_class($col) . ' ' . $this->foot_class($col));
					if ($class && !isset($attr['class']))
						$attr['class'] = $class;
					$colspan = (int)$this->foot_colspan($col);
					$span = $colspan > 1 ? $colspan - 1 : 0;
					if ($colspan > 1)
						$attr['colspan'] = $colspan;
					$html .= '<td' . Framework::attr($attr) . '>' . $content[$idx] . '</td>';
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
