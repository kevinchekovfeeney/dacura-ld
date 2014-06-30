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
		Date/Time Start: <input id="date_timepicker_start" type="text"
		<?php 
			if(isset($params['startdate'])) {
				echo "value=\"" . $params['startdate'] . "\"";
			}
		?>
		>
		End: <input id="date_timepicker_end" type="text"
		<?php 
			if(isset($params['enddate'])) {
				echo "value=\"" . $params['enddate'] . "\"";
			}
		?>
		>
		User: <select id="user_picker"><option value="0">All users</option>
		<?php 
			$dwas = new StatisticsDacuraAjaxServer($service->settings);
			$c_id = $service->getCollectionID();
			$d_id = $service->getDatasetID();
			$users = $dwas->getUsersInContext($c_id, $d_id);
			foreach($users as $key => $value) {
				echo "<option value = '$key'";
				if(isset($params['userid'])) {
					if ($params['userid'] == $key) {
						echo " selected=\"selected\"";
					}
				}
				echo ">$key (";
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
	$('.pctitle').html("").hide();
	$('.filter').hide();
}

/**
 * Perform AJAX call for session logs and call prepareLogHtml function
 * @author: Andre Stern
 * @param: String userId, String startTimeStamp (as unixtimestamp)
 * startTimeStamp is the session start action timestamp
 */
dacura.statistics.showSessionLog = function(userId, startTimeStamp) {
	dacura.statistics.clearscreens();

	var ajs = dacura.statistics.api.detailedUserSession(userId, startTimeStamp); 
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving Session Log");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');
	};

	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		if(data.length > 0 ){
			var obj = JSON.parse(data);
			$('.pctitle').html("User: " + userId + " (" + obj.userName + ") / Session: " + obj.timestamp ).show();
			dacura.statistics.prepareLogHtml(obj);
		}
		else {
			dacura.toolbox.writeErrorMessage('#userhelp', "Error: no data returned from api call");
		}   
		$('#generalstats').show();  //rename or create new html element
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
	});
}

/**
 * Prepare the html div of the page for Session Logs, to be used after AJAX call
 * @author: Andre Stern
 * @param: Object obj
 * obj is the parsed JSON received via AJAX call
 */
dacura.statistics.prepareLogHtml = function(obj) {
	var html = "";
	html += "<style>table.table1, th, td{border-collapse:collapse;border:1px solid black;}th, td{padding:15px;}</style>";
	html += "<div><table class=\"table1\" id=\"sessions_table\"><thead><tr><th>Date and time</th><th>User</th><th>Duration</th><th>Processed</th><th>Accepted</th><th>Rejected</th><th>Skipped</th></tr></thead>";
	html += "<tbody><tr><td>" + obj.timestamp + "</td><td>" + obj.user + " (" + obj.userName + ")</td><td>" + obj.duration + "</td>";
	html += "<td>" + (obj.accepts + obj.rejects + obj.skips) + "</td><td>" + obj.accepts + "</td><td>" + obj.rejects + "</td>";
	html += "<td>" + obj.skips + "</td></tr></tbody></table></div><br><br>";

	//var sessionTable = $('#sessions_table').dataTable();
	
	html += "<div align=\"left\"><b>Detailed session log:</b></div><br><br>";
	html += "<style>table.table2, table.table2 th, table.table2 td{border-collapse:collapse;border:0px solid black; border-spacing:2px; padding:5px 35px;}</style>";
	html += "<div><table class=\"table2\"><thead><tr><th>Date and time</th><th>Action</th><th>Action duration</th><th>Candidate ID</th></tr></thead><tbody>";
	$.each(obj.log, function (i, item) {
		html += "<tr><td>" + i + "</td>";
		html += "<td>" + item['action'] + "</td>";
		html += "<td>" + item['elapsedTime'] + "</td>";
		if(item['id'])
			html += "<td>" + item['id'] + "</td></tr>";
		else
			html += "<td></td>";	
	});	
	html += "</tbody></table></div>";
	
	$('#generalstats').html(html);
}

/**
 * Correct the date parameters for filtered statistics 
 * @author: Max Brunner
 * @param: String startDateString (as "dd.mm.yy hh:mm"), String endDateString (as "dd.mm.yy hh:mm"), String userId
 */
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

/**
 * Perform AJAX call for dated statistics and call prepareHtml function
 * @author: Max Brunner
 * @param: String startDateString (as "dd.mm.yy hh:mm"), String endDateString (as "dd.mm.yy hh:mm"), String userId
 * When userId == 0, performs the statistics for all the users
 */
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
	else {
		$('.filter-error').html("");
	}
	dacura.statistics.clearscreens();

	if (userId != "0") {
		history.pushState({"fnct":"generalUserDatedStatistics", "start":startDateString, "end":endDateString, "user":userId}, "", dacura.system.pageURL() + "/" + userId + "/" + startDate + "/" + endDate);
		var ajs = dacura.statistics.api.generalUserDatedStats(startDate, endDate, userId);
	}
	else {
		history.pushState({"fnct":"generalDatedStatistics", "start":startDateString, "end":endDateString, "user":"0"}, "", dacura.system.pageURL() + "/" + startDate + "/" + endDate);
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
					$('.pctitle').html("User " + userId + " Statistics - " + startDateString + " to " + endDateString).show();
				}
				else {
					$('.pctitle').html("General System Statistics - " + startDateString + " to " + endDateString).show();
				}

				

				if (obj.hasData == false) {
					$('#generalstats').html("No data available for the selected period/user(s)...");
				}
				else {
					if (userId != "0") {
						dacura.statistics.prepareGeneralHtml(obj, true, true);
					}
					else {
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
		
	//$('#generalstats').html(html);
	$('#generalstats').show();

}

/**
 * Perform AJAX call for non-dated statistics and call prepareHtml function
 * @author: Max Brunner
 * @param: String userId
 * When userId == 0, performs the statistics for all the users
 */
dacura.statistics.generalStatistics = function(userId) {
	dacura.statistics.clearscreens();
	$('.filter-error').html("");

	if (userId != "0") {
		history.pushState({"fnct":"generalUserStats", "user":userId}, "", dacura.system.pageURL() + "/" + userId);
		var ajs = dacura.statistics.api.generalUserStats(userId);
	}
	else {
		history.pushState({"fnct":"generalStats"}, "", dacura.system.pageURL());
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

/**
 * Prepare the html div of the page, to be used after AJAX call made by other functions
 * @author: Max Brunner
 * @param: Object obj, Boolean isDated, Boolean isUser
 * obj is the parsed JSON received via AJAX call, isDated and isUser are flags to correct assemble the page 
 */
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
		html += "<tr id='session" + item['unix_timestamp'] + item['user'] + "'><td>" + item['timestamp'] + "</td>";
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
			html += "<tr id='user" + item['user'] +"'><td>" + item['user'] + " (" + item['user_name'] + ")</td>";
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

	// Inserting links and hovers
	$.each(obj.user_sessions, function (i, item) {
		$('#session' + item['unix_timestamp'] + item['user']).hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('#session' + item['unix_timestamp'] + item['user']).click( function (event){
			//window.location.href = dacura.system.pageURL() + "/" + item['user'] + "/session/" + item['unix_timestamp'];
			dacura.statistics.showSessionLog(item['user'], item['unix_timestamp']);
	    });
	});
	
	$.each(obj.user_rankings, function (i, item) {
		$('#user' + item['user']).hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('#user' + item['user']).click( function (event){
			//window.location.href = dacura.system.pageURL() + "/" + item['user'];
			dacura.statistics.generalStatistics(item['user']);
	    });
	});

	// Enabling datatables plugin for both tables, with legacy options
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

/**
 * Deals with history back and forward calls, complements the pushstate calls on other functions
 * @author: Max Brunner
 * @param: Object e
 * obj is the history entry's state object loaded with pushstate
 */
window.onpopstate = function(e) {
	switch (e.state.fnct) {
		case "generalUserDatedStatistics":
			$('input[id=date_timepicker_start]').val(e.state.start);
			$('input[id=date_timepicker_end]').val(e.state.end);
			$("select option:contains(" + e.state.user + ")").prop('selected', 'selected');
			dacura.statistics.generalDatedStatistics(e.state.start, e.state.end, e.state.user);
			break;
		case "generalDatedStatistics":
			$('input[id=date_timepicker_start]').val(e.state.start);
			$('input[id=date_timepicker_end]').val(e.state.end);
			$("select option:contains('All users')").prop('selected', 'selected');
			dacura.statistics.generalDatedStatistics(e.state.start, e.state.end, 0);
			break;
		case "generalUserStats":
			$('input[id=date_timepicker_start]').val("");
			$('input[id=date_timepicker_end]').val("");
			$("select option:contains(" + e.state.user + ")").prop('selected', 'selected');
			dacura.statistics.generalStatistics(e.state.user);
			break;
		case "generalStats":
			$('input[id=date_timepicker_start]').val("");
			$('input[id=date_timepicker_end]').val("");
			$("select option:contains('All users')").prop('selected', 'selected');
			dacura.statistics.generalStatistics(0);
			break;
	}
};

$(function() {
	//Enabling the datetimepicker plugin
	$('#date_timepicker_start').datetimepicker({
		format:'d.m.Y H:i',
		lang:'en'
	});
	 
	$('#date_timepicker_end').datetimepicker({
		format:'d.m.Y H:i',
		lang:'en'
	});

	// Select the correct start function, accordingly with the URL called
	<?php
	if(isset($params['sessionid'])) {
		// start session log
		echo "dacura.statistics.showSessionLog(" . $params['userid'] . ", " . $params['sessionid'] . ");";
	}
	else if (isset($params['userid'])) {
		if (isset($params['startdate'])) {
			// start user dated
			echo "dacura.statistics.generalDatedStatistics(\"" . $params['startdate'] . "\", \"" . $params['enddate'] . "\", " . $params['userid'] . ");";
		}
		else {
			// start user undated
			echo "dacura.statistics.generalStatistics(" . $params['userid'] . ");";
		}	
	}
	else if (isset($params['startdate'])) {
		// start general dated
		echo "dacura.statistics.generalDatedStatistics(\"" . $params['startdate'] . "\", \"" . $params['enddate'] . "\", 0);";
	}
	else {
		echo "dacura.statistics.generalStatistics(0);";
	}?>
});
</script>

