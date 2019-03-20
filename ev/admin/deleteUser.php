<?php
	//deleteUser.php - allows a user to delete a user from the database
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
	
	if(strpos($_SESSION['user_role'],"lead_") !== false && isset($_GET["r"]) && $_GET["r"] != "1") $topTitle = "Delete Reviewer";
	else if(strpos($_SESSION['user_role'],"lead_") !== false && $_GET["r"] == "1") $topTitle = "Remove Reviewer";
	else $topTitle = "Delete User";

	if(isset($_POST["username"]) && isset($_POST["delOK"]) && $_POST["delOK"] == "Y") {
		if(isset($_POST["revOnly"]) && $_POST["revOnly"] == "Y") { //only remove the user from this reviewer role
			//First get the user information
			$uStmt = $db->prepare("SELECT `username`,`role` FROM `users` WHERE `username` = ? LIMIT 1");
			$uStmt->bind_param('s', $_POST['username']);
			$uStmt->execute();
			$uStmt->store_result();
			$uStmt->bind_result($cu_username, $cu_role);
			$uStmt->fetch();
			$uStmt->close();
			
			$delRole = str_replace("lead_","reviewer_",$_SESSION["user_role"]);
			$newRole = str_replace($delRole,"",$cu_role);
			$newRole = str_replace("||","|",$newRole); //remove the role might leave 2 ||, so we need to change the 2 into 1
			$eStmt = $db->prepare("UPDATE `users` SET `role` = ? WHERE `username` = ? LIMIT 1");
			$eStmt->bind_param('ss', $newRole, $_POST['username']);
		} else {
			//Update the user information in the database
			$eStmt = $db->prepare("DELETE FROM `users` WHERE `username` = ? LIMIT 1");
			$eStmt->bind_param('s', $_POST["username"]);
		}
		
		if(!$eStmt->execute()) {
			echo $eStmt->error;
			exit();
		}
		
		//If we get this far, then show the sucess message
		include "adminTop.php";
		if(strpos($_SESSION['user_role'],"lead_") !== false) {
?>
					<h3 align="center">The reviewer was deleted successfully!</h3>
					<p align="center"><a href="userList.php">Back to Reviewer List</a></p>
<?php
		} else {
?>
					<h3 align="center">The user deleted successfully!</h3>
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
	
	$uStmt = $db->prepare("SELECT `username`,`first_name`,`last_name`,`role` FROM `users` WHERE `username` = ? LIMIT 1");
	$uStmt->bind_param('s',$user);
	$uStmt->execute();
	$uStmt->store_result();
	if($uStmt->num_rows < 1) {
		echo "No user found with that username! (Error: ".$uStmt->error.")";
		exit();
	}
	
	$uStmt->bind_result($uName,$fName,$lName,$uRole);
	$uStmt->fetch();
	
	include "adminTop.php";
?>
					<style type="text/css">
						input[type='button'] {
							font-weight: bold;
							font-size: 20pt;
							border: solid 1px #000000;
							height: 50px;
							width: 200px;
						}
					</style>
					<script type="text/javascript">
						function cancelDelete() {
							//If they cancel, we will direct them back to the edit.php page,
							//which should be the page they just came from
				
							window.location.href = 'editUser.php?u=<?=$uName?>';
						}
			
						function deleteUser() {
							document.getElementById('delOK').value = 'Y';
							document.getElementById('deleteForm').submit();
						}
					</script>
<?php
	if($_GET["r"] == "1") { //this is a reviewer only being removed from the reviewer role
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>You are about to remove this Reviewer from your list. They will not have access to any proposals for your event and any proposals assigned to them currently will be unassigned.</td>
						</tr>
						<tr>
							<td>
								<table border="0" align="center" cellpadding="5" cellspacing="0">
									<tr>
										<td style="font-weight: bold">First Name:</td>
										<td><?=$fName?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Last Name:</td>
										<td><?=$lName?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Email Address:</td>
										<td><?=$uName?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center" style="font-weight: bold; font-size: 20pt; padding-top: 20px">
								Are you sure you want to remove this reviewer?<br />&nbsp; 
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" cellspacing="0" cellpadding="0" width="100%">
									<tr>
										<td width="50%" align="center">
											<input type="button" value="Yes" style="background-color: green" onClick="deleteUser()" />
										</td>
										<td width="50%" align="center">
											<input type="button" value="No" style="background-color: red" onClick="cancelDelete()" />
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<form name="deleteForm" id="deleteForm" method="post" action="deleteUser.php?r=1">
						<input type="hidden" name="username" id="username" value="<?=$uName?>" />
						<input type="hidden" name="delOK" id="delOK" value="N" />
						<input type="hidden" name="revOnly" id="revOnly" value="Y" />
					</form>
<?php	
	} else {
?>
					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>Deleting this user will remove the user from the database. This means that the user will not have access to any part of the proposals system. If you only want to change the user's role(s), then please edit the user's information by going to the <a href="editUser.php?u=<?=$uName?>">Edit User page</a>.</td>
						</tr>
						<tr>
							<td>
								<table border="0" align="center" cellpadding="5" cellspacing="0">
									<tr>
										<td style="font-weight: bold">First Name:</td>
										<td><?=$fName?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Last Name:</td>
										<td><?=$lName?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Email Address:</td>
										<td><?=$uName?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center" style="font-weight: bold; font-size: 20pt; padding-top: 20px">
								Are you sure you want to delete this user?<br />&nbsp; 
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" cellspacing="0" cellpadding="0" width="100%">
									<tr>
										<td width="50%" align="center">
											<input type="button" value="Yes" style="background-color: green" onClick="deleteUser()" />
										</td>
										<td width="50%" align="center">
											<input type="button" value="No" style="background-color: red" onClick="cancelDelete()" />
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<form name="deleteForm" id="deleteForm" method="post" action="deleteUser.php">
						<input type="hidden" name="username" id="username" value="<?=$uName?>" />
						<input type="hidden" name="delOK" id="delOK" value="N" />
					</form>
<?php
	}
	
	include "adminBottom.php";
?>