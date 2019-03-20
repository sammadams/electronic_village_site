<?php
	//deleteUser.php - allows a user to delete a user from the database
	//accessible only to admin users
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"reviewer_") !== false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$topTitle = "Delete Event";

	if(isset($_POST["eventID"]) && isset($_POST["delOK"]) && $_POST["delOK"] == "Y") {
		$eventID = preg_replace("/\D/", "", $_POST["eventID"]);
		if($eventID > 0) {
			//Update the user information in the database
			$eStmt = $db->prepare("DELETE FROM `events` WHERE `id` = ? LIMIT 1");
			$eStmt->bind_param('s', $eventID);
		
			if(!$eStmt->execute()) $errMsg = 'There was a MySQL error: '.$eStmt->error;
		} else $errMsg = 'There is no event with the given ID!';
			
		if(empty($errMsg)) {
			//If we get this far, then show the sucess message
			include "adminTop.php";
?>
					<h3 align="center">The event was deleted successfully!</h3>
					<p align="center"><a href="eventList.php">Back to Event List</a></p>
<?php
			include "adminBottom.php";
		
			exit();
		}
	}
	
	//Get the user information
	$eventID = (!empty($_GET["id"])) ? preg_replace("/\D/", "", $_GET["id"]) : 0;
	if($eventID <= 0) $errMsg = 'No event ID was given!';
	else {
		$eStmt = $db->prepare("SELECT `event` FROM `events` WHERE `id` = ? LIMIT 1");
		$eStmt->bind_param('s', $eventID);
		$eStmt->execute();
		$eStmt->store_result();
		if($eStmt->num_rows < 1) {
			$errMsg = 'No event found with that ID!';
			if(!empty($eStmt->error)) $errMsg .= ' (Error: '.$eStmt->error.')';
		}
	
		$eStmt->bind_result($eName);
		$eStmt->fetch();
	}
	
	include "adminTop.php";
?>
					<style type="text/css">
						input[type='button'] {
							font-weight: bold;
							font-size: 20pt;
							border: solid 1px #000000;
							height: 50px;
							width: 200px;
						}
					</style>
					<script type="text/javascript">
						function cancelDelete() {
							//If they cancel, we will direct them back to the edit.php page,
							//which should be the page they just came from
				
							window.location.href = 'editEvent.php?id=<?php echo $eventID; ?>';
						}
			
						function deleteEvent() {
							document.getElementById('delOK').value = 'Y';
							document.getElementById('deleteForm').submit();
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
					
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>Deleting this event will remove the event from the database. This means that any schedule sessions or proposals associated with this event will no longer show in the admin area (the will NOT be deleted from the database).</td>
						</tr>
						<tr>
							<td>
								<table border="0" align="center" cellpadding="5" cellspacing="0">
									<tr>
										<td style="font-weight: bold">Event Name:</td>
										<td><?php echo $eName; ?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center" style="font-weight: bold; font-size: 20pt; padding-top: 20px">
								Are you sure you want to delete this event?<br />&nbsp; 
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" cellspacing="0" cellpadding="0" width="100%">
									<tr>
										<td width="50%" align="center">
											<input type="button" value="Yes" style="background-color: green" onClick="deleteEvent()" />
										</td>
										<td width="50%" align="center">
											<input type="button" value="No" style="background-color: red" onClick="cancelDelete()" />
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<form name="deleteForm" id="deleteForm" method="post" action="deleteEvent.php">
						<input type="hidden" name="eventID" id="eventID" value="<?php echo $eventID; ?>" />
						<input type="hidden" name="delOK" id="delOK" value="N" />
					</form>
<?php
	include "adminBottom.php";
?>