<?php
	//viewProp.php -- shows the details for a particular proposal and gives options to delete, withdraw, etc.
	//accessible to admin users only (chair, admin)

	include_once "login.php";
	$y = $confYear;

	$topTitle = "View Presentation";
	
	if(strpos($_SESSION['user_role'],"_reviewer") !== false) {
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(strpos($_SESSION['user_role'],"lead_") !== false) $backURL = 'listProps_lead.php';
	else $backURL = 'listProps_admin.php';
	
	$id = isset($_GET["id"]) ? strip_tags($_GET["id"]) : strip_tags($_POST["id"]);
	if($id == "") {
		echo "No ID given!";
		exit();
	}
	
	$q_stmt = $db->prepare("SELECT `id`,`title`,`contact`,`presenters`,`times`,`topics`,`computer`,`summary`,`abstract`,`password`,`salt`,`comments`,`photoOK`,`emailOK`,`type`,`status`,`confirmed` FROM `proposals` WHERE `id` = ?");
	$q_stmt->bind_param('s',$id);
	$q_stmt->execute();
	$q_stmt->store_result();
	$q_stmt->bind_result($tmpID, $tmpTitle, $tmpContact, $tmpPresenters, $tmpTimes, $tmpTopics, $tmpComputer, $tmpSummary, $tmpAbstract, $tmpPass, $tmpSalt, $tmpComments, $tmpPhotoOK, $tmpEmailOK, $tmpType, $tmpStatus, $tmpConfirmed);
	$q_stmt->fetch();
			
	$propData = array(
		"id" => $tmpID,
		"title" => $tmpTitle,
		"contact" => $tmpContact,
		"presenters" => $tmpPresenters,
		"times" => $tmpTimes,
		"topics" => $tmpTopics,
		"computer" => $tmpComputer,
		"summary" => $tmpSummary,
		"abstract" => $tmpAbstract,
		"comments" => $tmpComments,
		"type" => $tmpType,
		"status" => $tmpStatus,
		"confirmed" => $tmpConfirmed
	);
			
	//Now, get the presenters information
	$tmpP = explode("|",$tmpPresenters);
	for($p = 0; $p < count($tmpP); $p++) {
		$p_stmt = $db->prepare("SELECT `ID`,`Prefix`,`First Name`,`Last Name`,`Title`,`City`,`State`,`Province`,`Postal Code`,`Country`,`Phone`,`Extension`,`Fax`,`Email`,`Member`,`Student`,`Affiliation Name`,`Affiliation Country`,`Publish Email`,`First Time`,`Certificate` FROM `presenters` WHERE `id` = ?");
		$p_stmt->bind_param('s',$tmpP[$p]);
		$p_stmt->execute();
		$p_stmt->store_result();
		$p_stmt->bind_result($presID, $presPrefix, $presFirstName, $presLastName, $presTitle, $presCity, $presState, $presProvince, $presPostCode, $presCountry, $presPhone, $presExt, $presFax, $presEmail, $presMember, $presStudent, $presAffiliationName, $presAffiliationCountry, $presPublishEmail, $presFirstTime, $presCertificate);
		$p_stmt->fetch();
		
		if($presPublishEmail == 1) $presPublishEmail = "Y";
		else $presPublishEmail = "N";
		
		if($presMember == 1) $presMember = "Y";
		else $presMember = "N";
		
		if($presStudent == 1) $presStudent = "Y";
		else $presStudent = "N";
		
		if($presFirstTime == 1) $presFirstTime = "Y";
		else $presFirstTime = "N";
		
		if($presCertificate == 1) $presCertificate = "Y";
		else $presCertificate = "N";
		
		$thisPres = array(
			"id" => $presID,
			"prefix" => $presPrefix,
			"first_name" => $presFirstName,
			"last_name" => $presLastName,
			"job_title" => $presTitle,
			"city" => $presCity,
			"state" => $presState,
			"province" => $presProvince,
			"postal_code" => $presPostCode,
			"country" => $presCountry,
			"phone" => $presPhone,
			"extension" => $presExt,
			"fax" => $presFax,
			"email" => $presEmail,
			"member" => $presMember,
			"student" => $presStudent,
			"affiliation_name" => $presAffiliationName,
			"affiliation_country" => $presAffiliationCountry,
			"publish_email" => $presPublishEmail,
			"first_time" => $presFirstTime,
			"certificate" => $presCertificate
		);
		
		if($presEmail == $propData["contact"]) $thisPres["role"] = "main";
		else $thisPres["role"] = "";
		
		if(!is_array($propData["presenters"])) $propData["presenters"] = array();
		$propData["presenters"][] = $thisPres;
	}
	
	if($propData["type"] == "Technology Fairs") {
		$summaryMaxWords = 50;
		$abstractMaxWords = 100;
		$showTimes = true;
		$showPrefs = true;
	} else if($propData["type"] == "Mini-Workshops") {
		$summaryMaxWords = 50;
		$abstractMaxWords = 100;
		$showTimes = true;
		$showPrefs = false;
	} else if($propData["type"] == "Developers Showcase") {
		$summaryMaxWords = 100;
		$abstractMaxWords = 200;
		$showTimes = false;
		$showPrefs = false;
	} else if($propData["type"] == "Mobile Apps for Education Showcase") {
		$summaryMaxWords = 50;
		$abstractMaxWords = 100;
		$showTimes = false;
		$showPrefs = false;
	} else if($propData["type"] == "Classroom of the Future") {
		$summaryMaxWords = 50;
		$abstractMaxWords = 200;
		$topics = array("Blended Learning","Common Core (and Technology)","Flipped Classroom","Mobile Learning","Online Learning","Open Resources","Pedagogical Applications","Professional Development","Professional Learning Communities (PLC)","Social Change","Standards-based Teaching (with Technology)","Universal Design for Learning","Web-based Tools");
		$showTimes = false;
		$showPrefs = false;
	} else if($propData["type"] == "Hot Topics") {
		$summaryMaxWords = 50;
		$abstractMaxWords = 200;
		$topics = array("Virtual Reality in Language Learning","Students and Digital Literacy","Online Professional Development for ESL/EFL Instructors","Intelligent CALL and the Future of Teaching","The Great CALL Debate - Is the term CALL outdated?");
		$showTimes = false;
		$showPrefs = false;
	} else if($propData["type"] == "Graduate Student Research") {
		$summaryMaxWords = 50;
		$abstractMaxWords = 200;
		$showTimes = false;
		$showPrefs = false;
	}
	
	$topTitle = "View Proposal Information";
	include "adminTop.php";
?>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
			
			span.label {
				color: green;
				font-size: 9pt;
				font-style: italic;
			}
			
			span.required {
				color: red;
				font-weight: bold;
			}
			
			#presInfoDiv {
				position: absolute;
				height: 515px;
				width: 700px;
				padding: 20px;
				border: solid 2px #000000;
				background-color: #FFFFFF;
				z-index: 2;
			}
			
			#bgDiv {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background-color: #000000;
				opacity: 0.5;
				filter: alpha(opacity=50);
				z-index: 1;
			}
			
			a.presName {
				text-decoration: none;
				color: #000000;
				border-bottom: dashed 1px transparent;
			}
			
			a.presName:hover {
				border-bottom: dashed 1px #000000;
			}

			td.userInfo {
				font-size: .85em;
			}
			
			a {
				text-decoration: none;
				border-bottom: dashed 1px #CCCCCC;
				color: #0066CC;
			}
			
			a:hover {
				border-bottom: solid 1px #0066CC;
			}
		</style>
		<script type="text/javascript">
			var presenters = new Array(); //holds the information from the presenters
<?php
	for($n = 0; $n < count($propData["presenters"]); $n++) {
		$pArr = $propData["presenters"][$n];
?>

			presenters[<?php echo $n; ?>] = new Array();
			presenters[<?php echo $n; ?>]['id'] = '<?php echo $pArr["id"]; ?>';
			presenters[<?php echo $n; ?>]['role'] = '<?php echo $pArr["role"]; ?>';
			presenters[<?php echo $n; ?>]['prefix'] = '<?php echo $pArr["prefix"]; ?>';
			presenters[<?php echo $n; ?>]['first_name'] = '<?php echo $pArr["first_name"]; ?>';
			presenters[<?php echo $n; ?>]['last_name'] = '<?php echo $pArr["last_name"]; ?>';
			presenters[<?php echo $n; ?>]['job_title'] = '<?php echo $pArr["job_title"]; ?>';
			presenters[<?php echo $n; ?>]['city'] = '<?php echo $pArr["city"]; ?>';
			presenters[<?php echo $n; ?>]['state'] = '<?php echo $pArr["state"]; ?>';
			presenters[<?php echo $n; ?>]['province'] = '<?php echo $pArr["province"]; ?>';
			presenters[<?php echo $n; ?>]['zip'] = '<?php echo $pArr["postal_code"]; ?>';
			presenters[<?php echo $n; ?>]['country'] = '<?php echo $pArr["country"]; ?>';
			presenters[<?php echo $n; ?>]['phone'] = '<?php echo $pArr["phone"]; ?>';
			presenters[<?php echo $n; ?>]['extension'] = '<?php echo $pArr["extension"]; ?>';
			presenters[<?php echo $n; ?>]['fax'] = '<?php echo $pArr["fax"]; ?>';
			presenters[<?php echo $n; ?>]['email'] = '<?php echo $pArr["email"]; ?>';
			presenters[<?php echo $n; ?>]['publish_email'] = '<?php echo $pArr["publish_email"]; ?>';
			presenters[<?php echo $n; ?>]['member'] = '<?php echo $pArr["member"]; ?>';
			presenters[<?php echo $n; ?>]['student'] = '<?php echo $pArr["student"]; ?>';
			presenters[<?php echo $n; ?>]['affiliation_name'] = '<?php echo $pArr["affiliation_name"]; ?>';
			presenters[<?php echo $n; ?>]['affiliation_country'] = '<?php echo $pArr["affiliation_country"]; ?>';
			presenters[<?php echo $n; ?>]['first_time'] = '<?php echo $pArr["first_time"]; ?>';
			presenters[<?php echo $n; ?>]['certificate'] = '<?php echo $pArr["certificate"]; ?>';
<?php
	}
?>
			
			function init() {
				document.getElementById('bgDiv').style.display = 'none';
				document.getElementById('presInfoDiv').style.display = 'none';
			}
			
			function showPresInfo(n) {
				//get the number of the presenter we are adding
				var pN = parseInt(n) + parseInt(1);
				
				var d = document.getElementById('presInfoDiv');
				var dW = 700;
				var dH = 500;

				//First figure the top and left of the DIV
				var sW = document.body.clientWidth;
				var sH = document.body.clientHeight;
				var dT = (sH / 2) - (dH / 2);
				var dL = (sW / 2) - (dW / 2);
				d.style.top = parseInt(dT) + parseInt(document.body.scrollTop);
				d.style.left = dL;
				
				//Show the BG Div and hide scrolling
				var b = document.getElementById('bgDiv');
				b.style.width = sW;
				b.style.height = sH;
				b.style.top = document.body.scrollTop;
				b.style.display = '';
				document.body.style.overflow = 'hidden';
				
				//Show the presInfoDiv
				d.style.display = '';
				
				document.getElementById('presInfoTitle').innerHTML = 'Presenter #' + pN;

				//now, fill the form with the current presenter's data
				document.getElementById('pres_prefix').innerHTML = presenters[n]['prefix'];
				document.getElementById('pres_first_name').innerHTML = presenters[n]['first_name'];
				document.getElementById('pres_last_name').innerHTML = presenters[n]['last_name'];
				document.getElementById('pres_job_title').innerHTML = presenters[n]['job_title'];
				document.getElementById('pres_city').innerHTML = presenters[n]['city'];
				document.getElementById('pres_state').innerHTML = presenters[n]['state'];
				document.getElementById('pres_province').innerHTML = presenters[n]['province'];
				document.getElementById('pres_zip').innerHTML = presenters[n]['zip'];
				document.getElementById('pres_country').innerHTML = presenters[n]['country'];
				document.getElementById('pres_phone').innerHTML = presenters[n]['phone'];
				document.getElementById('pres_extension').innerHTML = presenters[n]['extension'];
				document.getElementById('pres_fax').innerHTML = presenters[n]['fax'];
				document.getElementById('pres_email').innerHTML = presenters[n]['email'];
				document.getElementById('pres_publish_email').innerHTML = presenters[n]['publish_email'];
				document.getElementById('pres_member').innerHTML = presenters[n]['member'];
				document.getElementById('pres_student').innerHTML = presenters[n]['student'];
				document.getElementById('pres_affiliation_name').innerHTML = presenters[n]['affiliation_name'];
				document.getElementById('pres_affiliation_country').innerHTML = presenters[n]['affiliation_country'];
				document.getElementById('pres_first_time').innerHTML = presenters[n]['first_time'];
				document.getElementById('pres_certificate').innerHTML = presenters[n]['certificate'];
			}
			
			function hidePresInfo() {
				var d = document.getElementById('presInfoDiv');
				d.style.display = 'none';
				
				var b = document.getElementById('bgDiv');
				b.style.display = 'none';
				
				document.body.style.overflow = 'auto';
			}
			
			window.onload = function() {
				init();
			};
		</script>
		<p align="center"><input type="button" value="Back to Proposals List" onClick="window.location.href='<?php echo $backURL; ?>'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Edit Proposal" onClick="window.location.href='editProp.php?id=<?php echo $id; ?>'" /></p>
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<td id="summaryTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Title</span><br />
					<p style="margin-left: 25px"><?php echo stripslashes($propData["title"]); ?></p>
				</td>
			</tr>
			<tr>
				<td colspan="2" id="presentersTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Presenters</span><br />
					<table id="presentersTable" border="0" cellspacing="0" cellpadding="5" width="100%">
						<tr>
							<td colspan="3" style="font-size: 10pt">Click on a presenter's name to view their information.</td>
						</tr>
						<tr>
							<td style="font-weight: bold; text-align: center; font-size: 10pt; width: 100px">Main Contact</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 300px">Name</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 300px">Email</td>
						</tr>
<?php
	for($n = 0; $n < count($propData["presenters"]); $n++) {
		if($n % 2 == 0) $bgColor = "#CCCCCC";
		else $bgColor = "#FFFFFF";
		
		$pArr = $propData["presenters"][$n];
?>
						<tr>
							<td style="background-color: <?php echo $bgColor; ?>; text-align: center; font-size: 10pt; width: 100px"><?php if($pArr["role"] == "main") { ?>&gt;&gt;<?php } else { ?>&nbsp;<?php } ?></td>
							<td style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 300px"><a href="javascript:void(0)" onClick="showPresInfo('<?php echo $n; ?>')"><?php echo $pArr["first_name"]." ".$pArr["last_name"]; ?></a></td>
							<td style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 300px"><a href="mailto:<?php echo $pArr['email']; ?>"><?php echo $pArr['email']; ?></a></td>
						</tr>
<?php
	}
?>
						<tr>
							<td colspan="3"><span class="label">The main contact is the person responsible for correspondence with the CALL-IS about the proposal. The main contact should notify all other presenters about the status of the proposal as well as notify the CALL-IS of any changes in presenters' information. Only contact presenters other than the main contact if you are unable to reach the main contact (e.g., because of a bad email address).</span></td>
						</tr>
					</table>
				</td>
			</tr>
<?php
	if($propData["times"] != "") {
		$presTimes = str_replace("|",", ",$propData["times"]);
?>
			<tr>
				<td id="timesTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Presentation Time</span><br />
					<p style="margin-left: 25px"><?php echo $presTimes; ?></p>
				</td>
			</tr>
<?php
	}

	$presTopics = str_replace("|",", ",$propData["topics"]);
?>
			<tr>
				<td id="topicsTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Presentation Topics</span><br />
					<p style="margin-left: 25px"><?php echo $presTopics; ?></p>
				</td>
			</tr>
<?php
	if($propData["computer"] != "") {
		if($propData["computer"] == "PC") $presComputer = "Windows (PC)";
		else if($propData["computer"] == "Mac") $presComputer = "Macintosh (Apple)";
		else if($propData["computer"] == "Either") $presComputer = "Either (No preference)";
		else if($propData["computer"] == "None") $presComputer = "None (Will bring own device)";
?>			
			<tr>
				<td id="timesTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Computer Preference</span><br />
					<p style="margin-left: 25px"><?php echo $presComputer; ?></p>
				</td>
			</tr>
<?php
	}
?>
			<tr>
				<td id="summaryTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
<?php
	$sWords = preg_match_all("/\S+/",$propData["summary"],$matches);
?>
					<span style="font-weight: bold">Summary (<?php echo $sWords; ?> words)</span><br />
					<p style="margin-left: 25px"><?php echo $propData["summary"]; ?></p>
				</td>
			</tr>
			<tr>
				<td id="abstractTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
<?php
	$aWords = preg_match_all("/\S+/",$propData["abstract"],$matches);
?>
					<span style="font-weight: bold">Abstract (<?php echo $aWords; ?> words)</span><br />
					<p style="margin-left: 25px"><?php echo $propData["abstract"]; ?></p>
				</td>
			</tr>
			<tr>
				<td id="summaryTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Comments to Event Organizers</span><br />
					<p style="margin-left: 25px"><?php echo stripslashes($propData["comments"]); ?></p>
				</td>
			</tr>
			<tr>
				<td id="confirmedTD" style="border-top: solid 1px #CCCCCC; padding: 20px;">
					<span style="font-weight: bold;">Confirmed?</span> <?php if($propData["confirmed"] == "Y") { ?> Yes <?php } else if($propData["confirmed"] == "N") { ?> No <?php } else if($propData["confirmed"] == "?") { ?> Unknown <?php } ?>
				</td>
			</tr>
<?php
	//Now, get the reviews for this proposal
	$revStmt = $db->prepare("SELECT `id`,`prop_id`,`reviewer`,`event`,`review`,`comments` FROM `reviews` WHERE `prop_id` = ?");
	$revStmt->bind_param('s',$propData["id"]);
	$revStmt->execute();
	$revStmt->bind_result($revID,$revPropID,$revReviewer,$revEvent,$revReview,$revComments);
	$reviews = array();
	while($revStmt->fetch()) {
		$reviews[] = array(
			"id" => $revID,
			"prop_id" => $revPropID,
			"reviewer" => $revReviewer,
			"event" => $revEvent,
			"review" => $revReview,
			"comments" => $revComments
		);
	}
	
	//Now, get the full name of each reviewer
	$uStmt = $db->prepare("SELECT `username`,`first_name`,`last_name` FROM `users` WHERE 1");
	$uStmt->execute();
	$uStmt->bind_result($uName,$uFirstName,$uLastName);
	$users = array();
	while($uStmt->fetch()) {
		$users[] = array(
			"username" => $uName,
			"name" => $uFirstName." ".$uLastName
		);
	}
	
	//Now, update the reviews array with the full name
	for($r = 0; $r < count($reviews); $r++) {
		for($u = 0; $u < count($users); $u++) {
			if($reviews[$r]["reviewer"] == $users[$u]["username"]) {
				$reviews[$r]["reviewer"] = $users[$u]["name"];
				break;
			}
		}
	}
	
	if(count($reviews) > 0) {
?>
			<tr>
				<td colspan="2" id="presentersTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
					<span style="font-weight: bold">Reviews</span><br />
					<table id="presentersTable" border="0" cellspacing="0" cellpadding="5" width="100%">
<?php
		if($propData["type"] == "Technology Fairs") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: center; font-size: 10pt; width: 100px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$scoreStr = $rArr["review"];
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: center; font-size: 10pt; width: 100px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Mini-Workshops") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 350px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
				$tmpScore = explode("|",$rArr["review"]);
				$scoreStr = '<p style="margin-top: 0; margin-bottom: 0; border-bottom: solid 1px #555555;"><strong>Total: '.$tmpScore[5].'</strong></p>';
				$scoreStr .= '<p style="margin-top:0">Style & Content: '.$tmpScore[0].'<br />';
				$scoreStr .= 'Novelty: '.$tmpScore[1].'<br />';
				$scoreStr .= 'Practicality: '.$tmpScore[2].'<br />';
				$scoreStr .= 'Feasibility: '.$tmpScore[3].'<br />';
				$scoreStr .= 'Pedagogical Soundness: '.$tmpScore[4].'</p>';
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 350px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Developers Showcase") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: center; font-size: 10pt; width: 100px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$scoreStr = $rArr["review"];
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: center; font-size: 10pt; width: 100px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Mobile Apps for Education Showcase") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: center; font-size: 10pt; width: 100px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$tmpScore = explode("|",$rArr["review"]);
				$scoreStr = '<p style="margin-top: 0; margin-bottom: 0; border-bottom: solid 1px #555555;"><strong>Total: '.$tmpScore[4].'</p>';
				$scoreStr .= '<p style="margin-top: 0">Innovation: '.$tmpScore[0].'<br />';
				$scoreStr .= 'Usability: '.$tmpScore[1].'<br />';
				$scoreStr .= 'Format/Time: '.$tmpScore[2].'<br />';
				$scoreStr .= 'Abstract Quality: '.$tmpScore[3].'</p>';
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 150px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Classroom of the Future") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 150px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$tmpScore = explode("|",$rArr["review"]);
				$scoreStr = '<p style="margin-top: 0; margin-bottom: 0; border-bottom: solid 1px #555555;"><strong>Total: '.$tmpScore[4].'</p>';
				$scoreStr .= '<p style="margin-top: 0">Innovation: '.$tmpScore[0].'<br />';
				$scoreStr .= 'Style: '.$tmpScore[1].'<br />';
				$scoreStr .= 'Context: '.$tmpScore[2].'<br />';
				$scoreStr .= 'Writing: '.$tmpScore[3].'</p>';
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 150px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Hot Topics") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 150px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$tmpScore = explode("|",$rArr["review"]);
				$scoreStr = '<p style="margin-top: 0; margin-bottom: 0; border-bottom: solid 1px #555555;"><strong>Total: '.$tmpScore[4].'</p>';
				$scoreStr .= '<p style="margin-top: 0">Innovation: '.$tmpScore[0].'<br />';
				$scoreStr .= 'Context: '.$tmpScore[1].'<br />';
				$scoreStr .= 'Quality: '.$tmpScore[2].'<br />';
				$scoreStr .= 'Pedagogy: '.$tmpScore[3].'</p>';
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 150px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Graduate Student Research") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 150px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$tmpScore = explode("|",$rArr["review"]);
				$scoreStr = '<p style="margin-top: 0; margin-bottom: 0; border-bottom: solid 1px #555555;"><strong>Total: '.$tmpScore[4].'</p>';
				$scoreStr .= '<p style="margin-top: 0">Innovation: '.$tmpScore[0].'<br />';
				$scoreStr .= 'Context: '.$tmpScore[1].'<br />';
				$scoreStr .= 'Quality: '.$tmpScore[2].'<br />';
				$scoreStr .= 'Pedagogy: '.$tmpScore[3].'</p>';
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 150px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		} else if($propData["type"] == "Technology Fair Classics") {
?>
						<tr>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 200px">Reviewer</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 150px">Score</td>
							<td style="font-weight: bold; text-align: left; font-size: 10pt; width: 400px">Comments</td>
						</tr>
<?php
			for($r = 0; $r < count($reviews); $r++) {
				if($r % 2 == 0) $bgColor = "#CCCCCC";
				else $bgColor = "#FFFFFF";
		
				$rArr = $reviews[$r];
		
				//We need to process the score to show it properly
				$tmpScore = explode("|",$rArr["review"]);
				$scoreStr = '<p style="margin-top: 0; margin-bottom: 0; border-bottom: solid 1px #555555;"><strong>Total: '.$tmpScore[4].'</p>';
				$scoreStr .= '<p style="margin-top: 0">Innovation: '.$tmpScore[0].'<br />';
				$scoreStr .= 'Context: '.$tmpScore[1].'<br />';
				$scoreStr .= 'Quality: '.$tmpScore[2].'<br />';
				$scoreStr .= 'Pedagogy: '.$tmpScore[3].'</p>';
?>
						<tr>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 200px"><?php echo $rArr["reviewer"]; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 150px"><?php echo $scoreStr; ?></td>
							<td valign="top" style="background-color: <?php echo $bgColor; ?>; text-align: left; font-size: 10pt; width: 400px"><?php echo $rArr["comments"]; ?></td>
						</tr>
<?php
			}
		}
?>
					</table>
				</td>
			</tr>
<?php
	}
?>
		</table>
		<p align="center"><input type="button" value="Back to Proposals List" onClick="window.location.href='<?php echo $backURL; ?>'" /> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Edit Proposal" onClick="window.location.href='editProp.php?id=<?php echo $id; ?>'" /></p>
		<div id="presInfoDiv">
			<h3 align="center" id="presInfoTitle" style="margin-top:0">Presenter #1</h3>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">Name:</span> <span id="pres_prefix">&nbsp;</span> <span id="pres_first_name">&nbsp;</span> <span id="pres_last_name">&nbsp;</span></p>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">Job Title:</span> <span id="pres_job_title">&nbsp;</span></p>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">City:</span> <span id="pres_city">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">State:</span> <span id="pres_state">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Province:</span>	<span id="pres_province">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">ZIP / Postal Code:</span> <span id="pres_zip">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Country:</span> <span id="pres_country">&nbsp;</span></p>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">Phone:</span> <span id="pres_phone">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Extension:</span> <span id="pres_extension">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Fax:</span> <span id="pres_fax">&nbsp;</span></p>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">Email:</span> <span id="pres_email">&nbsp;</span></p>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">Organization:</span> <span id="pres_affiliation_name">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Organization Country:</span> <span id="pres_affiliation_country">&nbsp;</span></p>
			<p style="text-align: left; margin-bottom:0"><span style="font-weight: bold">May TESOL publish the presenter's email address in the program book?</span> <span id="pres_publish_email">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Is the presenter a member of TESOL?</span> <span id="pres_member">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Is the presenter a student?</span> <span id="pres_student">&nbsp;</span></p>
			<p style="text-align: left; margin-top:0; margin-bottom:0"><span style="font-weight: bold">Is this the presenter's first time presenting at the TESOL convention?</span> <span id="pres_first_time">&nbsp;</span></p>
			<p style="text-align: left; margin-top: 0; margin-bottom:0"><span style="font-weight: bold">Does the presenter NEED a paper certificate?</span> <span id="pres_certificate">&nbsp;</span></p>
			<p align="center"><br /><input type="button" value="Close" onClick="hidePresInfo()" /></p>
		</div>
		<div id="bgDiv"></div>
		<noscript>
			<style type="text/css">
				#pagecontainer { display: none; }
				#bgDiv { display: none; }
				#presFormDiv { display: none; }
			</style>
			<div id="noscriptmsg">
				<h1 align="center" style="color:red">Javascript Required!</h1>
				<p>This page requires javascript. Please enable javascript in your browser and click your browser's refresh button.</p>
			</div>
		</noscript>
<?php
	include "adminBottom.php";
?>