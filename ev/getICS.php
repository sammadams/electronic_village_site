<?php
	// getICS.php -- outputs the session information in ICS format (for importing into a calendar)
	// available to the public, so no login is required

	include_once "../../ev_config.php";
	include_once "../../ev_library.php";

	if(isset($_GET["s"])) $sID = strip_tags($_GET["s"]); //get the specified session id
	else {
		echo "No session ID given!";
		exit();
	}
	
	if(preg_match("/\D/",$sID)) { //given session id contains non-numeric characters
		echo "An invalid session ID was given!";
		exit();
	}
	
	//Get the session information from the database
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE `id` = ?");
	$sStmt->bind_param('s',$sID);
	$sStmt->execute();
	$sStmt->bind_result($id,$location,$date,$time,$event,$title,$presentations);
	$sStmt->fetch();
	
	foreach($locations AS $l) {
		if($location == $l['id']) {
			$location = $l['name'].' ('.$l['room'].')';
			break;
		}
	}
	
	//echo $time.'<br>';
	
	//Format the dates and times for ICS format
	$tmpDate = explode("-",$date);
	$month = intval($tmpDate[1]); //intval gets rid of any leading zeros
	$day = intval($tmpDate[2]);
	$year = $tmpDate[0]; //4-digit value for the year
	
	$tmpTime = explode("-",$time);
	$startTime = explode(":",$tmpTime[0]);
	$sHour = intval($startTime[0]);
	$sMin = intval($startTime[1]);
	
	$endTime = explode(":",$tmpTime[1]);
	$eHour = intval($endTime[0]);
	$eMin = intval($endTime[1]);
	
	$start_time = mktime($sHour,$sMin,0,$month,$day,$year);
	$end_time = mktime($eHour,$eMin,0,$month,$day,$year); //events don't last more than 1 day, so use the same month, day and year
	
	if($location == "ev") $location = "Electronic Village (Room 701-B)";
	else if($location == "ts") $location = "Technology Showcase (Room 701-A)";
	
	$tzStr = $configs['confTimeZone'];
	$tzObj = new DateTimeZone($tzStr);
	$dtObj = new DateTime($configs['confStartDate'], $tzObj);
	$isDST = $dtObj->format('I');

	$tzOffset = $dtObj->getOffset() / 3600; // we need the offset in hours
	$gmtConversion = $tzOffset * -1; // we want to reverse the number to add it to the current time - the hour difference between the location and GMT (Atlanta is GMT -4, so add 4 to get GMT)
	$gCSHour = $sHour + $gmtConversion; //get the GMT hour
	$gCEHour = $eHour + $gmtConversion; //get the GMT hour

	$gCSStamp = mktime($gCSHour,intval($sMin),0,$month,$day,$year);
	$gCEStamp = mktime($gCEHour,intval($eMin),0,$month,$day,$year);
	
	if($isDST) {
		if(abs($tzOffset) < 10 && $tzOffset < 0) $dstOffset = '-0'.abs($tzOffset).'00';
		else if($tzOffset < 0) $dstOffset = $tzOffset.'00';
		else if(abs($tzOffset) < 10) $dstOffset = '+0'.abs($tzOffset).'00';
		else $dstOffset = '+'.$tzOffset.'00';
		
		$tzOffset = $tzOffset - 1; // STD time is 1 hour behind DST
		if(abs($tzOffset) < 10 && $tzOffset < 0) $stdOffset = '-0'.abs($tzOffset).'00';
		else if($tzOffset < 0) $stdOffset = $tzOffset.'00';
		else if(abs($tzOffset) < 10) $stdOffset = '+0'.abs($tzOffset).'00';
		else $stdOffset = '+'.$tzOffset.'00';

		$dstAbbr = $dtObj->format('T'); // the abbreviation (eg. 'EDT' or 'PST')
		$stdObj = new DateTime($confYear.'-12-01', $tzObj);
		$stdAbbr = $stdObj->format('T');
	} else {
		if(abs($tzOffset) < 10 && $tzOffset < 0) $stdOffset = '-0'.abs($tzOffset).'00';
		else if($tzOffset < 0) $stdOffset = $tzOffset.'00';
		else if(abs($tzOffset) < 10) $stdOffset = '+0'.abs($tzOffset).'00';
		else $stdOffset = '+'.$tzOffset.'00';
		
		$tzOffset = $tzOffset + 1; // DST is 1 hours ahead of STD
		if(abs($tzOffset) < 10 && $tzOffset < 0) $dstOffset = '-0'.abs($tzOffset).'00';
		else if($tzOffset < 0) $dstOffset = $tzOffset.'00';
		else if(abs($tzOffset) < 10) $dstOffset = '+0'.abs($tzOffset).'00';
		else $dstOffset = '+'.$tzOffset.'00';
		
		$stdAbbr = $dtObj->format('T');
		$dstAbbr = $dtObj->modify('+6 months')->format('T');
	}
	
    $ical = "BEGIN:VCALENDAR\n";
    $ical .= "VERSION:2.0\n";
    $ical .= "PRODID:-//CALL-IS//Electronic Village Online Schedule//EN\n";
    $ical .= "CALSCALE:GREGORIAN\n";
    $ical .= "METHOD:PUBLISH\n";
    $ical .= "BEGIN:VTIMEZONE\n";
    $ical .= "TZID:{$tzStr}\n";
    $ical .= "X-LIC-LOCATION:{$tzStr}\n";
    $ical .= "BEGIN:DAYLIGHT\n";
    $ical .= "TZOFFSETFROM:{$stdOffset}\n";
    $ical .= "TZOFFSETTO:{$dstOffset}\n";
    $ical .= "TZNAME:{$dstAbbr}\n";
    $ical .= "DTSTART:19700308T020000\n";
    $ical .= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU\n";
    $ical .= "END:DAYLIGHT\n";
    $ical .= "BEGIN:STANDARD\n";
    $ical .= "TZOFFSETFROM:{$dstOffset}\n"; //switch the from and to for the standard time zone
    $ical .= "TZOFFSETTO:{$stdOffset}\n";
    $ical .= "TZNAME:{$stdAbbr}\n";
    $ical .= "DTSTART:19701101T020000\n";
    $ical .= "RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU\n";
    $ical .= "END:STANDARD\n";
    $ical .= "END:VTIMEZONE\n";
    $ical .= "BEGIN:VEVENT\n";
    $ical .= "DTSTAMP:".date('Ymd\THis\Z')."\n";
    $ical .= "DTSTART;TZID={$tzStr}:".date('Ymd\THis\Z',$gCSStamp)."\n";
    $ical .= "DTEND;TZID={$tzStr}:".date('Ymd\THis\Z',$gCEStamp)."\n";
    $ical .= "STATUS:CONFIRMED\n";
    $ical .= "SUMMARY:{$title}\n";
    $ical .= "DESCRIPTION:For a list of presentations, please visit http://call-is.org/eve/schedule.php?s={$id}\n";
    $ical .= "ORGANIZER;CN=Electronic Village:MAILTO:ev@call-is.org\n";
    $ical .= "CLASS:PUBLIC\n";
    $ical .= "CREATED:".date('Ymd\THis\Z')."\n";
    $ical .= "LOCATION:{$location}\n";
    $ical .= "URL:https://call-is.org/ev/schedule.php?s={$id}\n";
    $ical .= "SEQUENCE:1\n";
    $ical .= "LAST-MODIFIED:".date('Ymd\THis\Z')."\n";
    $ical .= "UID:".date('Ymd\THis',$gCSStamp)."-ev@call-is.org\n";
    $ical .= "END:VEVENT\n";
    $ical .= "END:VCALENDAR";
    
    header('Content-type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename=evCal.ics');  

	echo $ical;

//	echo "<pre>".$ical."</pre>";;
