<?php

# Class containing a variety of date/time processing functions
class datetime
{
	# Function to produce a date array
	function getDateTimeArray ($value)
	{
		# Obtain an array of time components
		list (
			$datetime['year'],
			$datetime['month'],
			$datetime['day'],
			$datetime['hour'],
			$datetime['minute'],
			$datetime['second'],
		) = sscanf (($value == 'timestamp' ? date ('Y-m-d H:i:s') : $value), '%4s-%2s-%2s %2s:%2s:%2s');
		
		# Construct a combined time formatted string
		$datetime['time'] = $datetime['hour'] . ':' . $datetime['minute'] . ':' . $datetime['second'];
		
		# Construct a combined SQL-format datetime formatted string
		$datetime['datetime'] = $datetime['year'] . '-' . $datetime['month'] . '-' . $datetime['day'] . ' ' . $datetime['time'];
		
		# Return the array
		return $datetime;
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
}
