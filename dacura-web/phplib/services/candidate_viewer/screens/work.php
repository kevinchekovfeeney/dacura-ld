<?php $file_base = $dacura_settings['files_url'];
?>
<div id='dc-work-page'>

	<div id="dc-work">
		<div id="work-topbar">
			<div id="work-controls">
				<div class='work-control-question'>Does this page contain a report of a fatal political violence event in the UK or Ireland?</div>
				<div id="candidate-controls">
					<input type='submit' id='candidate-accept' value="Yes"> 
					<input type='submit' id='candidate-reject' value="No">
				</div>
			</div>
			<div id="work-session">
				<div class='work-session-hover work-session-pane' id='work-session-controls'>
					<div id='dc-session-busy' class='dc-loading'><img src='web/ajax-loader.gif'>
						<div class="" id='dc-session-busy-message'>Updating session</div>
					</div>
					<div class='dc-candidates-time'>00:00:00</div>
					<div id='work-session-buttons'>
						<input type='submit' id='candidate-pause' value="Pause">
						<input type='submit' id='candidate-resume' value="Resume">
						<input type='submit' id='candidate-end' value="End">
					</div>
				</div>
				<div class='work-session-pane'>
					<div class='work-session-chunknameintro'>user</div>
				<div id='work-session-username'><?=$params['user']?></div>
				</div>
				<div class='work-session-pane'>
					<div class='work-session-chunknameintro'>Viewed</div>
					<div class='current-chunkid' id='work-session-viewed'></div>
				</div>
				<div class='work-session-pane'>
					<div class='work-session-chunknameintro'>Accepted</div>
					<div class='current-chunkid' id='work-session-accepted'></div>
				</div>
				<div class='work-session-pane'>
					<div class='work-session-chunknameintro'>Rejected</div>
					<div class='current-chunkid' id='work-session-rejected'></div>
				</div>
			</div>
			<div id='work-slider'>
				<div id="candidate-slider"></div>
			</div>
		</div>
		
		<div id='work-pause'>
				<h2 id="dc-work-pause-header">Session Paused</h2>
				<div style="text-align: center"><img src='http://i127.photobucket.com/albums/p149/WaDoom/animals_dancing_panda.gif'></div>
				
		</div>
		<div id='work-fetching'>
				<h2 id="dc-work-fetching-header">msg</h2>
				<center><img src='<?=$file_base?>images/ajax-loader.gif'></center>
				<p>Fetching next candidate</p>
		</div>
		<div id="work-show">
			<div id='work-candidate-banner'></div>
			<div id="work-show-img"></div>
		</div>
	</div>
	<div id="dc-content">
	</div>
	
</div>
</body>
</html>
