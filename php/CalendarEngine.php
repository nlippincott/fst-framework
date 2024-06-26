<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-24, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Calendar Generation Engine, abstract base class.
 *
 * Abstract base class for the Calendar Generation Engine.
 * This class is used to generated calendar-based HTML tables.
 *
 * To use the engine,
 * one must derive a class from CalendarEngine and override, at a minimum, the
 * protected methods 'date' and 'outside'.
 * Other available methods may be overridden to further tailor the output.
 */
abstract class CalendarEngine extends TableEngine {

	/** @ignore */
	protected $ym; // Year and month, YYYY-MM
	/** @ignore */
	protected $dt; // Date of Sunday of first week in calendar
	/** @ignore */
	protected $weeks; // Number of weeks in calendar

	/**
	 * Constructor, set up the calendar month.
	 *
	 * Initializes the calendar for the month to be rendered in the table.
	 * If $ym is given as a DateTime object, calendar is rendered for the
	 * year/month of the date that was given. If $ym is a string, it must
	 * be in either YYYY-MM or YYYY-MM-DD format to indicate the month to
	 * be rendered.
	 * 
	 * @param mixed $ym Month to be displayed in calendar
	 */
	public function __construct ($ym=false) {

		// If $ym is an object, convert to string
		if (is_object($ym)) {
			if (!is_a($ym, '\DateTime'))
				throw new UsageException(
					"Parameter 1 is not a DateTime object");
			$ym = $ym->format('Y-m');
		}

		// Verify format of $ym, if given
		if ($ym && !preg_match('/^\d\d\d\d-\d\d/', $ym))
			throw new UsageException("Parameter 1 is not valid: $ym");

		// Parameter in YYYY-MM format, or default to current month
		if ($ym === false) $ym = date('Y-m');
		$this->ym = substr($ym, 0, 7);

		// Date of first day of first week
		$this->dt = date('Y-m-d', strtotime("{$this->ym}-01"));
		$dayfirst = $this->week_monday() ? 1 : 0;
		while (date('w', strtotime($this->dt)) != $dayfirst)
			$this->dt = date('Y-m-d', strtotime($this->dt) - 12*60*60);

		// Number of weeks required for calendar
		$this->weeks = 4;
		while (date('Y-m', strtotime($this->dt) +
				$this->weeks * 7*24*60*60 + 3601) <= $ym)
			$this->weeks++;
	}

	/**
	 * Get content for a given date.
	 *
	 * Derived classes must override this method to return the HTML content
	 * for the given date. The value returned will be sent directly to the
	 * output of the calendar table.
	 * 
	 * @param string $dt Date in YYYY-MM-DD format
	 * @return string Table cell content for the given date
	 */
	abstract protected function date ($dt);

	/**
	 * Get cell class string.
	 *
	 * Derived classes may override this method to return the
	 * class attributes
	 * to be applied to the cell for the given date. If a string is returned,
	 * that string is included in the class attribute of the td element.
	 * Cells for dates that fall outside the month being generated (i.e. for
	 * dates in the first row that preceed the first of the month, or dates
	 * in the last row that follow the last day of the month) will have
	 * class name 'outside' automatically added to the class attribute.
	 * 
	 * @param string $dt Date in YYYY-MM-DD format
	 * @return string Class name(s)
	 */
	protected function date_class ($dt) { return ''; }

	// Get contents of a table cell.
	//
	// This method overrides Table::cell and is final.
	// 
	// @return string Class name(s) for the date cell
	/** @ignore */
	final protected function cell ($row, $col) {

		// Determine date for current block
		$dt = date('Y-m-d',
			strtotime($this->dt) + ($row * 7 + $col) * 24*60*60 + 3601);

		// If date is outside month and not showing outside dates, return
		//	a non-breaking space, else return date's content
		return $this->show_outside() || substr($dt, 0, 7) == $this->ym ?
			$this->date($dt) : '&nbsp;';
	}

	// Get class name(s) for a table cell.
	//
	// This method overrides Table::cell_class and is final.
	/** @ignore */
	final protected function cell_class ($row, $col) {

		// Determine date for current block
		$dt = date('Y-m-d',
			strtotime($this->dt) + ($row * 7 + $col) * 24*60*60 + 3601);

		// Determine if date is outside current month
		$outside = substr($dt, 0, 7) != $this->ym;

		// Build class string; if date is outside current month, include
		//	class name 'outside', if showing the date, include current date's
		//	class
		$class = !$outside || $this->show_outside() ?
			$this->date_class($dt) : '';
		if ($outside)
			$class = trim("$class outside");

		// Return the class string
		return $class;
	}

	// Get column span for a table cell.
	//
	// This method overrides Table::cell_colspan and is final.
	/** @ignore */
	final protected function cell_colspan ($row, $col) {

		// If showing outside dates, no column spanning needed
		if ($this->show_outside())
			return 1;

		// Determine date for current block
		$dt = date('Y-m-d',
			strtotime($this->dt) + ($row * 7 + $col) * 24*60*60 + 3601);

		// If date within current month, no column span
		if (substr($dt, 0, 7) == $this->ym)
			return 1;

		// If date preceeds current month, span enough blocks to first of month
		if ($dt < $this->ym) {
			$span = 0;
			while ($dt < $this->ym) {
				$dt = date('Y-m-d', strtotime($dt) + 24*60*60 + 3601);
				$span++;
			}
			return $span;
		}

		// Date is beyond last date in calendar, span remainder of week
		return 7 - $col;
	}

	// Get table columns.
	//
	// This method overrides Table::columns and is final.
	/** @ignore */
	final protected function columns () { return range(0, 6); }

	// Get table rows.
	//
	// This method overrides Table::rows and is final.
	/** @ignore */
	final protected function rows () { return range(0, $this->weeks - 1); }

	// Get column header contents.
	//
	// This method overrides Table::head and is final.
	/** @ignore */
	final protected function head ($col) {

		if ($this->show_month())
			return $this->week_monday() ?
				date('F Y', strtotime("{$this->ym}-01")) .
					'</th></tr><tr>' .
					'<th class="day">Mon</th>' .
					'<th class="day">Tue</th>' .
					'<th class="day">Wed</th>' .
					'<th class="day">Thu</th>' .
					'<th class="day">Fri</th>' .
					'<th class="day">Sat</th>' .
					'<th class="day">Sun' :
				date('F Y', strtotime("{$this->ym}-01")) .
					'</th></tr><tr>' .
					'<th class="day">Sun</th>' .
					'<th class="day">Mon</th>' .
					'<th class="day">Tue</th>' .
					'<th class="day">Wed</th>' .
					'<th class="day">Thu</th>' .
					'<th class="day">Fri</th>' .
					'<th class="day">Sat';

		$day = $this->week_monday() ? ($col + 1) % 7 : $col;

		switch ($day) {
		case 0: return 'Sun';
		case 1: return 'Mon';
		case 2: return 'Tue';
		case 3: return 'Wed';
		case 4: return 'Thu';
		case 5: return 'Fri';
		case 6: return 'Sat';
		case 7: return 'Sun';
		}
	}

	// Get column span for a header column.
	//
	// This method overrides Table::head_colspan and is final.
	/** @ignore */
	final protected function head_colspan ($day)
		{ return $this->show_month() ? 7 : 1; }

	/**
	 * Show month in calenar heading.
	 *
	 * Derived classes may override this method to override its default
	 * behavior. If this method returns true (which is the default),
	 * the engine will generate the month
	 * name and 4-digit year in the heading, in addition to the names of the
	 * days of the week. If return false, only headings for the days of the
	 * week are produced.
	 *
	 * @return bool Show month flag
	 */
	protected function show_month () { return true; }

	/**
	 * Show dates outside the current month.
	 *
	 * Derived classes may override this method to override its default
	 * behavior. If this method returns true, the engine will generate
	 * cells for dates in the previous month in the first week and dates for
	 * the next month in the last week, as opposed to empty cells. If returns
	 * false (which is the default), only dates for the month being generated
	 * will result in cell data. Cells for dates outside the current month
	 * will have class attribute 'outside' automatically applied.
	 *
	 * @return bool Show outside dates flag
	 */
	protected function show_outside () { return false; }

	/**
	 * Start weeks on Monday instead of Sunday.
	 *
	 * Derived classes may override this method to override its default
	 * behavior. If this method returns true, weeks will begin on Monday
	 * instead of Sunday. If returns false (which is the default), weeks
	 * will begin on Sunday.
	 *
	 * @return bool Start week on Monday flag
	 */
	protected function week_monday () { return false; }
}
