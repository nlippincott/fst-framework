<?php

// FST Application Framework, Version 5.4
// Copyright (c) 2004-20, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

// Revision history:
//	v5.3 - Constructor accpets DateTime object or string for initialization

/// @cond
namespace FST;
/// @endcond

/**
 * @brief Calendar Generation Engine, abstract base class.
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

	/// @cond
	protected $ym; // Year and month, YYYY-MM
	protected $dt; // Date of Sunday of first week in calendar
	protected $weeks; // Number of weeks in calendar
	/// @endcond

	/**
	 * @brief Constructor, set up the calendar month.
	 * @param mixed $ym Month to be displayed in calendar
	 *
	 * Initializes the calendar for the month to be rendered in the table.
	 * If $ym is given as a DateTime object, calendar is rendered for the
	 * year/month of the date that was given. If $ym is a string, it must
	 * be in either YYYY-MM or YYYY-MM-DD format to indicate the month to
	 * be rendered.
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
	 * @brief Get content for a given date.
	 * @param string $dt Date
	 * @retval string Table cell content for the given date
	 *
	 * Derived classes must override this function to return the HTML content
	 * for the given date. The value returned will be sent directly to the
	 * output of the calendar table.
	 */
	abstract protected function date ($dt);

	/**
	 * @brief Get cell class string.
	 * @param string $dt Date in YYYY-MM-DD format
	 * @retval string Class name(s)
	 *
	 * Derived classes may override this function to return the
	 * class attributes
	 * to be applied to the cell for the given date. If a string is returned,
	 * that string is included in the class attribute of the td element.
	 * Cells for dates that fall outside the month being generated (i.e. for
	 * dates in the first row that preceed the first of the month, or dates
	 * in the last row that follow the last day of the month) will have
	 * class name 'outside' automatically added to the class attribute.
	 */
	protected function date_class ($dt) { return ''; }

	/**
	 * @brief Get contents of a table cell.
	 * @retval string Class name(s) for the date cell
	 *
	 * This method overrides Table::cell and is final.
	 */
	final protected function cell ($row, $col) {

		// Determine date for current block
		$dt = date('Y-m-d',
			strtotime($this->dt) + ($row * 7 + $col) * 24*60*60 + 3601);

		// If date is outside month and not showing outside dates, return
		//	a non-breaking space, else return date's content
		return $this->show_outside() || substr($dt, 0, 7) == $this->ym ?
			$this->date($dt) : '&nbsp;';
	}

	/**
	 * @brief Get class name(s) for a table cell.
	 *
	 * This method overrides Table::cell_class and is final.
	 */
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

	/**
	 * @brief Get column span for a table cell.
	 *
	 * This method overrides Table::cell_colspan and is final.
	 */
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

	/**
	 * @brief Get table columns.
	 *
	 * This method overrides Table::columns and is final.
	 */
	final protected function columns () { return range(0, 6); }

	/**
	 * @brief Get table rows.
	 *
	 * This method overrides Table::rows and is final.
	 */
	final protected function rows () { return range(0, $this->weeks - 1); }

	/**
	 * @brief Get column header contents.
	 *
	 * This method overrides Table::head and is final.
	 */
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

	/**
	 * @brief Get column span for a header column.
	 *
	 * This method overrides Table::head_colspan and is final.
	 */
	final protected function head_colspan ($day)
		{ return $this->show_month() ? 7 : 1; }

	/**
	 * @brief Show month in calenar heading.
	 * @retval bool Show month flag
	 *
	 * Derived classes may override this function to override its default
	 * behavior. If this function returns true (which is the default),
	 * the engine will generate the month
	 * name and 4-digit year in the heading, in addition to the names of the
	 * days of the week. If return false, only headings for the days of the
	 * week are produced.
	 */
	protected function show_month () { return true; }

	/**
	 * @brief Show dates outside the current month.
	 * @retval bool Show outside dates flag
	 *
	 * Derived classes may override this function to override its default
	 * behavior. If this function returns true, the engine will generate
	 * cells for dates in the previous month in the first week and dates for
	 * the next month in the last week, as opposed to empty cells. If returns
	 * false (which is the default), only dates for the month being generated
	 * will result in cell data. Cells for dates outside the current month
	 * will have class attribute 'outside' automatically applied.
	 */
	protected function show_outside () { return false; }

	/**
	 * @brief Start weeks on Monday instead of Sunday.
	 * @retval bool Start week on Monday flag
	 *
	 * Derived classes may override this function to override its default
	 * behavior. If this function returns true, weeks will begin on Monday
	 * instead of Sunday. If returns false (which is the default), weeks
	 * will begin on Sunday.
	 */
	protected function week_monday () { return false; }
}
