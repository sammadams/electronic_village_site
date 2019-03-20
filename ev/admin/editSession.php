<?php
	//editSession.php - allows a user to edit session information in the database
	//accessible only to admin and chair users
	
	include_once "login.php";
	$topTitle = "Edit Session";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false) { //only admins and chairs have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
			
	if(isset($_POST["sessionTitle"]) && isset($_POST["sessionDate"]) && isset($_POST["sessionTime"])) {
		$id = strip_tags($_POST["sessionID"]);
		$title = strip_tags($_POST["sessionTitle"]);
		$date = strip_tags($_POST["sessionDate"]);
		$time = strip_tags($_POST["sessionTime"]);
		$location = strip_tags($_POST["sessionLocation"]);
		$event = strip_tags($_POST["sessionEvent"]);
		
		//Update the session information in the database
		$eStmt = $db->prepare("UPDATE `sessions` SET `title` = ?,`date` = ?,`time` = ?,`location` = ?,`event` = ? WHERE `id` = ? LIMIT 1");
		$eStmt->bind_param('ssssss', $title, $date, $time, $location, $event, $id);
		if(!$eStmt->execute()) {
			echo $eStmt->error;
			exit();
		}
		
		//If we get this far, then show the sucess message
		include "adminTop.php";
?>
					<h3 align="center">The session information was edited successfully!</h3>
					<p align="center"><a href="sessionList.php">Back to Session List</a></p>
<?php				
		include "adminBottom.php";	
		exit();
	}
	
	//Get the session information
	$session = isset($_GET["s"]) ? strip_tags($_GET["s"]) : "";
	if($session == "") {
		echo "No session ID given!";
		exit();
	}
	
	$sStmt = $db->prepare("SELECT id, location, date, time, event, title FROM sessions WHERE id = ? LIMIT 1");
	$sStmt->bind_param('s',$session);
	$sStmt->execute();
	$sStmt->store_result();
	if($sStmt->num_rows < 1) {
		echo "No session found with that ID! (Error: ".$sStmt->error.")";
		exit();
	}
	
	$sStmt->bind_result($sID, $sLocation, $sDate, $sTime, $sEvent, $sTitle);
	$sStmt->fetch();
	
	include "adminTop.php";
		
	$tmpTime = explode("-",$sTime);
	$sStart = $tmpTime[0];
	$sEnd = $tmpTime[1];
?>
					<script type="text/javascript">						
						function deleteSession() {
							window.location.href = 'deleteSession.php?s=<?php echo $sID; ?>';
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
							
							//check the location
							if(document.getElementById('sessionLocation').value == '') {
								alert('You did not specify a location for this session!');
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
							<td><input type="text" id="sTitle" name="sTitle" value="<?php echo $sTitle; ?>" size="60" /></td>
						</tr>
						<tr>
							<td valign="top">Date:</td>
							<td>
								<input type="radio" name="sDate" id="sDate_0" onClick="updateDate(this)" value="<?php echo date('Y-m-d', strtotime($configs['confStartDate'])); ?>"<?php if($sDate == date("Y-m-d", strtotime($configs['confStartDate']))) { ?> checked="true"<?php } ?>> <span onClick="checkEl('sDate_0')"><?php echo date("F j, Y", strtotime($configs['confStartDate'])); ?></span><br />
<?php
	$nextDate = strtotime($configs['confStartDate'].' +1 days');
	$dN = 1;
	while($nextDate <= strtotime($configs['confEndDate'])) {
?>
								<input type="radio" name="sDate" id="sDate_<?php echo $dN; ?>" onClick="updateDate(this)" value="<?php echo date('Y-m-d', $nextDate); ?>"<?php if($sDate == date("Y-m-d", $nextDate)) { ?> checked="true"<?php } ?>> <span onClick="checkEl('sDate_<?php echo $dN; ?>')"><?php echo date("F j, Y", $nextDate); ?></span><br />
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
								<?php echo createTimeSelect('sStart', 'Start...', $sStart); ?>
								 &nbsp; to &nbsp; 
								<?php echo createTimeSelect('sEnd', 'End...', $sEnd); ?>
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
								<input type="radio" name="sEvent" id="sEvent_<?php echo $evtID; ?>" value="<?php echo $evtID; ?>" onClick="updateEvent()"<?php if($sEvent == $evtID) { ?> checked="true"<?php } ?> /> <span onClick="checkEl('sEvent_<?php echo $evtID; ?>')"><?php echo $evtEvent; ?></span><br />
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
								<input type="radio" name="sLocation" id="sLocation_<?php echo $locID; ?>" value="<?php echo $locID; ?>" onClick="updateLocation()"<?php if($sLocation == $locID) { ?> checked="true"<?php } ?> /><span onClick="checkEl('sLocation_<?php echo $locID; ?>')"><?php echo $locationName; ?></span></br>
<?php
	}
?>	
							</td>
						</tr>
						<tr>
							<td align="center" colspan="2"><input type="button" value="Cancel" onClick="window.location.href='sessionList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Submit" onClick="checkForm()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Delete Session" onClick="deleteSession()" /></td>
						</tr>
					</table>
					<form name="sessionForm" id="sessionForm" method="post" action="">
						<input type="hidden" name="sessionTitle" id="sessionTitle" value="<?php echo $sTitle; ?>" />
						<input type="hidden" name="sessionDate" id="sessionDate" value="<?php echo $sDate; ?>" />
						<input type="hidden" name="sessionTime" id="sessionTime" value="<?php echo $sTime; ?>" />
						<input type="hidden" name="sessionID" id="sessionID" value="<?php echo $sID; ?>" />
						<input type="hidden" name="sessionEvent" id="sessionEvent" value="<?php echo $sEvent; ?>" />
						<input type="hidden" name="sessionLocation" id="sessionLocation" value="<?php echo $sLocation; ?>" />
					</form>
<?php
	include "adminBottom.php";

	function createTimeSelect($elID, $firstOption, $selectedValue, $minHour = 6, $maxHour = 21, $minInterval = 15) {
		$html = '<select name="'.$elID.'" id="'.$elID.'" onChange="updateTime(this)"><option value="">'.$firstOption.'</option>';
		$hour = $minHour;
		$minutes = 0;
		while($hour < $maxHour) {
			$timeValue = '';
			if($hour < 10) $timeValue .= '0'.$hour;
			else $timeValue .= $hour;
			$timeValue .= ':';
			if($minutes < 10) $timeValue .= '0'.$minutes;
			else $timeValue .= $minutes;
			
			$html .= '<option value="'.$timeValue.'"';
			
			if($selectedValue == $timeValue) $html .= ' selected';
			$html .= '>';
			
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