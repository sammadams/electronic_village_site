<?php
	include_once('../../ev_config.php');
	include_once('../../ev_library.php');
	
	if(!$propOpen) redirect('propClosed.php');
	
	$evt = preg_replace("/\D/", "", $_GET["evt"]);
	$evtStmt = $db->prepare("SELECT id, event, webDescription, propLink, webTitle, coordinator, coordinatorEmail FROM events WHERE getsProposals = 'Y' AND isActive = 'Y' AND id = ?");
	$evtStmt->bind_param('s', $evt);
	$evtStmt->execute();
	$evtStmt->bind_result($evtID, $evtEvent, $evtWebDescription, $evtPropLink, $evtWebTitle, $evtCoordinator, $evtCoordinatorEmail);
	$evtStmt->fetch();
	$evtStmt->close();
	
	$spStmt = $db->prepare("SELECT title, summary, abstract FROM sample_proposals WHERE visible = 'Y' AND event = ?");
	$spStmt->bind_param('s', $evt);
	$spStmt->execute();
	$spStmt->bind_result($spTitle, $spSummary, $spAbstract);
	
	$sample_proposals = array();
	while($spStmt->fetch()) {
		$sample_proposals[] = array(
			'title' => $spTitle,
			'summary' => $spSummary,
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
				<td style="padding-top: 20px; padding-bottom: 20px; border-bottom: solid 1px #CCCCCC">You are invited to submit a proposal for participation in <?php echo $evtWebTitle; ?>:<br /><br />
					<?php echo $evtWebDescription; ?>

					<br /><br />
					<span style="font-weight: bold">Coordinator(s):</span> <?php echo $evtCoordinator; ?> (<a href="mailto:<?php echo $evtCoordinatorEmail; ?>"><?php echo $evtCoordinatorEmail; ?></a>)<br /><br />

					You may submit your proposal for the <?php echo $evtWebTitle; ?> >> <a href="prop.php?t=<?php echo $evtID; ?>">here</a>.
				</td>
			</tr>
			<tr>
				<td style="padding-bottom: 20px;"><h2>Sample Proposals</h2></td>
			</tr>
<?php
	foreach($sample_proposals AS $sp) {
?>

			<tr>
				<td style="border-bottom: solid 1px #CCCCCC">
					<table border="0" width="100%">
						<tr><td width="100" style="font-weight: bold; padding-bottom: 20px; vertical-align: top;">Title:</td><td style="vertical-align: top; padding-bottom: 20px;"><?php echo $sp['title']; ?></td></tr>
						<tr><td width="100" style="font-weight: bold; padding-bottom: 20px; vertical-align: top;">Summary:</td><td style="vertical-align: top; padding-bottom: 20px;"><?php echo $sp['summary']; ?></td></tr>
						<tr><td width="100" style="font-weight: bold; padding-bottom: 20px; vertical-align: top;">Abstract:</td><td style="vertical-align: top; padding-bottom: 20px;"><?php echo $sp['abstract']; ?></td></tr>
					</table>
				</td>
			</tr>
<?php	
	}
?>
		</table>
	</body>
</html>