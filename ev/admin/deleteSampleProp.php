<?php
	//deleteSampleProp.php - allows a user to delete a sample proposal from the database
	//accessible only to admin users
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false && strpos($_SESSION['user_role'],"lead_") === false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$topTitle = "Delete Sample Proposal";

	if(isset($_POST["proposal_id"]) && isset($_POST["delOK"]) && $_POST["delOK"] == "Y") {
		$id = preg_replace("/\D/","",$_POST["proposal_id"]);
		
		//Update the user information in the database
		$eStmt = $db->prepare("DELETE FROM `sample_proposals` WHERE `id` = ? LIMIT 1");
		$eStmt->bind_param('s', $id);
		
		if(!$eStmt->execute()) {
			echo $eStmt->error;
			exit();
		}
		
		//If we get this far, then show the sucess message
		include "adminTop.php";
?>
					<h3 align="center">The sample proposal was deleted successfully!</h3>
					<p align="center"><a href="samplePropList.php">Back to Sample Proposal List</a></p>
<?php
		include "adminBottom.php";
		
		exit();
	}
	
	//Get the user information
	$id = isset($_GET["id"]) ? strip_tags($_GET["id"]) : "";
	if($id == "") {
		echo "No sample proposal ID was given!";
		exit();
	}
	
	$pStmt = $db->prepare("SELECT sp.id AS id, e.event AS event, sp.title AS title, sp.summary AS summary, sp.abstract AS abstract FROM sample_proposals AS sp, events AS e WHERE e.id = sp.event AND sp.id = ? LIMIT 1");
	$pStmt->bind_param('s',$id);
	$pStmt->execute();
	$pStmt->store_result();
	if($pStmt->num_rows < 1) {
		echo "No sample proposal was found with that id! (Error: ".$pStmt->error.")";
		exit();
	}
	
	$pStmt->bind_result($propID,$propEvent,$propTitle,$propSummary,$propAbstract);
	$pStmt->fetch();
	
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
				
							window.location.href = 'editSampleProp.php?id=<?=$id?>';
						}
			
						function deleteUser() {
							document.getElementById('delOK').value = 'Y';
							document.getElementById('deleteForm').submit();
						}
					</script>

					<table border="0" align="center" cellpadding="5">
						<tr>
							<td>You are about to delete this sample proposal from this event. Deleting this sample proposal will remove it from the database. This means that the proposal data will no longer exist in any part of the proposals system. If you only want to change the visibility of this sample proposal, then please edit the sample proposals information by going to the <a href="editSampleProp.php?id=<?php echo $id; ?>">Edit Sample Proposal page</a>.</td>
						</tr>
						<tr>
							<td>
								<table border="0" align="center" cellpadding="5" cellspacing="0">
									<tr>
										<td style="font-weight: bold">Event:</td>
										<td><?php echo $propEvent; ?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Title:</td>
										<td><?php echo $propTitle; ?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Summary:</td>
										<td><?php echo $propSummary; ?></td>
									</tr>
									<tr>
										<td style="font-weight: bold">Abstract:</td>
										<td><?php echo $propAbstract; ?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center" style="font-weight: bold; font-size: 20pt; padding-top: 20px">
								Are you sure you want to delete this sample proposal?<br />&nbsp; 
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
					<form name="deleteForm" id="deleteForm" method="post" action="deleteSampleProp.php">
						<input type="hidden" name="proposal_id" id="proposal_id" value="<?php echo $id; ?>" />
						<input type="hidden" name="delOK" id="delOK" value="N" />
					</form>
<?php
	include "adminBottom.php";
?>