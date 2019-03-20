<?php
	//editEvent.php - allows a user to edit event information in the database
	//accessible only to admin users
	
	include_once "login.php";
	$topTitle = "Edit Event";
	
	if(strpos($_SESSION['user_role'],"admin") === false) { //only admins can view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(isset($_POST["editEventName"])) {
		$eventName = strip_tags($_POST["editEventName"]);
		$eventAdminSuffix = preg_replace("/[^a-z_]/", "", $_POST["editEventAdminSuffix"]);
		
		$eventWebDescription = (!empty($_POST["editEventWebDescription"])) ? strip_tags($_POST["editEventWebDescription"], '<span><a><br><ul><li><i><b><strong><em>') : NULL;
		$eventEmailDescription = (!empty($_POST["editEventEmailDescription"])) ? strip_tags($_POST["editEventEmailDescription"], '<span><a><br><ul><li><i><b><strong><em>') : NULL;
		$eventPropLink = (!empty($_POST["editEventPropLink"])) ? strip_tags($_POST["editEventPropLink"]) : NULL;
		$eventWebTitle = (!empty($_POST["editEventWebTitle"])) ? strip_tags($_POST["editEventWebTitle"]) : NULL;
		$eventEmailTitle = (!empty($_POST["editEventEmailTitle"])) ? strip_tags($_POST["editEventEmailTitle"]) : NULL;
		$eventTopics = (!empty($_POST["editEventTopics"])) ? strip_tags($_POST["editEventTopics"]) : NULL;
		
		$eventSummaryMaxWords = (int)preg_replace("/\D/", "", $_POST["editEventSummaryMaxWords"]);
		if(!$eventSummaryMaxWords) $eventSummaryMaxWords = 50;
		
		$eventAbstractMaxWords = (int)preg_replace("/\D/", "", $_POST["editEventAbstractMaxWords"]);
		if(!$eventAbstractMaxWords) $eventAbstractMaxWords = 200;
		
		$eventCoordinator = (!empty($_POST["editEventCoordinator"])) ? strip_tags($_POST["editEventCoordinator"]) : NULL;
		$eventCoordinatorEmail = (!empty($_POST["editEventCoordinatorEmail"])) ? strip_tags($_POST["editEventCoordinatorEmail"]) : NULL;

		$eventShowTimes = strip_tags($_POST["editEventShowTimes"]);
		if($eventShowTimes != "Y" && $eventShowTimes != "N") $eventShowTimes = "N";
		
		$eventShowPrefs = strip_tags($_POST["editEventShowPrefs"]);
		if($eventShowPrefs != "Y" && $eventShowPrefs != "N") $eventShowPrefs = "N";
		
		$eventGetsProposals = strip_tags($_POST["editEventGetsProposals"]);
		if($eventGetsProposals != "Y" && $eventGetsProposals != "N") $eventGetsProposals = "N";
		
		$eventIsActive = strip_tags($_POST["editEventIsActive"]);
		if($eventIsActive != "Y" && $eventIsActive != "N") $eventIsActive = "Y";
		
		$eventPropTable = preg_replace("/[^a-z_]/", "", $_POST["editEventPropTable"]);
		if($eventGetsProposals == "Y") $eventPropTable = 'proposals';
		
		$eventID = (int)strip_tags($_POST["editEventID"]);
		
		// error check the ABSOLUTELY required data
		if(empty($eventName)) $errMsg = 'Please enter an event name!';
		else if(empty($eventAdminSuffix)) $errMsg = 'Please enter an admin suffix!';
		else if(empty($eventID)) $errMsg = 'No event ID was given!';
		else {
			// We can't have duplicate admin suffixes, so check for duplicates
			$asRes = $db->query("SELECT id FROM events WHERE adminSuffix = '$eventAdminSuffix'");
			while($asRow = $asRes->fetch_array()) {
				if($asRow[0] != $eventID) $errMsg = 'That admin suffix is already being used by another event. Please enter a unique admin suffix!';
				break;
			}
		}
		
		if(empty($errMsg)) {
			//Update the user information in the database
			$eStmt = $db->prepare("UPDATE `events` SET `event` = ?, `adminSuffix` = ?, `webDescription` = ?, `emailDescription` = ?, `propLink` = ?, `webTitle` = ?, `emailTitle` = ?, `topics` = ?, `summaryMaxWords` = ?, `abstractMaxWords` = ?, `coordinator` = ?, `coordinatorEmail` = ?, `showTimes` = ?, `showPrefs` = ?, `getsProposals` = ?,`isActive` = ?, `propTable` = ? WHERE `id` = ? LIMIT 1");
			$eStmt->bind_param('ssssssssssssssssss', $eventName, $eventAdminSuffix, $eventWebDescription, $eventEmailDescription, $eventPropLink, $eventWebTitle, $eventEmailTitle, $eventTopics, $eventSummaryMaxWords, $eventAbstractMaxWords, $eventCoordinator, $eventCoordinatorEmail, $eventShowTimes, $eventShowPrefs, $eventGetsProposals, $eventIsActive, $eventPropTable, $eventID);
			if(!$eStmt->execute()) {
				$errMsg = 'There as a MySQL error: '. $eStmt->error;
			}
		}
		
		if(empty($errMsg)) {
			//If we get this far, then show the sucess message
			include "adminTop.php";
?>
					<h3 align="center">The event information was edited successfully!</h3>
					<p align="center"><a href="eventList.php">Back to Event List</a></p>
<?php				
			include "adminBottom.php";	

			exit();
		}
	}
	
	//Get the event information
	$id = isset($_GET["id"]) ? strip_tags($_GET["id"]) : "";
	if($id == "") {
		echo "No event was given!";
		exit();
	}
	
	$eStmt = $db->prepare("SELECT `id`,`event`,`adminSuffix`,`webDescription`,`emailDescription`,`propLink`,`webTitle`,`emailTitle`,`topics`,`summaryMaxWords`,`abstractMaxWords`,`coordinator`,`coordinatorEmail`,`showTimes`,`showPrefs`,`getsProposals`,`isActive`,`propTable` FROM `events` WHERE `id` = ? LIMIT 1");
	$eStmt->bind_param('s',$id);
	$eStmt->execute();
	$eStmt->store_result();
	if($eStmt->num_rows < 1) {
		echo "No event found with that id! (Error: ".$eStmt->error.")";
		exit();
	}
	
	$eStmt->bind_result($eID,$eName,$eAdminSuffix,$eWebDescription,$eEmailDescription,$ePropLink,$eWebTitle,$eEmailTitle,$eTopics,$eSummaryMaxWords,$eAbstractMaxWords,$eCoordinator,$eCoordinatorEmail,$eShowTimes,$eShowPrefs,$eGetsProps,$eIsActive,$ePropTable);
	$eStmt->fetch();
	
	$enableTinyMCE = array(array('eventWebDescription',800,300),array('eventEmailDescription',800,100));
	include "adminTop.php";
?>
					<script type="text/javascript">
						function checkForm() {
							if(document.getElementById('eventName').value == '') {
								alert('You did not enter an event!');
								document.getElementById('eventName').focus();
								return false;
							}
							
							if(document.getElementById('eventCoordinatorEmail').value != '') {
								if(!validateEmail(document.getElementById('eventCoordinatorEmail').value)) {
									alert('You did not enter a valid email address!');
									document.getElementById('eventCoordinatorEmail').focus();
									return false;
								}
							}
				
							//If we get this far, everything is correct, so submit the form
							document.getElementById('editEventName').value = document.getElementById('eventName').value;
							document.getElementById('editEventAdminSuffix').value = document.getElementById('eventAdminSuffix').value;
							document.getElementById('editEventWebDescription').value = tinymce.get('eventWebDescription').getContent();
							document.getElementById('editEventEmailDescription').value = tinymce.get('eventEmailDescription').getContent();
							document.getElementById('editEventPropLink').value = document.getElementById('eventPropLink').value;
							document.getElementById('editEventWebTitle').value = document.getElementById('eventWebTitle').value;
							document.getElementById('editEventEmailTitle').value = document.getElementById('eventEmailTitle').value;
							document.getElementById('editEventTopics').value = document.getElementById('eventTopics').value.replace(/(?:\r\n|\r|\n)/g, '|');
							document.getElementById('editEventSummaryMaxWords').value = document.getElementById('eventSummaryMaxWords').value;
							document.getElementById('editEventAbstractMaxWords').value = document.getElementById('eventAbstractMaxWords').value;
							document.getElementById('editEventCoordinator').value = document.getElementById('eventCoordinator').value;
							document.getElementById('editEventCoordinatorEmail').value = document.getElementById('eventCoordinatorEmail').value;
							document.getElementById('editEventShowTimes').value = document.getElementById('eventShowTimes').options[document.getElementById('eventShowTimes').selectedIndex].value;
							document.getElementById('editEventShowPrefs').value = document.getElementById('eventShowPrefs').options[document.getElementById('eventShowPrefs').selectedIndex].value;
							document.getElementById('editEventGetsProposals').value = document.getElementById('eventGetsProposals').options[document.getElementById('eventGetsProposals').selectedIndex].value;
							document.getElementById('editEventIsActive').value = document.getElementById('eventIsActive').options[document.getElementById('eventIsActive').selectedIndex].value;
							document.getElementById('editEventPropTable').value = document.getElementById('eventPropTable').options[document.getElementById('eventPropTable').selectedIndex].value;
							
							document.getElementById('eventForm').submit();
						}
						
						function deleteEvent() {
							window.location.href = 'deleteEvent.php?id=<?php echo $eID; ?>';
						}

						function validateEmail(email) { 
							var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
							return re.test(email);
						}			

						function showPropTableDiv(el) {
							if(el.options[el.selectedIndex].value == 'N') document.getElementById('eventPropTableDiv').style.display = '';
							else document.getElementById('eventPropTableDiv').style.display = 'none';
						}
					</script>
<?php
	if(!empty($errMsg)) {
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td valign="top" style="color: red; font-weight: bold">ERROR:</td>
							<td style="color: red"><?php echo $errMsg; ?></td>
						</tr>
					</table>
<?php
	}
?>
					<p style="text-align: left"><span style="font-weight: bold">The name of the event used in the admin system:</span><br />
						<input type="text" size="50" id="eventName" name="eventName" size="50" value="<?php echo $eName; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The suffix to use for admin roles in the admin system (e.g. "_fairs" for Technology Fairs):</span><br />
						<input type="text" size="20" id="eventAdminSuffix" name="eventAdminSuffix" value="<?php echo $eAdminSuffix; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The description of the event to be used in the website version of the Call for Proposals:</span><br />
						<textarea id="eventWebDescription" name="eventWebDescription" rows="10" cols="100"><?php echo $eWebDescription; ?></textarea>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The description of the event to be used in the email version of the Call for Proposals:</span><br />
						<textarea id="eventEmailDescription" name="eventEmailDescription" rows="5" cols="100"><?php echo $eEmailDescription; ?></textarea>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The anchor name used in the website that lets a user scroll directly to that event:</span><br />
						<input type="text" size="20" id="eventPropLink" name="eventPropLink" value="<?php echo $ePropLink; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The title of the event used in the website version of the Call for Proposals:</span><br />
						<input type="text" size="50" id="eventWebTitle" name="eventWebTitle" value="<?php echo $eWebTitle; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The title of the event used in the email version of the Call for Proposals:</span><br />
						<input type="text" size="50" id="eventEmailTitle" name="eventEmailTitle" value="<?php echo $eEmailTitle; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold;">The suggested topics for the proposals website (each topic on a new line):</span><br />
						<textarea id="eventTopics" name="eventTopics" rows="15" cols="100"><?php echo str_replace("|", "\n", $eTopics); ?></textarea>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The maximum words allowed in the summary for the program book:</span> <input type="number" id="eventSummaryMaxWords" name="eventSummaryMaxWords" value="<?php echo $eSummaryMaxWords; ?>" style="width: 60px;"/></p>
					<p style="text-align: left"><span style="font-weight: bold">The maximum words allowed in the abstract, which is read by the reviewers:</span> <input type="number" id="eventAbstractMaxWords" name="eventAbstractMaxWords" value="<?php echo $eAbstractMaxWords; ?>" style="width: 60px;" /></p>
					<p style="text-align: left"><span style="font-weight: bold">The coordinator(s) of the event (list each person, separated by a comma):</span><br />
						<input type="text" size="50" id="eventCoordinator" name="eventCoordinator" value="<?php echo $eCoordinator; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The email address to use as the coordinator's email:</span><br />
						<input type="text" size="50" id="eventCoordinatorEmail" name="eventCoordinatorEmail" value="<?php echo $eCoordinatorEmail; ?>" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Show the possible presentation times in the proposals form for this event? </span>
						<select id="eventShowTimes" name="eventShowTimes">
							<option value="N"<?php if($eShowTimes == "N") { ?> selected="selected"<?php } ?>>No</option>
							<option value="Y"<?php if($eShowTimes == "Y") { ?> selected="selected"<?php } ?>>Yes</option>
						</select>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Show the computer preferences in the proposals form for this event? </span>
						<select id="eventShowPrefs" name="eventShowPrefs">
							<option value="N"<?php if($eShowPrefs == "N") { ?> selected="selected"<?php } ?>>No</option>
							<option value="Y"<?php if($eShowPrefs == "Y") { ?> selected="selected"<?php } ?>>Yes</option>
						</select>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Is this event included in the regular Call for Proposals?</span>
						<select id="eventGetsProposals" name="eventGetsProposals" onchange="showPropTableDiv(this)">
							<option value="Y"<?php if($eGetsProps == "Y") { ?> selected="selected"<?php } ?>>Yes</option>
							<option value="N"<?php if($eGetsProps == "N") { ?> selected="selected"<?php } ?>>No</option>
						</select>
						<div id="eventPropTableDiv"<?php if($eGetsProposals == 'Y') { ?> style="display: none;"<?php } ?>>
							<p style="text-align: left; margin-left: 25px;"><span style="font-weight: bold">Which proposals table is used for this event?</span>
								<select id="eventPropTable" name="eventPropTable">
									<option value="other_proposals"<?php if($ePropTable == 'other_proposals') { ?> selected="selected"<?php } ?>>Other</option>
									<option value="classics_proposals"<?php if($ePropTable == 'classics_proposals') { ?> selected="selected"<?php } ?>>Classics</option>
								</select>
							</p>
						</div>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Is this event an active event in this proposals cycle?</span>
						<select id="eventIsActive" name="eventIsActive">
							<option value="Y"<?php if($eIsActive == "Y") { ?> selected="selected"<?php } ?>>Yes</option>
							<option value="N"<?php if($eIsActive == "N") { ?> selected="selected"<?php } ?>>No</option>
						</select>
					</p>
					<p style="text-align: center">
						<input type="button" value="Cancel" onClick="window.location.href='eventList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
						<input type="button" value="Submit" onClick="checkForm()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
						<input type="button" value="Delete Event" onClick="deleteEvent()" />
					</p>
					<form name="eventForm" id="eventForm" method="post" action="">
						<input type="hidden" name="editEventID" id="editEventID" value="<?php echo $eID; ?>" />
						<input type="hidden" name="editEventName" id="editEventName" value="" />
						<input type="hidden" name="editEventAdminSuffix" id="editEventAdminSuffix" value="" />
						<input type="hidden" name="editEventWebDescription" id="editEventWebDescription" value="" />
						<input type="hidden" name="editEventEmailDescription" id="editEventEmailDescription" value="" />
						<input type="hidden" name="editEventPropLink" id="editEventPropLink" value="" />
						<input type="hidden" name="editEventWebTitle" id="editEventWebTitle" value="" />
						<input type="hidden" name="editEventEmailTitle" id="editEventEmailTitle" value="" />
						<input type="hidden" name="editEventTopics" id="editEventTopics" value="" />
						<input type="hidden" name="editEventSummaryMaxWords" id="editEventSummaryMaxWords" value="" />
						<input type="hidden" name="editEventAbstractMaxWords" id="editEventAbstractMaxWords" value="" />
						<input type="hidden" name="editEventCoordinator" id="editEventCoordinator" value="" />
						<input type="hidden" name="editEventCoordinatorEmail" id="editEventCoordinatorEmail" value="" />
						<input type="hidden" name="editEventShowTimes" id="editEventShowTimes" value="" />
						<input type="hidden" name="editEventShowPrefs" id="editEventShowPrefs" value="" />
						<input type="hidden" name="editEventGetsProposals" id="editEventGetsProposals" value="" />
						<input type="hidden" name="editEventIsActive" id="editEventIsActive" value="" />
						<input type="hidden" name="editEventPropTable" id="editEventPropTable" value="" />
					</form>
<?php
	include "adminBottom.php";
?>