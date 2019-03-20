<?php
	//reset.php -- allows the user to reset their password
	
	include "../../ev_config.php";
	include "../../ev_library.php";
	
	$y = date("Y") + 1;
	
	if(isset($_GET["id"])) $propID = strip_tags($_GET["id"]);
	else if(isset($_POST["propID"])) $propID = $_POST["propID"];
	
	if(isset($_GET["token"])) $token = strip_tags($_GET["token"]);
	
	if(isset($_POST["contact"], $_POST["hashPass"], $_POST["propID"])) { //password is set, so update and take to login screen
		$contact = strip_tags($_POST['contact']);
		$pass = strip_tags($_POST['hashPass']);
		$id = $_POST['propID'];
		
		//echo "<pre>";
		//echo $pass."\n";

		if($pass != "") { //a new password was entered
			if(strlen($pass) != 128) {
				header('Location: error.php?err=Password error: LENGTH');
			}
			
			$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
			$password = hash('sha512', $pass . $random_salt);
			
			//echo $random_salt."\n".$password;
			//echo "</pre>";
			
			//We now need to update the database with the new password and salt
			
			$q_stmt = $db->prepare("UPDATE `proposals` SET `password` = ?, `salt` = ? WHERE `id` = ? LIMIT 1");
			$q_stmt->bind_param('sss',$password, $random_salt, $id);
			if($q_stmt->execute() === false) {
				echo $q_stmt->error;
				exit();
			} else {
				//Now, delete the entry from the "password_resets" table so they can't reset it again
				//without going through the forgot password pages
				
				$d_stmt = $db->prepare("DELETE FROM `password_resets` WHERE `propID` = ? AND `contact` = ? LIMIT 1");
				$d_stmt->bind_param('ss',$id,$contact);
				if($d_stmt->execute() === false) {
					echo $d_stmt->error;
					exit();
				} else {
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Reset Password</title>
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
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Reset Password</span></td>
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
				<td align="center">
					Your password has been reset. Please return to the login screen to access your proposal information.<br /><br /><a href="edit.php?id=<?=$id?>">Return to the login screen</a>.
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
					exit();
				}
			}
		}
	}
	
	//Now, look up the token in the database and see if they match
	$t_stmt = $db->prepare("SELECT * FROM `password_resets` WHERE `propID` = ? AND `token` = ? LIMIT 1");
	$t_stmt->bind_param("ss",$propID,$token);
	$t_stmt->execute();
	$t_stmt->store_result();
	if($t_stmt->num_rows == 1) { //a match was found, so show the reset password form
?>
<html>
	<head>
		<title>Electronic Village Proposals -- Reset Password</title>
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
		<script type="text/javascript" src="sha512.js"></script>
		<script type="text/javascript">
			function formhash() {
				var thisForm = document.getElementById('resetPassForm');
				var pInput = document.createElement("INPUT");
				thisForm.appendChild(pInput);
				pInput.name = 'hashPass';
				pInput.type = 'hidden';
				pInput.value = hex_sha512(document.getElementById('pass').value);
				
				document.getElementById('pass').value = '';
				
				thisForm.submit();
			}

			function checkPass() {
				if(document.getElementById('pass').value == '' && document.getElementById('confirmPass').value == '') {
					document.getElementById('pass_match').innerHTML = 'Passwords do not match';
					document.getElementById('pass_match').style.color = 'red';
				} else if(document.getElementById('pass').value != document.getElementById('confirmPass').value) {
					document.getElementById('pass_match').innerHTML = 'Passwords do not match!';
					document.getElementById('pass_match').style.color = 'red';
				} else {
					document.getElementById('pass_match').innerHTML = 'Passwords match!';
					document.getElementById('pass_match').style.color = 'green';
				}
			}
		</script>
	</head>
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Reset Password</span></td>
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
					<form name="resetPassForm" id="resetPassForm" method="post" action="reset.php">
						<table border="0" align="center">
							<tr>
								<td align="right">Main contact email:</td>
								<td><input type="text" name="contact" id="contact" /></td>
							</tr>
							<tr>
								<td align="right">New password:</td>
								<td><input type="password" name="pass" id="pass" onkeyup="checkPass()" /></td>
							</tr>
							<tr>
								<td align="right">Confirm password:</td>
								<td><input type="password" name="confirmPass" id="confirmPass" onkeyup="checkPass()" /></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td id="pass_match" width="250" style="color:red; font-size: 12pt; font-weight: bold">Passwords do not match</td>
							</tr>
							<tr>
								<td colspan="2" align="center"><br /><input type="button" value="Continue" onclick="formhash()" style="font-size: 14pt; font-weight: bold; height: 30px; width: 100px" />
							</tr>
						</table>
						<input type="hidden" name="propID" value="<?=$propID?>" />
					</form>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
	} else {
		echo "Token not found!";
		exit();
	}
?>