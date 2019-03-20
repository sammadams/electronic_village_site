<?php
    /*
    	This script is included in each file that needs to be protected by logging in.
    	This script checks to see if a user is logged in, and if not, shows the login screen.
    	Because the script is included in each file, there is no need for a redirect.
    	The file can simply include this at the beginning and then continue as normal.
     */
     
    //Do the includes, just in case
	include_once '../../../ev_config.php';
	include_once '../../../ev_library.php';
 
	sec_session_start(); // Our custom secure way of starting a PHP session.

	if(isset($_POST['userName'], $_POST['userPass'])) { //login information was sent
	    $email = strip_tags($_POST['userName']);
	    $password = strip_tags($_POST['userPass']); // The hashed password.

		/*
			If the login is successful, the next if statement (login_checkUser) will return true and
			whatever happens in the script this file is included in will continue forward. If the login
			is unsuccessful, then the login_checkUser part will return false and the user will see the
			login screen again. An error message will be set by the login function, so we can check for
			that error message and display it on the screen if set.
		 */
		$loginError = "";
	    loginUser($email, $password, $db);
	}
 
 	if(login_checkUser($db) === false) { //not logged in, so show the login screen
 		$topTitle = "Login";
 		include "adminTop.php";
?>
					<script type="text/javascript" src="../sha512.js"></script>
					<script type="text/javascript">
						function validateEmail(email) { 
							var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
							return re.test(email);
						}
		
						function validatePassword(pass) {
							var re = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z]{8,15}$/;
							return re.test(pass);
						}

						function checkForm() {
							if(!validateEmail(document.getElementById('uEmail').value)) {
								alert('You did not enter a valid email address for this user!');
								document.getElementById('uEmail').focus();
								return false;
							}
			
							if(!validatePassword(document.getElementById('uPass').value)) {
								alert('You did not enter a valid password for this user!\n\nA valid password meets these criteria:\n  - between 8 and 15 characters\n  - contains at least one uppercase letter\n  - contains at least one lowercase letter\n  - contains at least one number\n  - contains only letters and numbers');
								document.getElementById('uPass').focus();
								return false;
							}
			
							//If we get this far, everything is correct, so submit the form
							document.getElementById('userName').value = document.getElementById('uEmail').value;
							document.getElementById('userPass').value = hex_sha512(document.getElementById('uPass').value);
							document.getElementById('uPass').value = ''; //clear the password field so it doesn't get submitted unencrypted
			
							document.getElementById('loginForm').submit();
						}
						
						function checkKey(e) {
							var kc = e.which || e.code;
							if(kc == 13) { //the ENTER key was pressed
								checkForm();
								return false;
							}
						}
					</script>
<?php
		if(isset($loginError) && $loginError != "") {
?>
					<table border="0" cellpadding="5" align="center">
						<tr>
							<td valign="top" style="font-weight: bold; color: red">ERROR:</td>
							<td style="color: red"><?=$loginError?></td>
						</tr>
					</table>
<?php
		}
?>

					<table border="0" align="center" cellpadding="10">
						<tr>
							<td>Email Address:</td>
							<td><input type="text" id="uEmail" name="uEmail" /></td>
						</tr>
						<tr>
							<td>Password:</td>
							<td><input type="password" id="uPass" name="uPass" onkeypress="checkKey(event)" /></td>
						</tr>
						<tr>
							<td align="center" colspan="2"><input type="button" value="Submit" onClick="checkForm()" /></td>
						</tr>
					</table>
					<form name="loginForm" id="loginForm" method="post" action="">
						<input type="hidden" name="userName" id="userName" value="" />
						<input type="hidden" name="userPass" id="userPass" value="" />
					</form>
<?php
		include "adminBottom.php";
		
		exit();
 	}
?>