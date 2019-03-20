<?php
	include "../../ev_config.php";
	include "../../ev_library.php";
	
	$y = $confYear;
	
	if(isset($_GET["id"])) $propID = strip_tags($_GET["id"]);
	else if(isset($_POST["propID"])) $propID = $_POST["propID"];
	
	if(isset($_POST["propConfirmed"]) && $_POST["propConfirmed"] != "") {
		$propConfirmed = preg_replace("/[^Y|N]/","",$_POST["propConfirmed"]);
		$propType = strip_tags($_POST["propType"]);
		if($propType == "Technology Fairs (Classics)") {
			$u_stmt = $db->prepare("UPDATE `classics_proposals` SET `confirmed` = ? WHERE `id` = ? LIMIT 1");
		} else if($propType == "Other") {
			$u_stmt = $db->prepare("UPDATE `other_propsals` SET `confirmed` = ? WHERE `id` = ? LIMIT 1");
		} else {
			$u_stmt = $db->prepare("UPDATE `proposals` SET `confirmed` = ? WHERE `id` = ? LIMIT 1");
		}
		
		$u_stmt->bind_param('ss', $propConfirmed, $propID);
		if(!$u_stmt->execute()) {
			echo $u_stmt->error;
			exit();
		}
		
		// If we get here, then the database was updated successfully, so show the appropriate message for the users'
		// response. If they are participating, we want to thank them and pass along any further instructions based on the
		// event they are participating in. If they are NOT participating, we want to express our regret and thank them
		// for their time.
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Confirm Participation</title>
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
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $y; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Confirm Participation</span></td>
			</tr>
			<tr>
				<td>
<?php
	if($propConfirmed == "Y") {
?>
					<p style="font-weight: bold">Thank you! Your participation has been confirmed!</p>
<?php
		if($propType == "Developers Showcase") {
?>
					<p>Below are a few reminders about the Developers' Showcase event. Please read the following information carefully.</p>
					
					<p>
						<b>Audiovisual Equipment</b><br />
						The Electronic Village will provide an LCD projector and screen for your brief presentation/demonstration, equipped with microphones and speakers as you may need. Please come to the Electronic Village as soon as you arrive at the conference in order to see and understand how the venue will be setup.
					</p>
					
					<p>
						<b>What Happens Next?</b><br />
						Our event will take place in the Technology Showcase, located next to the Electronic Village. Presenters have approximately 8 minutes to demonstrate and/or operate their software and explain its features and applications. A question-and-answer session follows each presentation.
					</p>
					
					<p>
						<b>Registration and Housing</b><br />
						Go to the TESOL Convention page for more information about registration and housing.
					</p>					
<?php
		} else if($propType == "Technology Fairs") {
?>
					<p>Below are a few reminders about the Technology Fairs. PLease read the following information carefully.
						<ul>
							<li style="margin-bottom: 20px;">Please check the date and time of your presentation, then contact <a href="mailto:ev-fair@call-is.org">ev-fair@call-is.org</a> if there are any problems. It is very important that you check all the details of your presentation.</li>
							<li style="margin-bottom: 20px;">Have in mind that titles may be edited to fewer than 50 characters. If you want to redefine the title of you presentation or correct any detail, also send the changes to <a href="mailto:ev-fair@call-is.org">ev-fair@call-is.org</a>.</li>
							<li style="margin-bottom: 20px;">Please communicate to us any updates to the equipment needs listed in your initial proposal and verify any co-presenters' names and email addresses.  Since you are the contact or primary person listed, it is your responsibility to inform co-presenters of the status of this presentation.</li>
							<li style="margin-bottom: 20px;">Please show up 10 minutes in advance of your presentation to set up.  Remember that you will give your presentation twice during your assigned Technology Fair; you will have 20 - 25 minutes to complete it each time.  Prompt completion of your presentation will be appreciated by both the next presenter and the EV staff.</li>
							<li style="margin-bottom: 20px;">If you are scheduled at "PC" or a "Mac" station, you have one computer with two monitors for your use during your presentation. You may connect your own device to the second monitor if you wish.</li>
							<li style="margin-bottom: 20px;">If you are scheduled at a "BYOD" station, you will need to bring your own computer or device and will set up at the circular tables.</li>
							<li style="margin-bottom: 20px;">Please remember there will be presenters on either side of your station, so we would ask that you control your volume as a courtesy to the other presenters.</li>
							<li style="margin-bottom: 20px;">We recommend that you bring at least 30 handouts for your audience with information from your presentation or have a link that participants can copy to find further information online.</li>
						</ul>
 					</p>
 					<p>Thank you in advance for your cooperation and for your willingness to share your knowledge.  Please let us know if you have any questions or concerns.  The Electronic Village team looks forward to seeing you at the convention!</p>
<?php
		} else if($propType == "Graduate Student Research") {
?>
					<p>Please be aware that your presentation will be webcast live during the convention. After the convention, the recording will be globally and freely available. The CALL-IS Webcast Development Team Coordinator will be in contact with you with more information about webcasting as we get closer to the convention dates.</p>
					<p>Thank you for your willingness to present and share in this exciting professional venue. We look forward to hearing from you soon, and to seeing you at the convention!</p>
<?php
		} else if($propType == "Hot Topics") {
?>
					<p>Please be aware that your presentation will be webcast live during the convention. After the convention, the recording will be globally and freely available. The CALL-IS Webcast Development Team Coordinator will be in contact with you with more information about webcasting as we get closer to the convention dates.</p>
					<p>Thank you for your willingness to present and share in this exciting professional venue. We look forward to hearing from you soon, and to seeing you at the convention.</p>
<?php
		} else if($propType == "Mobile Apps for Education Showcase") {
?>
					<p>Below are a few reminders about the Mobile Apps for Education Showcase event. PLease read the following information carefully.
						<ul>
							<li style="margin-bottom: 20px;">The MAE Showcase will for about 2 hours and include several presentations. Presenters are expected to remain for the entire event.</li>
							<li style="margin-bottom: 20px;">Please understand that this is an invitation to present only and involves no financial commitment from TESOL or the CALL Interest Section. You will be responsible for payment of all convention-related expenses, including registration fees, travel, and housing.</li>
						</ul>
					</p>
<?php
		} else if($propType == "Mini-Workshops") {
?>
					<p>Below are a few reminders about the Mini-workshops. PLease read the following information carefully.</p>
					<p>
						<b>Audiovisual Equipment</b><br />
						The Electronic Village will provide an LCD projector and screen for your brief presentation/demonstration and 5 computers equipped with microphones and speakers for the hands-on work by 10 - 15 participants (2-3 at each computer). We encourage you to bring your own laptop for your presentation; otherwise, only 4 computers will be available to your participants.
					</p>
					
					<p>
						<b>What Happens Next?</b><br />
						Please inform all of your co-presenters (if any) where and when the workshop will be presented. We request that presenters check in at the Electronic Village as soon as they arrive at the convention.  This will ensure that the needed software has been loaded into the computers and your presentation will go as intended.
					</p>
 
					<p>
						<b>Registration and Housing</b><br />
						Go to the TESOL Convention page for more information about registration and housing.
					</p>

					<p>Congratulations on being accepted! Please contact us at <a href="mailto:ev-mini@call-is.org">ev-mini@call-is.org</a> if you have any questions. We look forward to seeing you and your colleagues at the convention.</p>
<?php
		} else if($propType == "Technology Fairs (Classics)") {
?>
					<p>Below are a few reminders about the Technology Fairs. PLease read the following information carefully.
						<ul>
							<li style="margin-bottom: 20px;">Please check the date and time of your presentation, then contact <a href="mailto:ev-classics@call-is.org">ev-classics@call-is.org</a> if there are any problems. It is very important that you check all the details of your presentation.</li>
							<li style="margin-bottom: 20px;">Have in mind that titles may be edited to fewer than 50 characters. If you want to redefine the title of you presentation or correct any detail, also send the changes to <a href="mailto:ev-classics@call-is.org">ev-classics@call-is.org</a>.</li>
							<li style="margin-bottom: 20px;">Please communicate to us any updates to the equipment needs listed in your initial proposal and verify any co-presenters' names and email addresses.  Since you are the contact or primary person listed, it is your responsibility to inform co-presenters of the status of this presentation.</li>
							<li style="margin-bottom: 20px;">Please show up 10 minutes in advance of your presentation to set up.  Remember that you will give your presentation twice during your assigned Technology Fair; you will have 20 - 25 minutes to complete it each time.  Prompt completion of your presentation will be appreciated by both the next presenter and the EV staff.</li>
							<li style="margin-bottom: 20px;">If you are scheduled at "PC" or a "Mac" station, you have one computer with two monitors for your use during your presentation. You may connect your own device to the second monitor if you wish.</li>
							<li style="margin-bottom: 20px;">If you are scheduled at a "BYOD" station, you will need to bring your own computer or device and will set up at the circular tables.</li>
							<li style="margin-bottom: 20px;">Please remember there will be presenters on either side of your station, so we would ask that you control your volume as a courtesy to the other presenters.</li>
							<li style="margin-bottom: 20px;">We recommend that you bring at least 30 handouts for your audience with information from your presentation or have a link that participants can copy to find further information online.</li>
						</ul>
 					</p>
 					<p>Thank you in advance for your cooperation and for your willingness to share your knowledge.  Please let us know if you have any questions or concerns.  The Electronic Village team looks forward to seeing you at the convention!</p>
<?php
		}
?>
					<p><strong>Electronic Village Pass</strong><br>TESOL has instituted an "EV Pass" for the cost of $10. ALL PRESENTERS MUST HAVE THIS PASS IN ORDER TO ENTER THE ELECTRONIC VILLAGE. You can purchase the EV pass when you register for the conference, or on-site at the Electronic Village.</p>
					<p>
						<b>Do you need a printed certificate of presentation?</b><br />
						In an effort to reduce waste and conserve resources, the Electronic Village will ONLY be issuing digital certificates this year. If you MUST have a paper certificate, please <a href="certificate.php?id=<?php echo $propID; ?><?php if($_GET['classics']) { ?>&classics=1<?php } ?>">request a printed certificate</a> and we will prepare a paper certificate for you to pick up at the convention. We will not send paper certificates by mail. If you have any questions, please contact our program manager at ev@call-is.org.
					</p>
<?php
	} else if($propConfirmed == "N") {
?>
					<p style="font-weight: bold">Thank you for letting us know you will NOT present.</p>
					<p>We are sorry to hear that you will not be able to give your presentation at the Electronic Village. If you are still attending the convention, we hope you will stop by the Electronic Village and see some of the many wonderful presentations we are offering.</p>
					<p>We hope you will consider submitting a proposal for the next Electronic Village. The proposals submission website will open in August.</p>
					<p>If you have any questions about the Electronic Village, please contact the Program Manager at <a href="mailto:ev@call-is.org">ev@call-is.org</a>.
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
	}
	
	// First, get the title and event type of this proposal and display it for the user to verify
	if(isset($_GET["classics"]) && $_GET["classics"] == "1") {
		$q_stmt = $db->prepare("SELECT `title` FROM `classics_proposals` WHERE `id` = ?");
		$q_stmt->bind_param("s", $propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle);
		$q_stmt->fetch();
		$propType = "Technology Fairs (Classics)";
	} else if(isset($_GET["other"]) && $_GET["other"] == "1") {
		$q_stmt = $db->prepare("SELECT `title` FROM `other_proposals` WHERE `id` = ?");
		$q_stmt->bind_param("s", $propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle);
		$q_stmt->fetch();
		$propType = "Other";
	} else {
		$q_stmt = $db->prepare("SELECT `title`,`type` FROM `proposals` WHERE `id` = ?");
		$q_stmt->bind_param("s", $propID);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle,$propType);
		$q_stmt->fetch();
	}
	
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Confirm Participation</title>
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
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Confirm Participation</span></td>
			</tr>
			<tr>
				<td>
					<p>By clicking one of the buttons below, you are indicating if you will present or NOT present the following presentation in the Electronic Village:</p>
					<p style="margin-left: 25px"><b>Title:</b> <?php echo $propTitle?></p>
					<p style="margin-left: 25px"><b>Event:</b> <?php echo $propType?></p>
					<p>&nbsp;</p>
					<p style="border: solid 1px #000000; border-radius: 10px; width: 100%; background-color: #CCFFCC; color: #000000; padding: 10px; text-align: center; cursor: default;" onMouseOver="this.style.backgroundColor='#009900';this.style.color='#FFFFFF'" onMouseOut="this.style.backgroundColor='#CCFFCC';this.style.color='#000000'" onclick="confirmProp('Y')">I confirm that my co-presenters and I will present at the Electronic Village for the above presentation.</p>
					<p>&nbsp;</p>
					<p style="border: solid 1px #000000; border-radius: 10px; width: 100%; background-color: #FFCCCC; color: #000000; padding: 10px; text-align: center; cursor: default;" onMouseOver="this.style.backgroundColor='#990000';this.style.color='#FFFFFF'" onMouseOut="this.style.backgroundColor='#FFCCCC';this.style.color='#000000'" onclick="confirmProp('N')">I confirm that my co-presenters and I will <b>NOT</b> present at the Electronic Village for the above presentation.</p>
				</td>
			</tr>
		</table>
		<form name="cfForm" id="cfForm" method="post" action="">
			<input type="hidden" name="propConfirmed" id="propConfirmed" value="" />
			<input type="hidden" name="propType" id="propType" value="<?php echo $propType?>" />
		</form>
		<script type="text/javascript">
			function confirmProp(v) {
				document.getElementById('propConfirmed').value = v;
				document.getElementById('cfForm').submit();
			}
		</script>
	</body>
</html>