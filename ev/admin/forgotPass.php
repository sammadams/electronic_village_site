<?php
	//forgotPass.php -- allows the user to reset their password
	
	if(isset($_POST["forgotEmail"])) {
		$email = preg_replace("/[^a-zA-Z0-9s@_!#\$%&'\*\+\-\/=\?\^_`\{\|}~@\[\]\.]/","",strip_tags($_POST["forgotEmail"]));
		
		include_once "../library.php";
		
		$fStmt = $db->prepare("SELECT `id`,`first_name`,`last_name` FROM `users` WHERE `username` = ?");
		$fStmt->bind_param('s',$email);
		$fStmt->execute();
		$fStmt->bind_result($uID,$uFName,$uLName);
		$fStmt->fetch();
		$fStmt->close();
		
		if($fID > 0) { //a row exists, so send a forgot password token by email
			//Check to see if a token already exists for this user
			$eStmt = $db->prepare("SELECT `id`,`token`,`time` FROM `adimn_password_resets` WHERE `user` = ?");
			$eStmt->bind_param('s',$user);
			$user = $uID;
			$eStmt->execute();
			$eStmt->bind_result($eID,$eToken,$eTime);
			$eStmt->fetch();
			$eStmt->close();
			
			if($eID > 0) { //a token has already been created, so check the time
				list($tDate,$tTime) = explode(" ",$eTime);
				list($tYear,$tMonth,$tDay) = explode("-",$tDate);
				list($tHour,$tMin,$tSec) = explode(":",$tTime);
				
				$tEndTime = mktime($tHour,$tMin,$tSec,$tMonth,($tDay + 1),$tYear);
				$now = time();
				if($now < $tEndTime) { //token is not expired, so resend the same token
					if(sendResetEmail($email,$uFName." ".$uLName,$uID,$eToken)) {
						include "adminTop.php";
	?>
						<h1 align="center">Forgot Password?</h1>
						<p>You have already requested a password reset link in the last 24 hours. The email may have gone into a junk mail or SPAM folder in your email account. Another email with the reset link was sent to <?=$email?>. You must use the link in that email to reset your password. If you do not receive the reset password email within 24 hours, please contact <a href="ev@call-is.org">ev@call-is.org</a> and include your email address in the body of the message.</p>
	<?php
						include "adminBottom.php";
						exit();
					}
				}
			}

			$token = md5(uniqid(rand(),1)); // the token (for resetting the password)
			$token = hash('sha512',$token); //hash it (for added security and complexity)
			
			//insert the token into the database
			$tStmt = $db->prepare("INSERT INTO `admin_password_resets` (`id`,`user`,`email`,`token`,`time`) VALUES ('',?,?,?,?)");
			$tStmt->bind_param('ssss',$user,$email,$token,$timeStr);
			$user = $uID;
			$timeStr = date("Y-m-d H:i:s");

			if($tStmt->execute()) { //the information was inserted, so send the email
				//if the message is sent successfully print out the confirmation page
				if(sendResetEmail($email,$uFName." ".$uLName,$uID,$token)) {
					include "adminTop.php";
	?>
						<h1 align="center">Forgot Password?</h1>
						<p>An email was sent to <?=$email?>. You must use the link in that email to reset your password. Please check your email and remember that the email might be in a junk mail folder or SPAM folder. The link to reset your password will expire in 24 hours. If you do not receive the reset password email within 24 hours, please contact <a href="mailto:ev@call-is.org">ev@call-is.org</a> and include your email address in the body of the message.</p>
	<?php
					include "adminBottom.php";
				
					exit();
				} else $errMsg = "The reset password email could not be sent!";
			} else $errMsg = "The reset information could not be saved in the database!";
		} else $errMsg = "No user found with that email address!";
	}
	
	include "adminTop.php";
?>
					<h1 align="center">Forgot Password?</h1>
<?php
	if(isset($errMsg)) {
?>
					<p id="errMsgPara" style="text-align: center; color: red"><b>Error:</b> <?=$errMsg?></p>
<?php
	}
?>
					<p style="text-align: center">Enter your email address below and we will send you a link so that you can reset your password.</p>
					<form name="forgotPassForm" id="forgotPassForm" method="post" action="forgotPass.php">
						<table border="0" cellspacing="0" cellpadding="0" align="center">
							<tr>
								<td class="loginTxt" align="right">Email</td>
<?php
	if(isset($errMsg)) {
?>
								<td><input type="text" class="loginField" name="forgotEmail" id="forgotEmail" value="<?=$email?>" /></td>
<?php
	} else {
?>
								<td><input type="text" class="loginField" name="forgotEmail" id="forgotEmail" value="">
<?php
	}
?>
							</tr>
							<tr>
								<td colspan="2" style="padding-top: 10px"><input type="submit" id="forgotSubmit" class="loginBtn" value="Send Reset Password Link"></td>
							</tr>
						</table>
					</form>
<?php
	include "adminBottom.php";
	
	function sendResetEmail($email,$name,$id,$token) {
		$to = $email;
		$from = "ev@call-is.org";

		//define the subject of the email
		$subject = "Your password reset information";

		//create a boundary string. It must be unique so we use the MD5 algorithm to generate a random hash
		$random_hash = md5(date('r', time())); 

		//define the headers we want passed. Note that they are separated with \r\n
		$headers = "From: ".$from;

		//add boundary string and mime type specification
		$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"EV-".$random_hash."\""; 

		/*
			The main text of the message is stored in a text file on the server.
			We need to get that text and then put in the appropriate values where needed.
		*/
	
		$tmpName = "";
		$tmpHTMLName = "";
		if($fName != "") {
			$tmpName = "Dear ".$name.",";
			$tmpHTMLName = '<p style="font-family: Arial; font-size: 12pt">Dear '.$name.',</p>';
		}
	
		$tmpLink = 'http://call-is.org/ev/admin/resetPass.php?id='.$id.'&t='.$token;

		$tmpMsg = file_get_contents("resetPasswordEmail.txt");
		$tmpMsg = str_replace("[INSERT RANDOM HASH]",$random_hash,$tmpMsg);
		$tmpMsg = str_replace("[INSERT NAME]",$tmpName,$tmpMsg);
		$tmpMsg = str_replace("[INSERT LINK]",$tmpLink,$tmpMsg);
		$tmpMsg = str_replace("[INSERT HTML NAME]",$tmpHTMLName,$tmpMsg);

		//copy current buffer contents into $message variable and delete current output buffer
		$message = $tmpMsg;

		//send the email
		$mail_sent = @mail( $to, $subject, $message, $headers );
		
		return $mail_sent;
	}
?>