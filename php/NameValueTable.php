<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Name/Value Table Generation Engine
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

	/** @ignore */
	protected $class;
	/** @ignore */
	protected $header = [ 'name'=>'', 'value'=>'' ];
	/** @ignore */
	protected $table_rows = [];

	/**
	 * Initialize the NameValueTable object
	 * 
	 * @param string $class Class name to be applied to HTML table
	 */
	public function __construct ($class='') { $this->class = $class; }

	/**
	 * Set table header cell values
	 * 
	 * @param string $name Header data for the name (first) column
	 * @param string $value Header data for the value (second) column
	 */
	public function header ($name, $value)
		{ $this->header['name'] = $name; $this->header['value'] = $value; }

	/**
	 * Adds a row to the table
	 * 
	 * @param string $name Data for first (name or label) column
	 * @param string $value data for second (value) column
	 * @param string $class Class name to be applied to the row
	 */
	public function row ($name, $value, $class='') {
		$this->table_rows[] = [ 'name'=>$name, 'value'=>$value, 'class'=>$class ];
	}

	/** @ignore */
	protected function rows () { return $this->table_rows; }
	/** @ignore */
	protected function columns () { return [ 'name', 'value' ]; }

	/** @ignore */
	protected function cell ($row, $col) { return $row[$col]; }
	/** @ignore */
	protected function cell_class ($row, $col) { return $col; }
	/** @ignore */
	protected function head ($col) { return $this->header[$col]; }
	/** @ignore */
	protected function row_class ($row) { return $row['class']; }
	/** @ignore */
	protected function table_class () { return $this->class; }
}
