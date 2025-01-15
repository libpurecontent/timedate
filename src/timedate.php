<?php

# Class containing a variety of date/time processing functions
class timedate
{
	# Function to produce a date array
	public static function getDateTimeArray ($value)
	{
		# If no value, return an empty array
		if (!$value) {
			return array ('year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => '', 'second' => '', 'time' => '', 'datetime' => '');
		}
		
		# Obtain an array of time components
		list (
			$datetime['year'],
			$datetime['month'],
			$datetime['day'],
			$datetime['hour'],
			$datetime['minute'],
			$datetime['second'],
		) = sscanf ($value, '%4s-%2s-%2s %2s:%2s:%2s');
		
		# Construct a combined time formatted string
		if ($datetime['hour'] || $datetime['minute'] || $datetime['second']) {
			$datetime['time'] = $datetime['hour'] . ':' . $datetime['minute'] . ':' . $datetime['second'];
		} else {
			$datetime['time'] = '';
		}
		
		# Construct a combined SQL-format datetime formatted string
		$datetime['datetime'] = $datetime['year'] . '-' . $datetime['month'] . '-' . $datetime['day'] . ' ' . $datetime['time'];
		
		# Return the array
		return $datetime;
	}
	
	
	# Function to format the date
	#!# Could be made more efficient using strtotime()
	public static function formatDate ($date)
	{
		# Return false if the date is zeroed
		if ($date == '0000-00-00') {return false;}
		
		# Attempt to split out the year, month and date
		if (!list ($year, $month, $day) = explode ('-', $date)) {return $datestamp;}
		
		# If only a year has been entered, return that
		#!# This is bad if it's January 1st ...
		if (($month == '01') && ($day == '01')) {
			return $year;
		}
		
		# Else return the full date, with the date and month formatted sensibly
		$months = array (1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',);
		return (int) $day . '/' . $months[(int) $month] . "/$year";
	}
	
	
	# Function to present the date from a supplied array
	public static function presentDateFromArray ($value, $level = 'date')
	{
		# Convert empty strings to 0
		if (empty ($value['time'])) {$value['time'] = 0;}
		if (empty ($value['day'])) {$value['day'] = 0;}
		if (empty ($value['month'])) {$value['month'] = 0;}
		if (empty ($value['year'])) {$value['year'] = 0;}
		
		switch ($level) {
			case 'time':
				return $value['time'];
				break;
				
			case 'datetime':
				return $value['time'] . ', ' . date ('jS F, Y', mktime (0, 0, 0, $value['month'], $value['day'], $value['year']));
				break;
				
			case 'date':
				return date ('jS F, Y', mktime (0, 0, 0, $value['month'], $value['day'], $value['year']));
				break;
				
			case 'year':
				return $value['year'];
				break;
		}
	}
	
	
	# Function to convert a timestamp to a string usable by strtotime
	public static function convertTimestamp ($timestamp, $includeTime = true)
	{
		# Convert the timestamp
		$timestamp = preg_replace ('/-(\d{2})(\d{2})(\d{2})$/D', ' $1:$2:$3', $timestamp);
		
		# Determine the output string to use
		$format = 'l jS M Y' . ($includeTime ? ', g.ia' : '');	// Previously: ($includeTime ? 'g.ia \o\n ' : '') . 'jS M Y';
		
		# Convert the timestamp
		$string = date ($format, strtotime ($timestamp));
		
		# Return the string
		return $string;
	}
	
	
	# Function to format the date
	public static function convertBackwardsDateToText ($backwardsDateString, $format = 'l, jS F Y')
	{
		# Remove hyphens (as used in ISO dates e.g. 2012-07-18)
		$backwardsDateString = str_replace ('-', '', $backwardsDateString);
		
		# Ensure the string is numeric
		if (!ctype_digit ($backwardsDateString)) {return false;}
		
		# Upgrade 6-character strings (e.g. 120718) to 8 characters; 6-character are assumed to be prefixed with 20
		if (strlen ($backwardsDateString) == 6) {
			$centurySwitchPoint = 70;	// i.e. 1970 becomes 19, but 2069 becomes 20
			$year = substr ($backwardsDateString, 0, 2);
			$yearPrefix = ($year < $centurySwitchPoint ? 20 : 19);
			$backwardsDateString = $yearPrefix . $backwardsDateString;
		}
		
		# End if not 8 characters long now
		if (strlen ($backwardsDateString) != 8) {return false;}
		
		# Get the year month and day out of the string
		list ($year, $month, $day) = sscanf ($backwardsDateString, '%4s%2s%2s');
		
		# Convert to string
		$dateFormatted = date ($format, mktime (0, 0, 0, $month, $day, $year));
		
		# Return the string
		return $dateFormatted;
	}
	
	
	# Function to return an intelligent string for date of birth and death
	public static function dateBirthDeath ($yearBirth, $yearDeath)
	{
		# Return both if both supplied
		if (($yearBirth) && ($yearDeath)) {return " ($yearBirth - $yearDeath)";}
		
		# Return an empty string if neither supplied
		if ((!$yearBirth) && (!$yearDeath)) {return '';}
		
		# If only the year of birth is supplied, return that
		if (!$yearDeath) {return " (b. $yearBirth)";}
		
		# If only the year of death is supplied, return that
		if (!$yearBirth) {return " (d. $yearDeath)";}
	}
	
	
	# Function to parse a string for the time and return a correctly-formatted SQL version of it
	public static function parseTime ($input)
	{
		# 1a. Remove any surrounding whitespace from the input
		$time = strtolower (trim ($input));
		
		# 2a. Collapse all white space (i.e. allow excess internal whitespace) to a single space
		$time = preg_replace ("/\s+/",' ', $time);
		
		# 2b. Collapse whitespace next to a colon or a dot
		$time = str_replace (array (': ', '. ', ' :', ' .'), ' ', $time);
		
		# 2b. Convert allowed separators to a space
		$allowedSeparators = array (':', '.', ' ');
		foreach ($allowedSeparators as $allowedSeparator) {
			$time = str_replace ($allowedSeparator, ' ', $time);
		}
		
		# 3. Return false if a starting (originally non-whitespace) separator has been found
		if (preg_match ('/^ /', $time)) {return false;}
		
		# 4. Return false if two adjacent whitespaces are found (i.e. do not allow multiples of originally non-whitespace allowed characters)
		if (substr_count ($time, '  ')) {return false;}
		
		# 5. Remove any trailing separator
		$time = trim ($time);
		
		# 6b. Throw error if string contains other than: 0-9, whitespace separator, or the letters a m p
		#!# This could ideally be improved to weed out more problems earlier on
		if (preg_match ('/[^0-9\ amp]+/', $time)) {return false;}
		
		# 7a. Adjust am and pm for the possibility of a.m. or a.m or p.m. or p.m having been entered
		$time = str_replace ('a m', 'am', $time);
		$time = str_replace ('p m', 'pm', $time);
		
		# 7b. If string ends with am or pm then strip that off and hold it for later use
		if (preg_match ('/(am|pm)$/i', $time)) {
			$timeParts['meridiem'] = substr (strtolower ($time), -2);
			$time = substr ($time, 0, -2);
		}
		
		# 8. Throw error if string contains other than: 0-9 or space
		if (preg_match ('/[^0-9\ ]+/', $time)) {return false;}
		
		# 9. Remove any trailing separator
		$time = trim ($time);
		
		# 11. Throw error if string contains more than 5 or 6 numeric digits
		$numericOnlyString = str_replace (' ', '', $time);
		$numbersInString = strlen ($numericOnlyString);
		if ($numbersInString > 6) {return false;}
		
		# 10a. Throw error if string contains other than 0, 1, or 2 separators
		if (substr_count ($time, ' ') > 2) {return false;}
		
		# 12. Check whether string contains 5 or 6 numeric digits; if so, run several checks:
		if ($numbersInString == 5 || $numbersInString == 6) {
			
			# Throw error if not either 1 or 2 separators (i.e. if it contains 0 since it is already known that the string contains 0, 1, or 2
			if (substr_count ($time, ' ') == 0) {return false;}
			
			# Throw error if there are not 2 digits after last separator
			$temporary = explode (' ', $time);
			$timeParts['seconds'] = $temporary [(count ($temporary) - 1)];
			if (strlen ($timeParts['seconds']) != 2) {return false;}
			
			# Throw error IF last two digits are not valid seconds, i.e. 0(0)-59
			if ($timeParts['seconds'] > 59) {return false;}
			
			# Strip off the seconds and separator from the string
			$time = trim (substr ($time, 0, -2));
		}
		
		# 13. Allow for special case of .0 as meaning 0 minutes and resubstitute 0 for 0
		if (substr ($time, -2) === ' 0') {
			$timeParts['minutes'] = '00';
			
			# If so, then strip off the separator-zero from the string
			$time = trim (substr ($time, 0, -2));
			
		} else {
			
			# 10b. Throw error if string contains 3 or more numeric characters but starts with number-space-number-space, e.g. 1 1 00
			if (($numbersInString > 3) && (preg_match ('/^[0-9]\ [0-9]\ /', $time))) {return false;}
			
			# Throw error if string ends with space-number-space
			#!# Not clear what this is supposed to do; backslash-space in the regexp seems wrong, and comment doesn't match code
			if (preg_match ('/\ [0-9]$/', $time)) {return false;}
			
			# Recalculate the number of numeric digits in the string
			$numericOnlyString = str_replace (' ', '', $time);
			$numbersInString = strlen ($numericOnlyString);
			
			# 14. Check whether string contains 3 or 4 numeric digits; if so, run several checks:
			if ($numbersInString == 3 || $numbersInString == 4) {
				
				# Make sure the last two characters form a number between 00-59
				if (!preg_match ('/[0-5][0-9]$/', $time)) {return false;}
				
				# Extract the minutes
				$timeParts['minutes'] = substr ($time, -2);
				
				# Strip off the minutes (and trim again, although that should not be necessary)
				$time = trim (substr ($time, 0, -2));
			}
		}
		
		# 15a. Check that there is no whitespace left (all that should remain is hours)
		if (!preg_match ('/[0-9]{1,2}/', $time)) {return false;}
		
		# 15b. Validate the hour figure; firstly check that the hours are not above 23 (they cannot be less than 0 because - is not an allowable character)
		if ($time > 23) {return false;}
		
		# 15c. Run checks based on the existence of a meridiem
		if (isSet ($timeParts['meridiem'])) {
			
			# 15d. If the meridiem is am and the time is 12-23 then exit
			if (($timeParts['meridiem'] == 'am') && ($time > 12)) {return false;}
			
			# 15e. Replace 12am with 0
			if (($timeParts['meridiem'] == 'am') && ($time == 12)) {$time = 0;}
			
			# 15f. If the meridium is pm and the time is 0
			if (($timeParts['meridiem'] == 'pm') && ($time == 0)) {return false;}
			
			# 15g. Add 12 hours to the time if it's pm and hours is less than 
			if (($timeParts['meridiem'] == 'pm') && ($time < 12)) {$time += 12;}
		}
		
		# 16 Assign the hours, padding out hours to two digits if necessary
		$timeParts['hours'] = str_pad ($time, 2, '0', STR_PAD_LEFT);
		
		# Finally, assemble the time string using the allocated array parts, in the SQL format of 00:00:00
		$time = 
			(isSet ($timeParts['hours']) ? $timeParts['hours'] : '00') . ':' .
			(isSet ($timeParts['minutes']) ? $timeParts['minutes'] : '00') . ':' .
			(isSet ($timeParts['seconds']) ? $timeParts['seconds'] : '00');
		
		# Return the assembled and validated string
		return $time;
	}
	
	
	# Function to simplify a time string for display; e.g. '14:30:00' would become '2.30pm'; seconds are discarded in the results
	public static function simplifyTime ($sqlTime, $disambiguate0AmPm = false)
	{
		# Ensure valid format or return as-is
		if (!preg_match ('/^([0-2][0-9]):([0-9][0-9]):[0-9][0-9]$/', $sqlTime, $matches)) {
			return $sqlTime;
		}
		
		# Obtain the hours and minutes; seconds are discarded in the results
		$hours = (int) $matches[1];
		$minutes = $matches[2];
		
		# Set the suffix
		$suffix = ($hours >= 12 ? 'pm' : 'am');
		
		# If the hours are greater than 12, subtract; this will mean 0am and 12pm will both be unchanged
		if ($hours > 12) {
			$hours -= 12;
		}
		
		# Compile the string
		$time = $hours . ($minutes != '00' ? '.' . $minutes : '') . $suffix;
		
		# Deal with special cases of midnight and midday; see: https://en.wikipedia.org/wiki/12-hour_clock#Confusion_at_noon_and_midnight
		if ($disambiguate0AmPm) {
			if ($time == '0am') {
				$time = '0am midnight (start of day)';
			}
			if ($time == '12pm') {
				$time = '12pm midday';
			}
		}
		
		# Return the new time
		return $time;
	}
	
	
	# Function to convert a two-character year to a four-character year
	public static function convertYearToFourCharacters ($year, $autoCenturyConversationLastYear = 69)
	{
		# Check that the value given is an integer
		if (!is_numeric ($year)) {return false;}
		
		# If the value is empty, return empty
		if ($year == '') {return $year;}
		
		# If $add is true, use the function to add leading figures
		if (strlen ($year) == 2) {
			
			# Add either 19 or 20 as considered appropriate
			$year = (($year <= $autoCenturyConversationLastYear) ? '20' : '19') . $year;
		}
		
		# Return the result
		return ($year);
	}
	
	
	# Function to determine if a date is a valid date supplied in SQL syntax; # NB Using (strtotime ($string)) === -1) doesn't give proper results
	#R# Split into three functions
	public static function isValidDateFormat ($string, $dateTime = true)
	{
		# Trim the string
		$string = trim ($string);
		
		# If the time is not supplied, add a fake time
		if (!$dateTime) {$string .= ' 00:00:00';}
		
		# Check that there is a single space
		if (count ($dateTime = explode (' ', $string)) != 2) {
			return false;
		}
		
		# Check that the time contains two dashes is a single space
		if (count ($time = explode (':', $dateTime[1])) != 3) {
			return false;
		}
		
		# Check that each time value is two characters long
		if ((strlen ($time[0]) != 2) || (strlen ($time[1]) != 2) || (strlen ($time[2]) != 2)) {
			return false;
		}
		
		# Check that the elements are each appropriate numbers
		if (($time[0] < 0) || ($time[0] > 23) || ($time[1] < 0) || ($time[1] > 59) || ($time[2] < 0) || ($time[2] > 59)) {
			return false;
		}
		
		# Check that the date contains two dashes
		if (count ($date = explode ('-', $dateTime[0])) != 3) {
			return false;
		}
		
		# Check that each date value is the right length
		if ((strlen ($date[0]) != 4) || (strlen ($date[1]) != 2) || (strlen ($date[2]) != 2)) {
			return false;
		}
		
		# Check the date is valid
		if (!checkdate ($date[1], $date[2], $date[0])) {
			return false;
		}
		
		# Otherwise, all tests are passed, so return true
		return true;
	}
	
	
	# Function to get an array of dates in future months
	public static function getDatesForFutureMonths ($monthsAhead, $format = 'Y-m-d', $days = true, $fromDate = false /* or date in Y-m-d format */)
	{
		# Start an array to hold the dates
		$dates = array ();
		
		# Add an extra month ahead so that it is $months ahead plus any remaining days in that month
		$monthsAhead++;
		
		# Define the current day, month and year
		if ($fromDate && preg_match ('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $fromDate)) {
			list ($year, $month, $day) = explode ('-', $fromDate, 3);
		} else {
			$day = date ('d');
			$month = date ('m');
			$year = date ('Y');
		}
		
		# Advance through the calendar until finished
		while ($monthsAhead) {
			
			# Skip weekend days if required
			$weekday = date ('l', mktime (0, 0, 0, $month, $day, $year));
			$skip = false;
			if (is_array ($days)) {
				$skip = (!in_array (strtolower ($weekday), $days));
			}
			
			# Add the date
			if (!$skip) {$dates[] = date ($format, mktime (0, 0, 0, $month, $day, $year));}
			
			# Try incrementing the day
			if (checkdate ($month, $day + 1, $year)) {
				$day++;
			} else {
				
				# If the date is not valid, try incrementing the month but resetting the day
				if (checkdate ($month + 1, 1, $year)) {
					$day = 1;
					$month++;
					$monthsAhead--;
				} else {
					
					# If the date is not valid, try incrementing the month but resetting the day
					if (checkdate (1, 1, $year + 1)) {
						$day = 1;
						$month = 1;
						$year++;
						$monthsAhead--;
					}
				}
			}
		}
		
		# Return the dates
		return $dates;
	}
	
	
	# Function to get months, indexed by year
	public static function getMonthsByYear ($startDate = '1970-01-01', $endDate = false /* false indicates today */, $reverseOrdering = false, $twoFigureMonth = true)
	{
		# If no end date, use today
		if (!$endDate) {
			$endDate = date ('Y-m-d');
		}
		
		# End if invalid start date
		if (!preg_match ('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $startDate, $matches)) {return array ();}
		list ($startYear, $startMonth, $startDay) = array ($matches[1], $matches[2], $matches[3]);
		if (!checkdate ($startMonth, $startDay, $startYear)) {return array ();}
		
		# End if invalid end date
		if (!preg_match ('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $endDate, $matches)) {return array ();}
		list ($endYear, $endMonth, $endDay) = array ($matches[1], $matches[2], $matches[3]);
		if (!checkdate ($endMonth, $endDay, $endYear)) {return array ();}
		
		# End if the supplied year or year-month is in the future
		if ($startYear > $endYear) {return array ();}
		if ($startYear == $endYear) {
			if ($startMonth > $endMonth) {return array ();}
		}
		
		# Start a list of months
		$monthsByYear = array ();
		
		# Loop through each year until the current
		$yearsRange = range ($startYear, $endYear);
		if ($reverseOrdering) {
			$yearsRange = array_reverse ($yearsRange);
		}
		foreach ($yearsRange as $year) {
			
			# Fill the array with a list of months, normally 1-12, except for the start and finish year
			$monthRangeStart = ($year == $startYear ? $startMonth : 1);
			$monthRangeFinish = ($year == $endYear ? $endMonth : 12);
			$monthsRange = range ($monthRangeStart, $monthRangeFinish);
			if ($reverseOrdering) {
				$monthsRange = array_reverse ($monthsRange);
			}
			foreach ($monthsRange as $month) {
				$yearMonthAsText = date ('F', mktime (0, 0, 0, $month, 10)) . ', ' . $year;
				if ($twoFigureMonth) {
					$month = str_pad ($month, 2, '0', STR_PAD_LEFT);
				}
				$monthsByYear[$year][$month] = $yearMonthAsText;
			}
		}
		
		# Return the list of months
		return $monthsByYear;
	}
	
	
	# Function to get Mondays from a specific date
	public static function getMondays ($total = 12, $dateFormat = false /* e.g. 'ymd' for 6-digit backwards date format; default gives unixtime */, $forwards = true, $timestamp = false, $excludeCurrent = false)
	{
		# Determine the week and year to use, defaulting to the current date
		$week = (int) ($timestamp ? date ('W', $timestamp) : date ('W'));	// (int) removes the leading zeros
		$year = ($timestamp ? date ('Y', $timestamp) : date ('Y'));
		
		# Work through the required number of weeks
		$mondays = array ();
		while ($total) {
			
			# Assign the Monday, converting to backwards date format if required
			$monday = self::startOfWeek ($year, $week);
			
			# Increment the week, either forwards or backwards; year ends are dealt with automatically by date, e.g. week -10 will be the 10th week before the start of the current year
			$week = $week + ($forwards ? 1 : -1);
			
			# If excluding the current, skip the first one
			if ($excludeCurrent) {
				$excludeCurrent = false;
				continue;	// Skip to next but do not decrease the counter
			}
			
			# Add the Monday to the list
			$mondays[] = ($dateFormat ? date ($dateFormat, $monday) : $monday);
			
			# Reduce the counter
			$total--;
		}
		
		# Return the Mondays
		return $mondays;
	}
	
	
	# Function to get the Monday start date of the week for grouping purposes; from http://www.phpbuilder.com/board/showthread.php?t=10222903
	public static function startOfWeek ($year, $week)
	{
	    $jan1 = mktime (1, 1, 1, 1, 1, $year);	// 1.01am, which should guarantee against hour shifts
	    $mondayOffset = (11 - date ('w', $jan1)) %7 - 3;
	    $desiredMonday = strtotime (($week - 1) . ' weeks '. $mondayOffset . ' days', $jan1);
	    return $desiredMonday;
	}
	
	
	# Function to get the current academic year
	public static function academicYear ($yearStartMonth = 9, $asRangeString = false, $rangeSecondTwoDigits = false)
	{
		# Convert years to ia/ib/ii
		$year = date ('Y');
		$month = date ('n');
		$currentYearStart = ($month < $yearStartMonth ? $year - 1 : $year);
		
		# Return formatted as e.g. 2010-2011
		#!# Could be improved
		if ($asRangeString) {
			$followingYear = ($currentYearStart + 1);
			if ($rangeSecondTwoDigits) {
				$followingYear = substr ($followingYear, -2);
			}
			return ($currentYearStart . '-' . $followingYear);
		}
		
		# Return the current year
		return $currentYearStart;
	}
	
	
	# Function to determine if a day is a working day
	public static function isWorkingDay ($dateString /* in YYYY-MM-DD format */, $disallowChristmasToNewYear = true)
	{
		# Convert the start date to unixtime (using an arbitary time of 12 midday)
		list ($fourDigitYear, $twoDigitMonth, $twoDigitDay) = explode ('-', $dateString);
		$startUnixtime = mktime (12, 0, 0, $twoDigitMonth, $twoDigitDay, $fourDigitYear);
		
		# Determine the day of the week
		$weekday = date ('N', $startUnixtime);
		if ($weekday == 6 || $weekday == 7) {
			return false;
		}
		
		# If dates between Christmas and New Year are not permitted, check for those
		if ($disallowChristmasToNewYear) {
			if (($twoDigitMonth == 12) && ($twoDigitDay > 24)) {return false;}
			if (($twoDigitMonth == 01) && ($twoDigitDay == 01)) {return false;}
		}
		
		# Define a list of public holidays; taken from https://www.gov.uk/bank-holidays
		$publicHolidays = array (
			'2011-01-03', '2011-04-22', '2011-04-25', '2011-05-02', '2011-05-30', '2011-08-29', '2011-12-26', '2011-12-27',
			'2012-01-02', '2012-04-06', '2012-04-09', '2012-05-07', '2012-06-04', '2012-06-05', '2012-08-27', '2012-12-25', '2012-12-26',
			'2013-01-01', '2013-03-29', '2013-04-01', '2013-05-06', '2013-05-27', '2013-08-26', '2013-12-25', '2013-12-26',
			'2014-01-01', '2014-04-18', '2014-04-21', '2014-05-05', '2014-05-26', '2014-08-25', '2014-12-25', '2014-12-26',
			'2015-01-01', '2015-04-03', '2015-04-06', '2015-05-04', '2015-05-25', '2015-08-31', '2015-12-25', '2015-12-28',
			'2016-01-01', '2016-03-25', '2016-03-28', '2016-05-02', '2016-05-30', '2016-08-29', '2016-12-26', '2016-12-27',
			'2017-01-02', '2017-04-14', '2017-04-17', '2017-05-01', '2017-05-29', '2017-08-28', '2017-12-25', '2017-12-26',
			'2018-01-01', '2018-03-30', '2018-04-02', '2018-05-07', '2018-05-28', '2018-08-27', '2018-12-25', '2018-12-26',
			'2019-01-01', '2019-04-19', '2019-04-22', '2019-05-06', '2019-05-27', '2019-08-26', '2019-12-25', '2019-12-26',
			'2020-01-01', '2020-04-10', '2020-04-13', '2020-05-08', '2020-05-25', '2020-08-31', '2020-12-25', '2020-12-28',
			'2021-01-01', '2021-04-02', '2021-04-05', '2021-05-03', '2021-05-31', '2021-08-30', '2021-12-27', '2021-12-28',
			'2022-01-03', '2022-04-15', '2022-04-18', '2022-05-02', '2022-06-02', '2022-06-03', '2022-08-29', '2022-09-19', '2022-12-26', '2022-12-27',
			'2023-01-02', '2023-04-07', '2023-04-10', '2023-05-01', '2023-05-08', '2023-05-29', '2023-08-28', '2023-12-25', '2023-12-26',
			'2024-01-01', '2024-03-29', '2024-04-01', '2024-05-06', '2024-05-27', '2024-08-26', '2024-12-25', '2024-12-26',
			'2025-01-01', '2025-04-18', '2025-04-21', '2025-05-05', '2025-05-26', '2025-08-25', '2025-12-25', '2025-12-26',
			'2026-01-01', '2026-04-03', '2026-04-06', '2026-05-04', '2026-05-25', '2026-08-31', '2026-12-25', '2026-12-28',
			// Add to this list each year when new dates are confirmed; the gov.uk page includes an .ics file which can be converted to CSV
		);
		
		# Check if it is a public holiday
		if (in_array ($dateString, $publicHolidays)) {
			return false;
		}
		
		# It is a working day
		return true;
	}
	
	
	# Function to add JS calendar rending over the bookings table, based on the range of dates supplied (in Y-m-d SQL format)
	# Link dates options: true for all / false for none / simple array of dates / associative array of date => array (timeLabel => url)
	public static function calendar ($dates, $linkDates = true, $linkFormat = '#Y-m-d')
	{
		# Start the HTML
		$html = '';
		
		# Order the supplied dates, in case not already ordered
		sort ($dates);
		
		# Determine if the link dates have timeslots
		$hasTimeslots = application::isAssociativeArray ($linkDates);
		
		# Determine today
		$today = date ('Y-m-d');
		
		# Get the earliest and latest months
		$startDate = application::array_first_value ($dates);
		list ($startYear, $startMonth, $startDay_ignored) = explode ('-', $startDate);
		$endDate = application::array_last_value ($dates);
		list ($endYear, $endMonth, $endDay_ignored) = explode ('-', $endDate);
		
		# Build the calendar, looping through each year, month and day in each month within the range
		$currentMonth = $startMonth;
		$currentYear = $startYear;
		$weekdays = array (1 => 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');	// Indexed from 1 (ISO 8601) for Monday to Sunday
		while ($currentYear <= $endYear) {
			while (mktime (12, 0, 0, $currentMonth, 1, $currentYear) <= mktime (12, 0, 0, $endMonth, 1, $endYear)) {
				$currentDay = 1;
				
				# Add month container
				$currentTimestamp = mktime (12, 0, 0, $currentMonth, $currentDay, $currentYear);
				$html .= "\n\n\t" . '<div class="calendar-month" data-month="' . date ('Y-m', $currentTimestamp) . '">';
				
				# Add the heading
				$html .= "\n\n\t\t" . '<h3>' . date ('F', mktime (12, 0, 0, $currentMonth, 1, $currentYear)) . ' ' . $currentYear . '</h3>';
				
				# Start the table for this month
				$html .= "\n\n\t\t" . '<div class="calendar-table-container">';
				$html .= "\n\t\t\t" . '<table>';
				$html .= "\n\t\t\t\t" . '<tr>';
				foreach ($weekdays as $weekday) {
					$html .= "\n\t\t\t\t\t" . '<th>' . $weekday . '</th>';
				}
				$html .= "\n\t\t\t\t" . '</tr>';
				
				# Loop through each day
				$html .= "\n\t\t\t\t" . '<tr>';
				$lastDayInMonth = cal_days_in_month (CAL_GREGORIAN, $currentMonth, $currentYear);
				while ($currentDay <= $lastDayInMonth) {
					$currentTimestamp = mktime (12, 0, 0, $currentMonth, $currentDay, $currentYear);
					$currentWeekdayIndex = date ('N', $currentTimestamp);	// Indexed from 1 (ISO 8601) for Monday to Sunday, as above
					
					# Pad initial columns in first row, so that day 1 is under the correct day heading
					if ($currentDay == 1) {
						for ($weekdayIndex = 1; $weekdayIndex < $currentWeekdayIndex; $weekdayIndex++) {
							$html .= "\n\t\t\t\t\t" . '<td></td>';
						}
					}
					
					# Determine the cell content
					$currentDate = date ('Y-m-d', $currentTimestamp);
					$link = date ($linkFormat, $currentTimestamp);
					$dayLinked = "<a class=\"calendar-choosedate\" href=\"{$link}\" data-date=\"{$currentDate}\">" . $currentDay . '</a>';
					$dayUnlinked = '<span>' . $currentDay . '</span>';
					switch (true) {
						
						// Link dates mode = true: Show linked date
						case ($linkDates === true):
							$content = $dayLinked;
							break;
							
						// Link dates mode = false: Now unlinked date
						case (!$linkDates):
							$content = $dayUnlinked;
							break;
							
						// Associative array mode, as (date => array (timeLabel => url), ...): Dates linked if present, else unlinked; when linked, UI to select time
						case ($hasTimeslots):
							if (isSet ($linkDates[$currentDate])) {
								$content  = $dayLinked;
								$list = array ();
								foreach ($linkDates[$currentDate] as $label => $url) {
									$list[] = '<a href="' . htmlspecialchars ($url) . '">' . htmlspecialchars ($label) . '</a>';
								}
								$content .= "\n\t\t\t\t\t\t" . "<dialog class=\"calendar-timeslots\" data-date=\"{$currentDate}\">";
								$content .= "\n\t\t\t\t\t\t" . '<button class="calendar-dialog-close" type="reset">X</button>';
								$content .= "\n\t\t\t\t\t\t" . "<h4>" . date ('jS F, Y', $currentTimestamp) . '</h4>';
								$content .= "\n\t\t\t\t\t\t" . "<p>Please choose a time:</p>";
								$content .= application::htmlUl ($list, 7, 'calendar-timeslots');
								$content .= "\t\t\t\t\t\t</dialog>";
							} else {
								$content = $dayUnlinked;
							}
							break;
							
						// Simple array mode of dates: Dates linked if present, else unlinked
						case (is_array ($linkDates)):
							$content = (in_array ($currentDate, $linkDates) ? $dayLinked : $dayUnlinked);
							break;
					}
					
					# Add the cell
					$html .= "\n\t\t\t\t\t" . '<td data-date="' . $currentDate . '"' . ($currentDate == $today ? ' class="calendar-today" title="Today"' : '') . '>' . $content . '</td>';
					
					# New row if a Sunday
					if ($currentWeekdayIndex == 7) {
						if ($currentDay != $lastDayInMonth) {	// Avoid creating new line if at the end, as is closed later
							$html .= "\n\t\t\t\t" . '</tr>';
							$html .= "\n\t\t\t\t" . '<tr>';
						}
					}
					
					# Next day
					$currentDay++;
				}
				
				# Pad final columns in last row
				for ($weekdayIndex = $currentWeekdayIndex; $weekdayIndex < 7; $weekdayIndex++) {
					$html .= "\n\t\t\t\t\t" . '<td></td>';
				}
				
				# End month row
				$html .= "\n\t\t\t\t" . '</tr>';
				$html .= "\n\t\t\t" . '</table>';
				$html .= "\n\t\t" . '</div><!-- /.calendar-table-container -->';
				
				# End month container
				$html .= "\n\n\t" . '</div><!-- /.calendar-month -->';
				
				# Next month
				$currentDay = 1;
				if ($currentMonth == 12) {break;}
				$currentMonth++;
			}
			
			# Next year
			$currentDay = 1;
			$currentMonth = 1;
			$currentYear++;
		}
		
		# Surround with a main div
		$html = "\n\n" . '<div class="calendar-container">' . $html . "\n\n</div><!-- /.calendar-container -->";
		
		# Add in styles
		$stylesHtml = "\n\n" . '<style>
			.calendar-container {display: flow-root; margin-top: 25px;}
			.calendar-container .calendar-month {float: left; margin: 0 20px 20px 0; padding: 5px 10px;}
			.calendar-container .calendar-month:hover {background-color: #fcfcfc;}
			.calendar-container .calendar-month h3 {margin: 5px 0 10px; padding: 0; text-align: center;}
			.calendar-container .calendar-month .calendar-table-container {height: 20em;}
			.calendar-container .calendar-month table {border: 0; border-collapse: collapse; margin: 0; background-color: transparent;}
			.calendar-container .calendar-month table th, .calendar-container .calendar-month table td {width: 2em; max-width: 2em; height: 2em; max-height: 2em; text-align: center; vertical-align: middle; padding: 0.2em; background-color: transparent;}
			.calendar-container .calendar-month table td span, .calendar-container .calendar-month table td a.calendar-choosedate {display: inline-block; width: 1.4em; height: 1.4em; padding: 3px; border-radius: 50%; border: 1px solid transparent; text-decoration: none;}
			.calendar-container .calendar-month table td span {cursor: default;}
			.calendar-container .calendar-month table td.calendar-today span, .calendar-container .calendar-month table td.calendar-today a {border-color: #ddd;}
			.calendar-container .calendar-month table td:hover span {background-color: #eee;}
			.calendar-container .calendar-month table td a.calendar-choosedate {font-weight: bold; background-color: rgba(0, 105, 255, 0.066);}
			.calendar-container .calendar-month table td a.calendar-choosedate:hover {color: blue; background-color: rgba(0, 105, 255, 0.4);}
			.calendar-container dialog {width: 200px; height: 300px; padding: 20px; overflow-y: auto; border: 1px solid gray;}
			@keyframes calendar-fadein { from {opacity: 0;} to {opacity: 1;} }
			.calendar-container dialog[open] {animation: calendar-fadein 0.5s ease normal;}
			.calendar-container dialog h4 {margin-top: 20px;}
			.calendar-container dialog button.calendar-dialog-close {float: right;}
			.calendar-container dialog ul {margin: 25px 0 0; padding: 0; list-style: none;}
			.calendar-container dialog ul li {width: auto; margin-bottom: 10px;}
			.calendar-container dialog ul li a {text-decoration: none; border-bottom: 0; display: block; padding: 10px; border: 1px solid gray; border-radius: 10px;}
		</style>
		';
		$html = $stylesHtml . $html;
		
		# JS
		if ($linkDates) {
			$jsHtml = "\n\n" . "<script>
				document.addEventListener ('DOMContentLoaded', function () {
					document.querySelectorAll ('.calendar-container a').forEach (function (element) {
						element.addEventListener ('click', function (e) {
							const date = e.target.dataset.date;
							const month = date.slice (0, 7);	// i.e. YYYY-MM
							
							// Add in JS custom event, for convenience of client code
							document.dispatchEvent (new CustomEvent ('@calendar/datechosen', {bubbles: true, detail: date}));
							
							// Show timeslot selector if present
							const timeslotListHtml = e.target.parentElement.querySelector ('ul.calendar-timeslots');
							if (timeslotListHtml) {
								const dialog = document.querySelector ('dialog[data-date=\"' + date + '\"]');
								dialog.showModal ();
								dialog.querySelector ('button.calendar-dialog-close').addEventListener ('click', function () {dialog.close ();});
							}
						});
					});
					
					// Example client code use:
					// document.addEventListener ('@calendar/datechosen', function (e) {alert (e.detail);});
				});
				
				
			</script>
			";
			$html .= $jsHtml;
		}
		
		# Return the HTML
		return $html;
	}
}

?>
