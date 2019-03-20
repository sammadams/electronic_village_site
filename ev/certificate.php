<?php
	include "../../ev_config.php";
	include "../../ev_library.php";
	
	$y = $confYear;
	
	if(isset($_GET["id"])) $propID = strip_tags($_GET["id"]);
	else if(isset($_POST["propID"])) $propID = $_POST["propID"];
	
	if($_POST) {
		// Each $_POST element will be 'rc_' and the ID of the presenter. We need to go through all the $_POST
		// elements and separate them out into a group of "1" and "0" and then update the database accordingly.
		
		$isPaper = array();
		$noPaper = array();
		
		foreach($_POST AS $k => $v) {
			$thisID = substr($k,3,strlen($k));
			if($v == "1") $isPaper[] = $thisID;
			else if($v == "0") $noPaper[] = $thisID;
		}
		
		if(count($isPaper) > 1) {
			if($_POST["propType"] == "Technology Fairs (Classics)") $query = "UPDATE `classics_presenters` SET `Certificate` = ? WHERE `ID` IN (";
			else if($_POST["propType"] == "Other") $query = "UPDATE `other_presenters` SET `Certificate` = ? WHERE `ID` IN (";
			else $query = "UPDATE `presenters` SET `Certificate` = ? WHERE `ID` IN (";
			$type = 's';
			for($i = 0; $i < count($isPaper); $i++) {
				$query .= "?";
				if($i < (count($isPaper) - 1)) $query .= ",";
				$type .= 's';
			}
			$query .= ")";

			$params = array($type,'1');
			for($i = 0; $i < count($isPaper); $i++) {
				$params[] = $isPaper[$i];
			}
		
			$aStmt = $db->prepare($query);
			call_user_func_array(array($aStmt, 'bind_param'), $params);
			if(!$aStmt->execute()) {
				echo $aStmt->error;
				exit();
			}
	
			$aStmt->close();
		} else if(count($isPaper) == 1) { // only 1 to be updated so don't use call_user_func_array
			$aData = "1";
			if($_POST["propType"] == "Technology Fairs (Classics)") $aStmt = $db->prepare("UPDATE `classics_presenters` SET `Certificate` = ? WHERE `ID` = ? LIMIT 1");
			else if($_POST["propType"] == "Other") $aStmt = $db->prepare("UPDATE `other_presenters` SET `Certificate` = ? WHERE `ID` = ? LIMIT 1");
			else $aStmt = $db->prepare("UPDATE `presenters` SET `Certificate` = ? WHERE `ID` = ? LIMIT 1");
			$aStmt->bind_param('ss', $aData, $isPaper[0]);
			if(!$aStmt->execute()) {
				echo $aStmt->error;
				exit();
			}
	
			$aStmt->close();
		}


		if(count($noPaper) > 1) {
			if($_POST["propType"] == "Technology Fairs (Classics)") $query = "UPDATE `classics_presenters` SET `Certificate` = ? WHERE `ID` IN (";
			else if($_POST["propType"] == "Other") $query = "UPDATE `other_presenters` SET `Certificate` = ? WHERE `ID` IN (";
			else $query = "UPDATE `presenters` SET `Certificate` = ? WHERE `ID` IN (";
			$type = 's';
			for($i = 0; $i < count($noPaper); $i++) {
				$query .= "?";
				if($i < (count($noPaper) - 1)) $query .= ",";
				$type .= 's';
			}
			$query .= ")";

			$params = array($type,'0');
			for($i = 0; $i < count($noPaper); $i++) {
				$params[] = $noPaper[$i];
			}
		
			$bStmt = $db->prepare($query);
			call_user_func_array(array($bStmt, 'bind_param'), $params);
			if(!$bStmt->execute()) {
				echo $bStmt->error;
				exit();
			}
		
			$bStmt->close();
		} else if(count($noPaper) == 1) {
			$bData = "0";
			if($_POST["propType"] == "Technology Fairs (Classics)") $bStmt = $db->prepare("UPDATE `classics_presenters` SET `Certificate` = ? WHERE `ID` = ? LIMIT 1");
			else if($_POST["propType"] == "Other") $bStmt = $db->prepare("UPDATE `other_presenters` SET `Certificate` = ? WHERE `ID` = ? LIMIT 1");
			else $bStmt = $db->prepare("UPDATE `presenters` SET `Certificate` = ? WHERE `ID` = ? LIMIT 1");
			$bStmt->bind_param('ss', $bData, $noPaper[0]);
			if(!$bStmt->execute()) {
				echo $bStmt->error;
				exit();
			}
		
			$bStmt->close();		
		}
		
		// We end the IF statement here because we need to request the presenter information for the results
		// page and want to get updated information from the database.
	}

	// First, get the title and event type of this proposal and display it for the user to verify
	if(isset($_GET["classics"]) && $_GET["classics"] == "1") {
		$q_stmt = $db->prepare("SELECT `title`, `presenters` FROM `classics_proposals` WHERE `id` = ?");
		$q_stmt->bind_param("s", $propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle,$propPresenters);
		$q_stmt->fetch();
		$q_stmt->close();
		$propType = "Technology Fairs (Classics)";
	} else if(isset($_GET["other"]) && $_GET["other"] == "1") {
		$q_stmt = $db->prepare("SELECT `title`,	`presenters` FROM `other_proposals` WHERE `id` = ?");
		$q_stmt->bind_param("s", $propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle,$propPresenters);
		$q_stmt->fetch();
		$q_stmt->close();
		$propType = "Other";
	} else {
		$q_stmt = $db->prepare("SELECT `title`,`type`, `presenters` FROM `proposals` WHERE `id` = ?");
		$q_stmt->bind_param("s", $propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle,$propType,$propPresenters);
		$q_stmt->fetch();
		$q_stmt->close();
	}
	
	// Now, get the information for the presenters
	$tmpP = explode("|",$propPresenters);
	$type = '';
	if($propType == "Technology Fairs (Classics)") $query = "SELECT `ID`,`First Name`,`Last Name`,`Email`,`Certificate` FROM `classics_presenters` WHERE `id` IN (";
	else if($propType == "Other") $query = "SELECT `ID`,`First Name`,`Last Name`,`Email`,`Certificate` FROM `other_presenters` WHERE `id` IN (";
	else $query = "SELECT `ID`,`First Name`,`Last Name`,`Email`,`Certificate` FROM `presenters` WHERE `id` IN (";
	
	for($i = 0; $i < count($tmpP); $i++) {
	$query .= "?";
	if($i < (count($tmpP) - 1)) $query .= ",";
		$type .= 's';
	}
	$query .= ")";

	$params = array($type);
	for($i = 0; $i < count($tmpP); $i++) {
		$params[] = $tmpP[$i];
	}
		
	$pStmt = $db->prepare($query);
	call_user_func_array(array($pStmt, 'bind_param'), $params);
	if(!$pStmt->execute()) {
		echo $pStmt->error;
		exit();
	}
	
	$pStmt->bind_result($pID, $pFirstName, $pLastName, $pEmail, $pCertificate);
	$presenters = array();
	while($pStmt->fetch()) {
		$presenters[] = array(
			"ID" => $pID,
			"First Name" => $pFirstName,
			"Last Name" => $pLastName,
			"Email" => $pEmail,
			"Certificate" => $pCertificate
		);
	}
	
	if($_POST) {
		// If we get here, then the database was updated successfully.
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Request Paper Certificates</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}	
		</style>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $y; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Request Paper Certificates</span></td>
			</tr>
			<tr>
				<td>
					<p>Thank you! We have received your requests. As a reminder, paper certificates <b>MUST</b> be picked at the convention. <b>No paper certificates will be mailed!</b></p>
					<p>Below is a summary of your request:</p>
					<p>Presenters:</p>
<?php
		for($p = 0; $p < count($presenters); $p++) {
			if($presenters[$p]["Certificate"] == "1") {
				$pBG = "#CCFFCC";
				$cStr = "Paper certificate requested";
			} else if($presenters[$p]["Certificate"] == "0") {
				$pBG = "#FFCCCC";
				$cStr = "Paper certificate NOT requested";
			}
?>
					<p style="margin-left: 25px; background-color: <?php echo $pBG; ?>; padding: 5px;"><?php echo $presenters[$p]["First Name"]." ".$presenters[$p]["Last Name"]." (".$presenters[$p]["Email"].") - <b>".$cStr."</b>"; ?></p>
<?php
		}
?>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
		exit();
	} //END POST PROCESSING
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Request Paper Certificates</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}

			input[type=submit], input[type=button] {
				font-size: 16px;
				height: auto;
				width: auto;
				padding-left: 25px;
				padding-right: 25px;
				border: solid 1px #000000;
				background-color: #CCCCCC;
				border-radius: 5px;
				color: #000000;
				font-weight: bold;
			}
			
			input[type=submit]:hover, input[type=button]:hover {
				background-color: #888888;
				color: #FFFFFF;
			}					
		</style>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $y; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Request Paper Certificates</span></td>
			</tr>
			<tr>
				<td>
					<p>Please check the box next to any presenter requesting a paper certificate for the following presentation:</p>
					<p style="margin-left: 25px"><b>Title:</b> <?php echo $propTitle; ?></p>
					<p style="margin-left: 25px"><b>Event:</b> <?php echo $propType; ?></p>
					<p>&nbsp;</p>
					<p style="margin-left: 25px;">
<?php
	for($p = 0; $p < count($presenters); $p++) {
?>
						<input type="checkbox" name="pres_<?php echo $presenters[$p]["ID"]; ?>" id="pres_<?php echo $presenters[$p]["ID"]; ?>"<?php if($presenters[$p]["Certificate"] == "1") { ?> checked="true"<?php } ?>> <span style="cursor: default" onClick="checkEl('pres_<?php echo $presenters[$p]["ID"]; ?>')"><?php echo $presenters[$p]["First Name"]." ".$presenters[$p]["Last Name"]." (".$presenters[$p]["Email"].")"; ?></span><br /><br />
<?php
	}
?>
					</p>
					<p style="font-weight: bold">Paper certificates will be available for pick-up at the Electronic Village during the convention ONLY! No paper certificates will be mailed. It is the responsibility of the presenter to pick-up their paper certificate.</p>
					<p style="text-align: center"><br /><input type="button" value="Submit" onclick="doSubmit()" /></p>
				</td>
			</tr>
		</table>
		<form name="rcForm" id="rcForm" method="post" action="">
			<input type="hidden" name="propType" value="<?php echo $propType; ?>" />
<?php
	for($p = 0; $p < count($presenters); $p++) {
?>
			<input type="hidden" name="rc_<?php echo $presenters[$p]["ID"]; ?>" id="rc_<?php echo $presenters[$p]["ID"]; ?>" value="<?php echo $presenters[$p]["Certificate"]; ?>" />
<?php
	}
?>
		</form>
		<script type="text/javascript">
			function doSubmit() {
				var chkboxes = document.getElementsByTagName('INPUT');
				for(var i = 0; i < chkboxes.length; i++) {
					if(chkboxes[i].type == 'checkbox') {
						var thisID = chkboxes[i].id.substring(5,chkboxes[i].id.length);
						if(chkboxes[i].checked) document.getElementById('rc_' + thisID).value = '1';
						else document.getElementById('rc_' + thisID).value = '0';
					}
				}
				
				document.getElementById('rcForm').submit();
			}
			
			function checkEl(elID) {
				var el = document.getElementById(elID);
				
				if(el.checked) el.checked = false;
				else el.checked = true;
			}
		</script>
	</body>
</html>