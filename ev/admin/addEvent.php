<?php
	//addUser.php - allows a user to add users to the database
	//accessible only to admin users
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false) { //only admins can view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$topTitle = "Add Event";
		
	if(isset($_POST["addEventName"])) {
		$eventName = strip_tags($_POST["addEventName"]);
		$eventAdminSuffix = preg_replace("/[^a-z_]/", "", $_POST["addEventAdminSuffix"]);
		
		$eventWebDescription = (!empty($_POST["addEventWebDescription"])) ? strip_tags($_POST["addEventWebDescription"], '<span><a><br><ul><li><i><b><strong><em>') : NULL;
		$eventEmailDescription = (!empty($_POST["addEventEmailDescription"])) ? strip_tags($_POST["addEventEmailDescription"], '<span><a><br><ul><li><i><b><strong><em>') : NULL;
		$eventPropLink = (!empty($_POST["addEventPropLink"])) ? strip_tags($_POST["addEventPropLink"]) : NULL;
		$eventWebTitle = (!empty($_POST["addEventWebTitle"])) ? strip_tags($_POST["addEventWebTitle"]) : NULL;
		$eventEmailTitle = (!empty($_POST["addEventEmailTitle"])) ? strip_tags($_POST["addEventEmailTitle"]) : NULL;
		$eventTopics = (!empty($_POST["addEventTopics"])) ? strip_tags($_POST["addEventTopics"]) : NULL;
		
		$eventSummaryMaxWords = (int)preg_replace("/\D/", "", $_POST["addEventSummaryMaxWords"]);
		if(!$eventSummaryMaxWords) $eventSummaryMaxWords = 50;
		
		$eventAbstractMaxWords = (int)preg_replace("/\D/", "", $_POST["addEventAbstractMaxWords"]);
		if(!$eventAbstractMaxWords) $eventAbstractMaxWords = 200;
		
		$eventCoordinator = (!empty($_POST["addEventCoordinator"])) ? strip_tags($_POST["addEventCoordinator"]) : NULL;
		$eventCoordinatorEmail = (!empty($_POST["addEventCoordinatorEmail"])) ? strip_tags($_POST["addEventCoordinatorEmail"]) : NULL;
		if(!empty($eventCoordinatorEmail)) {
			if(filter_var($eventCoordinatorEmail, FILTER_VALIDATE_EMAIL)) $errMsg = 'The coordinator email was invalid!';
		}
		
		$eventShowTimes = strip_tags($_POST["addEventShowTimes"]);
		if($eventShowTimes != "Y" && $eventShowTimes != "N") $eventShowTimes = "N";
		
		$eventShowPrefs = strip_tags($_POST["addEventShowPrefs"]);
		if($eventShowPrefs != "Y" && $eventShowPrefs != "N") $eventShowPrefs = "N";

		$eventGetsProposals = strip_tags($_POST["addEventGetsProposals"]);
		if($eventGetsProposals != "Y" && $eventGetsProposals != "N") $eventGetsProposals = "N";

		$eventIsActive = strip_tags($_POST["addEventIsActive"]);
		if($eventIsActive != "Y" && $eventIsActive != "N") $eventIsActive = "N";
		
		$eventPropTable = preg_replace("/[^a-z_]/", "", $_POST["addEventPropTable"]);
		if($eventGetsProposals == "Y") $eventPropTable = 'proposals';
		
		// validate the required data
		if(empty($eventName)) $errMsg = 'Please enter an event name!';
		else if(empty($eventAdminSuffix)) $errMsg = 'Please enter an admin suffix!';
		else {
			// We can't have duplicate admin suffixes, so check for duplicates
			$asRes = $db->query("SELECT COUNT(*) FROM events WHERE adminSuffix = '$eventAdminSuffix'");
			$asRow = $asRes->fetch_array();
			if($asRow[0] > 0) $errMsg = 'That admin suffix is already being used by another event. Please enter a unique admin suffix!';
		}
		
		if(empty($errMsg)) {
			//Update the user information in the database
			$eStmt = $db->prepare("INSERT INTO `events` SET `event` = ?, `adminSuffix` = ?, `webDescription` = ?, `emailDescription` = ?, `propLink` = ?, `webTitle` = ?, `emailTitle` = ?, `topics` = ?, `summaryMaxWords` = ?, `abstractMaxWords` = ?, `coordinator` = ?, `coordinatorEmail` = ?, `showTimes` = ?, `showPrefs` = ?, `getsProposals` = ?,`isActive` = ?, `propTable` = ?");
			$eStmt->bind_param('sssssssssssssssss', $eventName, $eventAdminSuffix, $eventWebDescription, $eventEmailDescription, $eventPropLink, $eventWebTitle, $eventEmailTitle, $eventTopics, $eventSummaryMaxWords, $eventAbstractMaxWords, $eventCoordinator, $eventCoordinatorEmail, $eventShowTimes, $eventShowPrefs, $eventGetsProposals, $eventIsActive, $eventPropTable);
			if(!$eStmt->execute()) $errMsg = 'There was a MySQL error: '.$eStmt->error;
		}
		
		if(empty($errMsg)) {
			//If we get this far, then show the sucess message
			include "adminTop.php";
?>
					<h3 align="center">The event was added successfully!</h3>
					<p align="center"><a href="addEvent.php">Add another event</a></p>
					<p align="center"><a href="eventList.php">Back to Event List</a></p>
<?php				
			include "adminBottom.php";
		
			exit();
		}
	}

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
							document.getElementById('addEventName').value = document.getElementById('eventName').value;
							document.getElementById('addEventAdminSuffix').value = document.getElementById('eventAdminSuffix').value;
							document.getElementById('addEventWebDescription').value = tinymce.get('eventWebDescription').getContent();
							document.getElementById('addEventEmailDescription').value = tinymce.get('eventEmailDescription').getContent();
							document.getElementById('addEventPropLink').value = document.getElementById('eventPropLink').value;
							document.getElementById('addEventWebTitle').value = document.getElementById('eventWebTitle').value;
							document.getElementById('addEventEmailTitle').value = document.getElementById('eventEmailTitle').value;
							document.getElementById('addEventTopics').value = document.getElementById('eventTopics').value.replace(/(?:\r\n|\r|\n)/g, '|');
							document.getElementById('addEventSummaryMaxWords').value = document.getElementById('eventSummaryMaxWords').value;
							document.getElementById('addEventAbstractMaxWords').value = document.getElementById('eventAbstractMaxWords').value;
							document.getElementById('addEventCoordinator').value = document.getElementById('eventCoordinator').value;
							document.getElementById('addEventCoordinatorEmail').value = document.getElementById('eventCoordinatorEmail').value;
							document.getElementById('addEventShowTimes').value = document.getElementById('eventShowTimes').options[document.getElementById('eventShowTimes').selectedIndex].value;
							document.getElementById('addEventShowPrefs').value = document.getElementById('eventShowPrefs').options[document.getElementById('eventShowPrefs').selectedIndex].value;
							document.getElementById('addEventGetsProposals').value = document.getElementById('eventGetsProposals').options[document.getElementById('eventGetsProposals').selectedIndex].value;
							document.getElementById('addEventIsActive').value = document.getElementById('eventIsActive').options[document.getElementById('eventIsActive').selectedIndex].value;
							document.getElementById('addEventPropTable').value = document.getElementById('eventPropTable').options[document.getElementById('eventPropTable').selectedIndex].value;
							
							document.getElementById('eventForm').submit();
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
						<input type="text" size="50" id="eventName" name="eventName" size="50" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The suffix to use for admin roles in the admin system (e.g. "_fairs" for Technology Fairs):</span><br />
						<input type="text" size="20" id="eventAdminSuffix" name="eventAdminSuffix" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The description of the event to be used in the website version of the Call for Proposals:</span><br />
						<textarea id="eventWebDescription" name="eventWebDescription" rows="10" cols="100"></textarea>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The description of the event to be used in the email version of the Call for Proposals:</span><br />
						<textarea id="eventEmailDescription" name="eventEmailDescription" rows="5" cols="100"></textarea>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The anchor name used in the website that lets a user scroll directly to that event:</span><br />
						<input type="text" size="20" id="eventPropLink" name="eventPropLink" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The title of the event used in the website version of the Call for Proposals:</span><br />
						<input type="text" size="50" id="eventWebTitle" name="eventWebTitle" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The title of the event used in the email version of the Call for Proposals:</span><br />
						<input type="text" size="50" id="eventEmailTitle" name="eventEmailTitle" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold;">The suggested topics for the proposals website (each topic on a new line):</span><br />
						<textarea id="eventTopics" name="eventTopics" rows="15" cols="100"></textarea>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The maximum words allowed in the summary for the program book:</span> <input type="number" id="eventSummaryMaxWords" name="eventSummaryMaxWords" value="" style="width: 60px;"/></p>
					<p style="text-align: left"><span style="font-weight: bold">The maximum words allowed in the abstract, which is read by the reviewers:</span> <input type="number" id="eventAbstractMaxWords" name="eventAbstractMaxWords" value="" style="width: 60px;" /></p>
					<p style="text-align: left"><span style="font-weight: bold">The coordinator(s) of the event (list each person, separated by a comma):</span><br />
						<input type="text" size="50" id="eventCoordinator" name="eventCoordinator" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">The email address to use as the coordinator's email:</span><br />
						<input type="text" size="50" id="eventCoordinatorEmail" name="eventCoordinatorEmail" value="" />
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Show the possible presentation times in the proposals form for this event? </span>
						<select id="eventShowTimes" name="eventShowTimes">
							<option value="N">No</option>
							<option value="Y">Yes</option>
						</select>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Show the computer preferences in the proposals form for this event? </span>
						<select id="eventShowPrefs" name="eventShowPrefs">
							<option value="N">No</option>
							<option value="Y">Yes</option>
						</select>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Is this event included in the regular Call for Proposals?</span>
						<select id="eventGetsProposals" name="eventGetsProposals" onchange="showPropTableDiv(this)">
							<option value="Y">Yes</option>
							<option value="N">No</option>
						</select>
						<div id="eventPropTableDiv" style="display: none;">
							<p style="text-align: left; margin-left: 25px;"><span style="font-weight: bold">Which proposals table is used for this event?</span>
								<select id="eventPropTable" name="eventPropTable">
									<option value="other_proposals">Other</option>
									<option value="classics_proposals">Classics</option>
								</select>
							</p>
						</div>
					</p>
					<p style="text-align: left"><span style="font-weight: bold">Is this event an active event in this proposals cycle?</span>
						<select id="eventIsActive" name="eventIsActive">
							<option value="Y">Yes</option>
							<option value="N">No</option>
						</select>
					</p>
					<p style="text-align: center">
						<input type="button" value="Cancel" onClick="window.location.href='eventList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
						<input type="button" value="Submit" onClick="checkForm()" />
					</p>
					<form name="eventForm" id="eventForm" method="post" action="">
						<input type="hidden" name="addEventName" id="addEventName" value="" />
						<input type="hidden" name="addEventAdminSuffix" id="addEventAdminSuffix" value="" />
						<input type="hidden" name="addEventWebDescription" id="addEventWebDescription" value="" />
						<input type="hidden" name="addEventEmailDescription" id="addEventEmailDescription" value="" />
						<input type="hidden" name="addEventPropLink" id="addEventPropLink" value="" />
						<input type="hidden" name="addEventWebTitle" id="addEventWebTitle" value="" />
						<input type="hidden" name="addEventEmailTitle" id="addEventEmailTitle" value="" />
						<input type="hidden" name="addEventTopics" id="addEventTopics" value="" />
						<input type="hidden" name="addEventSummaryMaxWords" id="addEventSummaryMaxWords" value="" />
						<input type="hidden" name="addEventAbstractMaxWords" id="addEventAbstractMaxWords" value="" />
						<input type="hidden" name="addEventCoordinator" id="addEventCoordinator" value="" />
						<input type="hidden" name="addEventCoordinatorEmail" id="addEventCoordinatorEmail" value="" />
						<input type="hidden" name="addEventShowTimes" id="addEventShowTimes" value="" />
						<input type="hidden" name="addEventShowPrefs" id="addEventShowPrefs" value="" />
						<input type="hidden" name="addEventGetsProposals" id="addEventGetsProposals" value="" />
						<input type="hidden" name="addEventPropTable" id="addEventPropTable" value="" />
						<input type="hidden" name="addEventIsActive" id="addEventIsActive" value="" />
					</form>
<?php
	include "adminBottom.php";
?>