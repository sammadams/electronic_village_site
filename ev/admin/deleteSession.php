<?php
	//deleteSession.php - allows a user to delete a session from the schedule
	//accessible only to chairs, and admin users
	
	include_once "login.php";
	$topTitle = "Delete Session";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false) {
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(isset($_GET["s"])) $sessionID = strip_tags($_GET["s"]);
	else {
		echo "No session ID was given!";
		exit();
	}
	
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE `id` = ? LIMIT 1");
	$sStmt->bind_param('s',$sessionID);
	$sStmt->execute();
	$sStmt->store_result();
	if($sStmt->num_rows < 1) {
		echo "No session found with that ID! (Error: ".$sStmt->error.")";
		exit();
	}
	
	$sStmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEvent,$sTitle,$sPresentations);
	$sStmt->fetch();
	
	$tmpDate = explode("-",$sDate);
	$sMonth = intval($tmpDate[1]);
	$sDay = intval($tmpDate[2]);
	$sYear = intval($tmpDate[0]);
	
	$tmpTime = explode("-",$sTime);
	$tmpStart = explode(":",$tmpTime[0]);
	$sHour = intval($tmpStart[0]);
	if($sHour < 12) $sAMPM = "AM";
	else {
		$sHour = $sHour - 12;
		$sAMPM = "PM";
	}
	$sMinute = intval($tmpStart[1]);

	$tmpEnd = explode(":",$tmpTime[1]);
	$eHour = intval($tmpEnd[0]);
	if($eHour < 12) $eAMPM = "AM";
	else {
		$eHour = $eHour - 12;
		$eAMPM = "PM";
	}
	$eMinute = intval($tmpEnd[1]);
	
	//echo "<pre>";
	//print_r($_POST);
	//echo "</pre>";
	
	if(isset($_POST["delOK"]) && $_POST["delOK"] == "Y") {
		//the user clicked "YES", so delete the session
		
		$q_stmt = $db->prepare("DELETE FROM `sessions` WHERE `id` = ? LIMIT 1");
		$q_stmt->bind_param('s',$sessionID);
		if($q_stmt->execute()) { 
			include "adminTop.php";
?>
	<h3 align="center">Successfully deleted!</h3>
	<p align="center"><a href="sessionList.php">Back to Session List</a></p>
<?php
			include "adminBottom.php";
			exit();
		} else { //show an error page
			echo $q_stmt->error;
			exit();
		}
	}
	
	//Confirm they want to delete the session
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
				
				window.location.href = 'editSession.php?s=<?php echo $sessionID; ?>';
			}
			
			function delSession() {
				document.getElementById('delOK').value = 'Y';
				document.getElementById('delForm').submit();
			}
		</script>
		<table align="center" border="0" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 14pt">You are deleting the following session:<br />
					<table border="0" align="center" cellpadding="10" cellspacing="0">
						<tr>
							<td style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Event:</td>
							<td style="padding-bottom: 10px; font-size: 12pt"><?php echo $sEvent; ?></td>
						</tr>
						<tr>
							<td style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Title:</td>
							<td style="padding-bottom: 10px; font-size: 12pt"><?php echo $sTitle; ?></td>
						</tr>
						<tr>
							<td valign="top" style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Location:</td>
							<td valign="top" style="padding-bottom: 10px; font-size: 12pt"><?php if($sLocation == "ev") echo "Electronic Village"; else if($sLocation == "ts") echo "Technology Showcase"; ?></td>
						</tr>
						<tr>
							<td valign="top" style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Date:</td>
							<td valign="top" style="padding-bottom: 10px; font-size: 12pt">
<?php
	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
?>
								<?php echo $months[$sMonth]; ?> <?php echo $sDay; ?>, <?php echo $sYear; ?>
							</td>
						</tr>
						<tr>
							<td valign="top" style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Time:</td>
							<td valign="top" style="padding-bottom: 10px; font-size: 12pt">
								<?php echo $sHour; ?>:<?php if($sMinute < 10) echo "0".$sMinute; else echo $sMinute; ?> <?php echo $sAMPM; ?> to 
								<?php echo $eHour; ?>:<?php if($eMinute < 10) echo "0".$eMinute; else echo $eMinute; ?> <?php echo $eAMPM; ?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" style="font-weight: bold; font-size: 20pt">
					Are you sure you want to delete this session?<br />&nbsp; 
				</td>
			</tr>
			<tr>
				<td>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td width="50%" align="center">
								<input type="button" value="Yes" style="background-color: green" onClick="delSession()" />
							</td>
							<td width="50%" align="center">
								<input type="button" value="No" style="background-color: red" onClick="cancelDelete()" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<form name="delForm" id="delForm" method="post" action="">
			<input type="hidden" name="del_id" id="del_id" value="<?php echo $sessionID; ?>" />
			<input type="hidden" name="delOK" id="delOK" value="N" />
		</form>
<?php
	include "adminBottom.php";
?>