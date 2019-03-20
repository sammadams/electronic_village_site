<?php
    /*
    	logoutAdmin.php -- This script resets the $_SESSION so that the admin user is logged in as themselves.
     */
     
	include "login.php";
	
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
	    if(loginUser($email, $password, $db)) {
	    	//now, remove the admin variables from the $_SESSION array
	    	if(isset($_SESSION['admin_name'])) unset($_SESSION['admin_name']);
	    	if(isset($_SESSION['admin_first_name'])) unset($_SESSION['admin_first_name']);
	    	if(isset($_SESSION['admin_last_name'])) unset($_SESSION['admin_last_name']);
	    	
		    header("Location: index.php"); //redirect to the main menu for the new user
		    exit();
		}
	}

	$topTitle = "Return to Admin Menu";
	include "adminTop.php";
?>
					<script type="text/javascript" src="../sha512.js"></script>
					<script type="text/javascript">
						function validatePassword(pass) {
							var re = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z]{8,15}$/;
							return re.test(pass);
						}

						function checkForm() {
							if(!validatePassword(document.getElementById('uPass').value)) {
								alert('You did not enter a valid password for this user!\n\nA valid password meets these criteria:\n  - between 8 and 15 characters\n  - contains at least one uppercase letter\n  - contains at least one lowercase letter\n  - contains at least one number\n  - contains only letters and numbers');
								document.getElementById('uPass').focus();
								return false;
							}
			
							//If we get this far, everything is correct, so submit the form
							document.getElementById('userPass').value = hex_sha512(document.getElementById('uPass').value);
							document.getElementById('uPass').value = ''; //clear the password field so it doesn't get submitted unencrypted
			
							document.getElementById('loginForm').submit();
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
							<td colspan="2">Please re-enter your administrator password!</td>
						</tr>
						<tr>
							<td>Password:</td>
							<td><input type="password" id="uPass" name="uPass" /></td>
						</tr>
						<tr>
							<td align="center" colspan="2"><input type="button" value="Submit" onClick="checkForm()" /></td>
						</tr>
					</table>
					<form name="loginForm" id="loginForm" method="post" action="">
						<input type="hidden" name="userName" id="userName" value="<?=$_SESSION['admin_name']?>" />
						<input type="hidden" name="userPass" id="userPass" value="" />
					</form>
<?php
	include "adminBottom.php";
?>