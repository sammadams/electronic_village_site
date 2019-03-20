<?php
	//addSession.php - allows a user to edit session information in the database
	//accessible only to admin and chair users
	
	include_once "login.php";
	$topTitle = "Add Session";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false) { //only admins and chairs have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
			
	if(isset($_POST["sessionTitle"]) && isset($_POST["sessionDate"]) && isset($_POST["sessionTime"])) {
		$title = strip_tags($_POST["sessionTitle"]);
		$date = strip_tags($_POST["sessionDate"]);
		$time = strip_tags($_POST["sessionTime"]);
		$event = strip_tags($_POST["sessionEvent"]);
		$location = strip_tags($_POST["sessionLocation"]);
		
		//Insert the session information in the database
		$sStmt = $db->prepare("INSERT INTO `sessions` (`id`,`location`,`date`,`time`,`event`,`title`) VALUES ('0',?,?,?,?,?)");
		$sStmt->bind_param('sssss', $location, $date, $time, $event, $title);
		if(!$sStmt->execute()) {
			echo $sStmt->error;
			exit();
		}
		
		//If we get this far, then show the sucess message
		include "adminTop.php";
?>
					<h3 align="center">The session was added successfully!</h3>
					<p align="center"><a href="sessionList.php">Back to Session List</a></p>
					<p align="center"><a href="addSession.php">Add Another Session</a></p>
<?php				
		include "adminBottom.php";	
		exit();
	}
	
	include "adminTop.php";
?>
					<script type="text/javascript">												
						function updateTime() {
							//get the start time
							var sStartEl = document.getElementById('sStart');
							var sStart = sStartEl.options[sStartEl.selectedIndex].value;
														
							//get the end time
							var sEndEl = document.getElementById('sEnd');
							var sEnd = sEndEl.options[sEndEl.selectedIndex].value;
							
							//The start time cannot be later than the end time
							if(sStart != '' && sEnd != '' && sStart > sEnd) {
								alert('The start time is later than the end time!');
								return false;
							}
							
							document.getElementById('sessionTime').value = sStart + '-' + sEnd;
						}
						
						function checkForm() {
							//check the title
							if(document.getElementById('sTitle').value == '') {
								alert('You did not enter a title for this session!');
								document.getElementById('sTitle').focus();
								return false;
							}
							
							//check the date
							if(document.getElementById('sessionDate').value == '') {
								alert('You did select a valid date for this session!');
								return false;
							}
							
							//check the times
							if(document.getElementById('sessionTime').value == '') {
								alert('You did not select valid times for this session!');
								return false;
							}
							
							//check the event
							if(document.getElementById('sessionEvent').value == '') {
								alert('You did not select an event for this session!');
								return false;
							}
							
							//check the location
							if(document.getElementById('sessionLocation').value == '') {
								alert('You did not specify a location for this event!');
								return false;
							}
							
							//If we get this far, update and submit the form
							document.getElementById('sessionTitle').value = document.getElementById('sTitle').value;
							document.getElementById('sessionForm').submit();
						}
						
						function checkEl(elStr) {
							document.getElementById(elStr).checked = true;
							if(elStr.indexOf('sDate') > -1) updateDate();
							else if(elStr.indexOf('sStart') > -1 || elStr.indexOf('sEnd') > -1) updateTime();
							else if(elStr.indexOf('sEvent') > -1) updateEvent();
							else if(elStr.indexOf('sLocation') > -1) updateLocation();
						}
						
						function updateEvent() {
							var rEls = document.getElementsByName('sEvent');
							for(i = 0; i < rEls.length; i++) {
								if(rEls[i].checked) {
									document.getElementById('sessionEvent').value = rEls[i].value;
									break;
								}
							}
						}
						
						function updateLocation() {
							var lEls = document.getElementsByName('sLocation');
							for(i = 0; i < lEls.length; i++) {
								if(lEls[i].checked) {
									document.getElementById('sessionLocation').value = lEls[i].value;
									break;
								}
							}
						}

						function updateDate() {
							var lEls = document.getElementsByName('sDate');
							for(i = 0; i < lEls.length; i++) {
								if(lEls[i].checked) {
									document.getElementById('sessionDate').value = lEls[i].value;
									break;
								}
							}
						}
					</script>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>Title:</td>
							<td><input type="text" id="sTitle" name="sTitle" value="" size="60" /></td>
						</tr>
						<tr>
							<td valign="top">Date:</td>
							<td>
								<input type="radio" name="sDate" id="sDate_0" onClick="updateDate(this)" value="<?php echo date('Y-m-d', strtotime($configs['confStartDate'])); ?>"> <span onClick="checkEl('sDate_0')"><?php echo date("F j, Y", strtotime($configs['confStartDate'])); ?></span><br />
<?php
	$nextDate = strtotime($configs['confStartDate'].' +1 days');
	$dN = 1;
	while($nextDate <= strtotime($configs['confEndDate'])) {
?>
								<input type="radio" name="sDate" id="sDate_<?php echo $dN; ?>" onClick="updateDate(this)" value="<?php echo date('Y-m-d', $nextDate); ?>"> <span onClick="checkEl('sDate_<?php echo $dN; ?>')"><?php echo date("F j, Y", $nextDate); ?></span><br />
<?php
		$tmpDate = date("Y-m-d", $nextDate);
		$nextDate = strtotime($tmpDate.' +1 days');
		$dN++;
	}
?>
							</td>
						</tr>
						<tr>
							<td>Time:</td>
							<td>
								<?php echo createTimeSelect('sStart', 'Start...'); ?>
								 &nbsp; to &nbsp; 
								<?php echo createTimeSelect('sEnd', 'End...'); ?>
							</td>
						</tr>
						<tr>
							<td valign="top">Event:</td>
							<td>
<?php
	$evtStmt = $db->prepare("SELECT id, event FROM events WHERE 1");
	$evtStmt->execute();
	$evtStmt->store_result();
	$evtStmt->bind_result($evtID, $evtEvent);
	while($evtStmt->fetch()) {
?>
								<input type="radio" name="sEvent" id="sEvent_<?php echo $evtID; ?>" value="<?php echo $evtID; ?>" onClick="updateEvent()" /> <span onClick="checkEl('sEvent_<?php echo $evtID; ?>')"><?php echo $evtEvent; ?></span><br />
<?php
	}
?>
								<input type="radio" name="sEvent" id="sEvent_0" value="0" onClick="updateEvent()" /> <span onClick="checkEl('sEvent_0')">Other (e.g. Ask Us, Academic Sessions, etc.)</span>
							</td>
						</tr>
						<tr>
							<td valign="top">Location:</td>
							<td>
<?php
	$locStmt = $db->prepare("SELECT id, name, room FROM locations WHERE 1");
	$locStmt->execute();
	$locStmt->store_result();
	$locStmt->bind_result($locID, $locName, $locRoom);
	while($locStmt->fetch()) {
		if($locName != '') $locationName = $locName;
		else $locationName = $locRoom;
?>
								<input type="radio" name="sLocation" id="sLocation_<?php echo $locID; ?>" value="<?php echo $locID; ?>" onClick="updateLocation()" /><span onClick="checkEl('sLocation_<?php echo $locID; ?>')"><?php echo $locationName; ?></span></br>
<?php
	}
?>	
							</td>
						</tr>
						<tr>
							<td align="center" colspan="2"><input type="button" value="Cancel" onClick="window.location.href='sessionList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Submit" onClick="checkForm()" /></td>
						</tr>
					</table>
					<form name="sessionForm" id="sessionForm" method="post" action="">
						<input type="hidden" name="sessionTitle" id="sessionTitle" value="" />
						<input type="hidden" name="sessionDate" id="sessionDate" value="" />
						<input type="hidden" name="sessionTime" id="sessionTime" value="" />
						<input type="hidden" name="sessionEvent" id="sessionEvent" value="" />
						<input type="hidden" name="sessionLocation" id="sessionLocation" value="" />
					</form>
<?php
	include "adminBottom.php";
	
	function createTimeSelect($elID, $firstOption, $minHour = 6, $maxHour = 21, $minInterval = 15) {
		$html = '<select name="'.$elID.'" id="'.$elID.'" onChange="updateTime(this)"><option value="">'.$firstOption.'</option>';
		$hour = $minHour;
		$minutes = 0;
		while($hour < $maxHour) {
			$html .= '<option value="';
			if($hour < 10) $html .= '0'.$hour;
			else $html .= $hour;
			$html .= ':';
			if($minutes < 10) $html .= '0'.$minutes;
			else $html .= $minutes;
			$html .= '">';
			
			if($hour <= 12) $html .= $hour;
			else $html .= ($hour - 12);
			$html .= ':';
			if($minutes < 10) $html .= '0'.$minutes;
			else $html .= $minutes;
			
			if($hour < 12) $html .= ' AM';
			else $html .= ' PM';
			
			$html .= '</option>';
			
			$minutes = $minutes + $minInterval;
			if($minutes >= 60) {
				$hour++;
				$minutes = $minutes - 60;
			}
		}
		
		$html .= '</select>';
		
		return $html;
	}
?>