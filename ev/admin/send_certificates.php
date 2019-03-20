<?php
	// send_certificates.php -- creates PDF copies of the certificates and emails them to the participants

	include_once "login.php";

	$topTitle = "Send Certificates";

	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
	$confYear = "2018";
	$confDate1 = "2018-03-28";
	$confDate2 = "2018-03-29";
	$confDate3 = "2018-03-30";
	
	$evLocationStr = "Exhibition Hall - Booth 491";
	$tsLocationStr = "Exhibition Hall - Booth 540";

	if(strpos($_SESSION['user_role'],"admin") === false) {
		include "adminTop.php";
?>
	<h2 align="center" style="color: red">Access Denied!</h2>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}

	if(isset($_POST["certData"])) {
		//First, get the data
		$data = explode("||",trim(strip_tags($_POST["certData"]),"||"));
		$event = strip_tags($_POST["certEvent"]);
		if($event != "Volunteer") $sessionID = preg_replace("/\D/","",$_POST["certSession"]);

		require("../fpdf/fpdf.php");

		//Now, cycle through and create the certificates
		$sendSuccess = array();
		$sendFail = array();
		$random_hash = md5(date('r', time()));

		for($i = 0; $i < count($data); $i++) {
			if($event != "Volunteer") {
				list($email,$name,$title,$date,$start,$end) = explode("|",$data[$i]);
				//$email = "justin@jshewell.com";
			
				$name = stripslashes($name);
				$title = stripslashes($title);
			
				if($event == "Technology Fairs") $img = "cert_images/fairs.jpg";
				else if($event == "Developers Showcase") $img = "cert_images/ds.jpg";
				else if($event == "Mini-Workshops") $img = "cert_images/workshops.jpg";
				else if($event == "Mobile Apps for Education Showcase") $img = "cert_images/mae.jpg";
				else if($event == "Technology Fairs Classics") $img = "cert_images/classics.jpg";
				else if($event == "Graduate Student Research") $img = "cert_images/grad.jpg";
				else if(strpos($event,"Hot Topic") !== false) $img = "cert_images/ht.jpg";
				else if(strpos($event,"Academic Session") !== false) $img = "cert_images/as.jpg";
				else if(strpos($event,"InterSection") !== false) $img = "cert_images/intersection.jpg";
				else $img = "cert_images/evo.jpg";
			
				$pdf = new FPDF('L','pt',array(792,612));
				$pdf->AddPage();
				$pdf->Image($img,0,0,-300);
			
				//Print the name
				$pdf->SetFont('Arial','B',24);
				$nameWidth = $pdf->GetStringWidth(stripslashes($name));
				$nameY = 230;
				$nameX = (792 - $nameWidth) / 2;
				$pdf->Text($nameX,$nameY,$name);
			
				//Print the event
				$pdf->SetFont('Arial','B',16);
				$eventWidth = $pdf->GetStringWidth($event);
				$eventY = 350;
				$eventX = (792 - $eventWidth) / 2;
				$pdf->Text($eventX,$eventY, $event);
			
				//Print the title
				$titleWidth = $pdf->GetStringWidth($title);
				$titleFS = 16;
				$titleMaxW = 628;
				while($titleWidth > $titleMaxW) {
					$titleFS--;
					$pdf->SetFont('Arial','B',$titleFS);
					$titleWidth = $pdf->GetStringWidth($title);
				}
				$titleY = 400;
				$titleX = (792 - $titleWidth) / 2;
				$pdf->Text($titleX,$titleY,$title);
			
				//Print the Date and Time
				if(strpos($start,"AM") !== false && strpos($end,"AM") !== false) { //both have AM
					$start = rtrim($start," AM");
				} else if(strpos($start,"PM") !== false && strpos($end,"PM") !== false) { //both have PM
					$start = rtrim($start," PM");
				}
			
				$dateStr = $date.", ".$start." - ".$end;
				$pdf->SetFont('Arial','B',16); //reset the font size
				$dateWidth = $pdf->GetStringWidth($dateStr);
				$dateY = 452;
				$dateX = (792 - $dateWidth) / 2;
				$pdf->Text($dateX,$dateY,$dateStr);
			
				$pdf->Output('F','EV_Certificate.pdf');

				$subject = "Certificate for Participating in the Electronic Village at TESOL 2017";
			} else {
				list($name,$email) = explode("|",$data[$i]);
				$img = "cert_images/volunteer.jpg";
				

				$pdf = new FPDF('L','pt',array(792,612));
				$pdf->AddPage();
				$pdf->Image($img,0,0,-300);
			
				//Print the name
				$pdf->SetFont('Arial','B',24);
				$nameWidth = $pdf->GetStringWidth(stripslashes($name));
				$nameY = 230;
				$nameX = (792 - $nameWidth) / 2;
				$pdf->Text($nameX,$nameY,$name);
						
				$pdf->Output('F','EV_Certificate.pdf');

				$subject = "Certificate for Volunteering at the Electronic Village at TESOL 2017";
				$html = '<html>
							<head>
								<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
							</head>

							<body style="margin: 0">
								<img src="http://call-is.org/ev/admin/'.$img.'" height="750" width="1000" />
								<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 999">
									<table border="0">
										<tr>
											<td width="1000" height="290">&nbsp;</td>
										</tr>
										<tr>
											<td width="1000" height="50" align="center" valign="top">
												<p align="center" style="font-size: 35px; font-family: Arial">'.$name.'</p>
											</td>
										</tr>
										<tr>
											<td width="1000" height="405">&nbsp;</td>
										</tr>
									</table>
								</div>
							</body>
						</html>';
			}
			
			$pdfFile = chunk_split(base64_encode(file_get_contents('EV_Certificate.pdf')));

			//Now, send the email
			if($event == "Technology Fairs") $from = "ev-fair@call-is.org";
			else if($event == "Developers Showcase") $from = "ev-ds@call-is.org";
			else if($event == "Mini-Workshops") $from = "ev-mini@call-is.org";
			else if($event == "Mobile Apps for Education Showcase") $from = "ev-mae@call-is.org";
			else if($event == "Technology Fairs Classics") $from = "ev-classics@call-is.org";
			else if($event == "Graduate Student Research") $from = "ev-grad@call-is.org";
			else if($event == "Volunteer") $from = "ev-volunteers@call-is.org";
			else $from = "ev@call-is.org";

			$to = $email;
			//$to = "jshewell@asu.edu";
			$cc = $from;
			
			//define the headers we want passed. Note that they are separated with \r\n
			$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nCC: ".$cc."\r\nReply-To: ".$from."\r\n";
			//$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nReply-To: ".$from."\r\n";

			//add boundary string and mime type specification
			$headers .= "Content-Type: multipart/mixed; boundary=\"CALL-EV-mixed-".$random_hash."\"";

			if($event == "Volunteer") {
				$tmpMsg = file_get_contents("volunteerCertificateEmail.txt");
				$tmpMsg = str_replace("[INSERT RANDOM HASH]",$random_hash,$tmpMsg);
				$tmpMsg = str_replace("[INSERT NAME]",$name,$tmpMsg);
				$tmpMsg = str_replace("[INSERT YEAR]",$confYear,$tmpMsg);
				$tmpMsg = str_replace("[INSERT LEAD EMAIL]",$from,$tmpMsg);
				$tmpMsg = str_replace("[INSERT PDF FILE]",$pdfFile,$tmpMsg);
			} else {
				$tmpMsg = file_get_contents("certificateEmail.txt");
				$tmpMsg = str_replace("[INSERT RANDOM HASH]",$random_hash,$tmpMsg);
				$tmpMsg = str_replace("[INSERT NAME]",$name,$tmpMsg);
				$tmpMsg = str_replace("[INSERT YEAR]",$confYear,$tmpMsg);
				$tmpMsg = str_replace("[INSERT TITLE]",$title,$tmpMsg);
				$tmpMsg = str_replace("[INSERT DATE]",$date,$tmpMsg);
				$tmpMsg = str_replace("[INSERT START]",$start,$tmpMsg);
				$tmpMsg = str_replace("[INSERT END]",$end,$tmpMsg);
				$tmpMsg = str_replace("[INSERT LEAD EMAIL]",$from,$tmpMsg);
				$tmpMsg = str_replace("[INSERT PDF FILE]",$pdfFile,$tmpMsg);
			}

			$message = $tmpMsg;
			
			//send the email
			$mail_sent = @mail( $to, $subject, $message, $headers );

			if($mail_sent) $sendSuccess[] = array($email,$name);
			else $sendFail[] = array($email,$name);
		}
		
		include "adminTop.php";
?>
		<p style="text-align: center"><a href="send_certificates.php?s=<?=$sessionID?>">Back to Presenter List</a></p>
<?php
		if(count($sendSuccess) > 0) {
?>
		<p style="text-align: left">The following certificates were sent successfully:<br />
<?php
			for($s = 0; $s < count($sendSuccess); $s++) {
?>
		&nbsp; &nbsp; &nbsp; <?=$sendSuccess[$s][1]." (".$sendSuccess[$s][0].")"?><br />
<?php
			}
		}

		if(count($sendFail) > 0) {
?>
		<p style="text-align: left">The following certificates were <strong>NOT</strong> sent successfully:<br />
<?php
			for($f = 0; $f < count($sendFail); $f++) {
?>
		&nbsp; &nbsp; &nbsp; <?=$sendFail[$f][1]." (".$sendFail[$f][0].")"?><br />
<?php
				
				echo "<pre>";
				print_r(error_get_last());
				echo "</pre>";
			}
		}

		include "adminBottom.php";
		exit();
	}

	if(isset($_GET["t"])) {
		$t = strip_tags($_GET["t"]);
		if($t == 1) $eType = "Technology Fairs";
		else if($t == 2) $eType = "Mini-Workshops";
		else if($t == 3) $eType = "Developers Showcase";
		else if($t == 4) $eType = "Mobile Apps for Education Showcase";
		else if($t == 5) $eType = "Technology Fairs Classics";
		else if($t == 6) $eType = "Other";
		else if($t == 7) $eType = "Hot Topics";
		else if($t == 8) $eType = "Graduate Student Research";
	}

	if(isset($_GET["d"])) {
		$d = strip_tags($_GET["d"]);
		if($d == 1) $eDate = $confDate1;
		else if($d == 2) $eDate = $confDate2;
		else if($d == 3) $eDate = $confDate3;
	}
	
	include "getSessions.php";

	if(isset($_GET["s"])) {
		$sesID = strip_tags($_GET["s"]);
	}

	if(isset($_GET["v"])) { //send certificates to the volunteers
		include "adminTop.php";
?>
		<script type="text/javascript">
			function sendCerts() {
				var tmp = document.getElementById('volunteerData').value;
				tmp = tmp.replace(/\n/g,'||');
				document.getElementById('certData').value = tmp;
				document.getElementById('sendCertForm').submit();
			}
		</script>
		<p style="text-align: left">Enter the names and email addresses of each volunteer below. Use the format "name|email". Put each name and email pair on a NEW LINE.</p>
		<p align="center"><input type="button" value="Send Certificates to Volunteers" onClick="sendCerts()" /></p>
		<p align="center"><a href="send_certificates.php">Back to Session List</a></p>
		<table border="0" width="100%">
			<tr>
				<td width="100%">
					<textarea id="volunteerData" name="volunteerData" rows="10" style="width: 100%"></textarea>
				</td>
			</tr>
		</table>
		<form name="sendCertForm" id="sendCertForm" method="post" action="">
			<input type="hidden" name="certData" id="certData" value="" />
			<input type="hidden" name="certEvent" id="certEvent" value="Volunteer" />
		</form>
<?php
		include "adminBottom.php";
		exit();
	}

	if(isset($_GET["s"])) {
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["id"] == $sesID) break;
		}

		$curDate = $sessions[$i]["date"];
		$tmpDate = explode("-",$curDate);
		$tmpMonth = intval($tmpDate[1]);
		$tmpDay = intval($tmpDate[2]);
		$tmpYear = intval($tmpDate[0]);

		$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];

		$tmpTime = explode("-",$sessions[$i]["time"]);
		$tmpStart = explode(":",$tmpTime[0]);
		$tmpSHour = intval($tmpStart[0]);
		$tmpSMinutes = intval($tmpStart[1]);

		$tmpStartTimeStamp = mktime($tmpSHour,$tmpSMinutes,0,$tmpMonth,$tmpDay,$tmpYear);

		if($tmpSHour < 12) $sAMPM = "AM";
		else {
			$sAMPM = "PM";
			if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
		}
		$tmpSMinutes = $tmpStart[1];

		$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";

		$tmpEnd = explode(":",$tmpTime[1]);
		$tmpEHour = intval($tmpEnd[0]);
		$tmpEMinutes = intval($tmpEnd[1]);

		$tmpEndTimeStamp = mktime($tmpEHour,$tmpEMinutes,0,$tmpMonth,$tmpDay,$tmpYear);

		if($tmpEHour < 12) $eAMPM = "AM";
		else {
			$eAMPM = "PM";
			if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
		}
		$tmpEMinutes = $tmpEnd[1];

		$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;

		if($sessions[$i]["location"] == "ev") $locationStr = $evLocationStr;
		else if($sessions[$i]["location"] == "ts") $locationStr = $tsLocationStr;
		
		if($sessions[$i]["event"] == "Technology Fairs") $bgImg = "fairs.jpg";
		else if($sessions[$i]["event"] == "Mini-Workshops") $bgImg = "workshops.jpg";
		else if($sessions[$i]["event"] == "Developers Showcase") $bgImg = "ds.jpg";
		else if($sessions[$i]["event"] == "Mobile Apps for Education Showcase") $bgImg = "mae.jpg";
		else if($sessions[$i]["event"] == "Technology Fairs Classics") $bgImg = "classics.jpg";
		else if($sessions[$i]["event"] == "Hot Topics") $bgImg = "ht.jpg";
		else if($sessions[$i]["event"] == "Graduate Student Research") $bgImg = "grad.jpg";
		else if($sessions[$i]["event"] == "Other") {
		  if(strpos($sessions[$i]["title"],"Academic Session") !== false) $bgImg = "as.jpg";
		  else if(strpos($sessions[$i]["title"],"InterSection") !== false) $bgImg = "intersection.jpg";
		  else if(strpos($sessions[$i]["title"],"Hot Topic") !== false) $bgImg = "ht.jpg";
		  else $bgImg = "evo.jpg";
		}

		include "adminTop.php";
?>
	<style type="text/css">
		th.sList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}

		td.sList_rowEven {
			background-color: #FFFFFF;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.sList_rowOdd {
			background-color: #CCCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.sList_highlighted {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		input.viewBtn {
			background-color: #000000;
			color: #FFFFFF;
			border: solid 1px #000000;
			border-radius: 5px;
			height: 20px;
			padding-right: 10px;
			padding-left: 10px;
		}

		input.viewBtn_selected {
			background-color: #FFFFFF;
			color: #000000;
			border: solid 1px #FFFFFF;
			border-radius: 5px;
			height: 20px;
			padding-right: 10px;
			padding-left: 10px;
		}
		
		#certDiv_bg {
			position: absolute;
			top: 0;
			left: 0;
			height: 100%;
			width: 100%;
			background: rgba(0,0,0,0.6);
			z-index: 1;
		}
		
		#certDiv_mid {
			position: relative;
			height: 100%;
			width: 100%;
			z-index: 2;
		}
		
		#certDiv {
			height: 580px;
			width: 750px;
			position: absolute;
			top: 50%;
			left: 50%;
			margin: -290px 0 0 -375px;
			background-image: url('cert_images/<?=$bgImg?>');
			background-size: 750px 580px;
		}
		
		#certName {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: -105px 0 0 0;
			text-align: center;
			font-size: 20pt;
			z-index: 3;
		}
		
		#certEvent {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: 22px 0 0 0;
			text-align: center;
			font-size: 16pt;
			z-index: 3;
		}
		
		#certTitle {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: 68px 0 0 0;
			text-align: center;
			font-size: 16pt;
			z-index: 3;
		}
		
		#certDate {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: 118px 0 0 0;
			text-align: center;
			font-size: 16pt;
			z-index: 3;
		}
		
		#certClose {
			position: absolute;
			height: 25px;
			width: 100px;
			top: 50%;
			left: 50%;
			margin: -310px 0 0 285px;
			font-size: 12pt;
			color: #FFFFFF;
			font-weight: bold;
			cursor: pointer;
		}
	</style>
	<script type="text/javascript">
		var presentations = new Array();

<?php
		$pArr = $sessions[$i]["presentations"];
		for($j = 0; $j < count($pArr); $j++) {
?>
		presentations[<?=$j?>] = new Array();
		presentations[<?=$j?>]["id"] = '<?=$pArr[$j]["id"]?>';
		presentations[<?=$j?>]["title"] = '<?=addslashes(stripslashes($pArr[$j]["title"]))?>';
		presentations[<?=$j?>]["date"] = '<?=date("l, F j",$tmpStartTimeStamp)?>';
		presentations[<?=$j?>]["start"] = '<?=date("g:i A",$tmpStartTimeStamp)?>';
		presentations[<?=$j?>]["end"] = '<?=date("g:i A",$tmpEndTimeStamp)?>';
<?php
			if(count($pArr[$j]) > 0) {
				if(isset($pArr[$j]["presenters"])) {
					$prArr = $pArr[$j]["presenters"];
					if(count($prArr) > 0) {
?>
		presentations[<?=$j?>]["presenters"] = new Array();
<?php
						for($k = 0; $k < count($prArr); $k++) {
?>
		presentations[<?=$j?>]["presenters"][<?=$k?>] = new Array();
		presentations[<?=$j?>]["presenters"][<?=$k?>]["name"] = '<?=addslashes(stripslashes($prArr[$k]["first_name"]))." ".addslashes(stripslashes($prArr[$k]["last_name"]))?>';
		presentations[<?=$j?>]["presenters"][<?=$k?>]["email"] = '<?=$prArr[$k]["email"]?>';
		presentations[<?=$j?>]["presenters"][<?=$k?>]["selected"] = false;

<?php
						}
					}
				}
			}
		}
?>
		var selectAllClicked = false;

		function checkAll(n) {
			selectAllClicked = true;
			if(n == '') { //check ALL presenters on the page
				var ckEl = document.getElementById('chkAll');
				var sT = presentations.length;
				for(var i = 0; i < sT; i++) {
					var pEl = document.getElementById('chkAll_' + i);
					if(ckEl.checked) {
						pEl.checked = true;
						document.getElementById('chkTD_' + i).className = 'sList_highlighted';
						document.getElementById('pTD_' + i).className = 'sList_highlighted';
						document.getElementById('vTD_' + i).className = 'sList_highlighted';
						document.getElementById('eTD_' + i).className = 'sList_highlighted';
					} else {
						pEl.checked = false;
						if(i % 2 == 0) {
							document.getElementById('chkTD_' + i).className = 'sList_rowEven';
							document.getElementById('pTD_' + i).className = 'sList_rowEven';
							document.getElementById('vTD_' + i).className = 'sList_rowEven';
							document.getElementById('eTD_' + i).className = 'sList_rowEven';
						} else {
							document.getElementById('chkTD_' + i).className = 'sList_rowOdd';
							document.getElementById('pTD_' + i).className = 'sList_rowOdd';
							document.getElementById('vTD_' + i).className = 'sList_rowOdd';
							document.getElementById('eTD_' + i).className = 'sList_rowOdd';
						}
					}

					var pT = presentations[i]['presenters'].length;
					for(var j = 0; j < pT; j++) {
						var thisEl = document.getElementById('chkPres_' + i + '_' + j);
						if(ckEl.checked) thisEl.checked = true;
						else thisEl.checked = false;
						checkPres(i,j);
					}
				}
			} else {
				var ckEl = document.getElementById('chkAll_' + n);
				if(ckEl.checked) {
					document.getElementById('chkTD_' + n).className = 'sList_highlighted';
					document.getElementById('pTD_' + n).className = 'sList_highlighted';
					document.getElementById('vTD_' + n).className = 'sList_highlighted';
					document.getElementById('eTD_' + n).className = 'sList_highlighted';
				} else {
					if(n % 2 == 0) {
						document.getElementById('chkTD_' + n).className = 'sList_rowEven';
						document.getElementById('pTD_' + n).className = 'sList_rowEven';
						document.getElementById('vTD_' + n).className = 'sList_rowEven';
						document.getElementById('eTD_' + n).className = 'sList_rowEven';
					} else {
						document.getElementById('chkTD_' + n).className = 'sList_rowOdd';
						document.getElementById('pTD_' + n).className = 'sList_rowOdd';
						document.getElementById('vTD_' + n).className = 'sList_rowOdd';
						document.getElementById('eTD_' + n).className = 'sList_rowOdd';
					}
				}

				var pT = presentations[n]['presenters'].length;
				for(var i = 0; i < pT; i++) {
					var thisEl = document.getElementById('chkPres_' + n + '_' + i);
					if(ckEl.checked) thisEl.checked = true;
					else thisEl.checked = false;
					checkPres(n,i);
				}
			}

			selectAllClicked = false;
		}

		function selectAll(n) {
			var ckEl = document.getElementById('chkAll_' + n);
			if(ckEl.checked) ckEl.checked = false;
			else ckEl.checked = true;

			checkAll(n);
		}

		function checkPres(s,n) {
			var ckEl = document.getElementById('chkPres_' + s + '_' + n);
			if(ckEl.checked) {
				presentations[s]["presenters"][n]["selected"] = true;
				document.getElementById('chkTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('pTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('vTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('eTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('viewBtn_' + s + '_' + n).className = 'viewBtn_selected';
				document.getElementById('editBtn_' + s + '_' + n).className = 'viewBtn_selected';
			} else {
				presentations[s]["presenters"][n]["selected"] = false;
				if(s % 2 == 0) {
					document.getElementById('chkTD_' + s + '_' + n).className = 'sList_rowEven';
					document.getElementById('pTD_' + s + '_' + n).className = 'sList_rowEven';
					document.getElementById('vTD_' + s + '_' + n).className = 'sList_rowEven';
					document.getElementById('eTD_' + s + '_' + n).className = 'sList_rowEven'
				} else {
					document.getElementById('chkTD_' + s + '_' + n).className = 'sList_rowOdd';
					document.getElementById('pTD_' + s + '_' + n).className = 'sList_rowOdd';
					document.getElementById('vTD_' + s + '_' + n).className = 'sList_rowOdd';
					document.getElementById('eTD_' + s + '_' + n).className = 'sList_rowOdd';
				}
				
				document.getElementById('viewBtn_' + s + '_' + n).className = 'viewBtn';
				document.getElementById('editBtn_' + s + '_' + n).className = 'viewBtn';
			}

			//Check to see if all the presenters for this presentation are checked
			var pT = presentations[s]['presenters'].length;
			var allChecked = true;
			for(var i = 0; i < pT; i++) {
				if(presentations[s]['presenters'][i]['selected'] == false) {
					allChecked = false;
					break;
				}
			}

			var ckAllEl = document.getElementById('chkAll_' + s);
			if(selectAllClicked == false) {
				if(allChecked) { // all are checked, so check the box for the title
					if(ckAllEl.checked == false) {
						document.getElementById('chkAll_' + s).checked = true;
						document.getElementById('chkTD_' + s).className = 'sList_highlighted';
						document.getElementById('pTD_' + s).className = 'sList_highlighted';
						document.getElementById('vTD_' + s).className = 'sList_highlighted';
						document.getElementById('eTD_' + s).classname = 'sList_highlighted';
					}
				} else {
					if(ckAllEl.checked) {
						document.getElementById('chkAll_' + s).checked = false;
						if(s % 2 == 0) {
							document.getElementById('chkTD_' + s).className = 'sList_rowEven';
							document.getElementById('pTD_' + s).className = 'sList_rowEven';
							document.getElementById('vTD_' + s).className = 'sList_rowEven';
							document.getElementById('eTD_' + s).className = 'sList_rowEven';
						} else {
							document.getElementById('chkTD_' + s).className = 'sList_rowOdd';
							document.getElementById('pTD_' + s).className = 'sList_rowOdd';
							document.getElementById('vTD_' + s).className = 'sList_rowOdd';
							document.getElementById('eTD_' + s).className = 'sList_rowOdd';
						}
					}
				}
			}
		}

		function selectPres(s,n) {
			var ckEl = document.getElementById('chkPres_' + s + '_' + n);
			if(ckEl.checked) ckEl.checked = false;
			else ckEl.checked = true;

			checkPres(s,n);
		}

		function sendCerts() {
			var dataStr = '';
			for(var i = 0; i < presentations.length; i++) {
				for(var j = 0; j < presentations[i]['presenters'].length; j++) {
					if(presentations[i]['presenters'][j]['selected'] == true) {
						dataStr += presentations[i]['presenters'][j]['email'] + '|';
						dataStr += presentations[i]['presenters'][j]['name'] + '|';
						dataStr += presentations[i]['title'] + '|';
						dataStr += presentations[i]['date'] + '|';
						dataStr += presentations[i]['start'] + '|';
						dataStr += presentations[i]['end'] + '||';
					}
				}
			}

			document.getElementById('certData').value = dataStr;
			document.getElementById('sendCertForm').submit();
		}
		
		function viewCertificate(s,p) {
			document.getElementById('certName').innerHTML = presentations[s]['presenters'][p]['name'];
			document.getElementById('titleSpan').innerHTML = presentations[s]['title'];
			document.getElementById('certDate').innerHTML = presentations[s]['date'] + ', ' + presentations[s]['start'] + ' - ' + presentations[s]['end'];
			var certDiv = document.getElementById('certDiv_bg');
			certDiv.style.top = document.body.scrollTop + 'px';
			document.getElementById('certDiv_bg').style.display = '';
			document.body.style.overflow = 'hidden';
			
			//now, resize the font to the right size for the title
			var maxW = 590;
			var fSize = 16;
			while(document.getElementById('titleSpan').offsetWidth > maxW) {
				fSize--;
				document.getElementById('titleSpan').style.fontSize = fSize + 'pt';
			}
		}
		
		function hideCertificate() {
			//reset the title span
			document.getElementById('titleSpan').style.fontSize = '16pt';
			document.getElementById('certDiv_bg').style.display = 'none';
			document.body.style.overflow = 'auto';
		}
		
		function editInfo(n) {
			window.location.href = 'editProp.php?id=' + presentations[n]['id'] + '&t=send_certificates.php?s=<?=$sesID?>';
		}
	</script>
	<h2 style="text-align: center; font-family: Arial; margin: 0"><?=$sessions[$i]["title"]?></h2>
	<h3 style="text-align: center; font-family: Arial; margin: 0"><?=$timeStr?> (<?=$dateStr?>)</h3>
	<h3 style="text-align: center; font-family: Arial; margin: 0; margin-bottom: 10px"><?=$locationStr?></h3>
	<p align="center"><input type="button" value="Send Certificates to Checked Presenters" onClick="sendCerts()" /></p>
	<p align="center"><a href="send_certificates.php">Back to Session List</a></p>
	<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse">
		<tr>
			<th class="sList"><input type="checkbox" id="chkAll" onClick="checkAll('')" /></th>
			<th class="sList">&nbsp;</th>
			<th class="sList">&nbsp;</th>
			<th class="sList">&nbsp;</th>
		</tr>
<?php
		$pArr = $sessions[$i]["presentations"];
		for($j = 0; $j < count($pArr); $j++) {
			if($j % 2 == 0) $row_class = "sList_rowEven";
			else $row_class = "sList_rowOdd";

			$pN = $j + 1;
			if(count($pArr[$j]) > 0) {
				if(isset($pArr[$j]["presenters"])) {
					$prArr = $pArr[$j]["presenters"];

					if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs") {
?>
		<tr>
			<td id="chkTD_<?=$j?>" class="<?=$row_class?>"><input type="checkbox" id="chkAll_<?=$j?>" onClick="checkAll('<?=$j?>')" /></td>
			<td id="pTD_<?=$j?>" class="<?=$row_class?>" style="font-style: italic" onClick="selectAll('<?=$j?>')"><?=$pArr[$j]["title"]?> (<?=$pArr[$j]["station"]?>)</td>
			<td id="vTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
			<td id="eTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
		</tr>
<?php
					} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs Classics") {
?>
		<tr>
			<td id="chkTD_<?=$j?>" class="<?=$row_class?>"><input type="checkbox" id="chkAll_<?=$j?>" onClick="checkAll('<?=$j?>')" /></td>
			<td id="pTD_<?=$j?>" class="<?=$row_class?>" style="font-style: italic" onClick="selectAll('<?=$j?>')"><?=$pArr[$j]["title"]?> (<?=$pArr[$j]["station"]?>)</td>
			<td id="vTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
			<td id="eTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
		</tr>
<?php
					} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] != "Technology Fairs") {
?>
		<tr>
			<td id="chkTD_<?=$j?>" class="<?=$row_class?>"><input type="checkbox" id="chkAll_<?=$j?>" onClick="checkAll('<?=$j?>')" /></td>
			<td id="pTD_<?=$j?>" class="<?=$row_class?>" style="font-style: italic" onClick="selectAll('<?=$j?>')"><?=$pArr[$j]["title"]?></td>
			<td id="vTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
			<td id="eTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
		</tr>
<?php
					}

					for($k = 0; $k < count($prArr); $k++) {
?>
		<tr>
			<td id="chkTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>" style="padding-left: 15px"><input type="checkbox" id="chkPres_<?=$j?>_<?=$k?>" onClick="checkPres('<?=$j?>','<?=$k?>')" /></td>
			<td id="pTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>" style="padding-left: 15px" onClick="selectPres('<?=$j?>','<?=$k?>')"><span style="font-weight: bold"><?=stripslashes($prArr[$k]["first_name"]).' '.stripslashes($prArr[$k]["last_name"]).'</span> ('.$prArr[$k]["email"].')'?></td>
			<td id="vTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>"><input type="button" id="viewBtn_<?=$j?>_<?=$k?>" class="viewBtn" value="View Certificate" onClick="viewCertificate('<?=$j?>','<?=$k?>')"></td>
			<td id="eTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>"><input type="button" id="editBtn_<?=$j?>_<?=$k?>" class="viewBtn" value="Edit Info" onClick="editInfo('<?=$j?>')"></td>
		</tr>
<?php
					}
				}
			}
		}

		if($sessions[$i]["event"] == "Other") $thisEvent = $sessions[$i]["title"];
		else $thisEvent = $sessions[$i]["event"];
?>
	</table>
	<form name="sendCertForm" id="sendCertForm" method="post" action="">
		<input type="hidden" name="certData" id="certData" value="" />
		<input type="hidden" name="certEvent" id="certEvent" value="<?=$thisEvent?>" />
		<input type="hidden" name="certSession" id="certSession" value="<?=$_GET["s"]?>" />
	</form>
	<div id="certDiv_bg" style="display: none">
		<div id="certDiv_mid">
			<div id="certDiv"></div>
			<div id="certName">Justin Shewell</div>
			<div id="certTitle"><span id="titleSpan">Test Title</span></div>
			<div id="certEvent"><?=$thisEvent?></div>
			<div id="certDate">Wednesday, April 6, 8:30 - 9:20 AM</div>
			<div id="certClose" onClick="hideCertificate()">CLOSE &times;</div>
		</div>
	</div>
<?php
		include "adminBottom.php";
		exit();
	}

	include "adminTop.php";
?>
	<style type="text/css">
		th.sList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}

		td.sList_rowEven {
			background-color: #FFFFFF;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.sList_rowOdd {
			background-color: #CCCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.sList_highlighted {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}		
	</style>
	<script type="text/javascript">
		function highlightRow(e,r,n) {
			var rEl = document.getElementById('session' + e + '_row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) cEl.className = 'sList_highlighted';
				else if(n == 0) {
					if(parseInt(r) % 2 == 0) cEl.className = 'sList_rowEven';
					else cEl.className = 'sList_rowOdd';
				}
			}
		}

		function doSend(n) {
			var url = 'send_certificates.php?s=' + n;
			window.location.href = url;
		}
	</script>
		<p align="left">Click on a session to send the certificates to those presenters for that particular session.</p>
		<p align="right"><input type="button" value="Send Certificates to Volunteers" onClick="window.location.href='send_certificates.php?v=1'" /></p>
<?php
	$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Hot Topics","Graduate Student Research","Technology Fairs Classics","Other"); //get all event types -- Classroom of the future is not held in the EV, so it's not on our schedule

	for($e = 0; $e < count($eTypes); $e++) {
		ob_start();
		$rN = 0;
		$totalPrCount = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == $eTypes[$e]) { //list this session
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';

				//get the number of presentations scheduled for this session
				$pCount = count($sessions[$i]["presentations"]);
				$prCount = 0;
				for($p = 0; $p < $pCount; $p++) {
				  $prCount = $prCount + count($sessions[$i]["presentations"][$p]["presenters"]);
				}

				$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
				$tmpDate = explode("-",$sessions[$i]["date"]);
				$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];

				$tmpTime = explode("-",$sessions[$i]["time"]);
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
		<tr id="session<?=$e?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" width="400" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$sessions[$i]['title']?></td>
			<td class="<?=$rowClass?>" width="120" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$dateStr?></td>
			<td class="<?=$rowClass?>" width="170" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$timeStr?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$prCount?></td>
		</tr>
<?php
				$rN++;
				$totalPrCount = $totalPrCount + $prCount;
			}
		}

		$rows = ob_get_contents();
		ob_end_clean();
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?=$eTypes[$e]?> (Total # of Certificates: <?=$totalPrCount?>)</td>
		</tr>
		<tr>
			<th class="sList">Title</td>
			<th class="sList">Date</td>
			<th class="sList">Time</td>
			<th class="sList" style="text-align: center"># of Presenters</td>
		</tr>
<?php
		echo $rows;
?>
	</table><br /><br />
<?php
	}

	include "adminBottom.php";
?>