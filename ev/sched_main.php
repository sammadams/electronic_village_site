<?php

	/**************************************************************************************************
	 *	CONFIGURATION - These should be pulled from the database (configs table) or manually entered  *
	 **************************************************************************************************/
	$confStart = strtotime($configs['confStartDate']);
	$confEnd = strtotime($configs['confEndDate']);
	$confLocation = $configs['confLocation'];
	$locationWidth = $configs['schedLocationWidth']; //"330";
	$dateWidth = $configs['schedDateWidth']; //"110";
	$timeWidth = $configs['schedTimeWidth']; //"150";
	$pdfFileName = $configs['schedPDFFileName']; //"ev_program_2019.pdf";
	$mapFileName = $configs['schedMapFileName']; //"ev_map_2019.jpg";
	$showPDF = $configs['schedShowPDF']; //true;
	$showMap = $configs['schedShowMap']; //true;
	$isAllowed = $configs['schedIsAllowed']; //true; //the schedule is ready to be published - set to "false" if not ready


	/**************************************************************************************************
	 *	Summary for CALL for Newcomers because there is no actual presentation information in the DB  *
	 **************************************************************************************************/
	$cfnSummary = $configs['schedCFNSummary']; //"Learn CALL basics from experts and enhance your teaching with digital resources. This event includes hands-on guided practice in the Electronic Village on a variety of introductory CALL techniques and tools.";
	
	/**************************************************************************************************
	 *	Summary for Best of EVO sessions because there is no actual presentation information in the DB  *
	 **************************************************************************************************/
	$evoSummary =  $configs['schedEVOSummary']; //"This session is led by Electronic Village Online (EVO) Coordinators and EVO session moderators who will highlight outstanding sessions from this year's EVO. Every year, for five weeks in January-February, participants and ESOL experts engage in collaborative, online discussion or hands-on virtual workshops of professional and scholarly benefit.";
	
	$y = date("Y",$confStart);
	
	$tzStr = $configs['confTimeZone'];
	$tzObj = new DateTimeZone($tzStr);
	$dtObj = new DateTime($configs['confStartDate'], $tzObj);
	$tzOffset = $dtObj->getOffset() / 3600; // we need the offset in hours
	$gmtConversion = $tzOffset * -1; // we want to reverse the number to add it to the current time - the hour difference between the location and GMT (Atlanta is GMT -4, so add 4 to get GMT)
	
	/**************************************************************************************************
	 *	                                        END CONFIGURATION  *
	 **************************************************************************************************/

	if($isAllowed || (isset($_GET["db"]) && $_GET["db"] == "1")) { //allowed or we are debugging	

		//First, get all the schedule information from the database
		$qStr = "SELECT sessions.*, locations.name, locations.room, events.event, events.propTable FROM `sessions` LEFT JOIN `locations` ON sessions.location = locations.id LEFT JOIN events on sessions.event = events.id";
		if(isset($_GET["o"])) {
			$qStr .= " ORDER BY ";
			$order = strip_tags($_GET["o"]);
			if($order == "1") $qStr .= "`date`";
			else if($order == "2") $qStr .= "`time`";
			else if($order == "3") $qStr .= "`event`";
		} else $qStr .= " ORDER BY `date` ASC, `time` ASC, `location` DESC";
	
		$qStmt = $db->prepare($qStr);
		$qStmt->execute();
		$qStmt->bind_result($sID,$sLocationID,$sDate,$sTime,$sEventID,$sTitle,$sPresentations,$sLocationName, $sRoom, $sEvent, $sPropTable);
	
		$sessions = array();
		while($qStmt->fetch()) {
			$sessions[] = array(
				"id" => $sID,
				"location" => $sLocationName.' ('.$sRoom.')',
				"date" => $sDate,
				"time" => $sTime,
				"event" => $sEvent,
				"eventID" => $sEventID,
				"propTable" => $sPropTable,
				"title" => $sTitle,
				"presentations" => $sPresentations
			);
		}

		//get the proposal information
		$pStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `proposals` WHERE 1");
		$pStmt->execute();
		$pStmt->bind_result($pID,$pTitle,$pPresenters,$pSummary);
		$proposals = array();
		while($pStmt->fetch()) {
			$proposals[] = array(
				"id" => $pID,
				"title" => $pTitle,
				"presenters" => $pPresenters,
				"summary" => $pSummary
			);
		}
	
		//get the presenters information
		$prStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `Certificate` FROM `presenters`");
		$prStmt->execute();
		$prStmt->bind_result($prID,$prFN,$prLN,$prEmail,$prAN,$prAC,$prPE,$prCert);
		$presenters = array();
		while($prStmt->fetch()) {
			if($prAC == "United States") $prAC = "USA";
			else if($prAC == "Korea, South") $prAC = "South Korea";
			else if($prAC == "Russian Federation") $prAC = "Russia";
			else if($prAC == "United Arab Emirates") $prAC = "UAE";
			else if($prAC == "United Kingdom") $prAC = "UK";
			else if($prAC == "United States Minor Outlying Islands") $prAC = "USA";
			$presenters[] = array(
				"id" => $prID,
				"first_name" => $prFN,
				"last_name" => $prLN,
				"email" => $prEmail,
				"affiliation" => $prAN,
				"country" => $prAC,
				"emailOK" => $prPE,
				"certificate" => $prCert
				
			);
		}
	
		//get the station names
		$stStmt = $db->prepare("SELECT * FROM `stations` WHERE 1");
		$stStmt->execute();
		$stStmt->bind_result($stID,$stName);
		$stations = array();
		while($stStmt->fetch()) {
			$stations[] = array(
				"id" => $stID,
				"name" => $stName
			);
		}
	
		//now, update the proposals array with the presenters information
		for($i = 0; $i < count($proposals); $i++) {
			$tmp = explode("|",$proposals[$i]["presenters"]);
			$thisPres = array();
			for($j = 0; $j < count($tmp); $j++) {
				for($k = 0; $k < count($presenters); $k++) {
					if($tmp[$j] == $presenters[$k]["id"]) {
						$thisPres[] = $presenters[$k];
						break;
					}
				}
			}
		
			$proposals[$i]["presenters"] = $thisPres;
		}
	
		//now, do the same with the "other" presentations and presenters
		//get the proposal information
		$opStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `other_proposals`");
		$opStmt->execute();
		$opStmt->bind_result($opID,$opTitle,$opPresenters,$opSummary);
		$other_proposals = array();
		while($opStmt->fetch()) {
			$other_proposals[] = array(
				"id" => $opID,
				"title" => $opTitle,
				"presenters" => $opPresenters,
				"summary" => $opSummary
			);
		}
	
		//get the presenters information
		$oprStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `Certificate` FROM `other_presenters`");
		$oprStmt->execute();
		$oprStmt->bind_result($oprID,$oprFN,$oprLN,$oprEmail,$oprAN,$oprAC,$oprPE,$oprCert);
		$other_presenters = array();
		while($oprStmt->fetch()) {
			if($oprAC == "United States") $oprAC = "USA";
			else if($oprAC == "Korea, South") $oprAC = "South Korea";
			else if($oprAC == "Russian Federation") $oprAC = "Russia";
			else if($oprAC == "United Arab Emirates") $oprAC = "UAE";
			else if($oprAC == "United Kingdom") $oprAC = "UK";
			else if($oprAC == "United States Minor Outlying Islands") $oprAC = "USA";
			$other_presenters[] = array(
				"id" => $oprID,
				"first_name" => $oprFN,
				"last_name" => $oprLN,
				"email" => $oprEmail,
				"affiliation" => $oprAN,
				"country" => $oprAC,
				"emailOK" => $oprPE,
				"certificate" => $oprCert
			);
		}	

		//now, update the proposals array with the presenters information
		for($i = 0; $i < count($other_proposals); $i++) {
			$tmp = explode("|",$other_proposals[$i]["presenters"]);
			$thisPres = array();
			for($j = 0; $j < count($tmp); $j++) {
				for($k = 0; $k < count($other_presenters); $k++) {
					if($tmp[$j] == $other_presenters[$k]["id"]) {
						$thisPres[] = $other_presenters[$k];
						break;
					}
				}
			}
		
			$other_proposals[$i]["presenters"] = $thisPres;
		}
	
		//now, do the same with the "classics" presentations and presenters
		//get the proposal information
		$cpStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `classics_proposals`");
		$cpStmt->execute();
		$cpStmt->bind_result($cpID,$cpTitle,$cpPresenters,$cpSummary);
		$classics_proposals = array();
		while($cpStmt->fetch()) {
			$classics_proposals[] = array(
				"id" => $cpID,
				"title" => $cpTitle,
				"presenters" => $cpPresenters,
				"summary" => $cpSummary
			);
		}
	
		//get the presenters information
		$cprStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `Certificate` FROM `classics_presenters`");
		$cprStmt->execute();
		$cprStmt->bind_result($cprID,$cprFN,$cprLN,$cprEmail,$cprAN,$cprAC,$cprPE,$cprCert);
		$classics_presenters = array();
		while($cprStmt->fetch()) {
			if($cprAC == "United States") $cprAC = "USA";
			else if($cprAC == "Korea, South") $cprAC = "South Korea";
			else if($cprAC == "Russian Federation") $cprAC = "Russia";
			else if($cprAC == "United Arab Emirates") $cprAC = "UAE";
			else if($cprAC == "United Kingdom") $cprAC = "UK";
			else if($cprAC == "United States Minor Outlying Islands") $cprAC = "USA";
			$classics_presenters[] = array(
				"id" => $cprID,
				"first_name" => $cprFN,
				"last_name" => $cprLN,
				"email" => $cprEmail,
				"affiliation" => $cprAN,
				"country" => $cprAC,
				"emailOK" => $cprPE,
				"certificate" => $cprCert
			);
		}	

		//now, update the proposals array with the presenters information
		for($i = 0; $i < count($classics_proposals); $i++) {
			$tmp = explode("|",$classics_proposals[$i]["presenters"]);
			$thisPres = array();
			for($j = 0; $j < count($tmp); $j++) {
				for($k = 0; $k < count($classics_presenters); $k++) {
					if($tmp[$j] == $classics_presenters[$k]["id"]) {
						$thisPres[] = $classics_presenters[$k];
						break;
					}
				}
			}
		
			$classics_proposals[$i]["presenters"] = $thisPres;
		}
	
		//now, update the sessions array with the proposals information
		for($i = 0; $i < count($sessions); $i++) {
			$tmp = explode("||",$sessions[$i]["presentations"]);
			$thisPres = array();
			$pCount = 0;
			for($j = 0; $j < count($tmp); $j++) {
				$tmpP = explode("|",$tmp[$j]);
				$thisPres[$pCount] = array();
				if(count($tmpP) > 1) { //includes a station name
					if($tmpP[0] != "0" && $tmpP[1] != "0") { //there is a presentation scheduled for this station
						for($k = 0; $k < count($stations); $k++) {
							if($stations[$k]["id"] == $tmpP[0]) {
								$thisPres[$pCount]["station"] = $stations[$k]["name"];
								break;
							}
						}
					}
				
					$pID = $tmpP[1];
				} else $pID = $tmpP[0];
			
				if($sessions[$i]["propTable"] == 'proposals') {
					for($k = 0; $k < count($proposals); $k++) {
						if($proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["title"] = $proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $proposals[$k]["presenters"];
							break;
						}
					}
				} else if($sessions[$i]["propTable"] == "classics_proposals") {
					for($k = 0; $k < count($classics_proposals); $k++) {
						if($classics_proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["title"] = $classics_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $classics_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $classics_proposals[$k]["presenters"];
							break;
						}
					}				
				} else if($sessions[$i]["propTable"] == "other_proposals") {
					for($k = 0; $k < count($other_proposals); $k++) {
						if($other_proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["title"] = $other_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $other_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $other_proposals[$k]["presenters"];
							break;
						}
					}				
				}
			
				if(count($thisPres[$pCount]) == 0) array_splice($thisPres, $pCount, 1);
				else $pCount++;
			}
		
			//Currently, the only session types that have stations are Techology Fairs and Classics. If we find one of
			//those types of events, we need to sort by station name to get them in the correct order for listing in
			//the schedule.
		
			if(strpos($sessions[$i]["title"],"Technology Fair") !== false) { //this is a technology fair or a classics
				//Before we add it to the sessions array, we need to sort by station name
				//We want the Mac stations listed first, and then the PC stations and then the BYOD stations.
				//The IDs for each group are sequential, so we need to get the IDs for each group sort them.
			
				$stationTypes = array("Mac","PC","BYOD");
				$tmpStations = array();
				foreach($stationTypes AS $sType) {
					$tmpStationIDs = array();
					foreach($stations AS $k => $v) {
						if(strpos($v["name"],$sType) !== false) {
							$tmpStationIDs[] = $v["id"];
						}
					}
					
					sort($tmpStationIDs); //sort numerically ASC
		
					foreach($tmpStationIDs AS $tsID) {
						for($tStI = 0; $tStI < count($stations); $tStI++) {
							if($stations[$tStI]["id"] == $tsID) {
								$tmpStations[] = $stations[$tStI];
								break;
							}
						}
					}
				}
		
				$stations = $tmpStations; //get the sorted order back into the stations array
		
				//Now we have the stations sorted by ID ascending, so go through the $thisPres array and sort the same way
				$tmpPresentations = array();
				foreach($stations AS $k => $v) {
					for($tPrI = 0; $tPrI < count($thisPres); $tPrI++) {
						if($thisPres[$tPrI]["station"] == $v["name"]) { //found the right presentation for this station
							$tmpPresentations[] = $thisPres[$tPrI];
							break;
						}
					}
				}
			
				$thisPres = $tmpPresentations; //get the sorted array back into $thisPres
			} else if($sessions[$i]["title"] == "CALL for Newcomers") {
				$thisPres = array(array('title' => '', 'summary' => $cfnSummary, 'presenters' => array()));
			} else if(strpos($sessions[$i]["title"], "The Electronic Village Online (EVO)") !== false) {
				$thisPres = array(array('title' => '', 'summary' => $evoSummary, 'presenters' => array()));
			}
		
			$sessions[$i]["presentations"] = $thisPres;		
		}
	}
?>