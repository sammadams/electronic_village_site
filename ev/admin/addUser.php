<?php
	//addUser.php - allows a user to add users to the database
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
	
	if(strpos($_SESSION['user_role'],"lead_") !== false) $topTitle = "Add Reviewer";
	else $topTitle = "Add User";
		
	if(isset($_POST["addUName"]) && isset($_POST["addUPass"]) && isset($_POST["addURole"])) {
		$userFirstName = strip_tags($_POST["addUFirstName"]);
		$userLastName = strip_tags($_POST["addULastName"]);
		$userName = strip_tags($_POST["addUName"]);
		$userPass = strip_tags($_POST["addUPass"]);
		$userRole = strip_tags($_POST["addURole"]);
		
		//Check to see if the user is already there
		$eStmt = $db->prepare("SELECT `username`,`role` FROM `users` WHERE `username` = ? LIMIT 1");
		$eStmt->bind_param('s', $userName);
		$eStmt->execute();
		$eStmt->store_result();
		$eStmt->bind_result($cu_username, $cu_role);
		$eStmt->fetch();
		if($eStmt->num_rows > 0) { //user already exists
			if(strpos($cu_role, $userRole) !== false) {
				if(strpos($_SESSION['user_role'],"lead_") !== false) $addUserError = 'This reviewer is already on your list. Please check the information and try again.'; //user already has role
				else $addUserError = 'This user already has that role. Please check the information and try again.';
			} else {
				//add role to user data
				$userRole = $cu_role."|".$userRole;
				$uStmt = $db->prepare("UPDATE `users` SET `role` = ? WHERE `username` = ? LIMIT 1");
				$uStmt->bind_param('ss', $userRole, $userName);
			}
		} else {
			//generate the random salt and encrypt the password		
			$salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
			$password = hash('sha512', $userPass . $salt);

			$uStmt = $db->prepare("INSERT INTO `users` (`username`,`first_name`, `last_name`, `password`,`salt`,`role`) VALUES(?, ?, ?, ?, ?, ?)");
			$uStmt->bind_param('ssssss', $userName, $userFirstName, $userLastName, $password, $salt, $userRole);
		}

		if(!$uStmt->execute()) {
			$addUserError = $uStmt->error;
		} else {
			include "adminTop.php";
			if(strpos($_SESSION['user_role'],"lead_") !== false) {
?>
					<h3 align="center">The reviewer was added successfully!</h3>
					<p align="center"><a href="addUser.php">Add another reviewer</a></p>
					<p align="center"><a href="userList.php">Back to Reviewer List</a></p>
<?php
			} else {
?>
					<h3 align="center">The user was added successfully!</h3>
					<p align="center"><a href="addUser.php">Add another user</a></p>
					<p align="center"><a href="userList.php">Back to User List</a></p>
<?php
			}
			
			include "adminBottom.php";
				
			exit();
		}
	}
	
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
							if(document.getElementById('uFirstName').value == '') {
								alert('You did not enter a first name for this user!');
								document.getElementById('uFirstName').focus();
								return false;
							}
				
							if(document.getElementById('uLastName').value == '') {
								alert('You did not enter a last name for this user!');
								document.getElementById('uLastName').focus();
								return false;
							}
				
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

<?php
	if(strpos($_SESSION['user_role'],"lead_") === false) {
?>
							var sEl = document.getElementById('uRole');
							if(sEl != undefined || sEl == null) {
								if(sEl.options[sEl.selectedIndex].value == '') {
									alert('You did not select a role for this user!');
									return false;
								}
							}
<?php
	}
?>
							//If we get this far, everything is correct, so submit the form
							document.getElementById('addUFirstName').value = document.getElementById('uFirstName').value;
							document.getElementById('addULastName').value = document.getElementById('uLastName').value;
							document.getElementById('addUName').value = document.getElementById('uEmail').value;
							document.getElementById('addUPass').value = hex_sha512(document.getElementById('uPass').value);
							document.getElementById('uPass').value = ''; //clear the password field so it doesn't get submitted unencrypted
<?php
	if(strpos($_SESSION['user_role'],"lead_") === false) {
?>
							if(sEl != undefined) document.getElementById('addURole').value = sEl.options[sEl.selectedIndex].value;
<?php
	} else {
?>
							document.getElementById('addURole').value = document.getElementById('uRole').value;
<?php	
	}
?>				
							document.getElementById('userForm').submit();
						}
					</script>
<?php
	if(isset($addUserError) && $addUserError != "") {
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td valign="top" style="color: red; font-weight: bold">ERROR:</td>
							<td style="color: red"><?=$addUserError?></td>
						</tr>
					</table>
<?php
	}
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>First Name:</td>
							<td><input type="text" id="uFirstName" name="uFirstName" /></td>
						</tr>
						<tr>
							<td>Last Name:</td>
							<td><input type="text" id="uLastName" name="uLastName" /></td>
						</tr>
						<tr>
							<td>Email Address:</td>
							<td><input type="text" id="uEmail" name="uEmail" /></td>
						</tr>
						<tr>
							<td valign="top">Password:</td>
							<td><input type="text" id="uPass" name="uPass" /><br /><span class="label">The password is not hidden (i.e. doesn't show dots)<br />because it will be encrypted before being submitted.</td>
						</tr>
						<tr>
							<td>Role:</td>
<?php
	if(strpos($_SESSION['user_role'],"lead_") !== false) {
		if(strpos($_SESSION['user_role'],"_fairs") !== false) {
			$roleStr = 'Reviewer (Technology Fairs)';
			$elStr = 'reviewer_fairs';
		} else if(strpos($_SESSION['user_role'],"_mini") !== false) {
			$roleStr = 'Reviewer (Mini-Workshops)';
			$elStr = 'reviewer_mini';
		} else if(strpos($_SESSION['user_role'],"_ds") !== false) {
			$roleStr = 'Reviewer (Developers Showcase)';
			$elStr = 'reviewer_ds';
		} else if(strpos($_SESSION['user_role'],"_mae") !== false) {
			$roleStr = 'Reviewer (Mobile Apps for Education Showcase)';
			$elStr = 'reviewer_mae';
		} else if(strpos($_SESSION['user_role'],"_cotf") !== false) {
			$roleStr = 'Reviewer (Classroom of the Future)';
			$elStr = 'reviewer_cotf';
		} else if(strpos($_SESSION['user_role'],"_ht") !== false) {
			$roleStr = 'Reviewer (Hot Topics)';
			$elStr = 'reviewer_ht';
		} else if(strpos($_SESSION['user_role'],"_grad") !== false) {
			$roleStr = 'Reviewer (Graduate Student Research)';
			$elStr = 'reviewer_grad';
		} else if(strpos($_SESSION['user_role'],"_classics") !== false) {
			$roleStr = 'Reviewer (Technology Fair Classics)';
			$elStr = 'reviewer_classics';
		}
?>
							<td><?=$roleStr?><input type="hidden" name="uRole" id="uRole" value="<?=$elStr?>" /></td>
<?php
	} else if(strpos($_SESSION['user_role'],"chair") !== false) {
?>
							<td>
								<select name="uRole" id="uRole">
									<option value="">Please select a role...</option>
									<option value="reviewer_fairs">Reviewer (Technology Fairs)</option>
									<option value="reviewer_mini">Reviewer (Mini-Workshops)</option>
									<option value="reviewer_ds">Reviewer (Developers Showcase)</option>
									<option value="reviewer_mae">Reviewer (Mobile Apps for Education Showcase)</option>
									<option value="reviewer_cotf">Reviewer (Classroom of the Future)</option>
									<option value="reviewer_ht">Reviewer (Hot Topics)</option>
									<option value="reviewer_grad">Reviewer (Graduate Student Research)</option>
									<option value="reviewer_classics">Reviewer (Technology Fair Classics)</option>
									<option value="lead_fairs">Event Lead (Technology Fairs)</option>
									<option value="lead_mini">Event Lead (Mini-Workshops)</option>
									<option value="lead_ds">Event Lead (Developers Showcase)</option>
									<option value="lead_mae">Event Lead (Mobile Apps for Education Showcase)</option>
									<option value="lead_cotf">Event Lead (Classroom of the Future)</option>
									<option value="lead_ht">Event Lead (Hot Topics)</option>
									<option value="lead_grad">Event Lead (Graduate Student Research)</option>
									<option value="lead_classics">Event Lead (Technology Fair Classics)</option>
								</select>
							</td>
<?php
	} else if(strpos($_SESSION['user_role'],"admin") !== false) {
?>
							<td>
								<select name="uRole" id="uRole">
									<option value="">Please select a role...</option>
									<option value="reviewer_fairs">Reviewer (Technology Fairs)</option>
									<option value="reviewer_mini">Reviewer (Mini-Workshops)</option>
									<option value="reviewer_ds">Reviewer (Developers Showcase)</option>
									<option value="reviewer_mae">Reviewer (Mobile Apps for Education Showcase)</option>
									<option value="reviewer_cotf">Reviewer (Classroom of the Future)</option>
									<option value="reviewer_ht">Reviewer (Hot Topics)</option>
									<option value="reviewer_grad">Reviewer (Graduate Student Research)</option>
									<option value="reviewer_classics">Reviewer (Technology Fair Classics)</option>
									<option value="lead_fairs">Event Lead (Technology Fairs)</option>
									<option value="lead_mini">Event Lead (Mini-Workshops)</option>
									<option value="lead_ds">Event Lead (Developers Showcase)</option>
									<option value="lead_mae">Event Lead (Mobile Apps for Education Showcase)</option>
									<option value="lead_cotf">Event Lead (Classroom of the Future)</option>
									<option value="lead_ht">Event Lead (Hot Topics)</option>
									<option value="lead_grad">Event Lead (Graduate Student Research)</option>
									<option value="lead_classics">Event Lead (Technology Fair Classics)</option>
									<option value="chair">Chair</option>
									<option value="admin">Administrator</option>
								</select>
							</td>
<?php
	}
?>
						</tr>
						<tr>
							<td align="center" colspan="2"><input type="button" value="Cancel" onClick="window.location.href='userList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Submit" onClick="checkForm()" /></td>
						</tr>
					</table>
					<form name="userForm" id="userForm" method="post" action="">
						<input type="hidden" name="addUFirstName" id="addUFirstName" value="" />
						<input type="hidden" name="addULastName" id="addULastName" value="" />
						<input type="hidden" name="addUName" id="addUName" value="" />
						<input type="hidden" name="addUPass" id="addUPass" value="" />
<?php
	if(isset($elStr) && $elStr != "") {
?>
						<input type="hidden" name="addURole" id="addURole" value="<?=$elStr?>" />
<?php
	} else {
?>
						<input type="hidden" name="addURole" id="addURole" value="" />
<?php
	}
?>
					</form>
<?php
	include "adminBottom.php";
?>