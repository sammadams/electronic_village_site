<?php
	//withdrawProp.php -- removes a proposal from consideration by changing the status
	
	include_once "../../ev_config.php";
	include_once "../../ev_library.php";
	
	sec_session_start();
	
	if(login_check($db) === true) {
		if(isset($_POST["prop_id"])) $propID = strip_tags($_POST["prop_id"]);
		else {
			echo "No proposal ID was given!";
			exit();
		}
		
		$y = date("Y") + 1;
		
		//Get the proposal information from the database
		$q_stmt = $db->prepare("SELECT `id`, `title`, `type`, `status` FROM `proposals` WHERE `id` = ?");
		$q_stmt->bind_param('s',$propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($tmpID, $tmpTitle, $tmpType, $tmpStatus);
		$q_stmt->fetch();
			
		$propData = array(
			"id" => $tmpID,
			"title" => $tmpTitle,
			"type" => $tmpType,
			"status" => $tmpStatus
		);
		
		if($propData["type"] == "Technology Fairs") $from = "ev-fair@call-is.org";
		else if($propData["type"] == "Mini-Workshops") $from = "ev-mini@call-is.org";
		else if($propData["type"] == "Developers Showcase") $from = "ev-ds@call-is.org";
		else if($propData["type"] == "Mobile Apps for Education Showcase") $from = "ev-mae@call-is.org";
		else if($propData["type"] == "Classroom of the Future") $from = "ev-classroom@call-is.org";
		
		if(isset($_POST["delOK"]) && $_POST["delOK"] == "Y") {
			//the user clicked "YES", so withdraw the proposal
			
			$q_stmt = $db->prepare("UPDATE `proposals` SET `status` = 'withdrawn' WHERE `id` = ?");
			$q_stmt->bind_param('s',$propID);
			if($q_stmt->execute()) { //show the confirmation page
				logout(); //log the user out of the system
?>
<html>
	<head>
		<title>Electronic Village Proposals</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
		</style>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
	</head>
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Withdraw Submission: Confirmation</span></td>
			</tr>
			<tr>
				<td style="color: red; font-weight: bold" align="center">Proposal has been withdrawn<br />&nbsp;</td>
			</tr>
			<tr>
				<td>This proposal has been withdrawn. If you want to reinstate this proposal so that it can be edited and considered for acceptance to the Electronic Village, please email the event lead at <a href="mailto:<?=$from?>"><?=$from?></a>. Please include the title of your proposal and the proposal ID number: <?=$propID?>.
			</tr>
		</table>
	</body>
</html>
<?php
				exit();			
			} else { //show an error page
				echo $q_stmt->error;
				exit();
			}
		}
		
		//Confirm they want to withdraw their proposal		
?>
<html>
	<head>
		<title>Electronic Village Proposals -- <?=$propData["type"]?></title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
			
			input[type='button'] {
				font-weight: bold;
				font-size: 20pt;
				border: solid 1px #000000;
				height: 50px;
				width: 200px;
			}
		</style>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<script type="text/javascript">
			function cancelWithdraw() {
				//If they cancel, we will direct them back to the edit.php page,
				//which should be the page they just came from
				
				window.location.href = 'edit.php?id=<?=$propID?>';
			}
			
			function withdrawProp() {
				document.getElementById('delOK').value = 'Y';
				document.getElementById('withdrawForm').submit();
			}
		</script>
	</head>
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Withdraw Submission</span></td>
			</tr>
			<tr>
				<td style="font-size: 14pt">
					Withdrawing a proposal removes it from consideration for acceptance to the Electronic Village program. This means that the proposal will not be reviewed and not be included in the vetting process. It is possible for a withdrawn proposal to be reinstated by contacting the event lead at <a href="mailto:<?=$from?>"><?=$from?></a><br />&nbsp;.
				</td>
			</tr>
			<tr>
				<td style="font-size: 14pt">You are withdrawing the following proposal:<br /><span style="font-size: 12pt; margin: 20px"><b>Event:</b> <?=$propData["type"]?></span><br /><span style="font-size: 12pt; margin: 20px"><b>Title:</b> <?=$propData["title"]?></span><br />&nbsp;
				</td>
			</tr>
			<tr>
				<td align="center" style="font-weight: bold; font-size: 20pt">
					Are you sure you want to withdraw your proposal?<br />&nbsp; 
				</td>
			</tr>
			<tr>
				<td>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td width="50%" align="center">
								<input type="button" value="Yes" style="background-color: green" onClick="withdrawProp()" />
							</td>
							<td width="50%" align="center">
								<input type="button" value="No" style="background-color: red" onClick="cancelWithdraw()" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<form name="withdrawForm" id="withdrawForm" method="post" action="withdrawProp.php">
			<input type="hidden" name="prop_id" id="prop_id" value="<?=$propID?>" />
			<input type="hidden" name="delOK" id="delOK" value="N" />
		</form>
	</body>
</html>
<?php
		
	} else {
		echo "Not logged in!";
	}
?>