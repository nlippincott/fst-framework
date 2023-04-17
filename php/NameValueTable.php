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
 * @brief Name/Value Table Generation Engine
 *
 * Generates a table of name (or label) / value pairs.
 * This class is derived from TableEngine and is provide for producing
 * simple, two-column tables.
 *
 * To use this class, declare an object of the class, optionally call the
 * header method to define the column headings, call the row mention once
 * for each row to be generated, then print the object.
 *
 * The generated table will have two columns, the first being the "name"
 * column and the second being the "value" column. Cells (both TH and TD
 * elements) in these columns will have class names "name" and "value"
 * respectively.
 */
class NameValueTable extends TableEngine {

	/// @cond
	protected $class;
	protected $header = array('name'=>'', 'value'=>'');
	protected $table_rows = array();
	/// @endcond

	/**
	 * @brief Initialize the NameValueTable object
	 * @param string $class Class name to be applied to HTML table
	 */
	public function __construct ($class='') { $this->class = $class; }

	/**
	 * @brief Set table header cell values
	 * @param string $name Header data for the name (first) column
	 * @param string $value Header data for the value (second) column
	 */
	public function header ($name, $value)
		{ $this->header['name'] = $name; $this->header['value'] = $value; }

	/**
	 * @brief Adds a row to the table
	 * @param string $name Data for first (name or label) column
	 * @param string $value data for second (value) column
	 * @param string $class Class name to be applied to the row
	 */
	public function row ($name, $value, $class='') {
		$this->table_rows[] =
			array('name'=>$name, 'value'=>$value, 'class'=>$class);
	}

	/// @cond
	protected function rows () { return $this->table_rows; }
	protected function columns () { return array('name', 'value'); }

	protected function cell ($row, $col) { return $row[$col]; }
	protected function cell_class ($row, $col) { return $col; }
	protected function head ($col) { return $this->header[$col]; }
	protected function row_class ($row) { return $row['class']; }
	protected function table_class () { return $this->class; }
	/// @endcond
}
