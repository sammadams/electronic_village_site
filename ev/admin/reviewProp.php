<?php
	//reviewProp.php -- lets a reviewer view and save a score for a proposal
	//accessible only to admin users
	//includes views for chair, admin, event leads, and reviewers
	
	$topTitle = "Review Proposal";

	include "login.php";
	
	if(isset($_POST['reviewScore'])) { //a score was submitted
		//First, make sure we have every piece of information we need
		$score = strip_tags($_POST['reviewScore']);		
		$comments = strip_tags($_POST['reviewComments']);
		$reviewer = $_SESSION['user_name'];
		$id = strip_tags($_POST['reviewID']);
		$event = strip_tags($_POST["reviewEvent"]);
		
		if(isset($_POST["editID"]) && $_POST["editID"] != "") { //this review was edited, so just update
			$rID = strip_tags($_POST["editID"]);
			$r_stmt = $db->prepare("UPDATE `reviews` SET `review` = ?, `comments` = ? WHERE `id` = ?");
			$r_stmt->bind_param('sss',$score,$comments,$rID);
			if(!$r_stmt->execute()) {
				echo $r_stmt->error;
				exit();
			}
		} else { //insert a new review
			$r_stmt = $db->prepare("INSERT INTO `reviews` (`id`,`prop_id`,`reviewer`,`event`,`review`,`comments`) VALUES('0',?,?,?,?,?)");
			$r_stmt->bind_param('sssss',$id,$reviewer,$event,$score,$comments);
			if(!$r_stmt->execute()) {
				echo $r_stmt->error;
				exit();
			}
		}
		
		//if we get here, then everything was completed successfully, so go back to the list
		header('Location: reviewerList.php');
	}
	
	//gets the id of the proposal so we can retrieve the proposal information
	$id = isset($_GET["id"]) ? strip_tags($_GET["id"]) : strip_tags($_POST["id"]);
	if($id == "") {
		echo "No ID given!";
		exit();
	}
	
	//gets the id of any review already completed by this reviewer (contained in the URL)
	$eID = isset($_GET["editID"]) ? strip_tags($_GET["editID"]) : "";
	
	//Get the proposal information
	$q_stmt = $db->prepare("SELECT * FROM `proposals` WHERE `id` = ?");
	$q_stmt->bind_param('s',$id);
	$q_stmt->execute();
	$q_stmt->store_result();
	$q_stmt->bind_result($tmpID, $tmpTitle, $tmpContact, $tmpPresenters, $tmpTimes, $tmpTopics, $tmpComputer, $tmpSummary, $tmpAbstract, $tmpPass, $tmpSalt, $tmpComments, $tmpPhotoOK, $tmpEmailOK, $tmpType, $tmpStatus, $tmpCertificate);
	$q_stmt->fetch();

	//Get the information for any review already completed by this reviewer
	if($eID != "") {
		$r_stmt = $db->prepare("SELECT * FROM `reviews` WHERE `id` = ?");
		$r_stmt->bind_param('s',$eID);
		$r_stmt->execute();
		$r_stmt->store_result();
		$r_stmt->bind_result($tmpRID,$tmpRPropID,$tmpRReviewer,$tmpEvent,$tmpRScore,$tmpRComments);
		$r_stmt->fetch();
	} else { //set the variables to empty so we don't throw any warning messages
		$tmpRID = "";
		$tmpRPropID = "";
		$tmpRReviewer = "";
		$tmpEvent = "";
		$tmpRScore = "";
		$tmpRComments = "";
	}
	
	//If the user is a reviewer or an event lead, we need to make sure they are allowed to view this proposal
	if(strpos($_SESSION['user_role'],"lead_") !== false || strpos($_SESSION['user_role'],"reviewer_") !== false) {
		if(strpos($_SESSION['user_role'],"_fairs") !== false) $eType = 'Technology Fairs';
		else if(strpos($_SESSION['user_role'],"_mini") !== false) $eType = 'Mini-Workshops';
		else if(strpos($_SESSION['user_role'],"_ds") !== false) $eType = 'Developers Showcase';
		else if(strpos($_SESSION['user_role'],"_mae") !== false) $eType = 'Mobile Apps for Education Showcase';
		else if(strpos($_SESSION['user_role'],"_cotf") !== false) $eType = 'Classroom of the Future';
		else if(strpos($_SESSION['user_role'],"_ht") !== false) $eType = 'Hot Topics';
		else if(strpos($_SESSION['user_role'],"_grad") !== false) $eType = 'Graduate Student Research';
		else if(strpos($_SESSION['user_role'],"_classics") !== false) $eType = 'Technology Fair Classics';
		
		if($eType != $tmpType) { //access is now allowed for this user for the given proposal's event type
			include "adminTop.php";
?>
	<h3 align="center">You do not have permission to view this proposal!</h3>
	<p align="center">If you feel that this is an error, please contact your event lead or the Electronic Village chair.</p>
<?php
			include "adminBottom.php";
			exit();
		}
	}
	
	$topTitle .= " (".$tmpType.")";
	
	include "adminTop.php";
?>
	<table border="0" align="center" cellpadding="5">
		<tr>
			<th align="left" valign="top">Title:</th>
			<td><?php echo strip_tags(stripslashes($tmpTitle)); ?></td>
		</tr>
		<tr>
			<th align="left" valign="top">Summary:</th>
			<td><?php echo strip_tags(stripslashes($tmpSummary)); ?></td>
		</tr>
		<tr>
			<th align="left" valign="top">Abstract:</th>
			<td><?php echo strip_tags(stripslashes($tmpAbstract)); ?></td>
		</tr>
<?php
	if($tmpType == "Technology Fairs") {
?>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Recommendation:</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation" id="rRecommendation_1" value="1" onClick="updateScore()"<?php if($tmpRScore == 1) { ?> checked="true"<?php } ?> /></td>
			<td onClick="checkEl('rRecommendation_1')"><i>Reject:</i> content inappropriate to the Electronic Village or the proposal has little merit</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation" id="rRecommendation_2" value="2" onClick="updateScore()"<?php if($tmpRScore == 2) { ?> checked="true"<?php } ?> /></td>
			<td onClick="checkEl('rRecommendation_2')"><i>Probable Reject:</i> Basic flaws in content or proposal is poorly written</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation" id="rRecommendation_3" value="3" onClick="updateScore()"<?php if($tmpRScore == 3) { ?> checked="true"<?php } ?> /></td>
			<td onClick="checkEl('rRecommendation_3')"><i>Marginal Tend to Reject:</i> Major changes needed to make acceptable</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation" id="rRecommendation_4" value="4" onClick="updateScore()"<?php if($tmpRScore == 4) { ?> checked="true"<?php } ?> /></td>
			<td onClick="checkEl('rRecommendation_4')"><i>Marginal Tend to Accept:</i> Accuracy, clarity, completeness, and/or writing could be improved</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation" id="rRecommendation_5" value="5" onClick="updateScore()"<?php if($tmpRScore == 5) { ?> checked="true"<?php } ?> /></td>
			<td onClick="checkEl('rRecommendation_5')"><i>Clear Accept:</i> Acceptable as is, but improvements could be made</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation" id="rRecommendation_6" value="6" onClick="updateScore()"<?php if($tmpRScore == 6) { ?> checked="true"<?php } ?> /></td>
			<td onClick="checkEl('rRecommendation_6')"><i>Must Accept:</i> Outstanding submission; needs very little improvement</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Comments:<br /><span class="label">Please provide comments to support your recommendation above, including suggested improvements.</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><textarea name="rComments" id="rComments" rows="3" cols="80" onKeyUp="updateComments(this)"><?php echo stripslashes($tmpRComments); ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="button" value="Cancel" onClick="cancelReview()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Save" onClick="saveReview()" /></td>
		</tr>
	</table>
	<form name="reviewForm" id="reviewForm" method="post" action="">
		<input type="hidden" name="reviewScore" id="reviewScore" value="<?php echo $tmpRScore; ?>" />
		<input type="hidden" name="reviewComments" id="reviewComments" value="<?php echo $tmpRComments; ?>" />
		<input type="hidden" name="reviewID" id="reviewID" value="<?php echo $id; ?>" />
		<input type="hidden" name="editID" id="editID" value="<?php echo $eID; ?>" />
		<input type="hidden" name="reviewEvent" id="reviewEvent" value="<?php echo $tmpType; ?>" />
	</form>
	<script type="text/javascript">
		function checkEl(elStr) {
			document.getElementById(elStr).checked = true;
			updateScore();
		}
		
		function updateScore() {
			var el = document.getElementsByName('rRecommendation');
			for(i = 0; i < el.length; i++) {
				if(el[i].checked) {
					document.getElementById('reviewScore').value = el[i].value;
					break;
				}
			}
		}
		
		function updateComments(el) {
			document.getElementById('reviewComments').value = el.value;
		}
		
		function cancelReview() {
			var cancelOK = confirm('Are you sure you want to cancel?\n\nClick "OK" to cancel, or click "Cancel" to stay on this page.');
			if(cancelOK) window.location.href = 'reviewerList.php';
			else return false;
		}
		
		function saveReview() {
			if(document.getElementById('reviewScore').value == 0) {
				alert('You did not make a recommendation for this proposal!');
				return false;
			}
			
			if(document.getElementById('reviewComments').value == '') {
				alert('Please include some comments that support your recommendation!');
				return false;
			}
			
			//If we get this far, then we can submit the form
			document.getElementById('reviewForm').submit();
		}
		
		//updates the score fields in case a review is being edited and already has a score
		window.onload = function() {
			updateScore();
		};
	</script>
<?php
	} else if($tmpType == "Mini-Workshops") {
		if($tmpRScore != "") $tmpScore = explode("|",$tmpRScore);
		else $tmpScore = array(0,0,0,0,0,0);
?>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Rating:</td>
		</tr>
		<tr>
			<td style="text-align: right; vertical-align: top"><select name="rRecommendation_style_content" id="rRecommendation_style_content" onChange="updateScore()"><option>*</option><option<?php if($tmpScore[0] == 1) { ?> selected="true"<?php } ?>>1</option><option<?php if($tmpScore[0] == 2) { ?> selected="true"<?php } ?>>2</option><option<?php if($tmpScore[0] == 3) { ?> selected="true"<?php } ?>>3</option><option<?php if($tmpScore[0] == 4) { ?> selected="true"<?php } ?>>4</option><option<?php if($tmpScore[0] == 5) { ?> selected="true"<?php } ?>>5</option><option<?php if($tmpScore[0] == 6) { ?> selected="true"<?php } ?>>6</option><option<?php if($tmpScore[0] == 7) { ?> selected="true"<?php } ?>>7</option><option<?php if($tmpScore[0] == 8) { ?> selected="true"<?php } ?>>8</option><option<?php if($tmpScore[0] == 9) { ?> selected="true"<?php } ?>>9</option><option<?php if($tmpScore[0] == 10) { ?> selected="true"<?php } ?>>10</option><option<?php if($tmpScore[0] == 11) { ?> selected="true"<?php } ?>>11</option><option<?php if($tmpScore[0] == 12) { ?> selected="true"<?php } ?>>12</option><option<?php if($tmpScore[0] == 13) { ?> selected="true"<?php } ?>>13</option><option<?php if($tmpScore[0] == 14) { ?> selected="true"<?php } ?>>14</option><option<?php if($tmpScore[0] == 15) { ?> selected="true"<?php } ?>>15</option><option<?php if($tmpScore[0] == 16) { ?> selected="true"<?php } ?>>16</option><option<?php if($tmpScore[0] == 17) { ?> selected="true"<?php } ?>>17</option><option<?php if($tmpScore[0] == 18) { ?> selected="true"<?php } ?>>18</option><option<?php if($tmpScore[0] == 19) { ?> selected="true"<?php } ?>>19</option><option<?php if($tmpScore[0] == 20) { ?> selected="true"<?php } ?>>20</option></select></td>
			<td><i>Style & Content:</i> Is the proposal written clearly, properly, and convincingly?</td>
		</tr>
		<tr>
			<td style="text-align: right; vertical-align: top"><select name="rRecommendation_value" id="rRecommendation_value" onChange="updateScore()"><option>*</option><option<?php if($tmpScore[1] == 1) { ?> selected="true"<?php } ?>>1</option><option<?php if($tmpScore[1] == 2) { ?> selected="true"<?php } ?>>2</option><option<?php if($tmpScore[1] == 3) { ?> selected="true"<?php } ?>>3</option><option<?php if($tmpScore[1] == 4) { ?> selected="true"<?php } ?>>4</option><option<?php if($tmpScore[1] == 5) { ?> selected="true"<?php } ?>>5</option><option<?php if($tmpScore[1] == 6) { ?> selected="true"<?php } ?>>6</option><option<?php if($tmpScore[1] == 7) { ?> selected="true"<?php } ?>>7</option><option<?php if($tmpScore[1] == 8) { ?> selected="true"<?php } ?>>8</option><option<?php if($tmpScore[1] == 9) { ?> selected="true"<?php } ?>>9</option><option<?php if($tmpScore[1] == 10) { ?> selected="true"<?php } ?>>10</option><option<?php if($tmpScore[1] == 11) { ?> selected="true"<?php } ?>>11</option><option<?php if($tmpScore[1] == 12) { ?> selected="true"<?php } ?>>12</option><option<?php if($tmpScore[1] == 13) { ?> selected="true"<?php } ?>>13</option><option<?php if($tmpScore[1] == 14) { ?> selected="true"<?php } ?>>14</option><option<?php if($tmpScore[1] == 15) { ?> selected="true"<?php } ?>>15</option><option<?php if($tmpScore[1] == 16) { ?> selected="true"<?php } ?>>16</option><option<?php if($tmpScore[1] == 17) { ?> selected="true"<?php } ?>>17</option><option<?php if($tmpScore[1] == 18) { ?> selected="true"<?php } ?>>18</option><option<?php if($tmpScore[1] == 19) { ?> selected="true"<?php } ?>>19</option><option<?php if($tmpScore[1] == 20) { ?> selected="true"<?php } ?>>20</option></select></td>
			<td><i>Value:</i> Does the proposal address a current and meaningful application or issue of high interest to CALL enthusiasts?</td>
		</tr>
		<tr>
			<td style="text-align: right; vertical-align: top"><select name="rRecommendation_practicality" id="rRecommendation_practicality" onChange="updateScore()"><option>*</option><option<?php if($tmpScore[2] == 1) { ?> selected="true"<?php } ?>>1</option><option<?php if($tmpScore[2] == 2) { ?> selected="true"<?php } ?>>2</option><option<?php if($tmpScore[2] == 3) { ?> selected="true"<?php } ?>>3</option><option<?php if($tmpScore[2] == 4) { ?> selected="true"<?php } ?>>4</option><option<?php if($tmpScore[2] == 5) { ?> selected="true"<?php } ?>>5</option><option<?php if($tmpScore[2] == 6) { ?> selected="true"<?php } ?>>6</option><option<?php if($tmpScore[2] == 7) { ?> selected="true"<?php } ?>>7</option><option<?php if($tmpScore[2] == 8) { ?> selected="true"<?php } ?>>8</option><option<?php if($tmpScore[2] == 9) { ?> selected="true"<?php } ?>>9</option><option<?php if($tmpScore[2] == 10) { ?> selected="true"<?php } ?>>10</option><option<?php if($tmpScore[2] == 11) { ?> selected="true"<?php } ?>>11</option><option<?php if($tmpScore[2] == 12) { ?> selected="true"<?php } ?>>12</option><option<?php if($tmpScore[2] == 13) { ?> selected="true"<?php } ?>>13</option><option<?php if($tmpScore[2] == 14) { ?> selected="true"<?php } ?>>14</option><option<?php if($tmpScore[2] == 15) { ?> selected="true"<?php } ?>>15</option><option<?php if($tmpScore[2] == 16) { ?> selected="true"<?php } ?>>16</option><option<?php if($tmpScore[2] == 17) { ?> selected="true"<?php } ?>>17</option><option<?php if($tmpScore[2] == 18) { ?> selected="true"<?php } ?>>18</option><option<?php if($tmpScore[2] == 19) { ?> selected="true"<?php } ?>>19</option><option<?php if($tmpScore[2] == 20) { ?> selected="true"<?php } ?>>20</option></select></td>
			<td><i>Practicality:</i> Is the proposed presentation likely to offer hands-on experience to the participants?</td>
		</tr>
		<tr>
			<td style="text-align: right; vertical-align: top"><select name="rRecommendation_feasibility" id="rRecommendation_feasibility" onChange="updateScore()"><option>*</option><option<?php if($tmpScore[3] == 1) { ?> selected="true"<?php } ?>>1</option><option<?php if($tmpScore[3] == 2) { ?> selected="true"<?php } ?>>2</option><option<?php if($tmpScore[3] == 3) { ?> selected="true"<?php } ?>>3</option><option<?php if($tmpScore[3] == 4) { ?> selected="true"<?php } ?>>4</option><option<?php if($tmpScore[3] == 5) { ?> selected="true"<?php } ?>>5</option><option<?php if($tmpScore[3] == 6) { ?> selected="true"<?php } ?>>6</option><option<?php if($tmpScore[3] == 7) { ?> selected="true"<?php } ?>>7</option><option<?php if($tmpScore[3] == 8) { ?> selected="true"<?php } ?>>8</option><option<?php if($tmpScore[3] == 9) { ?> selected="true"<?php } ?>>9</option><option<?php if($tmpScore[3] == 10) { ?> selected="true"<?php } ?>>10</option><option<?php if($tmpScore[3] == 11) { ?> selected="true"<?php } ?>>11</option><option<?php if($tmpScore[3] == 12) { ?> selected="true"<?php } ?>>12</option><option<?php if($tmpScore[3] == 13) { ?> selected="true"<?php } ?>>13</option><option<?php if($tmpScore[3] == 14) { ?> selected="true"<?php } ?>>14</option><option<?php if($tmpScore[3] == 15) { ?> selected="true"<?php } ?>>15</option><option<?php if($tmpScore[3] == 16) { ?> selected="true"<?php } ?>>16</option><option<?php if($tmpScore[3] == 17) { ?> selected="true"<?php } ?>>17</option><option<?php if($tmpScore[3] == 18) { ?> selected="true"<?php } ?>>18</option><option<?php if($tmpScore[3] == 19) { ?> selected="true"<?php } ?>>19</option><option<?php if($tmpScore[3] == 20) { ?> selected="true"<?php } ?>>20</option></select></td>
			<td><i>Feasibility:</i> Is the session likely to be completed as planned within the 90-minute time limit?</td>
		</tr>
		<tr>
			<td style="text-align: right; vertical-align: top"><select name="rRecommendation_pedagogical_soundness" id="rRecommendation_pedagogical_soundness" onChange="updateScore()"><option>*</option><option<?php if($tmpScore[4] == 1) { ?> selected="true"<?php } ?>>1</option><option<?php if($tmpScore[4] == 2) { ?> selected="true"<?php } ?>>2</option><option<?php if($tmpScore[4] == 3) { ?> selected="true"<?php } ?>>3</option><option<?php if($tmpScore[4] == 4) { ?> selected="true"<?php } ?>>4</option><option<?php if($tmpScore[4] == 5) { ?> selected="true"<?php } ?>>5</option><option<?php if($tmpScore[4] == 6) { ?> selected="true"<?php } ?>>6</option><option<?php if($tmpScore[4] == 7) { ?> selected="true"<?php } ?>>7</option><option<?php if($tmpScore[4] == 8) { ?> selected="true"<?php } ?>>8</option><option<?php if($tmpScore[4] == 9) { ?> selected="true"<?php } ?>>9</option><option<?php if($tmpScore[4] == 10) { ?> selected="true"<?php } ?>>10</option><option<?php if($tmpScore[4] == 11) { ?> selected="true"<?php } ?>>11</option><option<?php if($tmpScore[4] == 12) { ?> selected="true"<?php } ?>>12</option><option<?php if($tmpScore[4] == 13) { ?> selected="true"<?php } ?>>13</option><option<?php if($tmpScore[4] == 14) { ?> selected="true"<?php } ?>>14</option><option<?php if($tmpScore[4] == 15) { ?> selected="true"<?php } ?>>15</option><option<?php if($tmpScore[4] == 16) { ?> selected="true"<?php } ?>>16</option><option<?php if($tmpScore[4] == 17) { ?> selected="true"<?php } ?>>17</option><option<?php if($tmpScore[4] == 18) { ?> selected="true"<?php } ?>>18</option><option<?php if($tmpScore[4] == 19) { ?> selected="true"<?php } ?>>19</option><option<?php if($tmpScore[4] == 20) { ?> selected="true"<?php } ?>>20</option></select></td>
			<td><i>Pedagogical soundness:</i> How well does the proposed plan promote pedagogical soundness?</td>
		</tr>
		<tr>
			<td style="font-weight: bold; text-align: right">Total:</td>
			<td id="rTotalScore"><?php echo $tmpScore[5]; ?> / 100</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Comments:<br /><span class="label">Please provide comments to support your ratings above, including suggested improvements.</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><textarea name="rComments" id="rComments" rows="3" cols="80" onKeyUp="updateComments(this)"><?php echo $tmpRComments; ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="button" value="Cancel" onClick="cancelReview()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Save" onClick="saveReview()" /></td>
		</tr>
	</table>
	<form name="reviewForm" id="reviewForm" method="post" action="">
		<input type="hidden" name="reviewScore" id="reviewScore" value="<?php echo $tmpRScore; ?>" />
		<input type="hidden" name="reviewComments" id="reviewComments" value="<?php echo $tmpRComments; ?>" />
		<input type="hidden" name="reviewID" id="reviewID" value="<?php echo $id; ?>" />
		<input type="hidden" name="editID" id="editID" value="<?php echo $eID; ?>" />
		<input type="hidden" name="reviewEvent" id="reviewEvent" value="<?php echo $tmpType; ?>" />
	</form>
	<script type="text/javascript">
		function updateScore() {
			var elArray = new Array('style_content','value','practicality','feasibility','pedagogical_soundness');
			var totalScore = 0;
			var scoreStr = '';
			for(i = 0; i < elArray.length; i++) {
				var thisEl = document.getElementById('rRecommendation_' + elArray[i]);
				var thisScore = thisEl.options[thisEl.selectedIndex].text;
				if(thisScore == '*') thisScore = 0;
				totalScore = parseInt(totalScore) + parseInt(thisScore);
				scoreStr += thisEl.options[thisEl.selectedIndex].text + '|';
			}
			
			scoreStr += totalScore;
			document.getElementById('rTotalScore').innerHTML = totalScore + ' / 100';
			document.getElementById('reviewScore').value = scoreStr;
		}
		
		function updateComments(el) {
			document.getElementById('reviewComments').value = el.value;
		}
		
		function cancelReview() {
			var cancelOK = confirm('Are you sure you want to cancel?\n\nClick "OK" to cancel, or click "Cancel" to stay on this page.');
			if(cancelOK) window.location.href = 'reviewerList.php';
			else return false;
		}
		
		function saveReview() {
			//Check to make sure we have all the information we need
			//Make sure a score was selected for each category
			var elArray = new Array('style_content','value','practicality','feasibility','pedagogical_soundness');
			for(i = 0; i < elArray.length; i++) {
				var thisEl = document.getElementById('rRecommendation_' + elArray[i]);
				if(thisEl.selectedIndex == 0) {
					alert('You did not select a score for each category!');
					return false;
				}
			}
			
			//We have a score for each category, so check for comments
			if(document.getElementById('reviewComments').value == '') {
				alert('Please include some comments to support your ratings!');
				return false;
			}
			
			//If we get this far, then we can submit
			document.getElementById('reviewForm').submit();
		}
	</script>
<?php
	} else if($tmpType == "Developers Showcase") {
	
	} else if($tmpType == "Mobile Apps for Education Showcase") {
		if($tmpRScore != "") $tmpScore = explode("|",$tmpRScore);
		else $tmpScore = array(0,0,0,0,0);
?>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Rating:</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Innovation</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_3" value="3" onClick="updateScore()"<?php if($tmpScore[0] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_2" value="2" onClick="updateScore()"<?php if($tmpScore[0] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_1" value="1" onClick="updateScore()"<?php if($tmpScore[0] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('innovation_3')">3 - new application (at least to me)</span><br /><span onClick="checkEl('innovation_2')">2 - new twist on an old app</span><br /><span onClick="checkEl('innovation_1')">1 - old hat (already in popular use)</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Usability</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_usability" id="rRecommendation_usability_3" value="3" onClick="updateScore()"<?php if($tmpScore[1] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_usability" id="rRecommendation_usability_2" value="2" onClick="updateScore()"<?php if($tmpScore[1] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_usability" id="rRecommendation_usability_1" value="1" onClick="updateScore()"<?php if($tmpScore[1] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('usability_3')">3 - plug 'n play</span><br /><span onClick="checkEl('usability_2')">2 - usable with some effort (registration, etc.)</span><br /><span onClick="checkEl('usability_1')">1 - cannot be used by others</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Format/Time</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_format" id="rRecommendation_format_3" value="3" onClick="updateScore()"<?php if($tmpScore[2] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_format" id="rRecommendation_format_2" value="2" onClick="updateScore()"<?php if($tmpScore[2] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_format" id="rRecommendation_format_1" value="1" onClick="updateScore()"<?php if($tmpScore[2] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('format_3')">3 - suitable for MAE format</span><br /><span onClick="checkEl('format_2')">2 - tight fit for 10 minutes</span><br /><span onClick="checkEl('format_1')">1 - too broad or complex for 10 minutes</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Abstract Quality</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_3" value="3" onClick="updateScore()"<?php if($tmpScore[3] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_2" value="2" onClick="updateScore()"<?php if($tmpScore[3] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_1" value="1" onClick="updateScore()"<?php if($tmpScore[3] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('quality_3')">3 - good abstract</span><br /><span onClick="checkEl('quality_2')">2 - middling abstract</span><br /><span onClick="checkEl('quality_1')">1 - poorly written abstract</span></td>
		</tr>
		<tr>
			<td style="font-weight: bold; text-align: right">Total:</td>
			<td id="rTotalScore"><?php echo $tmpScore[4]; ?> / 12</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Comments:<br /><span class="label">Please provide comments to support your recommendation above, including suggested improvements.</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><textarea name="rComments" id="rComments" rows="3" cols="80" onKeyUp="updateComments(this)"><?php echo stripslashes($tmpRComments); ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="button" value="Cancel" onClick="cancelReview()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Save" onClick="saveReview()" /></td>
		</tr>
	</table>
	<form name="reviewForm" id="reviewForm" method="post" action="">
		<input type="hidden" name="reviewScore" id="reviewScore" value="<?php echo $tmpRScore; ?>" />
		<input type="hidden" name="reviewComments" id="reviewComments" value="<?php echo $tmpRComments; ?>" />
		<input type="hidden" name="reviewID" id="reviewID" value="<?php echo $id; ?>" />
		<input type="hidden" name="editID" id="editID" value="<?php echo $eID; ?>" />
		<input type="hidden" name="reviewEvent" id="reviewEvent" value="<?php echo $tmpType; ?>" />
	</form>
	<script type="text/javascript">
		function checkEl(elStr) {
			document.getElementById('rRecommendation_' + elStr).checked = true;
			updateScore();
		}
		
		function updateScore() {
			var elArray = new Array('innovation','usability','format','quality');
			var totalScore = 0;
			var scoreStr = '';
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						scoreStr += radioEl[r].value + '|';
						totalScore = parseInt(totalScore) + parseInt(radioEl[r].value);
						break;
					}
				}
			}
			
			scoreStr += totalScore;
			document.getElementById('rTotalScore').innerHTML = totalScore + ' / 12';
			document.getElementById('reviewScore').value = scoreStr;
		}
		
		function updateComments(el) {
			document.getElementById('reviewComments').value = el.value;
		}
		
		function cancelReview() {
			var cancelOK = confirm('Are you sure you want to cancel?\n\nClick "OK" to cancel, or click "Cancel" to stay on this page.');
			if(cancelOK) window.location.href = 'reviewerList.php';
			else return false;
		}
		
		function saveReview() {
			//Check to make sure we have all the information we need
			//Make sure a score was selected for each category
			var elArray = new Array('innovation','usability','format','quality');
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				var isChecked = false;
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						isChecked = true;
						break;
					}
				}
				
				if(!isChecked) {
					alert('You did not select a score for each category!');
					return false;
				}
			}
			
			//We have a score for each category, so check for comments
			if(document.getElementById('reviewComments').value == '') {
				alert('Please include some comments to support your ratings!');
				return false;
			}
			
			//If we get this far, then we can submit
			document.getElementById('reviewForm').submit();
		}
	</script>
<?php
	} else if($tmpType == "Classroom of the Future") {
		if($tmpRScore != "") $tmpScore = explode("|",$tmpRScore);
		else $tmpScore = array(0,0,0,0,0);
?>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Rating:</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Innovation</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_3" value="3" onClick="updateScore()"<?php if($tmpScore[0] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_2" value="2" onClick="updateScore()"<?php if($tmpScore[0] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_1" value="1" onClick="updateScore()"<?php if($tmpScore[0] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('innovation_3')">3 - very innovative; rarely used in classrooms now</span><br /><span onClick="checkEl('innovation_2')">2 - innovative, but used frequently in classrooms now</span><br /><span onClick="checkEl('innovation_1')">1 - not innovative</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Style</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_style" id="rRecommendation_style_3" value="3" onClick="updateScore()"<?php if($tmpScore[1] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_style" id="rRecommendation_style_2" value="2" onClick="updateScore()"<?php if($tmpScore[1] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_style" id="rRecommendation_style_1" value="1" onClick="updateScore()"<?php if($tmpScore[1] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('style_3')">3 - excellent for a hands-on demonstration or presentation</span><br /><span onClick="checkEl('style_2')">2 - good for a hands-on demonstration or presentation</span><br /><span onClick="checkEl('style_1')">1 - seems more like a paper than a hands-on demonstration or presentation</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Global Context</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_context" id="rRecommendation_context_3" value="3" onClick="updateScore()"<?php if($tmpScore[2] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_context" id="rRecommendation_context_2" value="2" onClick="updateScore()"<?php if($tmpScore[2] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_context" id="rRecommendation_context_1" value="1" onClick="updateScore()"<?php if($tmpScore[2] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('context_3')">3 - has a global context</span><br /><span onClick="checkEl('context_2')">2 - seems somewhat geared toward one country</span><br /><span onClick="checkEl('context_1')">1 - would only be interesting to an audience from a single country</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Writing</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_writing" id="rRecommendation_writing_3" value="3" onClick="updateScore()"<?php if($tmpScore[3] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_writing" id="rRecommendation_writing_2" value="2" onClick="updateScore()"<?php if($tmpScore[3] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_writing" id="rRecommendation_writing_1" value="1" onClick="updateScore()"<?php if($tmpScore[3] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('writing_3')">3 - well written</span><br /><span onClick="checkEl('writing_2')">2 - has some inconsistencies that interfere with meaning</span><br /><span onClick="checkEl('writing_1')">1 - badly written</span></td>
		</tr>
		<tr>
			<td style="font-weight: bold; text-align: right">Total:</td>
			<td id="rTotalScore"><?php echo $tmpScore[4]; ?> / 12</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Comments:<br /><span class="label">Please provide comments to support your recommendation above, including suggested improvements.</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><textarea name="rComments" id="rComments" rows="3" cols="80" onKeyUp="updateComments(this)"><?php echo stripslashes($tmpRComments); ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="button" value="Cancel" onClick="cancelReview()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Save" onClick="saveReview()" /></td>
		</tr>
	</table>
	<form name="reviewForm" id="reviewForm" method="post" action="">
		<input type="hidden" name="reviewScore" id="reviewScore" value="<?php echo $tmpRScore; ?>" />
		<input type="hidden" name="reviewComments" id="reviewComments" value="<?php echo $tmpRComments; ?>" />
		<input type="hidden" name="reviewID" id="reviewID" value="<?php echo $id; ?>" />
		<input type="hidden" name="editID" id="editID" value="<?php echo $eID; ?>" />
		<input type="hidden" name="reviewEvent" id="reviewEvent" value="<?php echo $tmpType; ?>" />
	</form>
	<script type="text/javascript">
		function checkEl(elStr) {
			document.getElementById('rRecommendation_' + elStr).checked = true;
			updateScore();
		}
		
		function updateScore() {
			var elArray = new Array('innovation','style','context','writing');
			var totalScore = 0;
			var scoreStr = '';
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						scoreStr += radioEl[r].value + '|';
						totalScore = parseInt(totalScore) + parseInt(radioEl[r].value);
						break;
					}
				}
			}
			
			scoreStr += totalScore;
			document.getElementById('rTotalScore').innerHTML = totalScore + ' / 12';
			document.getElementById('reviewScore').value = scoreStr;
		}
		
		function updateComments(el) {
			document.getElementById('reviewComments').value = el.value;
		}
		
		function cancelReview() {
			var cancelOK = confirm('Are you sure you want to cancel?\n\nClick "OK" to cancel, or click "Cancel" to stay on this page.');
			if(cancelOK) window.location.href = 'reviewerList.php';
			else return false;
		}
		
		function saveReview() {
			//Check to make sure we have all the information we need
			//Make sure a score was selected for each category
			var elArray = new Array('innovation','style','context','writing');
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				var isChecked = false;
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						isChecked = true;
						break;
					}
				}
				
				if(!isChecked) {
					alert('You did not select a score for each category!');
					return false;
				}
			}
			
			//We have a score for each category, so check for comments
			if(document.getElementById('reviewComments').value == '') {
				alert('Please include some comments to support your ratings!');
				return false;
			}
			
			//If we get this far, then we can submit
			document.getElementById('reviewForm').submit();
		}
	</script>
<?php
	} else if($tmpType == "Hot Topics") {
		if($tmpRScore != "") $tmpScore = explode("|",$tmpRScore);
		else $tmpScore = array(0,0,0,0,0);
?>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Rating:</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Innovation</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_3" value="3" onClick="updateScore()"<?php if($tmpScore[0] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_2" value="2" onClick="updateScore()"<?php if($tmpScore[0] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_1" value="1" onClick="updateScore()"<?php if($tmpScore[0] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('innovation_3')">3 - very innovative; rarely used in educational/administrative contexts</span><br /><span onClick="checkEl('innovation_2')">2 - innovative, but used frequently in educational/administrative contexts now</span><br /><span onClick="checkEl('innovation_1')">1 - not innovative</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Global Context</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_context" id="rRecommendation_context_3" value="3" onClick="updateScore()"<?php if($tmpScore[2] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_context" id="rRecommendation_context_2" value="2" onClick="updateScore()"<?php if($tmpScore[2] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_context" id="rRecommendation_context_1" value="1" onClick="updateScore()"<?php if($tmpScore[2] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('context_3')">3 - has a global context</span><br /><span onClick="checkEl('context_2')">2 - seems U.S. based, but could be repurposed</span><br /><span onClick="checkEl('context_1')">1 - U.S. based; not functional outside that context</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Quality</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_3" value="3" onClick="updateScore()"<?php if($tmpScore[3] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_2" value="2" onClick="updateScore()"<?php if($tmpScore[3] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_1" value="1" onClick="updateScore()"<?php if($tmpScore[3] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('quality_3')">3 - well written, constructed or planned</span><br /><span onClick="checkEl('quality_2')">2 - acceptably written, constructed or planned</span><br /><span onClick="checkEl('quality_1')">1 - poorly written, constructed or planned</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Pedagogical Soundness</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_pedagogy" id="rRecommendation_pedagogy_3" value="3" onClick="updateScore()"<?php if($tmpScore[3] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_pedagogy" id="rRecommendation_pedagogy_2" value="2" onClick="updateScore()"<?php if($tmpScore[3] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_pedagogy" id="rRecommendation_pedagogy_1" value="1" onClick="updateScore()"<?php if($tmpScore[3] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('pedagogy_3')">3 - promotes pedagogical soundness through reference to current research</span><br /><span onClick="checkEl('pedagogy_2')">2 - pedagogically sound, but no current research reference</span><br /><span onClick="checkEl('pedagogy_1')">1 - no evidence of research; proposal is not solid</span></td>
		</tr>
		<tr>
			<td style="font-weight: bold; text-align: right">Total:</td>
			<td id="rTotalScore"><?php echo $tmpScore[4]; ?> / 12</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Comments:<br /><span class="label">Please provide comments to support your recommendation above, including suggested improvements.</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><textarea name="rComments" id="rComments" rows="3" cols="80" onKeyUp="updateComments(this)"><?php echo stripslashes($tmpRComments); ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="button" value="Cancel" onClick="cancelReview()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Save" onClick="saveReview()" /></td>
		</tr>
	</table>
	<form name="reviewForm" id="reviewForm" method="post" action="">
		<input type="hidden" name="reviewScore" id="reviewScore" value="<?php echo $tmpRScore; ?>" />
		<input type="hidden" name="reviewComments" id="reviewComments" value="<?php echo $tmpRComments; ?>" />
		<input type="hidden" name="reviewID" id="reviewID" value="<?php echo $id; ?>" />
		<input type="hidden" name="editID" id="editID" value="<?php echo $eID; ?>" />
		<input type="hidden" name="reviewEvent" id="reviewEvent" value="<?php echo $tmpType; ?>" />
	</form>
	<script type="text/javascript">
		function checkEl(elStr) {
			document.getElementById('rRecommendation_' + elStr).checked = true;
			updateScore();
		}
		
		function updateScore() {
			var elArray = new Array('innovation','context','quality','pedagogy');
			var totalScore = 0;
			var scoreStr = '';
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						scoreStr += radioEl[r].value + '|';
						totalScore = parseInt(totalScore) + parseInt(radioEl[r].value);
						break;
					}
				}
			}
			
			scoreStr += totalScore;
			document.getElementById('rTotalScore').innerHTML = totalScore + ' / 12';
			document.getElementById('reviewScore').value = scoreStr;
		}
		
		function updateComments(el) {
			document.getElementById('reviewComments').value = el.value;
		}
		
		function cancelReview() {
			var cancelOK = confirm('Are you sure you want to cancel?\n\nClick "OK" to cancel, or click "Cancel" to stay on this page.');
			if(cancelOK) window.location.href = 'reviewerList.php';
			else return false;
		}
		
		function saveReview() {
			//Check to make sure we have all the information we need
			//Make sure a score was selected for each category
			var elArray = new Array('innovation','context','quality','pedagogy');
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				var isChecked = false;
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						isChecked = true;
						break;
					}
				}
				
				if(!isChecked) {
					alert('You did not select a score for each category!');
					return false;
				}
			}
			
			//We have a score for each category, so check for comments
			if(document.getElementById('reviewComments').value == '') {
				alert('Please include some comments to support your ratings!');
				return false;
			}
			
			//If we get this far, then we can submit
			document.getElementById('reviewForm').submit();
		}
	</script>
<?php
	} else if($tmpType == "Graduate Student Research") {
		if($tmpRScore != "") $tmpScore = explode("|",$tmpRScore);
		else $tmpScore = array(0,0,0,0,0);
?>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Rating:</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Innovation</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_3" value="3" onClick="updateScore()"<?php if($tmpScore[0] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_2" value="2" onClick="updateScore()"<?php if($tmpScore[0] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_innovation" id="rRecommendation_innovation_1" value="1" onClick="updateScore()"<?php if($tmpScore[0] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('innovation_3')">3 - very innovative; rarely used in educational/administrative contexts</span><br /><span onClick="checkEl('innovation_2')">2 - innovative, but used frequently in educational/administrative contexts now</span><br /><span onClick="checkEl('innovation_1')">1 - not innovative</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Global Context</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_context" id="rRecommendation_context_3" value="3" onClick="updateScore()"<?php if($tmpScore[1] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_context" id="rRecommendation_context_2" value="2" onClick="updateScore()"<?php if($tmpScore[1] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_context" id="rRecommendation_context_1" value="1" onClick="updateScore()"<?php if($tmpScore[1] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('context_3')">3 - has a global context</span><br /><span onClick="checkEl('context_2')">2 - seems U.S. based, but could be repurposed</span><br /><span onClick="checkEl('context_1')">1 - U.S. based; not functional outside that context</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Quality</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_3" value="3" onClick="updateScore()"<?php if($tmpScore[2] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_2" value="2" onClick="updateScore()"<?php if($tmpScore[2] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_quality" id="rRecommendation_quality_1" value="1" onClick="updateScore()"<?php if($tmpScore[2] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('quality_3')">3 - well written, constructed or planned</span><br /><span onClick="checkEl('quality_2')">2 - acceptably written, constructed or planned</span><br /><span onClick="checkEl('quality_1')">1 - poorly written, constructed or planned</span></td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 10px; padding-left: 20px; font-style: italics">Pedagogical Soundness</td>
		</tr>
		<tr>
			<td style="text-align: right"><input type="radio" name="rRecommendation_pedagogy" id="rRecommendation_pedagogy_3" value="3" onClick="updateScore()"<?php if($tmpScore[3] == 3) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_pedagogy" id="rRecommendation_pedagogy_2" value="2" onClick="updateScore()"<?php if($tmpScore[3] == 2) { ?> checked="true"<?php } ?> /><br /><input type="radio" name="rRecommendation_pedagogy" id="rRecommendation_pedagogy_1" value="1" onClick="updateScore()"<?php if($tmpScore[3] == 1) { ?> checked="true"<?php } ?> /></td>
			<td><span onClick="checkEl('pedagogy_3')">3 - promotes pedagogical soundness through reference to current research</span><br /><span onClick="checkEl('pedagogy_2')">2 - pedagogically sound, but no current research reference</span><br /><span onClick="checkEl('pedagogy_1')">1 - no evidence of research; proposal is not solid</span></td>
		</tr>
		<tr>
			<td style="font-weight: bold; text-align: right">Total:</td>
			<td id="rTotalScore"><?php echo $tmpScore[4]; ?> / 12</td>
		</tr>
		<tr>
			<td colspan="2" style="padding-top: 20px; font-weight: bold">Comments:<br /><span class="label">Please provide comments to support your recommendation above, including suggested improvements.</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><textarea name="rComments" id="rComments" rows="3" cols="80" onKeyUp="updateComments(this)"><?php echo stripslashes($tmpRComments); ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="button" value="Cancel" onClick="cancelReview()" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Save" onClick="saveReview()" /></td>
		</tr>
	</table>
	<form name="reviewForm" id="reviewForm" method="post" action="">
		<input type="hidden" name="reviewScore" id="reviewScore" value="<?php echo $tmpRScore; ?>" />
		<input type="hidden" name="reviewComments" id="reviewComments" value="<?php echo $tmpRComments; ?>" />
		<input type="hidden" name="reviewID" id="reviewID" value="<?php echo $id; ?>" />
		<input type="hidden" name="editID" id="editID" value="<?php echo $eID; ?>" />
		<input type="hidden" name="reviewEvent" id="reviewEvent" value="<?php echo $tmpType; ?>" />
	</form>
	<script type="text/javascript">
		function checkEl(elStr) {
			document.getElementById('rRecommendation_' + elStr).checked = true;
			updateScore();
		}
		
		function updateScore() {
			var elArray = new Array('innovation','context','quality','pedagogy');
			var totalScore = 0;
			var scoreStr = '';
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						scoreStr += radioEl[r].value + '|';
						totalScore = parseInt(totalScore) + parseInt(radioEl[r].value);
						break;
					}
				}
			}
			
			scoreStr += totalScore;
			document.getElementById('rTotalScore').innerHTML = totalScore + ' / 12';
			document.getElementById('reviewScore').value = scoreStr;
		}
		
		function updateComments(el) {
			document.getElementById('reviewComments').value = el.value;
		}
		
		function cancelReview() {
			var cancelOK = confirm('Are you sure you want to cancel?\n\nClick "OK" to cancel, or click "Cancel" to stay on this page.');
			if(cancelOK) window.location.href = 'reviewerList.php';
			else return false;
		}
		
		function saveReview() {
			//Check to make sure we have all the information we need
			//Make sure a score was selected for each category
			var elArray = new Array('innovation','context','quality','pedagogy');
			for(i = 0; i < elArray.length; i++) {
				var radioEl = document.getElementsByName('rRecommendation_' + elArray[i]);
				var isChecked = false;
				for(r = 0; r < radioEl.length; r++) {
					if(radioEl[r].checked) {
						isChecked = true;
						break;
					}
				}
				
				if(!isChecked) {
					alert('You did not select a score for each category!');
					return false;
				}
			}
			
			//We have a score for each category, so check for comments
			if(document.getElementById('reviewComments').value == '') {
				alert('Please include some comments to support your ratings!');
				return false;
			}
			
			//If we get this far, then we can submit
			document.getElementById('reviewForm').submit();
		}
	</script>
<?php
	}
?>
<?php
	include "adminBottom.php";
?>