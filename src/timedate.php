<?php

# Class containing a variety of date/time processing functions
# http://download.geog.cam.ac.uk/projects/timedate/
# Version: 1.1.4

class timedate
{
	# Function to produce a date array
	function getDateTimeArray ($value)
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
		$datetime['time'] = $datetime['hour'] . ':' . $datetime['minute'] . ':' . $datetime['second'];
		
		# Construct a combined SQL-format datetime formatted string
		$datetime['datetime'] = $datetime['year'] . '-' . $datetime['month'] . '-' . $datetime['day'] . ' ' . $datetime['time'];
		
		# Return the array
		return $datetime;
	}
	
	
	# Function to format the date
	function formatDate ($date)
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
	function presentDateFromArray ($value, $level = 'date')
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
	function convertTimestamp ($timestamp, $includeTime = true)
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
	function convertBackwardsDateToText ($backwardsDateString, $format = 'l, jS F Y')
	{
		# Determine whether a hyphen is used to split the date and time
		$splitter = ((strpos ($backwardsDateString, '-') !== false) ? '-' : '');
		
		# Get the year month and day out of the string
		list ($year, $month, $day) = sscanf ($backwardsDateString, "%4s{$splitter}%2s{$splitter}%2s");
		
		# Convert to string
		$dateFormatted = date ($format, mktime (0, 0, 0, $month, $day, $year));
		
		# Return the string
		return $dateFormatted;
	}
	
	
	# Function to return an intelligent string for date of birth and death
	function dateBirthDeath ($yearBirth, $yearDeath)
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
	function parseTime ($input)
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
		if (ereg ('^ ', $time)) {return false;}
		
		# 4. Return false if two adjacent whitespaces are found (i.e. do not allow multiples of originally non-whitespace allowed characters)
		if (ereg ('  ', $time)) {return false;}
		
		# 5. Remove any trailing separator
		$time = trim ($time);
		
		# 6b. Throw error if string contains other than: 0-9, whitespace separator, or the letters a m p
		#!# This could ideally be improved to weed out more problems earlier on
		if (ereg ('[^0-9\ amp]+', $time)) {return false;}
		
		# 7a. Adjust am and pm for the possibility of a.m. or a.m or p.m. or p.m having been entered
		$time = str_replace ('a m', 'am', $time);
		$time = str_replace ('p m', 'pm', $time);
		
		# 7b. If string ends with am or pm then strip that off and hold it for later use
		if ((eregi ('am$', $time)) || (eregi ('pm$', $time))) {
			$timeParts['meridiem'] = substr ($time, -2);
			$time = substr ($time, 0, -2);
		}
		
		# 8. Throw error if string contains other than: 0-9 or space
		if (ereg ('[^0-9\ ]+', $time)) {return false;}
		
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
			if (($numbersInString > 3) && (ereg ('^[0-9]\ [0-9]\ ', $time))) {return false;}
			
			# Throw error if string ends with space-number-space
			if (ereg ('\ [0-9]$', $time)) {return false;}
			
			# Recalculate the number of numeric digits in the string
			$numericOnlyString = str_replace (' ', '', $time);
			$numbersInString = strlen ($numericOnlyString);
			
			# 14. Check whether string contains 3 or 4 numeric digits; if so, run several checks:
			if ($numbersInString == 3 || $numbersInString == 4) {
				
				# Make sure the last two characters form a number between 00-59
				if (!ereg ('[0-5][0-9]$', $time)) {return false;}
				
				# Extract the minutes
				$timeParts['minutes'] = substr ($time, -2);
				
				# Strip off the minutes (and trim again, although that should not be necessary)
				$time = trim (substr ($time, 0, -2));
			}
		}
		
		# 15a. Check that there is no whitespace left (all that should remain is hours)
		if (!ereg ('[0-9]{1,2}', $time)) {return false;}
		
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
	
	
	# Function to convert a two-character year to a four-character year
	function convertYearToFourCharacters ($year, $autoCenturyConversationLastYear = 69)
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
	function isValidDateFormat ($string, $dateTime = true)
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
	function getDatesForFutureMonths ($monthsAhead, $format = 'Y-m-d', $removeWeekends = false)
	{
		# Start an array to hold the dates
		$dates = array ();
		
		# Add an extra month ahead so that it is $months ahead plus any remaining days in that month
		$monthsAhead++;
		
		# Define the current day, month and year
		$day = date ('d');
		$month = date ('m');
		$year = date ('Y');
		
		# Advance through the calendar until finished
		while ($monthsAhead) {
			
			# Skip weekend days if required
			$skip = false;
			if ($removeWeekends) {
				$weekday = date ('l', mktime (0, 0, 0, $month, $day, $year));
				if (($weekday == 'Saturday') || ($weekday == 'Sunday')) {
					$skip = true;
				}
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
}
