<?php
	//changeRole.php - allows the user to select a user role (when multiple roles are assigned to one user)
	//accessible only to admin users
	
	include_once "login.php";
	
	if(isset($_POST["userRole"]) && $_POST["userRole"] != "") { //the user selected a role
		$_SESSION["user_role"] = $_POST["userRole"];
		$_SESSION["role_selected"] = "1";
		header("Location: index.php");
	}
	
	//If a user has multiple roles assigned, then the first thing they need to do is choose a role
	//The first time they login, the highest role is chosen (e.g. admin, chair, lead and then reviewer)
	$uStmt = $db->prepare("SELECT `role` FROM `users` WHERE `username` = ? LIMIT 1");
	$uStmt->bind_param('s',$_SESSION['user_name']);
	$uStmt->execute();
	$uStmt->bind_result($role);
	$uStmt->fetch();

	if(strpos($role,"|") !== false) { //multiple roles are assigned to this user
		$topTitle = "Select Role";
	
		include "adminTop.php";
?>
		<script type="text/javascript">
			function changeRole() {
				var rEl = document.getElementsByName('uRole');
				for(i = 0; i < rEl.length; i++) {
					if(rEl[i].checked) {
						document.getElementById('userRole').value = rEl[i].value;
						document.getElementById('roleForm').submit();
					}
				}
			}
			
			function selectRole(e) {
				var rEl = document.getElementsByName('uRole');
				for(i = 0; i < rEl.length; i++) {
					if(rEl[i].value == e) {
						rEl[i].checked = true;
						return;
					}
				}
			}
		</script>
		<p align="center">You are assigned multiple roles in the proposals system.<br />Please choose one of the roles below.</p>
		<table align="center">
			<tr>
				<td>
<?php
		$tmpRoles = explode("|",$role);
		//We want to list all Reviewer roles first, then lead roles, then chair, and then admin
		for($r = 0; $r < count($tmpRoles); $r++) {
			$reviewerRole = "";
			if($tmpRoles[$r] == "reviewer_fairs") $reviewerRole = "Reviewer (Technology Fairs)";
			else if($tmpRoles[$r] == "reviewer_mini") $reviewerRole = "Reviewer (Mini-Workshops)";
			else if($tmpRoles[$r] == "reviewer_ds") $reviewerRole = "Reviewer (Developers Showcase)";
			else if($tmpRoles[$r] == "reviewer_mae") $reviewerRole = "Reviewer (Mobile Apps for Education Showcase)";
			else if($tmpRoles[$r] == "reviewer_cotf") $reviewerRole = "Reviewer (Classroom of the Future)";
			else if($tmpRoles[$r] == "reviewer_ht") $reviewerRole = "Reviewer (Hot Topics)";
			else if($tmpRoles[$r] == "reviewer_grad") $reviewerRole = "Reviewer (Graduate Student Research)";
			else if($tmpRoles[$r] == "reviewer_classics") $reviewerRole = "Reviewer (Technology Fair Classics)";
			
			if($reviewerRole == "" || !isset($reviewerRole)) continue;
?>
					<input type="radio" name="uRole" value="<?php echo $tmpRoles[$r]; ?>"<?php if($tmpRoles[$r] == $_SESSION['user_role']) { ?> checked="true"<?php } ?> /> <span onClick="selectRole('<?php echo $tmpRoles[$r]; ?>')"><?php echo $reviewerRole; ?></span><br />
<?php
		}
		
		for($r = 0; $r < count($tmpRoles); $r++) {
			$leadRole = "";
			if($tmpRoles[$r] == "lead_fairs") $leadRole = "Event Lead (Technology Fairs)";
			else if($tmpRoles[$r] == "lead_mini") $leadRole = "Event Lead (Mini-Workshops)";
			else if($tmpRoles[$r] == "lead_ds") $leadRole = "Event Lead (Developers Showcase)";
			else if($tmpRoles[$r] == "lead_mae") $leadRole = "Event Lead (Mobile Apps for Education Showcase)";
			else if($tmpRoles[$r] == "lead_cotf") $leadRole = "Event Lead (Classroom of the Future)";
			else if($tmpRoles[$r] == "lead_ht") $leadRole = "Event Lead (Hot Topics)";
			else if($tmpRoles[$r] == "lead_grad") $leadRole = "Event Lead (Graduate Student Research)";
			else if($tmpRoles[$r] == "lead_classics") $leadRole = "Event Lead (Technology Fair Classics)";
			
			if($leadRole == "" || !isset($leadRole)) continue;
				if($reviewerRole != "") {
?>
					<br />
					<hr style="color: #CCCCCC" noshade>
					<br />
<?php
					$reviewerRole = "";
				}
?>
					<input type="radio" name="uRole" value="<?php echo $tmpRoles[$r]; ?>"<?php if($tmpRoles[$r] == $_SESSION['user_role']) { ?> checked="true"<?php } ?> /> <span onClick="selectRole('<?php echo $tmpRoles[$r]; ?>')"><?php echo $leadRole?></span><br />
<?php
		}
		
		for($r = 0; $r < count($tmpRoles); $r++) {
			$otherRole = "";
			if($tmpRoles[$r] == "admin") $otherRole = "Administrator";
			else if($tmpRoles[$r] == "chair") $otherRole = "Chair";
			
			if(!isset($otherRole) || $otherRole == "") continue;
				if($leadRole != "") {
?>
					<br />
					<hr style="color: #CCCCCC" noshade>
					<br />
<?php
					$leadRole = "";
				}
?>
					<input type="radio" name="uRole" value="<?php echo $tmpRoles[$r]; ?>"<?php if($tmpRoles[$r] == $_SESSION['user_role']) { ?> checked="true"<?php } ?> /> <span onClick="selectRole('<?php echo $tmpRoles[$r]; ?>')"><?php echo $otherRole?></span><br />
<?php
		}
?>
				</td>
			</tr>
			<tr>
				<td align="center" style="padding-top:20px"><input type="button" value="Submit" onClick="changeRole()" /></td>
			</tr>
		</table>
		<form name="roleForm" id="roleForm" method="post" action="">
			<input type="hidden" name="userRole" id="userRole" value="<?php echo $_SESSION['user_role']; ?>" />
		</form>
<?php
		include "adminBottom.php";
	} else { //only one role for this user
		include "adminTop.php";
?>
		<h2 align="center">You are only assigned to one role.</h2>
		<p align="center"><a href="index.php">Back to Main Menu</a></p>
<?php
		include "adminBottom.php";
	}
?>