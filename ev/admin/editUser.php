<?php
	//editUser.php - allows a user to edit users information in the database
	//accessible only to admin users
	
	include_once "login.php";
	$topTitle = "Edit User";
	
	if(strpos($_SESSION['user_role'],"reviewer_") !== false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(strpos($_SESSION['user_role'],"lead_") !== false) $topTitle = "Edit Reviewer";
	else $topTitle = "Edit User";
		
	if(isset($_POST["editUName"]) && isset($_POST["editUPass"]) && isset($_POST["editURole"])) {
		$userFirstName = strip_tags($_POST["editUFirstName"]);
		$userLastName = strip_tags($_POST["editULastName"]);
		$userName = strip_tags($_POST["editUName"]);
		$userPass = strip_tags($_POST["editUPass"]);
		$userRole = strip_tags($_POST["editURole"]);
		$userID = preg_replace("/\D/","",$_POST["editUID"]);
		
		//Update the user information in the database
		$eStmt = $db->prepare("UPDATE `users` SET `first_name` = ?,`last_name` = ?,`role` = ?, `username` = ? WHERE `id` = ? LIMIT 1");
		$eStmt->bind_param('sssss', $userFirstName,$userLastName,$userRole,$userName,$userID);
		if(!$eStmt->execute()) {
			echo $eStmt->error;
			exit();
		}
		
		//If a password was submitted, update the password
		if($userPass != "") {
			//generate the random salt and encrypt the password		
			$salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
			$password = hash('sha512', $userPass . $salt);
			
			$pStmt = $db->prepare("UPDATE `users` SET `password` = ?, `salt` = ? WHERE `id` = ? LIMIT 1");
			$pStmt->bind_param('sss',$password,$salt,$userID);
			if(!$pStmt->execute()) {
				echo $pStmt->error;
				exit();
			}
		}
		
		//If we get this far, then show the sucess message
		include "adminTop.php";
		if(strpos($_SESSION['user_role'],"lead_") !== false) {
?>
					<h3 align="center">The reviewer information was edited successfully!</h3>
					<p align="center"><a href="userList.php">Back to Reviewer List</a></p>
<?php
		} else {
?>
					<h3 align="center">The user information was edited successfully!</h3>
					<p align="center"><a href="userList.php">Back to User List</a></p>
<?php
		}
				
		include "adminBottom.php";
		
		exit();
	}
	
	//Get the user information
	$user = isset($_GET["u"]) ? strip_tags($_GET["u"]) : "";
	if($user == "") {
		echo "No username given!";
		exit();
	}
	
	$uStmt = $db->prepare("SELECT `id`,`username`,`first_name`,`last_name`,`role` FROM `users` WHERE `username` = ? LIMIT 1");
	$uStmt->bind_param('s',$user);
	$uStmt->execute();
	$uStmt->store_result();
	if($uStmt->num_rows < 1) {
		echo "No user found with that username! (Error: ".$uStmt->error.")";
		exit();
	}
	
	$uStmt->bind_result($uID,$uName,$fName,$lName,$uRole);
	$uStmt->fetch();
	
	/*
		If the person accessing this page is an event lead, they should only be able to remove someone from being a reviewer on their event, not delete them from the system altogether or edit their password, name, email address, etc.
		If the user is removed and has no other roles, they will automatically be deleted from the system. If they have other roles, they will only be removed from the reviewer role.
	 */
	
	if(strpos($_SESSION['user_role'],"lead_") !== false && strpos($uRole,"|") !== false) {
		include "adminTop.php";
?>
					<p style="text-align: left">This reviewer has other roles in the system, so you are not allowed to edit their information. You can remove them from your reviewer list by clicking the button below.</p>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>First Name:</td>
							<td><?php echo $fName; ?></td>
						</tr>
						<tr>
							<td>Last Name:</td>
							<td><?php echo $lName; ?></td>
						</tr>
						<tr>
							<td>Email Address:</td>
							<td><?php echo $uName; ?></td>
						</tr>
					</table>
					<p style="text-align: center"><input type="button" value="Cancel" onclick="window.location.href='userList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Remove Reviewer" onClick="window.location.href='deleteUser.php?u=<?php echo $uName; ?>&r=1'" /></p>
<?php
		include "adminBottom.php";
		exit();		
	} else { 
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
				
							if(document.getElementById('uPass').value != '') { //ignore if blank
								if(!validatePassword(document.getElementById('uPass').value)) {
									alert('You did not enter a valid password for this user!\n\nA valid password meets these criteria:\n  - between 8 and 15 characters\n  - contains at least one uppercase letter\n  - contains at least one lowercase letter\n  - contains at least one number\n  - contains only letters and numbers');
									document.getElementById('uPass').focus();
									return false;
								}
							}

<?php
		if(strpos($_SESSION['user_role'],"lead_") === false) {
?>
							var elArray = document.getElementsByTagName('input');
							var roleStr = '';
							for(i = 0; i < elArray.length; i++) {
								if(elArray[i].id.indexOf('uRole') != -1) {
									if(elArray[i].checked) { //add this to the roles
										var tmpRole = elArray[i].id.substring(6,elArray[i].id.length);
										roleStr += tmpRole + '|';
									}
								}
							}
							
							roleStr = roleStr.substring(0,roleStr.length - 1);
							if(roleStr == '') {
								alert('You did not select any roles for this user!');
								return false;
							}
<?php
		} else {
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
							var roleStr = '<?php echo $elStr; ?>';
<?php
		}
?>

							//If we get this far, everything is correct, so submit the form
							document.getElementById('editUFirstName').value = document.getElementById('uFirstName').value;
							document.getElementById('editULastName').value = document.getElementById('uLastName').value;
							document.getElementById('editUName').value = document.getElementById('uEmail').value;
							
							if(document.getElementById('uPass').value != '') {
								document.getElementById('editUPass').value = hex_sha512(document.getElementById('uPass').value);
								document.getElementById('uPass').value = ''; //clear the password field so it doesn't get submitted unencrypted
							}
							
							document.getElementById('editURole').value = roleStr;
				
							document.getElementById('userForm').submit();
						}
						
						function deleteUser() {
							window.location.href = 'deleteUser.php?u=<?php echo $uName; ?>';
						}
					</script>
<?php
	/*
	if(isset($addUserError) && $addUserError != "") {
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td valign="top" style="color: red; font-weight: bold">ERROR:</td>
							<td style="color: red"><?php echo $addUserError; ?></td>
						</tr>
					</table>
<?php
	}
	
	*/
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>First Name:</td>
							<td><input type="text" id="uFirstName" name="uFirstName" value="<?php echo $fName; ?>" /></td>
						</tr>
						<tr>
							<td>Last Name:</td>
							<td><input type="text" id="uLastName" name="uLastName" value="<?php echo $lName; ?>" /></td>
						</tr>
						<tr>
							<td>Email Address:</td>
							<td><input type="text" id="uEmail" name="uEmail" value="<?php echo $uName; ?>" /></td>
						</tr>
						
						<tr>
							<td valign="top">Password:</td>
							<td><input type="text" id="uPass" name="uPass" /><br /><span class="label"><strong>Only enter a password if you want to change the current password.</strong><br /><br />The password is not hidden (i.e. doesn't show dots) because it will be<br />encrypted before being submitted.</td>
						</tr>
						<tr>
							<td valign="top">Role:</td>
<?php
		if(strpos($_SESSION['user_role'],"lead_") !== false) {
?>
							<td><?php echo $roleStr; ?></td>
<?php
		} else if(strpos($_SESSION['user_role'],"chair") !== false) {
?>
							<td>
								<input type="checkbox" name="uRole_reviewer_fairs" id="uRole_reviewer_fairs"<?php if(strpos($uRole,"reviewer_fairs") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Technology Fairs)<br />
								<input type="checkbox" name="uRole_reviewer_mini" id="uRole_reviewer_mini"<?php if(strpos($uRole,"reviewer_mini") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Mini-Workshops)<br />
								<input type="checkbox" name="uRole_reviewer_ds" id="uRole_reviewer_ds"<?php if(strpos($uRole,"reviewer_ds") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Developers Showcase)<br />
								<input type="checkbox" name="uRole_reviewer_mae" id="uRole_reviewer_mae"<?php if(strpos($uRole,"reviewer_mae") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Mobile Apps for Education Showcase)<br />
								<input type="checkbox" name="uRole_reviewer_cotf" id="uRole_reviewer_cotf"<?php if(strpos($uRole,"reviewer_cotf") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Classroom of the Future)<br />
								<input type="checkbox" name="uRole_reviewer_ht" id="uRole_reviewer_ht"<?php if(strpos($uRole,"reviewer_ht") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Hot Topics)<br />
								<input type="checkbox" name="uRole_reviewer_grad" id="uRole_reviewer_grad"<?php if(strpos($uRole,"reviewer_grad") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Graduate Student Research)<br />
								<input type="checkbox" name="uRole_reviewer_classics" id="uRole_reviewer_classics"<?php if(strpos($uRole,"reviewer_classics") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Technology Fair Classics)<br />
								<input type="checkbox" name="uRole_lead_fairs" id="uRole_lead_fairs"<?php if(strpos($uRole,"lead_fairs") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Technology Fairs)<br />
								<input type="checkbox" name="uRole_lead_mini" id="uRole_lead_mini"<?php if(strpos($uRole,"lead_mini") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Mini-Workshops)<br />
								<input type="checkbox" name="uRole_lead_ds" id="uRole_lead_ds"<?php if(strpos($uRole,"lead_ds") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Developers Showcase)<br />
								<input type="checkbox" name="uRole_lead_mae" id="uRole_lead_mae"<?php if(strpos($uRole,"lead_mae") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Mobile Apps for Education Showcase)<br />
								<input type="checkbox" name="uRole_lead_cotf" id="uRole_lead_cotf"<?php if(strpos($uRole,"lead_cotf") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Classroom of the Future)<br />
								<input type="checkbox" name="uRole_lead_ht" id="uRole_lead_ht"<?php if(strpos($uRole,"lead_ht") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Hot Topics)<br />
								<input type="checkbox" name="uRole_lead_grad" id="uRole_lead_grad"<?php if(strpos($uRole,"lead_grad") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Graduate Student Research)<br />
								<input type="checkbox" name="uRole_lead_classics" id="uRole_lead_classics"<?php if(strpos($uRole,"lead_classics") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Technology Fair Classics)
							</td>
<?php
		} else if(strpos($_SESSION['user_role'],"admin") !== false) {
?>
							<td>
								<input type="checkbox" name="uRole_reviewer_fairs" id="uRole_reviewer_fairs"<?php if(strpos($uRole,"reviewer_fairs") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Technology Fairs)<br />
								<input type="checkbox" name="uRole_reviewer_mini" id="uRole_reviewer_mini"<?php if(strpos($uRole,"reviewer_mini") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Mini-Workshops)<br />
								<input type="checkbox" name="uRole_reviewer_ds" id="uRole_reviewer_ds"<?php if(strpos($uRole,"reviewer_ds") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Developers Showcase)<br />
								<input type="checkbox" name="uRole_reviewer_mae" id="uRole_reviewer_mae"<?php if(strpos($uRole,"reviewer_mae") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Mobile Apps for Education Showcase)<br />
								<input type="checkbox" name="uRole_reviewer_cotf" id="uRole_reviewer_cotf"<?php if(strpos($uRole,"reviewer_cotf") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Classroom of the Future)<br />
								<input type="checkbox" name="uRole_reviewer_ht" id="uRole_reviewer_ht"<?php if(strpos($uRole,"reviewer_ht") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Hot Topics)<br />
								<input type="checkbox" name="uRole_reviewer_grad" id="uRole_reviewer_grad"<?php if(strpos($uRole,"reviewer_grad") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Graduate Student Research)<br />
								<input type="checkbox" name="uRole_reviewer_classics" id="uRole_reviewer_classics"<?php if(strpos($uRole,"reviewer_classics") !== false) { ?> checked="true"<?php } ?> /> Reviewer (Technology Fair Classics)<br />
								<input type="checkbox" name="uRole_lead_fairs" id="uRole_lead_fairs"<?php if(strpos($uRole,"lead_fairs") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Technology Fairs)<br />
								<input type="checkbox" name="uRole_lead_mini" id="uRole_lead_mini"<?php if(strpos($uRole,"lead_mini") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Mini-Workshops)<br />
								<input type="checkbox" name="uRole_lead_ds" id="uRole_lead_ds"<?php if(strpos($uRole,"lead_ds") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Developers Showcase)<br />
								<input type="checkbox" name="uRole_lead_mae" id="uRole_lead_mae"<?php if(strpos($uRole,"lead_mae") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Mobile Apps for Education Showcase)<br />
								<input type="checkbox" name="uRole_lead_cotf" id="uRole_lead_cotf"<?php if(strpos($uRole,"lead_cotf") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Classroom of the Future)<br />
								<input type="checkbox" name="uRole_lead_ht" id="uRole_lead_ht"<?php if(strpos($uRole,"lead_ht") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Hot Topics)<br />
								<input type="checkbox" name="uRole_lead_grad" id="uRole_lead_grad"<?php if(strpos($uRole,"lead_grad") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Graduate Student Research)<br />
								<input type="checkbox" name="uRole_lead_classics" id="uRole_lead_classics"<?php if(strpos($uRole,"lead_classics") !== false) { ?> checked="true"<?php } ?> /> Event Lead (Technology Fair Classics)<br />
								<input type="checkbox" name="uRole_chair" id="uRole_chair"<?php if(strpos($uRole,"chair") !== false) { ?> checked="true"<?php } ?> /> Chair<br />
								<input type="checkbox" name="uRole_admin" id="uRole_admin"<?php if(strpos($uRole,"admin") !== false) { ?> checked="true"<?php } ?> /> Administrator
							</td>
<?php
		}
?>
						</tr>
						<tr>
							<td align="center" colspan="2"><input type="button" value="Cancel" onClick="window.location.href='userList.php'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Submit" onClick="checkForm()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Delete User" onClick="deleteUser()" /></td>
						</tr>
					</table>
					<form name="userForm" id="userForm" method="post" action="">
						<input type="hidden" name="editUID" id="editUID" value="<?php echo $uID; ?>" />
						<input type="hidden" name="editUFirstName" id="editUFirstName" value="" />
						<input type="hidden" name="editULastName" id="editULastName" value="" />
						<input type="hidden" name="editUName" id="editUName" value="" />
						<input type="hidden" name="editUPass" id="editUPass" value="" />
<?php
		if(isset($elStr) && $elStr != "") {
?>
						<input type="hidden" name="editURole" id="editURole" value="<?php echo $elStr; ?>" />
<?php
		} else {
?>
						<input type="hidden" name="editURole" id="editURole" value="" />
<?php
		}
?>
					</form>
<?php
		include "adminBottom.php";
	}
?>