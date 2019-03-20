<?php
	//deleteOther.php - allows a user to delete a presentation from the Other event (for events without proposals in the system)
	//accessible only to chairs, and admin users
	
	include_once "login.php";
	$topTitle = "Delete Presentation (Classics)";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false && strpos($_SESSION['user_role'],"_classics") === false) {
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
		
	if(isset($_POST["del_id"])) $propID = strip_tags($_POST["del_id"]);
	else {
		echo "No proposal ID was given!";
		exit();
	}
	
	//Get the proposal information from the database
	$q_stmt = $db->prepare("SELECT `id`, `title`, `presenters` FROM `classics_proposals` WHERE `id` = ?");
	$q_stmt->bind_param('s',$propID);
	$q_stmt->execute();
	$q_stmt->store_result();
	$q_stmt->bind_result($tmpID, $tmpTitle, $tmpPres);
	$q_stmt->fetch();
		
	$propData = array(
		"id" => $tmpID,
		"title" => $tmpTitle,
		"presenters" => $tmpPres
	);
	
	//get the presenters information
	$pr_stmt = $db->prepare("SELECT `ID`,`First Name`,`Last Name` FROM `classics_presenters` WHERE 1");
	$pr_stmt->execute();
	$pr_stmt->bind_result($prID,$prFN,$prLN);
	$presenters = array();
	$tmpPR = explode("|",$propData["presenters"]);
	while($pr_stmt->fetch()) {
		for($p = 0; $p < count($tmpPR); $p++) {
			if($prID == $tmpPR[$p]) { //this presenter is in this presentation
				$presenters[] = array(
					"id" => $prID,
					"first_name" => $prFN,
					"last_name" => $prLN
				);
				
				break;
			}
		}
	}
	
	if(isset($_POST["delOK"]) && $_POST["delOK"] == "Y") {
		//the user clicked "YES", so delete the proposal
		
		$q_stmt = $db->prepare("DELETE FROM `classics_proposals` WHERE `id` = ? LIMIT 1");
		$q_stmt->bind_param('s',$propID);
		if($q_stmt->execute()) {
			include "adminTop.php";
?>
	<h3 align="center">Successfully deleted!</h3>
	<p align="center"><a href="listProps_classics.php">Back to Presentation List</a></p>
<?php
			include "adminBottom.php";
			exit();
		} else {
			echo $q_stmt->error;
			exit();
		}
	}
	
	//Confirm they want to withdraw their proposal
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
				
				window.location.href = 'editClassics.php?id=<?=$propID?>';
			}
			
			function delProp() {
				document.getElementById('delOK').value = 'Y';
				document.getElementById('delForm').submit();
			}
		</script>
		<table align="center" border="0" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 14pt">You are deleting the following presentation:<br />
					<table border="0" align="center" cellpadding="10" cellspacing="0">
						<tr>
							<td style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Title:</td>
							<td style="padding-bottom: 10px; font-size: 12pt"><?=$propData["title"]?></td>
						</tr>
						<tr>
							<td valign="top" style="padding-left: 20px; padding-bottom: 10px; font-weight: bold; font-size: 12pt">Presenters:</td>
							<td valign="top" style="padding-bottom: 10px; font-size: 12pt">
<?php
	for($p = 0; $p < count($presenters); $p++) {
		echo $presenters[$p]["first_name"]." ".$presenters[$p]["last_name"]."<br />";
	}
?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" style="font-weight: bold; font-size: 20pt">
					Are you sure you want to delete this presentation?<br />&nbsp; 
				</td>
			</tr>
			<tr>
				<td>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td width="50%" align="center">
								<input type="button" value="Yes" style="background-color: green" onClick="delProp()" />
							</td>
							<td width="50%" align="center">
								<input type="button" value="No" style="background-color: red" onClick="cancelDelete()" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<form name="delForm" id="delForm" method="post" action="">
			<input type="hidden" name="del_id" id="del_id" value="<?=$propID?>" />
			<input type="hidden" name="delOK" id="delOK" value="N" />
		</form>
<?php
	include "adminBottom.php";
?>