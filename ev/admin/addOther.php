<?php
	//addOther.php - allows a user to add a presentation to the 
	//accessible only to leads, chairs, and admin users
	
	include_once "login.php";
	$topTitle = "Add Presentation (Other)";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false) {
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(isset($_POST["prop_title"])) {
		//First, save the presenters' information
		$tmp = explode("||",$_POST['prop_presenters']);
		$presStr = "";
		for($i = 1; $i < count($tmp); $i++) { //the first element is blank
			$tmpP = explode("|",$tmp[$i]);
			$thisP = array();
			for($j = 0; $j < count($tmpP); $j++) {
				list($tmpK, $tmpV) = explode("=",$tmpP[$j]);
				$thisP[$tmpK] = strip_tags($tmpV); //removes PHP and HTML tags from the string
			}
		
			$pQ_stmt = $db->prepare("INSERT INTO `other_presenters` (`ID`, `Prefix`, `First Name`, `Last Name`, `Title`, `City`, `State`, `Province`, `Postal Code`, `Country`, `Phone`, `Extension`, `Fax`, `Email`, `Member`, `Student`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `First Time`, `Certificate`) VALUES ('0',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'0')");
		
			if($thisP['member'] == "Y") $thisP['member'] = 1;
			else $thisP['member'] = 0;
		
			if($thisP['student'] == "Y") $thisP['student'] = 1;
			else $thisP['student'] = 0;
		
			if($thisP['publish_email'] == "Y") $thisP['publish_email'] = 1;
			else $thisP['publish_email'] = 0;
		
			if($thisP['first_time'] == "Y") $thisP['first_time'] = 1;
			else $thisP['first_time'] = 0;
		
			$pQ_stmt->bind_param('sssssssssssssssssss',$thisP['prefix'], $thisP['first_name'], $thisP['last_name'], $thisP['job_title'], $thisP['city'], $thisP['state'], $thisP['province'], $thisP['zip'], $thisP['country'], $thisP['phone'], $thisP['extension'], $thisP['fax'], $thisP['email'], $thisP['member'], $thisP['student'], $thisP['affiliation_name'], $thisP['affiliation_country'], $thisP['publish_email'], $thisP['first_time']);
		
			if(!$pQ_stmt->execute()) {
				//header('Location: /ev/error.php?err=Registration failure: INSERT PRESENTER');
				echo "Add Presenters - Error: ".$pQ_stmt->error;
				exit();
			}
				
			$presStr .= $db->insert_id."|";
		}

		//Now, enter the other proposal information into the database
		$q_stmt = $db->prepare("INSERT INTO `other_proposals` (`id`, `presenters`, `title`, `summary`, `emailOK`, `confirmed`) VALUES ('0',?,?,?,'1','1')");
	
		$q_stmt->bind_param('sss', trim($presStr,"|"), strip_tags($_POST['prop_title']), strip_tags($_POST["prop_summary"]));

		if(!$q_stmt->execute()) {
			echo "Add Proposal - Error: ".$q_stmt->error;
			exit();
		}
	
		$propID = $db->insert_id;
		
		//Now, insert the proposal id into sessions table
		
		$s_stmt = $db->prepare("SELECT * FROM `sessions` WHERE `id` = ?");
		$s_stmt->bind_param('s',strip_tags($_POST["prop_session"]));
		$s_stmt->execute();
		$s_stmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEvent,$sTitle,$sPresentations);
		$s_stmt->fetch();
		$thisSession = array(
			"id" => $sID,
			"location" => $sLocation,
			"date" => $sDate,
			"time" => $sTime,
			"event" => $sEvent,
			"title" => $sTitle,
			"presentations" => $sPresentations
		);
		$s_stmt->close();
		
		$sPres = $thisSession["presentations"];
		if($sPres != "") $sPres .= "||0|".$propID;
		else $sPres = "0|".$propID;
		
		$sU_stmt = $db->prepare("UPDATE `sessions` SET `presentations` = ? WHERE `id` = ?");
		$sU_stmt->bind_param('ss',$sPres,$thisSession["id"]);
		if(!$sU_stmt->execute()) {
			echo $sU_stmt->error;
			exit();
		}
		
		//If we get this far, we can show the session information (with the added proposal)
		header("Location: scheduleSession.php?s=".$thisSession["id"]);
	}
	
	$sesID = strip_tags($_GET["s"]);

	//get the session information
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE `id` = ?");
	$sStmt->bind_param('s',$sesID);
	$sStmt->execute();
	$sStmt->bind_result($id,$location,$date,$time,$event,$title,$presentations);
	$sStmt->fetch();
	$thisSession = array(
		"id" => $id,
		"location" => $location,
		"date" => $date,
		"time" => $time,
		"event" => $event,
		"title" => $title,
		"presentations" => $presentations
	);
	$sStmt->close();

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
			
			#presFormDiv {
				position: absolute;
				height: 500px;
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
	</style>
	<script type="text/javascript">
		var presenters = new Array(); //holds the information from the presenters
		
		function init() {
			document.getElementById('bgDiv').style.display = 'none';
			document.getElementById('presFormDiv').style.display = 'none';
		}
		
		function showPresForm() {
			//get the number of the presenter we are adding
			var pN = parseInt(presenters.length) + parseInt(1);
			
			var d = document.getElementById('presFormDiv');
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
			
			//Show the presFormDiv
			d.style.display = '';
			
			document.getElementById('presFormTitle').innerHTML = 'Presenter #' + pN;
			document.getElementById('leftPresFormButtonTD').innerHTML = '<input type="button" value="Cancel" onClick="hidePresForm()" />';
			document.getElementById('middlePresFormButtonTD').innerHTML = '<input type="button" value="Clear Form" onClick="resetPresForm()" />';
			document.getElementById('rightPresFormButtonTD').innerHTML = '<input type="button" value="Save" onClick="savePresForm(-1)" />';
			document.getElementById('pres_prefix').focus();
		}
		
		function editPresForm(n) {
			showPresForm(); //shows the presenter form
			
			//now, fill the form with the current presenter's data
			var el = document.getElementById('pres_prefix');
			for(i = 0; i < el.options.length; i++) {
				if(el.options[i].text == presenters[n]['prefix']) {
					el.selectedIndex = i;
					break;
				}
			}
			
			document.getElementById('pres_first_name').value = presenters[n]['first_name'];
			document.getElementById('pres_last_name').value = presenters[n]['last_name'];
			document.getElementById('pres_job_title').value = presenters[n]['job_title'];
			document.getElementById('pres_city').value = presenters[n]['city'];
			
			el = document.getElementById('pres_state');
			for(i = 0; i < el.options.length; i++) {
				if(el.options[i].text == presenters[n]['state']) {
					el.selectedIndex = i;
					break;
				}
			}
			
			document.getElementById('pres_province').value = presenters[n]['province'];
			document.getElementById('pres_zip').value = presenters[n]['zip'];
			
			el = document.getElementById('pres_country');
			for(i = 0; i < el.options.length; i++) {
				if(el.options[i].text == presenters[n]['country']) {
					el.selectedIndex = i;
					break;
				}
			}
			
			document.getElementById('pres_phone').value = presenters[n]['phone'];
			document.getElementById('pres_extension').value = presenters[n]['extension'];
			document.getElementById('pres_fax').value = presenters[n]['fax'];
			document.getElementById('pres_email').value = presenters[n]['email'];
			
			el = document.getElementsByName('pres_publish_email');
			for(i = 0; i < el.length; i++) {
				if(el[i].value == presenters[n]['publish_email']) {
					el[i].checked = true;
					break;
				}
			}
			
			el = document.getElementsByName('pres_member');
			for(i = 0; i < el.length; i++) {
				if(el[i].value == presenters[n]['member']) {
					el[i].checked = true;
					break;
				}
			}

			el = document.getElementsByName('pres_student');
			for(i = 0; i < el.length; i++) {
				if(el[i].value == presenters[n]['student']) {
					el[i].checked = true;
					break;
				}
			}
			
			document.getElementById('pres_affiliation_name').value = presenters[n]['affiliation_name'];
			
			el = document.getElementById('pres_affiliation_country');
			for(i = 0; i < el.options.length; i++) {
				if(el.options[i].text == presenters[n]['affiliation_country']) {
					el.selectedIndex = i;
					break;
				}
			}
			
			el = document.getElementsByName('pres_first_time');
			for(i = 0; i < el.length; i++) {
				if(el[i].value == presenters[n]['first_time']) {
					el[i].checked = true;
					break;
				}
			}
			
			var pN = parseInt(n) + parseInt(1);
			document.getElementById('presFormTitle').innerHTML = 'Presenter #' + pN;
			document.getElementById('leftPresFormButtonTD').innerHTML = '<input type="button" value="Cancel" onClick="hidePresForm()" />';
			document.getElementById('middlePresFormButtonTD').innerHTML = '<input type="button" value="Delete Presenter" onClick="deletePresenter(' + n + ')" />';
			document.getElementById('rightPresFormButtonTD').innerHTML = '<input type="button" value="Save" onClick="savePresForm(' + n + ')" />';				
		}
		
		function deletePresenter(n) {
			var delOK = confirm('Are you sure you want to delete this presenter?');
			if(delOK) {
				//If we have only one presenter, all information is deleted. Otherwise, we need to reassign the main contact to a different person
				//First determine if this is the main contact
				if(presenters[n]['role'] == 'main') {
					if(n == 0 && presenters.length > 1) { //this is the first presenter in the array and there are other presenters
						//set the second presenter to be the main contact
						presenters[1]['role'] = 'main';
					} else if(n > 0) { //there is more than one presenter, and this is not the first presenter
						//set the first presenter to be the main contact
						presenters[0]['role'] = 'main';
					}
				}
				
				hidePresForm();
				presenters.splice(n,1);
				listPresenters();
			}
		}
		
		function resetPresForm() {
			//Clears the presenter form
			document.getElementById('pres_prefix').selectedIndex = 0;
			document.getElementById('pres_first_name').value = '';
			document.getElementById('pres_last_name').value = '';
			document.getElementById('pres_job_title').value = '';
			document.getElementById('pres_city').value = '';
			document.getElementById('pres_state').selectedIndex = 0;
			document.getElementById('pres_province').value = '';
			document.getElementById('pres_zip').value = '';
			document.getElementById('pres_country').selectedIndex = 0;
			document.getElementById('pres_phone').value = '';
			document.getElementById('pres_extension').value = '';
			document.getElementById('pres_fax').value = '';
			document.getElementById('pres_email').value = '';
			for(i = 0; i < 2; i++) { document.getElementsByName('pres_publish_email')[i].checked = false; }
			for(i = 0; i < 2; i++) { document.getElementsByName('pres_member')[i].checked = false; }
			for(i = 0; i < 2; i++) { document.getElementsByName('pres_student')[i].checked = false; }
			document.getElementById('pres_affiliation_name').value = '';
			document.getElementById('pres_affiliation_country').selectedIndex = 0;
			for(i = 0; i < 2; i++) { document.getElementsByName('pres_first_time')[i].checked = false; }
		}

		function savePresForm(n) {
			//check for required fields
			if(document.getElementById('pres_first_name').value == '') {
				alert('You did not enter a first name for this presenter!');
				document.getElementById('pres_first_name').focus();
				return;
			} else if(document.getElementById('pres_last_name').value == '') {
				alert('You did not enter a last name for this presenter!');
				document.getElementById('pres_last_name').focus();
				return;
			} else if(document.getElementById('pres_city').value == '') {
				alert('You did not enter a city for this presenter!');
				document.getElementById('pres_city').focus();
				return;
			} else if(document.getElementById('pres_country').selectedIndex == 0) {
				alert('You did not select a country for this presenter!');
				return;
			} else if(document.getElementById('pres_province').value != '' && document.getElementById('pres_country').selectedIndex == 0) {
				alert('You entered a province outside the United States, but chose the United States as your country. Please check the country again!');
				return;
			} else if(document.getElementById('pres_email').value == '') {
				alert('You did not enter an email for this presenter!');
				document.getElementById('pres_email').focus();
				return;
			} else if(!validateEmail(document.getElementById('pres_email').value)) {
				alert('You did not enter a valid email address for this presenter!');
				document.getElementById('pres_email').focus();
				return;
			} else if(document.getElementById('pres_affiliation_name').value == '') {
				alert('You did not enter an organization for this presenter!');
				document.getElementById('pres_affiliation_name').focus();
				return;
			} else if(document.getElementById('pres_affiliation_country').selectedIndex == 0) {
				alert('You did not select an organization country for this presenter!');
				return;
			} else { //check the radio elements
				var pE = false;
				for(i = 0; i < 2; i++) {
					pE = document.getElementsByName('pres_publish_email')[i].checked;
					if(pE) break;
				}
				
				if(!pE) {
					alert('May TESOL publish this presenter\'s email in the program book? Please select "Yes" or "No"!');
					return;
				}
				
				var m = false;
				for(i = 0; i < 2; i++) {
					m = document.getElementsByName('pres_member')[i].checked;
					if(m) break;
				}
				
				if(!m) {
					alert('Is this presenter a member of TESOL? Please select "Yes" or "No"!');
					return;
				}
				
				var s = false;
				for(i = 0; i < 2; i++) {
					s = document.getElementsByName('pres_student')[i].checked;
					if(s) break;
				}
				
				if(!s) {
					alert('Is this presenter a student? Please select "Yes" or "No"!');
					return;
				}
				
				var fT = false;
				for(i = 0; i < 2; i++) {
					fT = document.getElementsByName('pres_first_time')[i].checked;
					if(fT) break;
				}
				
				if(!fT) {
					alert('Is this the presenter\'s first time presenting at the TESOL convention? Please select "Yes" or "No"!');
					return;
				}
			}
			
			//We have checked the data and it appears fine, so add the data to the presenters array
			if(n == -1) {
				var pI = presenters.length; //the index for the new entry
				presenters[pI] = new Array();
				if(pI == 0) presenters[pI]['role'] = 'main';
				else presenters[pI]['role'] = '';
			} else var pI = n;
						
			//put the data into the array
			presenters[pI]['prefix'] = document.getElementById('pres_prefix').options[document.getElementById('pres_prefix').selectedIndex].text;
			presenters[pI]['first_name'] = document.getElementById('pres_first_name').value;
			presenters[pI]['last_name'] = document.getElementById('pres_last_name').value;
			presenters[pI]['job_title'] = document.getElementById('pres_job_title').value;
			presenters[pI]['city'] = document.getElementById('pres_city').value;
			presenters[pI]['state'] = document.getElementById('pres_state').options[document.getElementById('pres_state').selectedIndex].text;
			presenters[pI]['province'] = document.getElementById('pres_province').value;
			presenters[pI]['zip'] = document.getElementById('pres_zip').value;
			presenters[pI]['country'] = document.getElementById('pres_country').options[document.getElementById('pres_country').selectedIndex].text;
			presenters[pI]['phone'] = document.getElementById('pres_phone').value;
			presenters[pI]['extension'] = document.getElementById('pres_extension').value;
			presenters[pI]['fax'] = document.getElementById('pres_fax').value;
			presenters[pI]['email'] = document.getElementById('pres_email').value;
			for(i = 0; i < 2; i++) {
				if(document.getElementsByName('pres_publish_email')[i].checked) {
					presenters[pI]['publish_email'] = document.getElementsByName('pres_publish_email')[i].value;
					break;
				}
			}
			for(i = 0; i < 2; i++) {
				if(document.getElementsByName('pres_member')[i].checked) {
					presenters[pI]['member'] = document.getElementsByName('pres_member')[i].value;
					break;
				}
			}
			for(i = 0; i < 2; i++) {
				if(document.getElementsByName('pres_student')[i].checked) {
					presenters[pI]['student'] = document.getElementsByName('pres_student')[i].value;
				}
			}
			presenters[pI]['affiliation_name'] = document.getElementById('pres_affiliation_name').value;
			presenters[pI]['affiliation_country'] = document.getElementById('pres_affiliation_country').options[document.getElementById('pres_affiliation_country').selectedIndex].text;
			for(i = 0; i < 2; i++) {
				if(document.getElementsByName('pres_first_time')[i].checked) {
					presenters[pI]['first_time'] = document.getElementsByName('pres_first_time')[i].value;
				}
			}
			
			listPresenters();
			hidePresForm();
		}
		
		function listPresenters() {				
			var pT = document.getElementById('presentersTable');
			
			//first, clear away any existing rows
			while(pT.rows.length > 0) pT.deleteRow(0);
			
			if(presenters.length == 0) return false;

			//now, add in the first rows (headers, etc.)
			var pR = pT.insertRow(0);
			var pC = pR.insertCell(0);
			pC.colSpan = 3;
			pC.style.fontSize = '10pt';
			pC.innerHTML = 'Click on a presenter\'s name to edit their information or delete the presenter.';
			
			pR = pT.insertRow(1);
			pC = pR.insertCell(0);
			pC.style.fontWeight = 'bold';
			pC.style.fontSize = '10pt';
			pC.style.textAlign = 'left';
			pC.style.width = '300px';
			pC.innerHTML = 'Name';
			
			pC = pR.insertCell(1);
			pC.style.fontWeight = 'bold';
			pC.style.fontSize = '10pt';
			pC.style.textAlign = 'left';
			pC.style.width = '300px';
			pC.innerHTML = 'Email';
							
			//now, add in new rows with the presenters information
			for(i = 0; i < presenters.length; i++) {
				if(i % 2 == 0) var bgColor = '#CCCCCC';
				else var bgColor = '#FFFFFF';
				
				var pN = parseInt(i) + parseInt(2);
				pR = pT.insertRow(pN);				
				pC = pR.insertCell(0);
				pC.style.textAlign = 'left';
				pC.style.backgroundColor = bgColor;
				pC.innerHTML = '<a href="javascript:void(0)" onClick="editPresForm(' + i + ')" class="presName">' + presenters[i]['first_name'] + ' ' + presenters[i]['last_name'] + '</a>';
				pC.onClick = function(n) {
					alert(n);
				}
									
				pC = pR.insertCell(1);
				pC.style.textAlign = 'left';
				pC.style.backgroundColor = bgColor;
				pC.innerHTML = '<a href="mailto:' + presenters[i]['email'] + '">' + presenters[i]['email'] + '</a>';
			}
			
			pN++;
		}

		function hidePresForm() {
			resetPresForm();
			
			var d = document.getElementById('presFormDiv');
			d.style.display = 'none';
			
			var b = document.getElementById('bgDiv');
			b.style.display = 'none';
			
			document.body.style.overflow = 'auto';
		}
		
		function countWords(el) {
			//alert(el.id);
			var words = el.value.match(/\S+/g).length;
			var maxWords = 50;
			var wC = document.getElementById('summary_total_words');
			wC.innerHTML = words;
		}
		
		function validateEmail(email) { 
			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(email);
		} 
		
		function checkForm() {
			//Check for a title
			if(document.getElementById('proposal_title').value == '') {
				alert('You did not enter a title for this presentation!');
				document.getElementById('proposal_title').focus();
				return false;
			}
			
			//Check for at least one presenter
			if(presenters.length == 0) {
				alert('You did not enter any presenters information. Please enter information for at least one presenter!');
				document.getElementById('presAddBtn').focus();
				return false;
			}

			//check to see that a summary was entered
			if(document.getElementById('summary').value == '') {
				alert('You did not enter a summary for this presentation!');
				document.getElementById('summary').focus();
				return false;
			}

			//IF we get this far, then there are no problems with the data, so we can put the data into the hidden form and submit
			document.getElementById('prop_title').value = document.getElementById('proposal_title').value;
			
			var presStr = '';
			for(i = 0; i < presenters.length; i++) {
				var p = presenters[i];
				presStr += '||role=' + p['role'] + '|prefix=' + p['prefix'] + '|first_name=' + p['first_name'] + '|last_name=' + p['last_name'] + '|job_title=' + p['job_title'] + '|city=' + p['city'] + '|state=' + p['state'] + '|province=' + p['province'] + '|zip=' + p['zip'] + '|country=' + p['country'] + '|phone=' + p['phone'] + '|extension=' + p['extension'] + '|fax=' + p['fax'] + '|email=' + p['email'] + '|publish_email=' + p['publish_email'] + '|member=' + p['member'] + '|student=' + p['student'] + '|affiliation_name=' + p['affiliation_name'] + '|affiliation_country=' + p['affiliation_country'] + '|first_time=' + p['first_time'];
			}
			document.getElementById('prop_presenters').value = presStr;
			
			document.getElementById('prop_summary').value = document.getElementById('summary').value;			
			document.getElementById('propForm').submit();
		}
		
		window.onload = function() {
			init();
		};
	</script>
	<table border="0" align="center">
		<tr>
			<td colspan="2" style="font-weight: bold">Session Information</td>
		</tr>
		<tr>
			<td style="padding-left: 20px">Title:</td>
			<td style="padding-left: 10px"><?=$thisSession["title"]?></td>
		</tr>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
		<tr>
			<td style="padding-left: 20px">Date:</td>
			<td style="padding-left: 10px"><?=$dateStr?></td>
		</tr>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
		<tr>
			<td style="padding-left: 20px">Time:</td>
			<td style="padding-left: 10px"><?=$timeStr?></td>
		</tr>
	</table>
	<table border="0" cellspacing="0" cellpadding="0" width="800" style="border-top: solid 1px #AAAAAA; border-bottom: solid 1px #AAAAAA">
		<tr>
			<td width="50" valign="top" style="font-weight: bold; padding-top: 20px; padding-bottom: 20px">Title:</td>
			<td width="750" style="padding-top: 20px; padding-bottom: 20px"><input type="text" name="proposal_title" id="proposal_title" style="width: 100%"></td>
		</tr>
	</table>
	<p align="left" style="font-weight: bold; margin-bottom: 0">Presenters</p>
	<table id="presentersTable" border="0" cellspacing="0" cellpadding="5" width="800">
	</table>
	<p><input type="button" id="presAddBtn" value="Click to add a presenter" onClick="showPresForm()" /></p>
	<p align="left" style="font-weight: bold; border-top: solid 1px #AAAAAA; padding-top: 20px; padding-bottom: 20px">Summary (50 words maximum)</span><br /><br />
		<textarea name="summary" id="summary" rows="3" cols="100" onkeyup="countWords(this)"></textarea><br /><br />
		<span style="font-weight: normal">Total Words:</span> <span id="summary_total_words" style="font-weight: normal">0</span>
	</p>
	<p align="center"><input type="button" value="Submit" onClick="checkForm()" ></p>
		<div id="presFormDiv">
			<table border="0" cellspacing="0" cellpadding="0" align="center">
				<tr>
					<td id="presFormTitle" align="center" style="font-weight: bold; padding-bottom: 20px">Presenter #1</td>
				</tr>
				<tr>
					<td style="font-size: 10pt">Items marked with a <span style="font-weight: bold; color: red">*</span> are required.</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td>Prefix:</td>
								<td><select name="pres_prefix" id="pres_prefix"><option></option><option>Mr.</option><option>Mrs.</option><option>Ms.</option><option>Dr.</option></select></td>
								<td><span class="required">*</span>First Name:</td>
								<td><input type="text" id="pres_first_name" name="pres_first_name" size="21"></td>
								<td><span class="required">*</span>Last Name:</td>
								<td><input type="text" id="pres_last_name" name="pres_last_name" size="24"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td valign="top" style="padding-top: 7px">Title:</td>
								<td><input type="text" name="pres_job_title" id="pres_job_title" style="width: 500px"><br /><span class="label">NOTE: Title refers to your job title (e.g. Lecturer)</span></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td valign="top" style="padding-top: 7px"><span class="required">*</span>City:</td>
								<td valign="top"><input type="text" id="pres_city" name="pres_city" size="30"></td>
								<td valign="top" style="padding-top: 7px">State:</td>
								<td valign="top"><select name="pres_state" id="pres_state"><option></option><option>Alabama</option><option>Alaska</option><option>Arizona</option><option>Arkansas</option><option>California</option><option>Colorado</option><option>Connecticut</option><option>Delaware</option><option>District of Columbia</option><option>Florida</option><option>Georgia</option><option>Hawaii</option><option>Idaho</option><option>Illinois</option><option>Indiana</option><option>Iowa</option><option>Kansas</option><option>Kentucky</option><option>Louisiana</option><option>Maine</option><option>Maryland</option><option>Massachusetts</option><option>Michigan</option><option>Minnesota</option><option>Mississippi</option><option>Missouri</option><option>Montana</option><option>Nebraska</option><option>Nevada</option><option>New Hampshire</option><option>New Jersey</option><option>New Mexico</option><option>New York</option><option>North Carolina</option><option>North Dakota</option><option>Ohio</option><option>Oklahoma</option><option>Oregon</option><option>Pennsylvania</option><option>Rhode Island</option><option>South Carolina</option><option>South Dakota</option><option>Tennessee</option><option>Texas</option><option>Utah</option><option>Vermont</option><option>Virginia</option><option>Washington</option><option>West Virginia</option><option>Wisconsin</option><option>Wyoming</option></select></td>
								<td valign="top" style="padding-top:7px">Province:</td>
								<td><input type="text" id="pres_province" name="pres_province" size="24"><br /><span class="label">Outside the US</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td>ZIP / Postal Code:</td>
								<td><input type="text" id="pres_zip" name="pres_zip" size="10"></td>
								<td><span class="required">*</span>Country:</td>
								<td valign="top"><select name="pres_country" id="pres_country"><option value=""></option><option value="United States">United States</option><option value="Afghanistan">Afghanistan</option><option value="Aland Islands">Aland Islands</option><option value="Albania">Albania</option><option value="Algeria">Algeria</option><option value="American Samoa">American Samoa</option><option value="Andorra">Andorra</option><option value="Angola">Angola</option><option value="Anguilla">Anguilla</option><option value="Antarctica">Antarctica</option><option value="Antigua And Barbuda">Antigua And Barbuda</option><option value="Argentina">Argentina</option><option value="Armenia">Armenia</option><option value="Aruba">Aruba</option><option value="Australia">Australia</option><option value="Austria">Austria</option><option value="Azerbaijan">Azerbaijan</option><option value="Bahamas">Bahamas</option><option value="Bahrain">Bahrain</option><option value="Bangladesh">Bangladesh</option><option value="Barbados">Barbados</option><option value="Belarus">Belarus</option><option value="Belgium">Belgium</option><option value="Belize">Belize</option><option value="Benin">Benin</option><option value="Bermuda">Bermuda</option><option value="Bhutan">Bhutan</option><option value="Bolivia">Bolivia</option><option value="Bosnia And Herzegovina">Bosnia And Herzegovina</option><option value="Botswana">Botswana</option><option value="Bouvet Island">Bouvet Island</option><option value="Brazil">Brazil</option><option value="British Indian Ocean Territory">British Indian Ocean Territory</option><option value="Brunei Darussalam">Brunei Darussalam</option><option value="Bulgaria">Bulgaria</option><option value="Burkina Faso">Burkina Faso</option><option value="Burundi">Burundi</option><option value="Cambodia">Cambodia</option><option value="Cameroon">Cameroon</option><option value="Canada">Canada</option><option value="Cape Verde">Cape Verde</option><option value="Cayman Islands">Cayman Islands</option><option value="Central African Republic">Central African Republic</option><option value="Chad">Chad</option><option value="Chile">Chile</option><option value="China">China</option><option value="Christmas Island">Christmas Island</option><option value="Cocos Islands">Cocos Islands</option><option value="Colombia">Colombia</option><option value="Comoros">Comoros</option><option value="Congo">Congo</option><option value="Congo">Congo</option><option value="Cook Islands">Cook Islands</option><option value="Costa Rica">Costa Rica</option><option value="Cote D ivoire">Cote D ivoire</option><option value="Croatia">Croatia</option><option value="Cuba">Cuba</option><option value="Cyprus">Cyprus</option><option value="Czech Republic">Czech Republic</option><option value="Denmark">Denmark</option><option value="Djibouti">Djibouti</option><option value="Dominica">Dominica</option><option value="Dominican Republic">Dominican Republic</option><option value="Ecuador">Ecuador</option><option value="Egypt">Egypt</option><option value="El Salvador">El Salvador</option><option value="Equatorial Guinea">Equatorial Guinea</option><option value="Eritrea">Eritrea</option><option value="Estonia">Estonia</option><option value="Ethiopia">Ethiopia</option><option value="Falkland Islands">Falkland Islands</option><option value="Faroe Islands">Faroe Islands</option><option value="Fiji">Fiji</option><option value="Finland">Finland</option><option value="France">France</option><option value="French Guiana">French Guiana</option><option value="French Polynesia">French Polynesia</option><option value="French Southern Territories">French Southern Territories</option><option value="Gabon">Gabon</option><option value="Gambia">Gambia</option><option value="Georgia">Georgia</option><option value="Germany">Germany</option><option value="Ghana">Ghana</option><option value="Gibraltar">Gibraltar</option><option value="Greece">Greece</option><option value="Greenland">Greenland</option><option value="Grenada">Grenada</option><option value="Guadeloupe">Guadeloupe</option><option value="Guam">Guam</option><option value="Guatemala">Guatemala</option><option value="Guernsey">Guernsey</option><option value="Guinea">Guinea</option><option value="Guinea-bissau">Guinea-bissau</option><option value="Guyana">Guyana</option><option value="Haiti">Haiti</option><option value="Heard Island And Mcdonald Islands">Heard Island And Mcdonald Islands</option><option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option><option value="Honduras">Honduras</option><option value="Hong Kong">Hong Kong</option><option value="Hungary">Hungary</option><option value="Iceland">Iceland</option><option value="India">India</option><option value="Indonesia">Indonesia</option><option value="Iran">Iran</option><option value="Iraq">Iraq</option><option value="Ireland">Ireland</option><option value="Isle Of Man">Isle Of Man</option><option value="Israel">Israel</option><option value="Italy">Italy</option><option value="Jamaica">Jamaica</option><option value="Japan">Japan</option><option value="Jersey">Jersey</option><option value="Jordan">Jordan</option><option value="Kazakhstan">Kazakhstan</option><option value="Kenya">Kenya</option><option value="Kiribati">Kiribati</option><option value="Korea, North">Korea, North</option><option value="Korea, South">Korea, South</option><option value="Kuwait">Kuwait</option><option value="Kyrgyzstan">Kyrgyzstan</option><option value="Laos">Laos</option><option value="Latvia">Latvia</option><option value="Lebanon">Lebanon</option><option value="Lesotho">Lesotho</option><option value="Liberia">Liberia</option><option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option><option value="Liechtenstein">Liechtenstein</option><option value="Lithuania">Lithuania</option><option value="Luxembourg">Luxembourg</option><option value="Macao">Macao</option><option value="Macedonia">Macedonia</option><option value="Madagascar">Madagascar</option><option value="Malawi">Malawi</option><option value="Malaysia">Malaysia</option><option value="Maldives">Maldives</option><option value="Mali">Mali</option><option value="Malta">Malta</option><option value="Marshall Islands">Marshall Islands</option><option value="Martinique">Martinique</option><option value="Mauritania">Mauritania</option><option value="Mauritius">Mauritius</option><option value="Mayotte">Mayotte</option><option value="Mexico">Mexico</option><option value="Micronesia">Micronesia</option><option value="Moldova">Moldova</option><option value="Monaco">Monaco</option><option value="Mongolia">Mongolia</option><option value="Montenegro">Montenegro</option><option value="Montserrat">Montserrat</option><option value="Morocco">Morocco</option><option value="Mozambique">Mozambique</option><option value="Myanmar">Myanmar</option><option value="Namibia">Namibia</option><option value="Nauru">Nauru</option><option value="Nepal">Nepal</option><option value="Netherlands">Netherlands</option><option value="Netherlands Antilles">Netherlands Antilles</option><option value="New Caledonia">New Caledonia</option><option value="New Zealand">New Zealand</option><option value="Nicaragua">Nicaragua</option><option value="Niger">Niger</option><option value="Nigeria">Nigeria</option><option value="Niue">Niue</option><option value="Norfolk Island">Norfolk Island</option><option value="Northern Mariana Islands">Northern Mariana Islands</option><option value="Norway">Norway</option><option value="Oman">Oman</option><option value="Pakistan">Pakistan</option><option value="Palau">Palau</option><option value="Palestine">Palestine</option><option value="Panama">Panama</option><option value="Papua New Guinea">Papua New Guinea</option><option value="Paraguay">Paraguay</option><option value="Peru">Peru</option><option value="Philippines">Philippines</option><option value="Pitcairn">Pitcairn</option><option value="Poland">Poland</option><option value="Portugal">Portugal</option><option value="Puerto Rico">Puerto Rico</option><option value="Qatar">Qatar</option><option value="Reunion">Reunion</option><option value="Romania">Romania</option><option value="Russian Federation">Russian Federation</option><option value="Rwanda">Rwanda</option><option value="Saint Barthélemy">Saint Barthelemy</option><option value="Saint Helena">Saint Helena</option><option value="Saint Kitts And Nevis">Saint Kitts And Nevis</option><option value="Saint Lucia">Saint Lucia</option><option value="Saint Martin">Saint Martin</option><option value="Saint Pierre And Miquelon">Saint Pierre And Miquelon</option><option value="Saint Vincent And The Grenadines">Saint Vincent And The Grenadines</option><option value="Samoa">Samoa</option><option value="San Marino">San Marino</option><option value="Sao Tome And Principe">Sao Tome And Principe</option><option value="Saudi Arabia">Saudi Arabia</option><option value="Senegal">Senegal</option><option value="Serbia">Serbia</option><option value="Seychelles">Seychelles</option><option value="Sierra Leone">Sierra Leone</option><option value="Singapore">Singapore</option><option value="Slovakia">Slovakia</option><option value="Slovenia">Slovenia</option><option value="Solomon Islands">Solomon Islands</option><option value="Somalia">Somalia</option><option value="South Africa">South Africa</option><option value="South Georgia And The South Sandwich Islands">South Georgia And The South Sandwich Islands</option><option value="Spain">Spain</option><option value="Sri Lanka">Sri Lanka</option><option value="Sudan">Sudan</option><option value="Suriname">Suriname</option><option value="Svalbard And Jan Mayen">Svalbard And Jan Mayen</option><option value="Swaziland">Swaziland</option><option value="Sweden">Sweden</option><option value="Switzerland">Switzerland</option><option value="Syrian Arab Republic">Syrian Arab Republic</option><option value="Taiwan">Taiwan</option><option value="Tajikistan">Tajikistan</option><option value="Tanzania">Tanzania</option><option value="Thailand">Thailand</option><option value="Timor-leste">Timor-leste</option><option value="Togo">Togo</option><option value="Tokelau">Tokelau</option><option value="Tonga">Tonga</option><option value="Trinidad And Tobago">Trinidad And Tobago</option><option value="Tunisia">Tunisia</option><option value="Turkey">Turkey</option><option value="Turkmenistan">Turkmenistan</option><option value="Turks And Caicos Islands">Turks And Caicos Islands</option><option value="Tuvalu">Tuvalu</option><option value="Uganda">Uganda</option><option value="Ukraine">Ukraine</option><option value="United Arab Emirates">United Arab Emirates</option><option value="United Kingdom">United Kingdom</option><option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option><option value="Uruguay">Uruguay</option><option value="Uzbekistan">Uzbekistan</option><option value="Vanuatu">Vanuatu</option><option value="Venezuela">Venezuela</option><option value="Viet Nam">Viet Nam</option><option value="Virgin Islands, British">Virgin Islands, British</option><option value="Virgin Islands, U.s.">Virgin Islands, U.S.</option><option value="Wallis And Futuna">Wallis And Futuna</option><option value="Western Sahara">Western Sahara</option><option value="Yemen">Yemen</option><option value="Zambia">Zambia</option><option value="Zimbabwe">Zimbabwe</option></select></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td>Phone:</td>
								<td><input type="text" id="pres_phone" name="pres_phone" size="25"></td>
								<td>Extension:</td>
								<td><input type="text" id="pres_extension" name="pres_extension" size="10"></td>
								<td>Fax:</td>
								<td><input type="text" id="pres_fax" name="pres_fax" size="25"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><span class="required">*</span>Email:</td>
								<td><input type="text" id="pres_email" style="width: 550px"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><span class="required">*</span>May TESOL publish your email address in the program book? <input type="radio" name="pres_publish_email" id="pres_publish_email" value="Y"> Yes &nbsp; <input type="radio" name="pres_publish_email" id="pres_publish_email" value="N"> No</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><span class="required">*</span>Are you a member of TESOL? <input type="radio" name="pres_member" id="pres_member" value="Y"> Yes &nbsp; <input type="radio" name="pres_member" id="pres_member" value="N"> No</td>
								<td><span class="required">*</span>Are you a student? <input type="radio" name="pres_student" id="pres_student" value="Y"> Yes &nbsp; <input type="radio" name="pres_student" id="pres_student" value="N"> No</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td valign="top" style="padding-top: 7px"><span class="required">*</span>Organization:</td>
								<td><input type="text" name="pres_affiliation_name" id="pres_affiliation_name" style="width: 500px"><br /><span class="label">NOTE: Organization should be your company, university, or similar.<br />Please do not add your department or division.</span></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><span class="required">*</span>Organization Country:</td>
								<td valign="top"><select name="pres_affiliation_country" id="pres_affiliation_country"><option value=""></option><option value="United States">United States</option><option value="Afghanistan">Afghanistan</option><option value="Aland Islands">Aland Islands</option><option value="Albania">Albania</option><option value="Algeria">Algeria</option><option value="American Samoa">American Samoa</option><option value="Andorra">Andorra</option><option value="Angola">Angola</option><option value="Anguilla">Anguilla</option><option value="Antarctica">Antarctica</option><option value="Antigua And Barbuda">Antigua And Barbuda</option><option value="Argentina">Argentina</option><option value="Armenia">Armenia</option><option value="Aruba">Aruba</option><option value="Australia">Australia</option><option value="Austria">Austria</option><option value="Azerbaijan">Azerbaijan</option><option value="Bahamas">Bahamas</option><option value="Bahrain">Bahrain</option><option value="Bangladesh">Bangladesh</option><option value="Barbados">Barbados</option><option value="Belarus">Belarus</option><option value="Belgium">Belgium</option><option value="Belize">Belize</option><option value="Benin">Benin</option><option value="Bermuda">Bermuda</option><option value="Bhutan">Bhutan</option><option value="Bolivia">Bolivia</option><option value="Bosnia And Herzegovina">Bosnia And Herzegovina</option><option value="Botswana">Botswana</option><option value="Bouvet Island">Bouvet Island</option><option value="Brazil">Brazil</option><option value="British Indian Ocean Territory">British Indian Ocean Territory</option><option value="Brunei Darussalam">Brunei Darussalam</option><option value="Bulgaria">Bulgaria</option><option value="Burkina Faso">Burkina Faso</option><option value="Burundi">Burundi</option><option value="Cambodia">Cambodia</option><option value="Cameroon">Cameroon</option><option value="Canada">Canada</option><option value="Cape Verde">Cape Verde</option><option value="Cayman Islands">Cayman Islands</option><option value="Central African Republic">Central African Republic</option><option value="Chad">Chad</option><option value="Chile">Chile</option><option value="China">China</option><option value="Christmas Island">Christmas Island</option><option value="Cocos Islands">Cocos Islands</option><option value="Colombia">Colombia</option><option value="Comoros">Comoros</option><option value="Congo">Congo</option><option value="Congo">Congo</option><option value="Cook Islands">Cook Islands</option><option value="Costa Rica">Costa Rica</option><option value="Cote D ivoire">Cote D ivoire</option><option value="Croatia">Croatia</option><option value="Cuba">Cuba</option><option value="Cyprus">Cyprus</option><option value="Czech Republic">Czech Republic</option><option value="Denmark">Denmark</option><option value="Djibouti">Djibouti</option><option value="Dominica">Dominica</option><option value="Dominican Republic">Dominican Republic</option><option value="Ecuador">Ecuador</option><option value="Egypt">Egypt</option><option value="El Salvador">El Salvador</option><option value="Equatorial Guinea">Equatorial Guinea</option><option value="Eritrea">Eritrea</option><option value="Estonia">Estonia</option><option value="Ethiopia">Ethiopia</option><option value="Falkland Islands">Falkland Islands</option><option value="Faroe Islands">Faroe Islands</option><option value="Fiji">Fiji</option><option value="Finland">Finland</option><option value="France">France</option><option value="French Guiana">French Guiana</option><option value="French Polynesia">French Polynesia</option><option value="French Southern Territories">French Southern Territories</option><option value="Gabon">Gabon</option><option value="Gambia">Gambia</option><option value="Georgia">Georgia</option><option value="Germany">Germany</option><option value="Ghana">Ghana</option><option value="Gibraltar">Gibraltar</option><option value="Greece">Greece</option><option value="Greenland">Greenland</option><option value="Grenada">Grenada</option><option value="Guadeloupe">Guadeloupe</option><option value="Guam">Guam</option><option value="Guatemala">Guatemala</option><option value="Guernsey">Guernsey</option><option value="Guinea">Guinea</option><option value="Guinea-bissau">Guinea-bissau</option><option value="Guyana">Guyana</option><option value="Haiti">Haiti</option><option value="Heard Island And Mcdonald Islands">Heard Island And Mcdonald Islands</option><option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option><option value="Honduras">Honduras</option><option value="Hong Kong">Hong Kong</option><option value="Hungary">Hungary</option><option value="Iceland">Iceland</option><option value="India">India</option><option value="Indonesia">Indonesia</option><option value="Iran">Iran</option><option value="Iraq">Iraq</option><option value="Ireland">Ireland</option><option value="Isle Of Man">Isle Of Man</option><option value="Israel">Israel</option><option value="Italy">Italy</option><option value="Jamaica">Jamaica</option><option value="Japan">Japan</option><option value="Jersey">Jersey</option><option value="Jordan">Jordan</option><option value="Kazakhstan">Kazakhstan</option><option value="Kenya">Kenya</option><option value="Kiribati">Kiribati</option><option value="Korea, North">Korea, North</option><option value="Korea, South">Korea, South</option><option value="Kuwait">Kuwait</option><option value="Kyrgyzstan">Kyrgyzstan</option><option value="Laos">Laos</option><option value="Latvia">Latvia</option><option value="Lebanon">Lebanon</option><option value="Lesotho">Lesotho</option><option value="Liberia">Liberia</option><option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option><option value="Liechtenstein">Liechtenstein</option><option value="Lithuania">Lithuania</option><option value="Luxembourg">Luxembourg</option><option value="Macao">Macao</option><option value="Macedonia">Macedonia</option><option value="Madagascar">Madagascar</option><option value="Malawi">Malawi</option><option value="Malaysia">Malaysia</option><option value="Maldives">Maldives</option><option value="Mali">Mali</option><option value="Malta">Malta</option><option value="Marshall Islands">Marshall Islands</option><option value="Martinique">Martinique</option><option value="Mauritania">Mauritania</option><option value="Mauritius">Mauritius</option><option value="Mayotte">Mayotte</option><option value="Mexico">Mexico</option><option value="Micronesia">Micronesia</option><option value="Moldova">Moldova</option><option value="Monaco">Monaco</option><option value="Mongolia">Mongolia</option><option value="Montenegro">Montenegro</option><option value="Montserrat">Montserrat</option><option value="Morocco">Morocco</option><option value="Mozambique">Mozambique</option><option value="Myanmar">Myanmar</option><option value="Namibia">Namibia</option><option value="Nauru">Nauru</option><option value="Nepal">Nepal</option><option value="Netherlands">Netherlands</option><option value="Netherlands Antilles">Netherlands Antilles</option><option value="New Caledonia">New Caledonia</option><option value="New Zealand">New Zealand</option><option value="Nicaragua">Nicaragua</option><option value="Niger">Niger</option><option value="Nigeria">Nigeria</option><option value="Niue">Niue</option><option value="Norfolk Island">Norfolk Island</option><option value="Northern Mariana Islands">Northern Mariana Islands</option><option value="Norway">Norway</option><option value="Oman">Oman</option><option value="Pakistan">Pakistan</option><option value="Palau">Palau</option><option value="Palestine">Palestine</option><option value="Panama">Panama</option><option value="Papua New Guinea">Papua New Guinea</option><option value="Paraguay">Paraguay</option><option value="Peru">Peru</option><option value="Philippines">Philippines</option><option value="Pitcairn">Pitcairn</option><option value="Poland">Poland</option><option value="Portugal">Portugal</option><option value="Puerto Rico">Puerto Rico</option><option value="Qatar">Qatar</option><option value="Reunion">Reunion</option><option value="Romania">Romania</option><option value="Russian Federation">Russian Federation</option><option value="Rwanda">Rwanda</option><option value="Saint Barthélemy">Saint Barthelemy</option><option value="Saint Helena">Saint Helena</option><option value="Saint Kitts And Nevis">Saint Kitts And Nevis</option><option value="Saint Lucia">Saint Lucia</option><option value="Saint Martin">Saint Martin</option><option value="Saint Pierre And Miquelon">Saint Pierre And Miquelon</option><option value="Saint Vincent And The Grenadines">Saint Vincent And The Grenadines</option><option value="Samoa">Samoa</option><option value="San Marino">San Marino</option><option value="Sao Tome And Principe">Sao Tome And Principe</option><option value="Saudi Arabia">Saudi Arabia</option><option value="Senegal">Senegal</option><option value="Serbia">Serbia</option><option value="Seychelles">Seychelles</option><option value="Sierra Leone">Sierra Leone</option><option value="Singapore">Singapore</option><option value="Slovakia">Slovakia</option><option value="Slovenia">Slovenia</option><option value="Solomon Islands">Solomon Islands</option><option value="Somalia">Somalia</option><option value="South Africa">South Africa</option><option value="South Georgia And The South Sandwich Islands">South Georgia And The South Sandwich Islands</option><option value="Spain">Spain</option><option value="Sri Lanka">Sri Lanka</option><option value="Sudan">Sudan</option><option value="Suriname">Suriname</option><option value="Svalbard And Jan Mayen">Svalbard And Jan Mayen</option><option value="Swaziland">Swaziland</option><option value="Sweden">Sweden</option><option value="Switzerland">Switzerland</option><option value="Syrian Arab Republic">Syrian Arab Republic</option><option value="Taiwan">Taiwan</option><option value="Tajikistan">Tajikistan</option><option value="Tanzania">Tanzania</option><option value="Thailand">Thailand</option><option value="Timor-leste">Timor-leste</option><option value="Togo">Togo</option><option value="Tokelau">Tokelau</option><option value="Tonga">Tonga</option><option value="Trinidad And Tobago">Trinidad And Tobago</option><option value="Tunisia">Tunisia</option><option value="Turkey">Turkey</option><option value="Turkmenistan">Turkmenistan</option><option value="Turks And Caicos Islands">Turks And Caicos Islands</option><option value="Tuvalu">Tuvalu</option><option value="Uganda">Uganda</option><option value="Ukraine">Ukraine</option><option value="United Arab Emirates">United Arab Emirates</option><option value="United Kingdom">United Kingdom</option><option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option><option value="Uruguay">Uruguay</option><option value="Uzbekistan">Uzbekistan</option><option value="Vanuatu">Vanuatu</option><option value="Venezuela">Venezuela</option><option value="Viet Nam">Viet Nam</option><option value="Virgin Islands, British">Virgin Islands, British</option><option value="Virgin Islands, U.s.">Virgin Islands, U.S.</option><option value="Wallis And Futuna">Wallis And Futuna</option><option value="Western Sahara">Western Sahara</option><option value="Yemen">Yemen</option><option value="Zambia">Zambia</option><option value="Zimbabwe">Zimbabwe</option></select></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><span class="required">*</span>Is this your first time presenting at the TESOL convention? <input type="radio" name="pres_first_time" id="pres_first_time" value="Y"> Yes &nbsp; <input type="radio" name="pres_first_time" id="pres_first_time" value="N"> No</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td width="34%" align="center" id="leftPresFormButtonTD"><input type="button" value="Cancel" onClick="hidePresForm()" /></td>
								<td width="33%" align="center" id="middlePresFormButtonTD"><input type="button" value="Clear Form" onClick="resetPresForm()" /></td>
								<td width="33%" align="center" id="rightPresFormButtonTD"><input type="button" value="Save" onClick="savePresForm()" /></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
		<div id="bgDiv"></div>
		<form name="propForm" id="propForm" method="post" action="">
			<input type="hidden" name="prop_title" id="prop_title" value="" />
			<input type="hidden" name="prop_presenters" id="prop_presenters" value="" />
			<input type="hidden" name="prop_summary" id="prop_summary" value="" />
			<input type="hidden" name="prop_session" id="prop_session" value="<?=$sesID?>" />
		</form>
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