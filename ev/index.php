<?php
	include_once('./ev_config.php');
	include_once('./ev_library.php');
	// include_once('./sched_main.php');

	// if(!$propOpen) redirect('propClosed.php');

	$evtStmt = $db->prepare("SELECT id, event, webDescription, propLink, webTitle, coordinator, coordinatorEmail FROM events WHERE getsProposals = 'Y' AND isActive = 'Y' ORDER BY id ASC");
	$evtStmt->execute();
	$evtStmt->bind_result($evtID, $evtEvent, $evtWebDescription, $evtPropLink, $evtWebTitle, $evtCoordinator, $evtCoordinatorEmail);
	
	$events = array();
	while($evtStmt->fetch()) {
		$events[] = array(
			"id" => $evtID,
			"event" => $evtEvent,
			"webDescription" => $evtWebDescription,
			"propLink" => $evtPropLink,
			"webTitle" => $evtWebTitle,
			"coordinator" => $evtCoordinator,
			"coordinatorEmail" => $evtCoordinatorEmail
		);
	}
	
	$evtStmt->close();
	
	$spStmt = $db->prepare("SELECT e.event, sp.title, sp.summary, sp.abstract FROM events AS e, sample_proposals AS sp WHERE e.id = sp.event AND visible = 'Y'");
	$spStmt->execute();
	$spStmt->bind_result($spEvent, $spTitle, $spSummary, $spAbstract);
	
	$sample_proposals = array();
	while($spStmt->fetch()) {
		if(!array_key_exists($spEvent, $sample_proposals)) $sample_proposals[$spEvent] = array();
		$sample_proposals[$spEvent] = array(
			'title' => $spTitle,
			'sumary' => $spSummary,
			'abstract' => $spAbstract
		);
	}
	
	$spStmt->close();
	

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
		</style>
		<link rel="icon" type="image/png" href="https://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $confYear; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Call for Proposals</span><br /><br /><span style="font-size: 14pt; font-weight: bold">Deadline for submissions:</span> <span style="font-size: 14pt; font-weight: bold; color: red"><?php echo date("F j, Y", $propCloseDate); ?></span></td>
			</tr>
			<tr>
				<td style="padding-top: 20px; padding-bottom: 20px; border-bottom: solid 1px #CCCCCC">You are invited to submit a proposal for participation in one or more of the CALL Interest Section's Electronic Village Special Events. More than one proposal from the same individual may be accepted based upon space availability.<br /><br />Click on an event for a detailed description and link to a proposal submission form.<br /><br />
<?php
	foreach($events AS $evt) {
?>
					<a href="#<?php echo $evt['propLink']; ?>"><?php echo $evt['webTitle']; ?></a><br />
<?php
	}
?>
					<br /><br />
					<span style="font-style: italic; font-weight: bold">Equipment:</span><br>
					The following technology will be available at no charge:
					<ul>
						<li>PCs and Macintosh computers (please specify when submitting proposals)</li>
						<li>Microphones</li>
						<li>Internet connections</li>
						<li>Projection equipment for Developers' Showcase, Mini-Workshops, and Mobile Apps for Education</li>
					</ul><br />

					For presentations requiring mobile devices or other hardware, the presenter is responsible for supplying the required equipment. Presenters are welcome to bring their own equipment (e.g. laptops, digital cameras, mobile devices, etc.); however, the Electronic Village cannot guarantee compatibility with our projection equipment or Internet connections. For the Mobile Apps for Education Showcase, presenters will be able to display their mobile device screens to the audience. However, some applications may be reliant on the convention center wi-fi &#151; please be prepared with screenshots in case of difficulties.<br /><br />
 
					<span style="font-style: italic; font-weight: bold">Handouts:</span><br />
					It is recommended that presenters of accepted proposals bring 20-30 copies of presentation handouts for Technology Fairs, Developers' Showcase, and Mobile Apps for Education Showcase. For Mini-Workshops, prepare materials for 20 participants maximum. Before your presentation, please consider creating an online version of your handout, uploading a copy to the appropriate area on the CALL-IS website, or adding an online version of your handout to the CALL-IS library in the TESOL CALL-IS Community.
				</td>
			</tr>
<?php
	foreach($events AS $evt) {
?>
			<tr>
				<td style="padding-top: 20px; padding-bottom: 20px; border-bottom: solid 1px #CCCCCC">
					<a name="<?php echo $evt['propLink']; ?>">
					<span style="font-style: italic; font-weight: bold; font-size: 20pt"><?php echo strtoupper($evt['webTitle']); ?></span><br /><br />
					<?php echo $evt['webDescription']; ?>

					<br /><br />
					<span style="font-weight: bold">Coordinator(s):</span> <?php echo $evt['coordinator']; ?> (<a href="mailto:<?php echo $evt['coordinatorEmail']; ?>"><?php echo $evt['coordinatorEmail']; ?></a>)<br /><br />
<?php
		if(count($sample_proposals[$evt['event']]) > 0) {
?>					
					<a href="sampleProp.php?evt=<?php echo $evt['id']; ?>" target="_blank">View sample proposals for <?php echo $evt['webTitle']; ?></a><br /><br />
<?php
		}
?>
					You may submit your proposal for the <?php echo $evt['webTitle']; ?> >> <a href="prop.php?t=<?php echo $evt['id']; if($_GET["db"]) { echo '&db=1'; } ?>">here</a>.<br /><br />

					< <a href="#">Page top</a> >
				</td>
			</tr>
<?php
	}
?>
		</table>
	</body>
</html>