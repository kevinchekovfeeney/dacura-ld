<div id="pagecontent">
	<div class="pctitle"></div>
	<div class="pcbreadcrumbs dch">
		<div class="pccon">
		<?php $service->renderScreen("available_context", array("type" => "admin"), "core");?>
		</div>
		<?php
		// PHP tag that returns the breadcrumbs
		$arg = isset($params['userid']) ? $params['userid'] : false;
		echo $service->getBreadCrumbsHTML($arg); ?>
	</div><br>
	
	<div class="pcbusy"></div>
	
	<div class="filter">
		Filter - 
		Date/Time Start: <input id="date_timepicker_start" type="text">
		End: <input id="date_timepicker_end" type="text">
		User: <select id="user_picker"><option value="0">All users</option>
		<?php 
			$dwas = new StatisticsDacuraAjaxServer($service->settings);
			$c_id = $service->getCollectionID();
			$d_id = $service->getDatasetID();
			$users = $dwas->getUsersInContext($c_id, $d_id);
			foreach($users as $key => $value) {
				echo "<option value = '$key'>'$key' (";
				echo $users[$key]->getRealName();
				echo ")</option>";
			}?>
		</select>
		
		<input type="button" onclick="dacura.statistics.filteredStats(date_timepicker_start.value, date_timepicker_end.value, user_picker.value);" value="Filter">
		
		
		<div class="filter-error"></div>
		<br>
	</div>
	
	<div id="generalstats">Nothing loaded...</div>

</div>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.dataTables.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.datetimepicker.css")?>" />

<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<script src='<?=$service->url("js", "jquery.datetimepicker.js")?>'></script>

<script>

dacura.statistics.clearscreens = function() {
	$('#generalstats').hide();
	//$('#userview').hide();
	//$('#roleview').hide();
	$('.pctitle').html("").hide();
	$('.filter').hide();
}

dacura.statistics.showSessionLog = function(userId, startTimeStamp) {
	// clear screen
	// prepare ajax call
	// do ajax call
	// receive the return
	// process the json and show info on the screen (html)
}


dacura.statistics.filteredStats = function(startDateString, endDateString, userId) {
	if (startDateString == "" && endDateString == "") { 
		dacura.statistics.generalStatistics(userId);
		return;
	}
	else if (startDateString == "") {
		startDateString = "01.01.1970 00:01";
	}
	else if (endDateString == "") {
		endDateString = "01.01.2100 00:01";
	}

	dacura.statistics.generalDatedStatistics(startDateString, endDateString, userId);
}

dacura.statistics.generalDatedStatistics = function(startDateString, endDateString, userId) {

	var dateTimeArrayStart = startDateString.split(" ");
	var dateTimeArrayEnd = endDateString.split(" ");

	var dateArrayStart = dateTimeArrayStart[0].split(".");
	var dateArrayEnd = dateTimeArrayEnd[0].split(".");

	var timeArrayStart = dateTimeArrayStart[1].split(":");
	var timeArrayEnd = dateTimeArrayEnd[1].split(":");

	var startDate = Date.UTC(dateArrayStart[2], dateArrayStart[1] - 1, dateArrayStart[0], timeArrayStart[0], timeArrayStart[1]) / 1000;
	var endDate = Date.UTC(dateArrayEnd[2], dateArrayEnd[1] - 1, dateArrayEnd[0], timeArrayEnd[0], timeArrayEnd[1]) / 1000;

	//General check of date validity
	
	if (startDate > endDate) {
		$('.filter-error').html("ERROR: the initial date is bigger than the final date!").show();
		return;
	}
	//console.log(startDate);
	//console.log(endDate);
	dacura.statistics.clearscreens();

	if (userId != "0") {
		var ajs = dacura.statistics.api.generalUserDatedStats(startDate, endDate, userId);
	}
	else {
		var ajs = dacura.statistics.api.generalDatedStats(startDate, endDate);
	}

	var self=this;
	
	ajs.beforeSend = function() {
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving Information...");
	};
	ajs.complete = function() {
		dacura.toolbox.clearBusyMessage('.pcbusy');
	};
	$.ajax(ajs) // ajs needs to be an object like: {url: "script.php", type: "GET"} , data: { ... }, dataType: "json"} ??
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0) {
				var obj = JSON.parse(data);

				if (userId != "0") {
					$('.pctitle').html("User " + userId + " General Statistics - " + startDateString + " to " + endDateString).show();
				}
				else {
					$('.pctitle').html("General System Statistics - " + startDateString + " to " + endDateString).show();
				}

				

				if (obj.hasData == false) {
					$('#generalstats').html("No data available for the selected period/user(s)...");
				}
				else {
					if (userId != "0") {
						console.log("call true");
						dacura.statistics.prepareGeneralHtml(obj, true, true);
					}
					else {
						console.log("call false");
						dacura.statistics.prepareGeneralHtml(obj, true, false);
					}
				}
			}
			else {
				$('#generalstats').html("No users selected.");
			}    	
			$('#generalstats').show();
			$('.filter').show();
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
		}
	);
		
	$('#generalstats').html(html);
	$('#generalstats').show();

}

dacura.statistics.prepareGeneralHtml = function(obj, isDated, isUser) {

	var html = "";

	// General Information Section

	html += "<div class=\"pcsectionhead\">General Information</div>";
	html += "<div align=\"center\"><table border=\"0\" width=80% align=\"center\" cellpadding=\"5\">";

	if(!isUser) {
		html += "<tr bgcolor=\"#e2e4ff\"><td>Last user logged in</td>";
		$.each(obj.last_user, function (i, item) {
			html += "<td align=\"right\">" + i + " (" + item + ")</td></tr>";
		});
		html += "<tr bgcolor=\"white\"><td>Last user logged in timestamp</td><td align=\"right\">" + obj.last_user_timestamp + "</td></tr>";
	}
	else {
		html += "<tr bgcolor=\"white\"><td>User last timestamp</td><td align=\"right\">" + obj.last_user_timestamp + "</td></tr>";
	}
	
	html += "<tr bgcolor=\"#e2e4ff\"><td>Total number of sessions</td><td align=\"right\">" + obj.number_of_sessions + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Total online time</td><td align=\"right\">" + obj.total_online_time + "</td></tr>";

	if (!isUser) {
		html += "<tr bgcolor=\"#e2e4ff\"><td>Number of active users</td><td align=\"right\">" + obj.number_of_active_users + "</td></tr>";

		html += "<tr bgcolor=\"white\"><td>Active users</td><td align=\"right\">";
		$.each(obj.active_users, function (i, item) {
			html += i + " (" + item + ")<br>";
		});
		html += "</td></tr>";

		if (!isDated) {
			html += "<tr bgcolor=\"#e2e4ff\"><td>Number of active users last week</td><td align=\"right\">" + obj.number_of_active_users_last_week + "</td></tr>";
			html += "<tr bgcolor=\"white\"><td>Active users last week</td><td align=\"right\">";
			$.each(obj.last_week_users, function (i, item) {
				html += i + " (" + item + ")<br>";
			});
			html += "</td></tr>";
			html += "<tr bgcolor=\"#e2e4ff\"><td>Number of inactive users last week</td><td align=\"right\">" + obj.number_of_inactive_users_period + "</td></tr>";
			html += "<tr bgcolor=\"white\"><td>Inactive users last week</td><td align=\"right\">";
			$.each(obj.period_inactive_users, function (i, item) {
				html += i + " (" + item + ")<br>";
			});
		}
		else {
			html += "<tr bgcolor=\"#e2e4ff\"><td>Number of inactive users on the period</td><td align=\"right\">" + obj.number_of_inactive_users_period + "</td></tr>";
			html += "<tr bgcolor=\"white\"><td>Inactive users on the period</td><td align=\"right\">";
			$.each(obj.period_inactive_users, function (i, item) {
				html += i + " (" + item + ")<br>";
			});
			html += "</table></div><br><br>";
		}
	}
	html += "</table></div><br><br>";
	
	// Candidates Processing Section
	
	html += "<div class=\"pcsectionhead\">Candidates Processing</div>";
	html += "<div align=\"center\"><table border=\"0\" width=80% align=\"center\" cellpadding=\"5\">";
	console.log(!isDated);
	console.log(!isUser);
	console.log((!isDated || !isUser))
	if (!isDated && !isUser) {
		html += "<tr bgcolor=\"#e2e4ff\"><td>Total number of candidates</td><td align=\"right\">" + obj.total_number_of_candidates + "</td></tr>";
	}
	html += "<tr bgcolor=\"white\"><td>Processed candidates (Log)</td><td align=\"right\">" + obj.number_of_processed + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Processed candidates (SQL)</td><td align=\"right\">" + obj.number_of_processed_sql + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Accepted candidates (Log)</td><td align=\"right\">" + obj.number_of_accepts + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Accepted candidates (SQL)</td><td align=\"right\">" + obj.number_of_accepts_sql + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Rejected candidates (Log)</td><td align=\"right\">" + obj.number_of_rejects + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Rejected candidates (SQL)</td><td align=\"right\">" + obj.number_of_rejects_sql + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Skipped candidates (Log)</td><td align=\"right\">" + obj.number_of_skips + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Skipped candidates (SQL)</td><td align=\"right\">" + obj.number_of_skips_sql + "</td></tr>";
	if (!isDated && !isUser) {
		html += "<tr bgcolor=\"white\"><td>Total number of unprocessed candidates</td><td align=\"right\">" + obj.total_number_of_unprocessed_candidates + "</td></tr>";
	}
	html += "</table></div><br><br>";

	// Processing Averages Section
	
	html += "<div class=\"pcsectionhead\">Processing Averages</div>";
	html += "<div align=\"center\"><table border=\"0\" width=80% align=\"center\" cellpadding=\"5\">";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Average Session Time</td><td align=\"right\">" + obj.average_session_time + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Average candidates processed per session</td><td align=\"right\">" + obj.average_processed_per_session + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Average candidates accepted per session</td><td align=\"right\">" + obj.average_accepted_per_session + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Average candidates skipped per session</td><td align=\"right\">" + obj.average_skipped_per_session + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Average candidates rejected per session</td><td align=\"right\">" + obj.average_rejected_per_session + "</td></tr>";
	html += "<tr bgcolor=\"white\"><td>Average candidate processing time</td><td align=\"right\">" + obj.mean_candidate_processing_time + "</td></tr>";
	html += "<tr bgcolor=\"#e2e4ff\"><td>Average candidates processed per hour</td><td align=\"right\">" + obj.mean_candidate_processing_per_hour + "</td></tr>";
	if (!isDated && !isUser) {
		html += "<tr bgcolor=\"white\"><td>Session time per day on last week (ignoring weekends)</td><td align=\"right\">" + obj.mean_work_per_day_last_week + "</td></tr>";
	}
	html += "</table></div><br><br>";

	// Estimations Section
	if (!isDated && !isUser) {
		html += "<div class=\"pcsectionhead\">Estimations</div>";
		html += "<div align=\"center\"><table border=\"0\" width=80% align=\"center\" cellpadding=\"5\">";
		html += "<tr bgcolor=\"#e2e4ff\"><td>Estimated session time to complete unprocessed candidates</td><td align=\"right\">" + obj.estimated_work_to_be_done + "</td></tr>";
		html += "<tr bgcolor=\"white\"><td>Estimated completion date (based on the last 7 days)</td><td align=\"right\">" + obj.estimated_completion_date + "</td></tr>";
		html += "</table></div><br><br>";
	}
	
	// User Sessions
	
	html += "<div class=\"pcsectionhead\">Users Sessions</div>";
	html += "<div><table id=\"sessions_table\">";
	html += "<thead><tr>";
	html += "<th>Timestamp</th><th>User</th><th>Duration</th><th>Processed</th><th>Accepted</th><th>Rejected</th><th>Skipped</th><th></th><th></th>";
	html += "</tr></thead><tbody>";
	$.each(obj.user_sessions, function (i, item) {
		html += "<tr><td>" + item['timestamp'] + "</td>";
		html += "<td>" + item['user'] + " (" + item['user_name'] + ")</td>";
		html += "<td>" + item['duration'] + "</td>";
		html += "<td>" + item['processed'] + "</td>";
		html += "<td>" + item['accepted'] + "</td>";
		html += "<td>" + item['rejected'] + "</td>";
		html += "<td>" + item['skipped'] + "</td>";
		html += "<td>" + item['unix_timestamp'] + "</td>";
		html += "<td>" + item['unix_duration'] + "</td></tr>";
	});
	html += "</tbody></table></div><br><br><br>";

	// User Rankings
	
	if (!isUser) {
		html += "<div class=\"pcsectionhead\">User Rankings</div>";
		html += "<div><table id=\"rank_table\">";
		html += "<thead><tr>";
		html += "<th>User</th><th>Online Time</th><th></th><th>Mean Processing Time</th><th></th><th># of Sessions</th>";
		html +=	"<th>Session Mean Time</th><th></th><th>Processed</th><th>Skipped</th>";
		html += "</tr></thead><tbody>";
		$.each(obj.user_rankings, function (i, item) {
			html += "<tr><td>" + item['user'] + " (" + item['user_name'] + ")</td>";
			html += "<td>" + item['onlinetime'] + "</td>";
			html += "<td>" + item['onlinetime_unix'] + "</td>";
			html += "<td>" + item['mean_processing_time'] + "</td>";
			html += "<td>" + item['mean_processing_time_unix'] + "</td>";
			html += "<td>" + item['number_of_sessions'] + "</td>";
			html += "<td>" + item['session_mean_time'] + "</td>";
			html += "<td>" + item['session_mean_time_unix'] + "</td>";
			html += "<td>" + item['processed'] + "</td>";
			html += "<td>" + item['skips'] + "</td></tr>";	
		});
		html += "</tbody></table></div><br><br><br>";
	}
	
	$('#generalstats').html(html);
	var sessionTable = $('#sessions_table').dataTable({
		"aoColumns": [
	                  { "iDataSort": 7 },
	                  null,
	                  { "iDataSort": 8 },
	                  null,
	                  null,
	                  null,
	                  null,
	                  {"bVisible":false},
	                  {"bVisible":false}
		]
	});
	sessionTable.fnSort( [ [0,'desc'] ] );
	
	
	if (!isUser) {
		var rankTable = $('#rank_table').dataTable({
			"aoColumns": [
		                  null,
		                  { "iDataSort": 2 },
		                  {"bVisible":false},
		                  { "iDataSort": 4 },
		                  {"bVisible":false},
		                  null,
		                  { "iDataSort": 7 },
		                  {"bVisible":false},
		                  null,
		                  null
			]
		});
		rankTable.fnSort( [ [1,'desc'] ] );
	}
}


dacura.statistics.generalStatistics = function(userId) {
	dacura.statistics.clearscreens();

	if (userId != "0") {
		//console.log(userId);
		var ajs = dacura.statistics.api.generalUserStats(userId);
	}
	else {
		var ajs = dacura.statistics.api.generalStats();
	}
	
	var self=this;
	ajs.beforeSend = function() {
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving Information...");
	};
	ajs.complete = function() {
		dacura.toolbox.clearBusyMessage('.pcbusy');
	};
	$.ajax(ajs) // ajs needs to be an object like: {url: "script.php", type: "GET"} , data: { ... }, dataType: "json"} ??
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0){
				var obj = JSON.parse(data);

				if (userId != "0") {
					$('.pctitle').html("User " + userId + " General Statistics").show();
				}
				else {
					$('.pctitle').html("General System Statistics").show();
				}

				if (obj.hasData == false) {
					$('#generalstats').html("No data available for the selected period/user(s)...");
				}
				else {
					if (userId != "0") {
						dacura.statistics.prepareGeneralHtml(obj, false, true);
					}
					else {
						dacura.statistics.prepareGeneralHtml(obj, false, false);
					}
				}
			}
			else {
				$('#generalstats').html("No users selected.");
			}    	
			$('#generalstats').show();
			$('.filter').show();
			
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
		});
}

$(function() {

	$('#date_timepicker_start').datetimepicker({
		format:'d.m.Y H:i',
		lang:'en'
	});
	 
	$('#date_timepicker_end').datetimepicker({
		format:'d.m.Y H:i',
		lang:'en'
	});
	
	dacura.statistics.generalStatistics(0);
	// dacura.statistics.showSessionLog = function(74, 1403191785); // to test session log viewer
});
</script>

