<html>
	<head>
		<style type="text/css">
			body {
				margin: 0;
				padding: 0;
				font-family: Arial;
			}			
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script type="text/javascript">
			function init() {
				var ht = $(document.body).height();
				$('#bb_scheduleTD').css('height', ht + 'px');
				$('#bb_scheduleIFrame').css('height', ht + 'px').attr('src','/ev/bb_schedule.php?db=1');
				$('#tsLiveStreamIFrame, #evLiveStreamIFrame').css({'height': (ht/2) + 'px', 'width':'100%'});
			}
		</script>
	</head>
	
	<body onload="init()">
		<table border="0" width="100%">
			<tr>
				<td id="bb_scheduleTD" rowspan="2" width="50%">
					<iframe id="bb_scheduleIFrame" style="border: none; width: 100%;"></iframe>
				</td>
<?php
	/*
				
				<td id="liveStreamTD" align="center">
					<h3 align="center">Technology Showcase Live Stream</h3>
					<iframe id="tsLiveStreamIFrame" width="560" height="315" src="https://www.youtube.com/embed/live_stream?channel=UChnWYx1ZGtHnzzpV5t98J4Q" frameborder="0" allowfullscreen></iframe>
					<br><br>
					<iframe id="evLiveStreamIFrame" width="560" height="315" src="https://www.youtube.com/embed/live_stream?channel=UCWzUQsJbiBWU2xT6P5Qm9kw" frameborder="0" allowfullscreen></iframe>
				</td>
	*/
?>
			</tr>
<?php
	/*
			<tr>
				<td id="twitterTD" align="center">
					<a class="twitter-timeline"  href="https://twitter.com/hashtag/EVILLAGE18" data-widget-id="976562840889827328">#EVILLAGE18 Tweets</a>
			        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</td>
				<td id="facebookTD" align="center">
				
				</td>
			</tr>
	*/
?>
		</table>
	</body>
</html>
<?php

	/*
		Twitter Widget Code
		
		<a class="twitter-timeline"  href="https://twitter.com/hashtag/EVILLAGE18" data-widget-id="976562840889827328">#EVILLAGE18 Tweets</a>
        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
          
	 */
?>