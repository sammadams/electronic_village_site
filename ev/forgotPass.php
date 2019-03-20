<?php
	include "../../ev_config.php";
	include "../../ev_library.php";
	
	$y = date("Y") + 1;
	
	if(isset($_GET["id"])) $propID = strip_tags($_GET["id"]);
	else if(isset($_POST["propID"])) $propID = $_POST["propID"];
	
	if(isset($_GET["resend"]) && strip_tags($_GET["resend"]) == 1) {
		//The user has requested that we resend the password reset email
		//We will just remove the current password_reset entry and start again
		//There should only be one entry, but this will remove all of the ones that match that propID
		
		//First, we need to get the contact from the database
		$np_stmt = $db->prepare("SELECT `contact` FROM `password_resets` WHERE `propID` = ? LIMIT 1");
		$np_stmt->bind_param('s',$propID);
		$np_stmt->execute();
		$np_stmt->store_result();
		$np_stmt->bind_result($propContact);
		$np_stmt->fetch();
		
		$np_stmt = $db->prepare("DELETE FROM `password_resets` WHERE `propID` = ?");
		$np_stmt->bind_param('s', $propID);
		if(!$np_stmt->execute()) {
			echo $np_stmt->error;
			exit();
		}
	}
	
	if(isset($_POST["contact"]) && strip_tags($_POST["contact"]) != "") $propContact = strip_tags($_POST["contact"]);
	
	if(isset($propContact) && $propContact != "") {
		$q_stmt = $db->prepare("SELECT `title`,`type` FROM `proposals` WHERE `id` = ? AND `contact` = ?");
		$q_stmt->bind_param("ss", $propID, $propContact);
		$q_stmt->execute();
		$q_stmt->store_result();
		$q_stmt->bind_result($propTitle,$propType);
		$q_stmt->fetch();
		if($q_stmt->num_rows == 1) { //found the record
			//Check the password_resets table to see if there is already an entry
			$np_stmt = $db->prepare("SELECT * FROM `password_resets` WHERE `propID` = ? AND `contact` = ?");
			$np_stmt->bind_param('ss',$propID,$propContact);
			$np_stmt->execute();
			$np_stmt->store_result();
			
			if($np_stmt->num_rows > 0) { //a record already exists
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Forgot Password</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
			
			a.forgotLink {
				font-size: 10pt;
				text-decoration: none;
				border-bottom: none;
			}
			
			a.forgotLink:hover {
				border-bottom: dashed 1px #CCCCCC;
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
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Forgot Password</span></td>
			</tr>
			<tr>
				<td>
					<p>The link to reset the password for this proposal was already sent to <?=$propContact?>. Please check your email, making sure to look in any junk or spam folders.</p>
					<p>You can resend the password reset email by clicking below. Resending the password reset email will resend a new link. This means that any links sent previously in any emails from the proposals system will not work.</p>
					<p><a href="forgotPass.php?id=<?=$propID?>&resend=1">Resend the reset password email</a></p>
					<p><a href="edit.php?id=<?=$propID?>">Back to the login screen</a></p>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
				exit();
			}
			
			//We will reset the password to a generic, random password and send that new password in an email to the main contact
			//They will be required to change the password when they log in using that random password.
			//UPDATE: Because we use javascript to hash the submitted password, and the password here is not submitted, the two
			//passwords do not match. So, we will generate a token that is unique and send that to the user in an email.
			//The token will be in a link they can use to reset the password
			$token = md5(uniqid(rand(),1)); // the token
			
			//Save the new password in the password reset table
			$np_stmt = $db->prepare("INSERT INTO `password_resets` (`propID`,`contact`,`token`) VALUES (?,?,?)");
			$np_stmt->bind_param('sss',$propID,$propContact,$token);
			if($np_stmt->execute()) { //the new password was inserted, so send it by email
				if($propType == "Technology Fairs") $from = "ev-fair@call-is.org";
				else if($propType == "Mini-Workshops") $from = "ev-mini@call-is.org";
				else if($propType == "Developers Showcase") $from = "ev-ds@call-is.org";
				else if($propType == "Mobile Apps for Education Showcase") $from = "ev-mae@call-is.org";
				else if($propType == "Classroom of the Future") $from = "ev-classroom@call-is.org";
				
				//define the receiver of the email
				$to = $propContact;

				//define the subject of the email
				$subject = "Electronic Village ".(date("Y") + 1).": ".$propType." (Password Reset)";
	
				//create a boundary string. It must be unique so we use the MD5 algorithm to generate a random hash
				$random_hash = md5(date('r', time())); 

				//define the headers we want passed. Note that they are separated with \r\n
				$headers = "From: ".$from."\r\nReply-To: ".$from;

				//add boundary string and mime type specification
				$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"CALL-EV-".$random_hash."\""; 

				/*
					The main text of the message is stored in a text file on the server.
					We need to get that text and then put in the appropriate values where needed.
				*/
				
				
				$tmpMsg = file_get_contents("forgotPassEmail.txt");
				$tmpMsg = str_replace("[INSERT RANDOM HASH]",$random_hash,$tmpMsg);
				$tmpMsg = str_replace("[INSERT PROP ID]",$propID,$tmpMsg);
				$tmpMsg = str_replace("[INSERT TITLE]",$propTitle,$tmpMsg);
				$tmpMsg = str_replace("[INSERT LINK]",'http://call-is.org/ev/reset.php?id='.$propID.'&token='.$token,$tmpMsg);
				$tmpMsg = str_replace("[INSERT TYPE EMAIL]",$from,$tmpMsg);
				$tmpMsg = str_replace("[INSERT YEAR]",$y,$tmpMsg);
				$tmpMsg = str_replace("[INSERT PROP TYPE]",$propType,$tmpMsg);

				//copy current buffer contents into $message variable and delete current output buffer
				$message = $tmpMsg;

				//send the email
				$mail_sent = @mail( $to, $subject, $message, $headers );

				//if the message is sent successfully print out the confirmation page
				if($mail_sent) {
					$y = date("Y") + 1;
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Forgot Password</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
			
			a.forgotLink {
				font-size: 10pt;
				text-decoration: none;
				border-bottom: none;
			}
			
			a.forgotLink:hover {
				border-bottom: dashed 1px #CCCCCC;
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
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Forgot Password</span></td>
			</tr>
			<tr>
				<td>
					<p>An email has been sent to <?=$propContact?> with instructions on how to reset password. Please check your email.</p>
					
					<p><a href="edit.php?id=<?=$propID?>">Back to the Login Screen</a></p>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
					exit();
				} else {
					echo "Mail not sent!";
				}				
			} else {
				echo $np_stmt->error;
				exit();
			}
		} else {
			$errMsg = "There main contact email did not match our records for the proposal you are trying to access. Please check the address and try again. If you continue to have problems, contact the event lead. The event lead's email address is in the submission confirmation email you received when you submitted your proposal.";
		}
	}
	
	$y = date("Y") + 1;
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Forgot Password</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
			
			a.forgotLink {
				font-size: 10pt;
				text-decoration: none;
				border-bottom: none;
			}
			
			a.forgotLink:hover {
				border-bottom: dashed 1px #CCCCCC;
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
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Forgot Password</span></td>
			</tr>
<?php
	if(isset($errMsg)) {
?>
			<tr>
				<td style="color: red" align="center"><span style="font-weight: bold">Error:</span> <?=$errMsg?><br />&nbsp;</td>
			</tr>
<?php
	}
?>
			<tr>
				<td>
					<form name="forgotPassForm" id="forgotPassForm" method="post" action="forgotPass.php">
						<table border="0" align="center">
							<tr>
								<td>Main contact email:</td>
								<td><input type="text" name="contact" /></td>
							</tr>
							<tr>
								<td colspan="2" align="center"><br /><input type="submit" value="Continue" style="font-size: 14pt; font-weight: bold; height: 30px; width: 100px" />
							</tr>
						</table>
						<input type="hidden" name="propID" value="<?=$propID?>" />
					</form>
				</td>
			</tr>
		</table>
	</body>
</html>