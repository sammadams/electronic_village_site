<?php
	ini_set('error_reporting','E_ALL');
	ini_set('display_errors','On');
	//ini_set('display_startup_errors','1');
	
	include_once "../../ev_config.php";
	include_once "../../ev_library.php";
	
	$evtID = preg_replace("/\D/", "", $_POST["event_id"]);
	$evtStmt = $db->prepare("SELECT webTitle, coordinatorEmail FROM events WHERE id = ?");
	$evtStmt->bind_param('s', $evtID);
	$evtStmt->execute();
	$evtStmt->bind_result($webTitle, $coordinatorEmail);
	$evtStmt->fetch();
	$evtStmt->close();
	
	$from = $coordinatorEmail;
		
	//First, insert the presenters into the presenters table
	$presStr = "";
	$mainContact = "";
	$tmp = explode("||",$_POST['prop_presenters']);
	for($i = 1; $i < count($tmp); $i++) { //the first element is blank
		$tmpP = explode("|",$tmp[$i]);
		$thisP = array();
		for($j = 0; $j < count($tmpP); $j++) {
			list($tmpK, $tmpV) = explode("=",$tmpP[$j]);
			$thisP[$tmpK] = strip_tags($tmpV); //removes PHP and HTML tags from the string
		}
		
		$pQ_stmt = $db->prepare("INSERT INTO presenters (`ID`, `Prefix`, `First Name`, `Last Name`, `Title`, `City`, `State`, `Province`, `Postal Code`, `Country`, `Phone`, `Extension`, `Fax`, `Email`, `Member`, `Student`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `First Time`, `Certificate`) VALUES ('0',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'0')");
		
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
			echo "Error: ".$pQ_stmt->error." (INSERT PRESENTERS)";
			exit();
		}
				
		$presStr .= $db->insert_id."|";
		
		if($thisP['role'] == "main") $mainContact = $thisP['email'];
	}

	if(strlen(strip_tags($_POST["prop_password"])) != 128) {
		//header('Location: error.php?err=Password error: LENGTH');
		echo "Password error: length";
		exit();
	}

	$password = strip_tags($_POST["prop_password"]);
	
	$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
	$password = hash('sha512', $password . $random_salt);
	
	//Now, enter the other proposal information into the database
	$q_stmt = $db->prepare("INSERT INTO proposals (`id`, `title`, `contact`, `presenters`, `times`, `topics`, `computer`, `summary`, `abstract`, `password`, `salt`, `comments`, `photoOK`, `emailOK`, `type`, `status`) VALUES ('0',?,?,?,?,?,?,?,?,?,?,?,'1','0',?,'new')");
	
	$timesStr = trim(strip_tags($_POST["prop_times"]),"|");
	$topicsStr = trim(strip_tags($_POST["prop_topics"]),"|");

	$q_stmt->bind_param('ssssssssssss',strip_tags($_POST['prop_title']), $mainContact, trim($presStr,"|"), $timesStr, $topicsStr, $_POST["prop_computer"], strip_tags($_POST["prop_summary"]), strip_tags($_POST["prop_abstract"]), $password, $random_salt, strip_tags($_POST["prop_comments"]), $_POST["prop_type"]);

	if(!$q_stmt->execute()) {
		echo "Error: ".$q_stmt->error." (INSERT PROPOSAL)";
		exit();
	}
	
	$propID = $db->insert_id;

	/*
		If we get this far, then everything has been saved, so we can show the confirmation page to the submitter. We will basically print out all the information they submitted and tell them an email has been sent to the main contact email.
	*/


	//define the receiver of the email
	$to = $mainContact;

	//define the subject of the email
	$subject = "Electronic Village ".$confYear.": ".$_POST["prop_type"]." (Submission Confirmation)";
	
	//create a boundary string. It must be unique so we use the MD5 algorithm to generate a random hash
	$random_hash = md5(date('r', time())); 

	//define the headers we want passed. Note that they are separated with \r\n
	$headers = "From: ".$from."\r\nReply-To: ".$from;

	//add boundary string and mime type specification
	$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"CALL-EV-".$random_hash."\"";
	$headers .= "\r\nMIME-Version: 1.0"; 

	/*
		The main text of the message is stored in a text file on the server.
		We need to get that text and then put in the appropriate values where needed.
	*/
	
	
	$tmpMsg = file_get_contents("savePropEmail.txt");
	$tmpPres = "";
	$tmpPresRows = "";
	
	//now, add in new rows with the presenters information
	$tmp = explode("||",stripslashes($_POST['prop_presenters'])); //remove slashes for output to browser and/or email
	
	for($i = 1; $i < count($tmp); $i++) { //the first element is blank
		if($i % 2 == 0) $bgColor = '#CCCCCC';
		else $bgColor = '#FFFFFF';
		$tmpP = explode("|",$tmp[$i]);
		$thisP = array();
		for($j = 0; $j < count($tmpP); $j++) {
			list($tmpK, $tmpV) = explode("=",$tmpP[$j]);
			$thisP[$tmpK] = strip_tags($tmpV); //removes PHP and HTML tags from the string
		}
		
		$tmpPres .= "     ".$thisP['first_name']." ".$thisP['last_name']." (".$thisP['email'].")\n";
		$tmpPresRows .= "\n				<tr>\n					<td style=\"background-color: ".$bgColor."; text-align: center; font-size: 10pt; font-weight: bold\">";
		if($thisP['role'] == "main") $tmpPresRows .= ">>";
		else $tmpPresRows .= "&nbsp;";
		
		$tmpPresRows .= "</td>\n					<td style=\"background-color: ".$bgColor."; text-align: left\">".$thisP['first_name']." ".$thisP['last_name']."</td>\n					<td style=\"background-color: ".$bgColor."; text-align: left\">".$thisP['email']."</td>\n				</tr>\n";
	}

	$tmpTimes = "";
	$tmpTimesRows = "";
	if($_POST["prop_times"] && $_POST["prop_times"] != "") {
		$tmpTimes .= "Presentation Time(s):\n";
		$tmpTimesRows .= "			<tr>\n		<td style=\"border-top: solid 1px #CCCCCC; padding: 20px\">\n			<span style=\"font-weight: bold\">Presentation Time(s)</span><br />\n			<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\">\n				<tr>\n					<td>\n";
		$tmpT = explode("|",$_POST["prop_times"]);
		for($t = 1; $t < count($tmpT); $t++) {
			$tmpTimes .= "     $tmpT[$t]\n";
			$tmpTimesRows .= $tmpT[$t]."<br />";
		}

		$tmpTimesRows .= "\n					</td>\n				</tr>\n			</table>\n		</td>\n	</tr>\n";
	}

	$tmpTopics = "";
	$tmpTopicsRows = "";
	$tmpT = explode("|",strip_tags(stripslashes($_POST["prop_topics"]))); //strip tags because of the "other" option
	for($t = 1; $t < count($tmpT); $t++) {
		$tmpTopics .= "     ".$tmpT[$t]."\n";
		$tmpTopicsRows .= $tmpT[$t]."<br />";
	}
	
	if($tmpTopics != "") $tmpTopics = "Presentation Topic(s):\n".$tmpTopics;
	if($tmpTopics != "") $tmpTopicsRows = "	<tr>\n		<td style=\"border-top: solid 1px #CCCCCC; padding: 20px\">\n			<span style=\"font-weight: bold\">Presentation Topic(s)</span><br />\n			<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\">\n				<tr>\n					<td>\n						".$tmpTopicsRows."\n					</td>\n				</tr>\n			</table>\n		</td>\n	</tr>\n";

	$tmpComputer = "";
	$tmpComputerRows = "";
	if($_POST["prop_computer"] && $_POST["prop_computer"] != "") {
		$tmpComputer .= "Computer Preference:\n";
		$tmpComputerRows .= "	<tr>\n		<td style=\"border-top: solid 1px #CCCCCC; padding: 20px\">\n			<span style=\"font-weight: bold\">Computer Preference</span><br />\n			<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\">\n				<tr>\n					<td>\n";

		if($_POST["prop_computer"] == "PC") {
			$tmpComputer .= "     Windows (PC)\n";
			$tmpComputerRows .= "Windows (PC)";
		} else if($_POST["prop_computer"] == "Mac") {
			$tmpComputer .= "     Macintosh (Apple)\n";
			$tmpComputerRows .= "Macintosh (Apple)";
		} else if($_POST["prop_computer"] == "Either") {
			$tmpComputer .= "     Either Windows (PC) or Macintosh (Apple)\n";
			$tmpComputerRows .= "Either Windows (PC) or Macintosh (Apple)";
		} else if($_POST["prop_computer"] == "None") {
			$tmpComputer .= "     I will bring my own device.\n";
			$tmpComputerRows .= "I will bring my own device.";
		}

		$tmpComputerRows .= "\n					</td>\n				</tr>\n			</table>\n		</td>\n	</tr>\n";
	}	
	
	$tmpSummary = strip_tags(stripslashes($_POST["prop_summary"]));
	$tmpAbstract = strip_tags(stripslashes($_POST["prop_abstract"]));
	$tmpComments = strip_tags(stripslashes($_POST["prop_comments"]));
	
	$tmpMsg = str_replace("[INSERT RANDOM HASH]",$random_hash,$tmpMsg);
	$tmpMsg = str_replace("[INSERT PROP ID]",$propID,$tmpMsg);
	$tmpMsg = str_replace("[INSERT TITLE]",strip_tags(stripslashes($_POST["prop_title"])),$tmpMsg);
	$tmpMsg = str_replace("[INSERT MAIN CONTACT]",$mainContact,$tmpMsg);
	$tmpMsg = str_replace("[INSERT TIMES]",$tmpTimes,$tmpMsg);
	$tmpMsg = str_replace("[INSERT TOPICS]",$tmpTopics,$tmpMsg);
	$tmpMsg = str_replace("[INSERT COMPUTER PREFERENCE]",$tmpComputer,$tmpMsg);
	$tmpMsg = str_replace("[INSERT SUMMARY]",$tmpSummary,$tmpMsg);
	$tmpMsg = str_replace("[INSERT ABSTRACT]",$tmpAbstract,$tmpMsg);
	$tmpMsg = str_replace("[INSERT COMMENTS]",$tmpComments,$tmpMsg);
	$tmpMsg = str_replace("[INSERT YEAR]",$confYear,$tmpMsg);
	$tmpMsg = str_replace("[INSERT PROP TYPE]",$_POST["prop_type"],$tmpMsg);
	$tmpMsg = str_replace("[INSERT TIMES ROWS]",$tmpTimesRows,$tmpMsg);
	$tmpMsg = str_replace("[INSERT TOPICS ROWS]",$tmpTopicsRows,$tmpMsg);
	$tmpMsg = str_replace("[INSERT COMPUTER PREFERENCE ROW]",$tmpComputerRows,$tmpMsg);
	$tmpMsg = str_replace("[INSERT PRESENTERS]",$tmpPres,$tmpMsg);
	$tmpMsg = str_replace("[INSERT PRESENTERS ROWS]",$tmpPresRows,$tmpMsg);
	$tmpMsg = str_replace("[INSERT TYPE EMAIL]",$from,$tmpMsg);

	//copy current buffer contents into $message variable and delete current output buffer
	$message = $tmpMsg;

	//send the email
	$mail_sent = @mail( $to, $subject, $message, $headers );

	//if the message is sent successfully print out the confirmation page
	if($mail_sent) {
?>
<html>
	<head>
		<title>Electronic Village Proposals</title>
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
		</style>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	</head>
	
	<body>
		<div id="pagecontainer">
			<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
				<tr>
					<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
				</tr>
				<tr>
					<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $confYear; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold"><?php echo $webTitle; ?> (Submission Confirmation)</span></td>
				</tr>
				<tr>
					<td style="padding: 20px">Your submission has been saved! Below is the information you submitted for your reference. An email has also been sent to <span style="font-weight: bold; color: blue"><?php echo $mainContact; ?></span> with the same information. Please save a copy of this information for your records. If you need to edit your submission (<span style="font-weight: bold; color: red">only available up until the submission deadline</span>), please go to <a href="http://call-is.org/ev/edit.php?id=<?php echo $propID; ?>">http://call-is.org/ev/edit.php?id=<?php echo $propID; ?></a> and enter your password.<br /><br />If you have questions about your submission, or general questions about the <?php echo $_POST["prop_type"]; ?> event, please contact <a href="mailto:<?php echo $from; ?>"><?php echo $from; ?></a>.</td>
				</tr>
				<tr>
					<td style="padding-top: 20px; font-weight: bold; font-size: 14pt">Submitted Information</td>
				</tr>
				<tr>
					<td style="border-top: solid 1px #CCCCCC; padding: 20px">
						<table border="0" cellspacing="0" cellpadding="0" width="100%">
							<tr>
								<td width="50" valign="top" style="font-weight: bold">Title:</td>
								<td width="710"><?php echo strip_tags(stripslashes($_POST["prop_title"])); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td id="presentersTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Presenters</span><br />
						<table id="presentersTable" border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td style="font-weight: bold; font-size: 10pt; text-align: center; width: 100px; border-bottom: solid 1px #000000">Main Contact</td>
								<td style="font-weight: bold; font-size: 10pt; text-align: left; width: 300px; border-bottom: solid 1px #000000">Name</td>
								<td style="font-weight: bold; font-size: 10pt; text-align: left; width: 300px; border-bottom: solid 1px #000000">Email</td>
							</tr>
<?php
	//now, add in new rows with the presenters information
	$tmp = explode("||",stripslashes($_POST['prop_presenters']));
	for($i = 1; $i < count($tmp); $i++) { //the first element is blank
		if($i % 2 == 0) $bgColor = '#CCCCCC';
		else $bgColor = '#FFFFFF';
		$tmpP = explode("|",$tmp[$i]);
		$thisP = array();
		for($j = 0; $j < count($tmpP); $j++) {
			list($tmpK, $tmpV) = explode("=",$tmpP[$j]);
			$thisP[$tmpK] = strip_tags($tmpV); //removes PHP and HTML tags from the string
		}
?>
							<tr>
								<td style="background-color: <?php echo $bgColor; ?>; text-align: center; font-size: 10pt; font-weight: bold"><?php if($thisP['role'] == "main") { ?>&gt;&gt;<?php } else { ?>&nbsp;<?php } ?></td>
								<td style="background-color: <?php echo $bgColor; ?>; text-align: left"><?php echo $thisP['first_name']." ".$thisP['last_name']; ?></td>
								<td style="background-color: <?php echo $bgColor; ?>; text-align: left"><?php echo $thisP['email']; ?></td>
							</tr>
<?php
	}
?>
						</table>
					</td>
				</tr>
<?php
	if($_POST["prop_times"] && $_POST["prop_times"] != "") {
?>
				<tr>
					<td id="timesTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Presentation Time(s)</span><br />
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td>
<?php
		$tmpTimes = explode("|",$_POST["prop_times"]);
		for($t = 1; $t < count($tmpTimes); $t++) {
			echo $tmpTimes[$t]."<br />";
		}
?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?php
	}
	
	if($tmpTopics != "") {
?>
				<tr>
					<td id="topicsTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Presentation Topic(s)</span><br />
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td>
<?php
		$tmpTopics = explode("|",stripslashes(strip_tags($_POST["prop_topics"]))); //strip tags because of the "other" option
		for($t = 1; $t < count($tmpTopics); $t++) {
			echo $tmpTopics[$t]."<br />";
		}
?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?php
	}
	
	if($_POST["prop_computer"] && $_POST["prop_computer"] != "") {
?>			
				<tr>
					<td id="timesTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Computer Preference</span><br />
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td>
<?php
		if($_POST["prop_computer"] == "PC") echo "Windows (PC)";
		else if($_POST["prop_computer"] == "Mac") echo "Macintosh (Apple)";
		else if($_POST["prop_computer"] == "Either") echo "Either Windows (PC) or Macintosh (Apple)";
		else if($_POST["prop_computer"] == "None") echo "I will bring my own device.";
?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?php
	}
?>
				<tr>
					<td id="summaryTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Summary</span><br />
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><?php echo stripslashes(strip_tags($_POST["prop_summary"])); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td id="abstractTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Abstract</span><br />
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><?php echo stripslashes(strip_tags($_POST["prop_abstract"])); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td id="summaryTD" style="border-top: solid 1px #CCCCCC; padding: 20px">
						<span style="font-weight: bold">Comments to Event Organizers</span><br />
						<table border="0" cellspacing="0" cellpadding="5" width="100%">
							<tr>
								<td><?php echo stripslashes(strip_tags($_POST["prop_comments"])); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
<?php
	} else {
		echo "Mail failed!";
	}
?>