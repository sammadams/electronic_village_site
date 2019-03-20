<?php
	// schedSearch.php -- allows a user to search the online schedule
	// available to the public, so no login is required

	include_once "../../ev_config.php";
	include_once "../../ev_library.php";
	include_once "sched_main.php";

	if($isAllowed || (isset($_GET["db"]) && $_GET["db"] == "1")) { //allowed or we are debugging
?>
<html>
	<head>
		<title>Search Schedule -- Electronic Village</title>
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
						
			a {
				text-decoration: none;
				border-bottom: dashed 1px #CCCCCC;
				color: #0066CC;
			}
			
			a:hover {
				border-bottom: solid 1px #0066CC;
			}

			th.sList {
				background-color: #333333;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				padding: 5px;
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
			
			div.presDiv {
				padding-left: 25px;
				padding-right: 5px;
				border-left: dashed 1px #000000;
				border-right: dashed 1px #000000;
				border-bottom: dashed 1px #000000;
			}
			
			input.searchBox {
				font-size: 12pt;
				height: 30px;
				font-family: Arial;
				width: 100%;
			}
			
			input.btn {
				font-size: 12pt;
				height: 30px;
				font-family: Arial;
				padding-left: 20px;
				padding-right: 20px;
			}
			
			td.matchTitle {
				border-top: solid 1px #000000;
				padding-top: 20px;
				font-weight: bold;
				font-style: italic;
			}
			
			td.matchSummary {
			
			}
			
			span.matchPresName {
				font-weight: bold;
			}
			
			span.match {
				background-color: #99FF99;
			}
			
			#resultsContainer {
				position: absolute;
				top: 0;
				left: 0;
				height: 100%;
				width: 100%;
				background-color: #FFFFFF;
			}
			
			#resultsDiv {
				background-color: #FFFFFF;
			}
		</style>
		<script type="text/javascript">
		    var presentations = new Array();
<?php
	/*
		We need to get all the presentations into an array because all the search functions will be done
		using javascript. This allows faster searches that don't require continuous access to the database
		or calls to the server.
	 */
	$pI = 0;
	for($i = 0; $i < count($sessions); $i++) {
		$pArr = $sessions[$i]["presentations"];
		for($j = 0; $j < count($pArr); $j++) {
?>
            
            
            presentations[<?php echo $pI; ?>] = new Array();
            presentations[<?php echo $pI; ?>]['matched'] = false;
            presentations[<?php echo $pI; ?>]['session'] = new Array();
            presentations[<?php echo $pI; ?>]['session']['date'] = '<?php echo $sessions[$i]["date"]; ?>';
            presentations[<?php echo $pI; ?>]['session']['time'] = '<?php echo $sessions[$i]["time"]; ?>';
            presentations[<?php echo $pI; ?>]['session']['location'] = '<?php echo $sessions[$i]["location"]; ?>';

<?php
			$prArr = $pArr[$j]["presenters"];
			if(count($prArr) > 0) {
?>
            presentations[<?php echo $pI; ?>]['presenters'] = new Array();
<?php
				for($k = 0; $k < count($prArr); $k++) {
?>
			presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>] = new Array();
            presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>]['first_name'] = '<?php echo addslashes(stripslashes(trim($prArr[$k]["first_name"]))); ?>';
            presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>]['last_name'] = '<?php echo addslashes(stripslashes(trim($prArr[$k]["last_name"]))); ?>';
            presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>]['affiliation'] = '<?php echo addslashes(stripslashes(trim($prArr[$k]["affiliation"]))); ?>';
            presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>]['country'] = '<?php echo addslashes(stripslashes(trim($prArr[$k]["country"]))); ?>';
            presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>]['email'] = '<?php echo trim($prArr[$k]["email"]); ?>';
            presentations[<?php echo $pI; ?>]['presenters'][<?=$k?>]['emailOK'] = '<?php echo $prArr[$k]["emailOK"]; ?>';
<?php
				}
			}
			
			$order = array("\r\n","\n","\r");
?>

            presentations[<?php echo $pI; ?>]['title'] = '<?php echo addslashes(stripslashes(trim($pArr[$j]["title"]))); ?>';
            presentations[<?php echo $pI; ?>]['summary'] = '<?php echo addslashes(stripslashes(trim(str_replace($order,"<br />",$pArr[$j]["summary"])))); ?>';
<?php
			if(isset($pArr[$j]["station"])) {
?>
            presentations[<?php echo $pI; ?>]['station'] = '<?php echo $pArr[$j]["station"]; ?>';
<?php
			} else {
?>
			presentations[<?php echo $pI; ?>]['station'] = '';
<?php
			}
		
			$pI++;
		}
	}
?>

			function showResults(sArr,t) {
				var htmlStr = '';
				var pCount = 0;
				for(var i = 0; i < presentations.length; i++) {
					if(presentations[i]['matched'] == true) {
						pCount++;
						if(htmlStr == '') { //this is the first match we've found
							htmlStr += '<table border="0" width="800" cellpadding="5" align="center">';
							htmlStr += '  <tr>';
							htmlStr += '     <td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>';
							htmlStr += '  </tr>';
							htmlStr += '  <tr>';
							htmlStr += '    <td style="padding-top: 25px">';
							htmlStr += '      <h2 align="center">Electronic Village Online Schedule</h2>';
							htmlStr += '      <h3 align="center">Search Page</h3>';
							htmlStr += '    </td>';
							htmlStr += '  </tr>';
							htmlStr += '  <tr>';
							htmlStr += '     <td align="center"><a href="javascript:void(0)" onClick="hideResults()">Search Again</a></td>';
							htmlStr += '  </tr>';							
							htmlStr += '  <tr>';
							htmlStr += '    <td style="font-weight: bold"><span id="searchCount">0</span> matches found!</td>';
							htmlStr += '  </tr>';
						}
						
						if(t == 'kw') {
							var matchedTitle = presentations[i]['title'];
							var matchedSummary = presentations[i]['summary'];
							
							for(var k = 0; k < sArr.length; k++) {
								var regexStr = '(' + sArr[k] + ')';
								var re = new RegExp(regexStr,"gi");
								matchedTitle = matchedTitle.replace(re,'<span class="match">$1</span>');
								matchedSummary = matchedSummary.replace(re,'<span class="match">$1</span>');
							}
						
							htmlStr += '  <tr>';
							htmlStr += '    <td class="matchTitle">' + matchedTitle + '</td>';
							htmlStr += '  </tr>';
							htmlStr += '  <tr>';
							htmlStr += '    <td class="matchSummary">' + matchedSummary + '</td>';
							htmlStr += '  </tr>';

							var prArr = presentations[i]['presenters'];
							if(prArr != undefined) {
								if(prArr.length > 0) {
									htmlStr += '  <tr>';
									htmlStr += '    <td>';
									for(var j = 0; j < prArr.length; j++) {
										htmlStr += ' &nbsp; &nbsp; <span class="matchPresName">';
										htmlStr += prArr[j]['first_name'] + ' ' + prArr[j]['last_name'];
										htmlStr += '</span>, <span class="matchPresAffiliation">';
										htmlStr += prArr[j]['affiliation'] + ', ' + prArr[j]['country'];
										htmlStr += '</span>';
									
										if(prArr[j]['emailOK'] == '1') htmlStr += ' <span class="matchPresEmail">(' + prArr[j]['email'] + ')</span>';
								
										if(j < (prArr.length - 1)) htmlStr += '<br />';
									}

									htmlStr += '    </td>';
									htmlStr += '  </tr>';					
								}
							}
						} else if(t == 'pr') {				
							htmlStr += '  <tr>';
							htmlStr += '    <td class="matchTitle">' + presentations[i]['title'] + '</td>';
							htmlStr += '  </tr>';
							htmlStr += '  <tr>';
							htmlStr += '    <td class="matchSummary">' + presentations[i]['summary'] + '</td>';
							htmlStr += '  </tr>';

							var prArr = presentations[i]['presenters'];
							if(prArr != undefined) {
								if(prArr.length > 0) {
									htmlStr += '  <tr>';
									htmlStr += '    <td>';
									for(var j = 0; j < prArr.length; j++) {
										var matchedFN = prArr[j]['first_name'];
										var matchedLN = prArr[j]['last_name'];
										var matchedEmail = prArr[j]['email'];
										var matchedAffiliation = prArr[j]['affiliation'];
										var matchedCountry = prArr[j]['country'];
										
										var tmpFN = sArr[0].split(' ');
										for(var fn = 0; fn < tmpFN.length; fn++) {
											var regexStr = '(' + tmpFN[fn] + ')';
											var re = new RegExp(regexStr,"gi");
											matchedFN = matchedFN.replace(re,'<span class="match">$1</span>');
										}
										
										var tmpLN = sArr[1].split(' ');
										for(var ln = 0; ln < tmpLN.length; ln++) {
											var regexStr = '(' + tmpLN[ln] + ')';
											var re = new RegExp(regexStr,"gi");
											matchedLN = matchedLN.replace(re,'<span class="match">$1</span>');										
										}
										
										var tmpAff = sArr[3].split(' ');
										for(var a = 0; a < tmpAff.length; a++) {
											var regexStr = '(' + tmpAff[a] + ')';
											var re = new RegExp(regexStr,"gi");
											matchedAffiliation = matchedAffiliation.replace(re,'<span class="match">$1</span>');										
										}
										
										var tmpCountry = sArr[4].split(' ');
										for(var c = 0; c < tmpCountry.length; c++) {
											var regexStr = '(' + tmpCountry[c] + ')';
											var re = new RegExp(regexStr,"gi");
											matchedCountry = matchedCountry.replace(re,'<span class="match">$1</span>');										
										}
										
										var tmpEmail = sArr[2].split(' ');
										for(var e = 0; e < tmpEmail.length; e++) {
											var regexStr = '(' + tmpEmail[e] + ')';
											var re = new RegExp(regexStr,"gi");
											matchedEmail = matchedEmail.replace(re,'<span class="match">$1</span>');										
										}
																				
										
										htmlStr += ' &nbsp; &nbsp; <span class="matchPresName">';
										htmlStr += matchedFN + ' ' + matchedLN;
										htmlStr += '</span>, <span class="matchPresAffiliation">';
										htmlStr += matchedAffiliation + ', ' + matchedCountry;
										htmlStr += '</span>';
									
										if(prArr[j]['emailOK'] == '1') htmlStr += ' <span class="matchPresEmail">(' + matchedEmail + ')</span>';
								
										if(j < (prArr.length - 1)) htmlStr += '<br />';
									}

									htmlStr += '    </td>';
									htmlStr += '  </tr>';					
								}
							}
						}
						
						htmlStr += '  <tr>';
						htmlStr += '    <td style="border-top: dashed 1px #AAAAAA; padding-bottom: 25px">';
						htmlStr += '      <table border="0" width="100%" cellpadding="5">';
						htmlStr += '        <tr>';
						
						var tmpDate = presentations[i]['session']['date'].split('-');
						var months = new Array('','January','February','March','April','May','June','July','August','September','October','November','December');
						var pM = parseInt(tmpDate[1]);
						var pMonth = months[pM];
						var pDay = parseInt(tmpDate[2]);
						var pYear = tmpDate[0];
						var dateStr = pMonth + ' ' + pDay + ', ' + pYear;
						
						htmlStr += '          <td width="<?php echo $dateWidth; ?>" align="center">' + dateStr + '</td>';
						
						var tmpTime = presentations[i]['session']['time'].split('-');
						var tmpStart = tmpTime[0].split(':');
						var sHour = parseInt(tmpStart[0]);
						var sMin = tmpStart[1];
						if(sHour < 12) var sAMPM = 'AM';
						else {
							var sAMPM = 'PM';
							if(sHour > 12) sHour = sHour - 12;
						}
						
						var tmpEnd = tmpTime[1].split(':');
						var eHour = parseInt(tmpEnd[0]);
						var eMin = tmpEnd[1];
						if(eHour < 12) var eAMPM = 'AM';
						else {
							var eAMPM = 'PM';
							if(eHour > 12) eHour = eHour - 12;
						}
						
						var timeStr = sHour + ':' + sMin + ' ' + sAMPM + ' to ' + eHour + ':' + eMin + ' ' + eAMPM;
						
						htmlStr += '          <td width="<?php echo $timeWidth; ?>" align="center">' + timeStr + '</td>';
						htmlStr += '          <td width="<?php echo $locationWidth; ?>" align="center">' + presentations[i]['session']['location'] + '</td>';
						
						if(presentations[i]['station'] != '') htmlStr += '          <td width="100" align="center">' + presentations[i]['station'] + '</td>';
						
						htmlStr += '        </tr>';
						htmlStr += '      </table>';
						htmlStr += '    </td>';
						htmlStr += '  </tr>';
					}					
				}
				
				if(htmlStr == '') { //no matches found
					htmlStr += '<table border="0" width="800" cellpadding="5" align="center">';
					htmlStr += '  <tr>';
					htmlStr += '     <td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>';
					htmlStr += '  </tr>';
					htmlStr += '  <tr>';
					htmlStr += '    <td style="padding-top: 25px">';
					htmlStr += '      <h2 align="center">Electronic Village Online Schedule</h2>';
					htmlStr += '      <h3 align="center">Search Page</h3>';
					htmlStr += '    </td>';
					htmlStr += '  </tr>';
					htmlStr += '  <tr>';
					htmlStr += '     <td align="center"><a href="javascript:void(0)" onClick="hideResults()">Search Again</a></td>';
					htmlStr += '  </tr>';							
					htmlStr += '  <tr>';
					htmlStr += '    <td style="font-weight: bold">0 matches found!</td>';
					htmlStr += '  </tr>';
					htmlStr += '</table>';
				} else htmlStr += '</table>';
				
				document.getElementById('resultsContainer').style.display = '';
				document.getElementById('resultsContainer').style.height = document.body.offsetHeight;
				document.getElementById('resultsContainer').style.width = document.body.offsetWidth;
				document.getElementById('resultsDiv').innerHTML = htmlStr;
				document.getElementById('searchCount').innerHTML = pCount;
			}

			function strip_tags(input, allowed) {
			  allowed = (((allowed || '') + '')
				.toLowerCase()
				.match(/<[a-z][a-z0-9]*>/g) || [])
				.join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
			  var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
				commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
			  return input.replace(commentsAndPhpTags, '')
				.replace(tags, function($0, $1) {
				  return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
				});
			}
			
			function removeBad(strTemp) { 
			    strTemp = strTemp.replace(/\<|\>|\"|\'|\%|\;|\(|\)|\&|\+|\-/g,""); 
			    return strTemp;
			} 
			
			function doSearch(t) {
				//First, clear out any previous search results
				for(var i = 0; i < presentations.length; i++) {
					presentations[i]['matched'] = false;
				}
				
				if(t == 'kw') { //search for keywords in titles and summaries
					//Second, get the keywords for the title and summary search
					var kwStr = removeBad(strip_tags(document.getElementById('tsKeyword').value));
					var tmpKW = kwStr.split(' ');
				
					//Third, go through the array and find matches
					for(var k = 0; k < tmpKW.length; k++) {
						var thisKW = tmpKW[k].toLowerCase();
						for(var i = 0; i < presentations.length; i++) {
							var tmpTitle = presentations[i]['title'].toLowerCase();
							var tmpSummary = presentations[i]['summary'].toLowerCase();
						
							if(tmpTitle.indexOf(thisKW) != -1 || tmpSummary.indexOf(thisKW) != -1) presentations[i]['matched'] = true;
						}
					}
					
					//Finally, show the matches
					showResults(tmpKW,'kw');
				} else if(t == 'pr') { //search presenter information
					var fnStr = removeBad(strip_tags(document.getElementById('prFN').value)).toLowerCase();
					var lnStr = removeBad(strip_tags(document.getElementById('prLN').value)).toLowerCase();
					var emailStr = removeBad(strip_tags(document.getElementById('prEmail').value)).toLowerCase();
					var affStr = removeBad(strip_tags(document.getElementById('prAffiliation').value)).toLowerCase();
					var countryStr = removeBad(strip_tags(document.getElementById('prCountry').value)).toLowerCase();
					
					for(var i = 0; i < presentations.length; i++) {
						var prArr = presentations[i]['presenters'];
						if(prArr != undefined) {
							for(var j = 0; j < prArr.length; j++) {
								var tmpFN = prArr[j]['first_name'].toLowerCase();
								var tmpLN = prArr[j]['last_name'].toLowerCase();
								var tmpEmail = prArr[j]['email'].toLowerCase();
								var tmpAffiliation = prArr[j]['affiliation'].toLowerCase();
								var tmpCountry = prArr[j]['country'].toLowerCase();
							
								if(fnStr != '' && tmpFN.indexOf(fnStr) != -1) presentations[i]['matched'] = true;
								if(lnStr != '' && tmpLN.indexOf(lnStr) != -1) presentations[i]['matched'] = true;
								if(emailStr != '' && tmpEmail.indexOf(emailStr) != -1) presentations[i]['matched'] = true;
								if(affStr != '' && tmpAffiliation.indexOf(affStr) != -1) presentations[i]['matched'] = true;
								if(countryStr != '' && tmpCountry.indexOf(countryStr) != -1) presentations[i]['matched'] = true;
							}
						}
					}

					//Finally, show the matches
					showResults(new Array(fnStr,lnStr,emailStr,affStr,countryStr),'pr');
				}				
			}
			
			function checkEnter(e,t) {
				if(!e) e = window.event;
				var keyCode = e.keyCode || e.which;
				if(keyCode == '13') doSearch(t);
				else return false;
			}
			
			function hideResults() {
				document.getElementById('resultsContainer').style.display = 'none';
				document.getElementById('tsKeyword').value = '';
				document.getElementById('prFN').value = '';
				document.getElementById('prLN').value = '';
				document.getElementById('prEmail').value = '';
				document.getElementById('prAffiliation').value = '';
				document.getElementById('prCountry').value = '';
			}
		</script>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td style="padding-top: 25px">
					<h2 align="center">Electronic Village Online Schedule</h2>
					<h3 align="center">Search Page</h3>
					<p align="center"><a href="schedule.php">See Full Schedule</a></p>
					<table border="0" align="center" width="100%" cellpadding="5" style="border: solid 1px #000000">
						<tr>
							<td style="font-weight: bold">Search Presentation Titles and Summaries by Keyword:</td>
						</tr>
						<tr>
							<td><input type="text" class="searchBox" id="tsKeyword" style="width: 100%" onkeyup="checkEnter(event,'kw')" /></td>
						</tr>
						<tr>
							<td align="center"><input type="button" class="btn" value="Search" onClick="doSearch('kw')" /></td>
						</tr>
					</table>
					<p align="center" style="font-size: 16pt; font-weight: bold">OR</p>
					<table border="0" align="center" width="100%" cellpadding="5" style="border: solid 1px #000000">
						<tr>
							<td style="font-weight: bold">Search Presenters:</td>
						</tr>
						<tr>
							<td>
								<table border="0" cellpadding="5" width="100%">
									<tr>
										<td width="90">First Name:</td>
										<td><input type="text" class="searchBox" id="prFN" size="35" onkeyup="checkEnter(event,'pr')" /></td>
										<td width="90" style="padding-left: 30px">Last Name:</td>
										<td><input type="text" class="searchBox" id="prLN" size="35" onkeyup="checkEnter(event,'pr')" /></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" cellpadding="5" width="100%">
									<tr>
										<td>Email:</td>
										<td><input type="text" class="searchBox" id="prEmail" size="90" onkeyup="checkEnter(event,'pr')" /></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" cellpadding="5" width="100%">
									<tr>
										<td>Affiliation:</td>
										<td><input type="text" class="searchBox" id="prAffiliation" size="35" onkeyup="checkEnter(event,'pr')" /></td>
										<td style="padding-left: 30px">Country:</td>
										<td><input type="text" class="searchBox" id="prCountry" size="30" onkeyup="checkEnter(event,'pr')" /></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center"><input type="button" class="btn" value="Search" onClick="doSearch('pr')" /></td>
						</tr>
					</table>
					<br /><br />
				</td>
			</tr>
		</table>
		<div id="resultsContainer" style="display: none">
			<div id="resultsDiv"></div>
		</div>
	</body>
</html>
<?php
	} else { //schedule is not allowed at this time
?>
<html>
	<head>
		<title>Schedule -- Electronic Village</title>
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
						
			a {
				text-decoration: none;
				border-bottom: dashed 1px #CCCCCC;
				color: #0066CC;
			}
			
			a:hover {
				border-bottom: solid 1px #0066CC;
			}

			th.sList {
				background-color: #333333;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				padding: 5px;
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
			
			div.presDiv {
				padding-left: 25px;
				padding-right: 5px;
				border-left: dashed 1px #000000;
				border-right: dashed 1px #000000;
				border-bottom: dashed 1px #000000;
			}
		</style>
		<script type="text/javascript">
			function highlightRow(e,r,n,c) {
				var rEl = document.getElementById('session' + e + '_row' + r);
				for(i = 0; i < rEl.cells.length; i++) {
					var cEl = rEl.cells[i];
					if(n == 1) cEl.className = 'sList_highlighted';
					else if(n == 0) cEl.className = c;
				}
			}
		
			function showPres(n) {
				var el = document.getElementById('session' + n + '_pres');
				if(el.style.display == 'none') el.style.display = '';
				else el.style.display = 'none';
			}
		</script>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td style="padding-top: 25px">
					<h2 align="center">Electronic Village Online Schedule</h2>
					<p>The schedule for Electronic Village <?=$y?> is not available at this time. The schedule will be available approximately 3 weeks before the TESOL convention. Please check back later.</p>
					<p>If you have any questions, please contact the Electronic Village Program Manager at <a href="mailto:ev@call-is.org">ev@call-is.org</a>.</p>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php	
	}
?>