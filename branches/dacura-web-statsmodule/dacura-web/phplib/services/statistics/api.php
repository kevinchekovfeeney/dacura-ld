<?php
// fake comment
getRoute()->get('/', 'generalStats');
getRoute()->get('/(\w+)', 'userStats');
getRoute()->get('/(\w+)/(\w+)', 'datedGeneralStats');
getRoute()->get('/sessions/(\w+)/(\w+)', 'detailedUserSession');
getRoute()->get('/(\w+)/(\w+)/(\w+)', 'datedUserStats');

include_once("StatisticsDacuraServer.php");

/*
 * This will fetch all actions performed in a session given its start time timestamp and its user id 
 */
function detailedUserSession($userid, $sessionStartTime) {
	global $service;
	$dwas = new StatisticsDacuraAjaxServer($service->settings);
	$userobj = $dwas->getUser($userid);
	
	//find the directory where that user's sessions are
	$url = $service->settings['dacura_sessions'] . $userid . "/candidate_viewer.session";
	$tempLog; // store all actions for desired session the way they were in the JSON
	if (file_exists($url)) {
		$file_handle = @fopen($url, "r");
		if ($file_handle) {
			while (($json = fgets($file_handle)) != false) {
				$tempLog = json_decode($json, true);
				if(key_exists($sessionStartTime, $tempLog)){ 
					if($tempLog[$sessionStartTime]['action'] == 'start')
						break; // session's been found and stored, leave loop
				}
			}
		}
		fclose($file_handle);	
	}
	
	// Session stats
	$sessionTotalTime = 0;
	$sessionStart;
	$sessionEnd = 0;
	$isPaused = false;
	$lastTimestamp = 0;
	$sessionAccepts = 0;
	$sessionRejects = 0;
	$sessionSkips = 0;
	
	foreach ($tempLog as $timestamp => $action_array) {
		switch($action_array["action"]) {
			case "start":
				$sessionStart = $timestamp; 
				$lastTimestamp = $timestamp;
				break;
			case "end":
				if ($isPaused) $sessionEnd = $lastTimestamp;
				else $sessionEnd = $timestamp;
				break;
			case "pause":
				$isPaused = true;
				$lastTimestamp = $timestamp;
				break;
			case "unpause":
				// paused time is not added to total session time
				$isPaused = false;
				$sessionTotalTime -= ($timestamp - $lastTimestamp);
				$lastTimestamp = $timestamp;
				break;
			case "abort":
				$sessionEnd = $lastTimestamp;
				break;
			case "accept":
				$sessionAccepts++;
				$lastTimestamp = $timestamp;
				break;
			case "reject":
				$sessionRejects++;
				$lastTimestamp = $timestamp;
				break;
			case "skip":
				$sessionSkips++;
				$lastTimestamp = $timestamp;
				break;
		}
	}
	$sessionTotalTime += ($sessionEnd - $sessionStart);
	
	$sessionInfo = array(); // makes all session timestamps and elapsed times more human readable for the HTML
	$tempTimestamp = $sessionStart;
	foreach($tempLog as $k=>$v){
		$sessionInfo[gmdate("d/M/Y H:i:s", $k)] = $tempLog[$k];	
		$sessionInfo[gmdate("d/M/Y H:i:s", $k)]["elapsedTime"] = gmdate("i:s", ($k - $tempTimestamp));
		$tempTimestamp = $k;
	}
	
	$detailedSession = array();
	$detailedSession["timestamp"] = gmdate("d/M/Y H:i:s", $sessionStart);
	$detailedSession["user"] = $userid;
	$detailedSession["userName"] = $userobj->getRealName();
	$detailedSession["duration"] = timeFormat($sessionTotalTime);
	$detailedSession["accepts"] = $sessionAccepts;
	$detailedSession["rejects"] = $sessionRejects;
	$detailedSession["skips"] = $sessionSkips;
	$detailedSession["log"] = $sessionInfo;
	
	// our testing: 74/1403191785, 75/1402580250, 49/1401304039, 71/1402580879}
	if($detailedSession){
		echo json_encode($detailedSession);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function timeFormat($unix_timestamp) {
	$result = "";
	$rest = $unix_timestamp;
	
	if ($rest > 86400) {
		$days = (int) ($rest / 86400);
		$result = $result . $days . " days ";
		$rest = $rest % 86400;
	}
	
	if ($rest > 3600) {
		$hours = (int) ($rest / 3600);
		$result = $result . $hours . "h";
		$rest = $rest % 3600;
	}
	
	if ($rest > 60) {
		$minutes = (int) ($rest / 60);
		$result = $result . $minutes . "min";
		$rest = $rest % 60;
	}
	
	if ($rest > 0) {
		$result = $result . $rest . "s";
	}
	
	return $result;
}

function datedGeneralStats($startDate, $endDate) {
	//print "Start date: " . $startDate . "<br>";
	//print "End date: " . $endDate . "<br>";
	calcGeneralStats(true, false, $startDate, $endDate);
}

function generalStats() {
	calcGeneralStats(false, false, 0, 0);
}

function userStats($id) {
	calcGeneralStats(false, $id, 0, 0);
}

function datedUserStats($startDate, $endDate, $id) {
	//print "Start date: " . $startDate . "<br>";
	//print "End date: " . $endDate . "<br>";
	calcGeneralStats(true, $id, $startDate,$endDate);
}

// returns a collection of users objects within this context
function getUsersArray() {
	global $service;
	$dwas = new StatisticsDacuraAjaxServer($service->settings);
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	$users = $dwas->getUsersInContext($c_id, $d_id);
	//print_r($users);
	return $users;
}

// returns an array of user sessions for each specified user
// array structure is session_array[userid][session_sequencial_number]
function getUserSessionsArray($users_ids) {
	global $service;
	$session_array = array();
	
	foreach($users_ids as $value) {
		$url = $service->settings['dacura_sessions'] . $value . "/candidate_viewer.session";
	
		if (file_exists($url)) {
			//print_r($url);
			//print "<br>";
				
			$file_handle = @fopen($url, "r");
			if ($file_handle) {
				$i = 0;
				while (($json = fgets($file_handle)) !== false) {
					$session_array[$value][$i] = json_decode($json, true);
					$i++;
				}
				if (!feof($file_handle)) {
					echo "Error: unexpected fgets() fail\n";
				}
				fclose($file_handle);
			}
			// the code below does not sucessfully deal with several lines
			//$json = file_get_contents($url);
			//print_r($json);
			//print_r(json_last_error());
			//print "<br>"; print "<br>";
		}
	}
	return $session_array;
}

function calcGeneralStats($isDated, $specificUser, $startDate, $endDate) {
	global $service;
	$dwas = new StatisticsDacuraAjaxServer($service->settings);
	
	// retrieving the necessary info
	$users = getUsersArray();
	
	if($specificUser) {
		$users_ids = Array(0 => $specificUser);
		$session_array = getUserSessionsArray($users_ids);
	}
	else {
		$users_ids = array_keys($users);
		//print_r($users_ids);
		$session_array = getUserSessionsArray($users_ids);
	}
	
	// use the array to make calculations and put the calcs into another object
	
	$hasData = false;
	$user_sessions_organized = array();
	$user_rankings = array();
	$last_user = array();
	$last_user_timestamp = 0;
	$current_unix_time = time();
	$first_timestamp = time();
	$number_of_sessions = 0;
	$active_users = array();
	$last_week_users = array();
	$total_online_time = 0;
	$total_online_time_last_week = 0;
	$number_of_accepts = 0;
	$number_of_rejects = 0;
	$number_of_skips = 0;
	
	//for each user
	foreach ($session_array as $user_id => $user_sessions_array) {
		$user_accepts = 0;
		$user_rejects = 0;
		$user_skips = 0;
		$user_online_time = 0;
		$user_number_of_sessions = 0;
		$is_active = false;
		$is_active_last_7_days = false;
		
		//for each session of the user
		foreach ($user_sessions_array as $session_id => $timestamps_array) {
			$session_total_time = 0;
			$session_start_time;
			$session_end_time = 0;
			$is_paused = false;
			$is_last_week = false;
			$last_timestamp = 0;
			$local_accepts = 0;
			$local_rejects = 0;
			$local_skips = 0;
			
			//for each action inside the session 
			foreach ($timestamps_array as $timestamp => $action_array) {
				//print "User: " . $user_id . " | " . date("d/M/Y H:i:s", $timestamp) . " | ACTION: " . $action_array["action"] . "<br>";
				//here we can have an if for limit different time stamps!!
				
				switch($action_array["action"]) {
					case "start":
						// if the search is dated and the timestamp is before the start or after the end -> continue to the next session!
						if ($isDated == true && ($timestamp < $startDate || $timestamp > $endDate)) continue 3;
						
						$number_of_sessions++; // increase global number of sessions
						$user_number_of_sessions++;
						if ($hasData == false) $hasData = true; // flag that indicates that the return has at least one session
						
						// flag this user as an active user
						if (!$is_active) {
							$is_active = true;
							$active_users[$user_id] = $users[$user_id]->getRealName();
						}
						
						
						
						if (!$isDated) {
							// flag this user as active on the last week (not necessary in dated)
							if (!$is_active_last_7_days && $timestamp > ($current_unix_time - 604800)) { // 604800 = 7 * 24 * 60 * 60 seconds
								$is_active_last_7_days = true;
								$last_week_users[$user_id] = $users[$user_id]->getRealName();
							}
							// flag this session as a last week session
							if (!$is_last_week && $timestamp > ($current_unix_time - 604800)) {
								$is_last_week = true;
							}
						}
					
						$session_start_time = $timestamp; // update this session start_time
						
						if ($timestamp > $last_user_timestamp) { // if this is the last session so far
							$last_user = array();
							$last_user[$user_id] = $users[$user_id]->getRealName();
							$last_user_timestamp = $timestamp;
						}
						
						if ($timestamp < $first_timestamp) {
							$first_timestamp = $timestamp;
						}
						
						$last_timestamp = $timestamp;
						break;
						
					case "end":
						if ($is_paused) $session_end_time = $last_timestamp;
						else $session_end_time = $timestamp;
						break;
						
					case "pause":
						$is_paused = true;
						$last_timestamp = $timestamp;
						break;
						
					case "unpause":
						$is_paused = false;
						$session_total_time -= ($timestamp - $last_timestamp);
						$last_timestamp = $timestamp;
						break;
						
					case "abort":
						$session_end_time = $last_timestamp;
						break;
						
					case "accept":
						$number_of_accepts++;
						$local_accepts++;
						$user_accepts++;
						$last_timestamp = $timestamp;
						break;
						
					case "reject":
						$number_of_rejects++;
						$local_rejects++;
						$user_rejects++;
						$last_timestamp = $timestamp;
						break;
						
					case "skip":
						$number_of_skips++;
						$local_skips++;
						$user_skips++;
						$last_timestamp = $timestamp;
						break;
				}		
			}
			
			// calc the session total time
			$session_total_time += ($session_end_time - $session_start_time);
			// increase the total online time with session total time
			$total_online_time += $session_total_time;
			$user_online_time += $session_total_time;
			
			// if is last week add the last week time to the total (not necessary when dated)
			if ($is_last_week && !$isDated) $total_online_time_last_week += $session_total_time;
			
			// fill the organized section arrays
			$user_sessions_organized[($number_of_sessions - 1)]['user'] = $user_id;
			$user_sessions_organized[($number_of_sessions - 1)]['user_name'] = $users[$user_id]->getRealName();
			$user_sessions_organized[($number_of_sessions - 1)]['timestamp'] = gmdate("d/M/Y H:i:s", $session_start_time);
			$user_sessions_organized[($number_of_sessions - 1)]['unix_timestamp'] = $session_start_time;
			$user_sessions_organized[($number_of_sessions - 1)]['duration'] = timeFormat($session_total_time);
			$user_sessions_organized[($number_of_sessions - 1)]['unix_duration'] = $session_total_time;
			$user_sessions_organized[($number_of_sessions - 1)]['processed'] = $local_accepts + $local_rejects;
			$user_sessions_organized[($number_of_sessions - 1)]['accepted'] = $local_accepts;
			$user_sessions_organized[($number_of_sessions - 1)]['rejected'] = $local_rejects;
			$user_sessions_organized[($number_of_sessions - 1)]['skipped'] = $local_skips;
		}
		
		if ($user_number_of_sessions > 0) {
			$actual_user_number = count($active_users) - 1;
			$user_processed = $user_accepts + $user_rejects;
			$user_rankings[$actual_user_number]['user'] = $user_id;
			$user_rankings[$actual_user_number]['user_name'] = $users[$user_id]->getRealName();
			$user_rankings[$actual_user_number]['accepts'] = $user_accepts;
			$user_rankings[$actual_user_number]['rejects'] = $user_rejects;
			$user_rankings[$actual_user_number]['skips'] = $user_skips;
			$user_rankings[$actual_user_number]['processed'] = $user_processed;
			$user_rankings[$actual_user_number]['onlinetime_unix'] = $user_online_time;
			$user_rankings[$actual_user_number]['onlinetime'] = timeFormat($user_online_time);
			$user_rankings[$actual_user_number]['number_of_sessions'] = $user_number_of_sessions;
			$user_rankings[$actual_user_number]['session_mean_time'] = timeFormat((int)($user_online_time / $user_number_of_sessions));
			$user_rankings[$actual_user_number]['session_mean_time_unix'] = (int)($user_online_time / $user_number_of_sessions);
			if ($user_processed > 0) {
				$user_rankings[$actual_user_number]['mean_processing_time'] = timeFormat((int)($user_online_time / $user_processed));
				$user_rankings[$actual_user_number]['mean_processing_time_unix'] = (int)($user_online_time / $user_processed);
			}
			else {
				$user_rankings[$actual_user_number]['mean_processing_time'] = "No candidates processed";
				$user_rankings[$actual_user_number]['mean_processing_time_unix'] = 9999999999;
			}
		}
	}
	
	$results_array = array();
	$results_array["hasData"] = $hasData;
	
	if (!$hasData) {
		echo json_encode($results_array);
		return;
	}
	
	
	/* // sorting the array, not necessary with datables plugin!
	$session_timestamps = array();
	foreach ($user_sessions_organized as $key => $row)
	{
		$session_timestamps[$key] = $row['unix_timestamp'];
	}
	array_multisort($session_timestamps, SORT_DESC, $user_sessions_organized);
	*/
	
	//print_r($user_sessions_organized);
	
	// count the total number of users
	$number_of_active_users = count($active_users);
	
	// count the total number of users on last week (zero when dated, only makes sense when not dated)
	$number_of_active_users_last_week = count($last_week_users);
	
	// the users inactive on the period (last week for non-dated)
	$period_inactive_users = array();
	if ($isDated) {
		// count the inactive users on the dated period
		foreach ($users_ids as $id) {
			if (!in_array($id, $active_users)) {
				$period_inactive_users[$id] = $users[$id]->getRealName();
			}
		}
	}
	else {
		// count the inactive users on the last week
		foreach ($active_users as $key => $user_name) {
			if (!key_exists($key, $last_week_users)) {
				$period_inactive_users[$key] = $user_name;
			}
		}
	}
	
	$number_of_inactive_users_period = count($period_inactive_users);
	
	$average_session_time = (int)($total_online_time / $number_of_sessions);
	$number_of_processed = $number_of_accepts + $number_of_rejects;
	$average_processed_per_session = $number_of_processed / $number_of_sessions;
	$average_accepted_per_session = $number_of_accepts / $number_of_sessions;
	$average_skipped_per_session = $number_of_skips / $number_of_sessions;
	$average_rejected_per_session = $number_of_rejects / $number_of_sessions;
	
	$mean_candidate_processing_time = (int)($total_online_time / $number_of_processed);
	$mean_candidate_processing_per_hour = 3600 / $mean_candidate_processing_time;
	
	// Searching for real candidate numbers on SQL
	
	if ($specificUser) {
		if($isDated) {
			$sql_results = $dwas->getUserCandidatesSQLDated($startDate, $endDate, $specificUser, $users[$specificUser]->getRealName());
			//print_r($sql_results);
		
			$number_of_accepts_sql = $sql_results[0][0];
			$number_of_rejects_sql = $sql_results[0][1];
			$number_of_skips_sql = $sql_results[0][2];
			$number_of_processed_sql = $number_of_accepts_sql + $number_of_rejects_sql;
		
			$total_number_of_candidates = 0;
		}
		else {
			$sql_results = $dwas->getUserCandidatesSQL($specificUser);
			//print_r($sql_results);
		
			$number_of_accepts_sql = $sql_results[0][0];
			$number_of_rejects_sql = $sql_results[0][1];
			$number_of_skips_sql = $sql_results[0][2];
			$number_of_processed_sql = $number_of_accepts_sql + $number_of_rejects_sql;
		
			$sql_results_total = $dwas->getTotalCandidatesNumber();
			$total_number_of_candidates = $sql_results_total[0][0];
		}
	}
	else {
		if($isDated) {
			$sql_results = $dwas->getCandidatesSQLDated($startDate, $endDate);
			//print_r($sql_results);
		
			$number_of_accepts_sql = $sql_results[0][0];
			$number_of_rejects_sql = $sql_results[0][1];
			$number_of_skips_sql = $sql_results[0][2];
			$number_of_processed_sql = $number_of_accepts_sql + $number_of_rejects_sql;
		
			$total_number_of_candidates = 0;
		}
		else {
			$sql_results = $dwas->getCandidatesSQL();
			//print_r($sql_results);
		
			$number_of_accepts_sql = $sql_results[0][0];
			$number_of_rejects_sql = $sql_results[0][1];
			$number_of_skips_sql = $sql_results[0][2];
			$number_of_processed_sql = $number_of_accepts_sql + $number_of_rejects_sql;
		
			$sql_results_total = $dwas->getTotalCandidatesNumber();
			$total_number_of_candidates = $sql_results_total[0][0];
		}
	}
	
	
	
	//print_r($total_number_of_candidates);
	
	$total_number_of_unprocessed_candidates = $total_number_of_candidates - $number_of_processed_sql;
	
	// time estimations (only for non dated!)
	
	$estimated_work_to_be_done = 0;
	$mean_work_per_day_last_week = 0;
	$estimated_completion_date = 0;
	
	if (!$isDated && !$specificUser) {
		$estimated_work_to_be_done = $total_number_of_unprocessed_candidates * $mean_candidate_processing_time;
		$mean_work_per_day_last_week = $total_online_time_last_week / 5; // excluding weekends, so 5 days
		$estimated_completion_date = time() + (($estimated_work_to_be_done / $total_online_time_last_week) * 604800);
	}
	
	// construct and return the object
	
	$results_array = array();
	
	$results_array["hasData"] = $hasData;
	//print "<br><br>";
	//print "\$last_user: " . print_r($last_user) . "<br>";
	$results_array["last_user"] = $last_user;
	
	//print "\$last_user_timestamp: " . gmdate("d/M/Y H:i:s", $last_user_timestamp) . "<br>";
	$results_array["last_user_timestamp"] = gmdate("d/M/Y H:i:s", $last_user_timestamp);
	
	//print "\$number_of_sessions: " . $number_of_sessions . "<br>";
	$results_array["number_of_sessions"] = $number_of_sessions;
	
	//print "\$total_online_time: " . $total_online_time . "<br>";
	$results_array["total_online_time"] = timeFormat($total_online_time);
	
	//print "\$number_of_active_users: " . $number_of_active_users . "<br>";
	$results_array["number_of_active_users"] = $number_of_active_users;
	
	//print "\$active_users: "; print_r($active_users); print "<br>";
	$results_array["active_users"] = $active_users;
	
	//print "\$number_of_active_users_last_week: " . $number_of_active_users_last_week . "<br>";
	$results_array["number_of_active_users_last_week"] = $number_of_active_users_last_week;
	
	//print "\$last_week_users: "; print_r($last_week_users); print "<br>";
	$results_array["last_week_users"] = $last_week_users;
	
	//print "\$number_of_inactive_users_period: " . $number_of_inactive_users_period . "<br>";
	$results_array["number_of_inactive_users_period"] = $number_of_inactive_users_period;
	
	//print "\$last_week_inactive_users: "; print_r($last_week_inactive_users); print "<br>";
	$results_array["period_inactive_users"] = $period_inactive_users;
	
	//print "\$total_number_of_candidates: " . $total_number_of_candidates . "<br>";
	$results_array["total_number_of_candidates"] = $total_number_of_candidates;
	
	//print "\$number_of_processed: " . $number_of_processed . "<br>";
	$results_array["number_of_processed"] = $number_of_processed;
	
	//print "\$number_of_processed_sql: " . $number_of_processed_sql . "<br>";
	$results_array["number_of_processed_sql"] = $number_of_processed_sql;
	
	//print "\$number_of_accepts: " . $number_of_accepts . "<br>";
	$results_array["number_of_accepts"] = $number_of_accepts;
	
	//print "\$number_of_accepts_sql: " . $number_of_accepts_sql . "<br>";
	$results_array["number_of_accepts_sql"] = $number_of_accepts_sql;
	
	//print "\$number_of_rejects: " . $number_of_rejects . "<br>";
	$results_array["number_of_rejects"] = $number_of_rejects;
	
	//print "\$number_of_rejects_sql: " . $number_of_rejects_sql . "<br>";
	$results_array["number_of_rejects_sql"] = $number_of_rejects_sql;
	
	//print "\$number_of_skips: " . $number_of_skips . "<br>";
	$results_array["number_of_skips"] = $number_of_skips;
	
	//print "\$number_of_skips_sql: " . $number_of_skips_sql . "<br>";
	$results_array["number_of_skips_sql"] = $number_of_skips_sql;
	
	//print "\$total_number_of_unprocessed_candidates: " . $total_number_of_unprocessed_candidates . "<br>";
	$results_array["total_number_of_unprocessed_candidates"] = $total_number_of_unprocessed_candidates;
	
	//print "\$average_session_time: " . number_format(($average_session_time / 60), 2) . " minutes<br>";
	$results_array["average_session_time"] = timeFormat($average_session_time);
	
	//print "\$average_processed_per_session: " . number_format($average_processed_per_session, 2) . "<br>";
	$results_array["average_processed_per_session"] = number_format($average_processed_per_session, 2);
	
	//print "\$average_accepted_per_session: " . number_format($average_accepted_per_session, 2) . "<br>";
	$results_array["average_accepted_per_session"] = number_format($average_accepted_per_session, 2);
	
	//print "\$average_skipped_per_session: " . number_format($average_skipped_per_session, 2) . "<br>";
	$results_array["average_skipped_per_session"] = number_format($average_skipped_per_session, 2);
	
	//print "\$average_rejected_per_session: " . number_format($average_rejected_per_session, 2) . "<br>";
	$results_array["average_rejected_per_session"] = number_format($average_rejected_per_session, 2);
	
	//print "\$mean_candidate_processing_time: " . number_format($mean_candidate_processing_time, 2) . " seconds<br>";
	$results_array["mean_candidate_processing_time"] = timeFormat($mean_candidate_processing_time);
	
	//print "\$mean_candidate_processing_per_hour: " . number_format($mean_candidate_processing_per_hour, 2) . "<br>";
	$results_array["mean_candidate_processing_per_hour"] = number_format($mean_candidate_processing_per_hour, 2);
	
	//print "\$mean_work_hours_per_day_last_week: " . number_format(($mean_work_hours_per_day_last_week / 3600), 2) . " hours<br>";
	$results_array["mean_work_per_day_last_week"] = timeFormat($mean_work_per_day_last_week);
	
	//print "\$estimated_work_to_be_done: " . number_format(($estimated_work_to_be_done / 3600), 2) . " hours<br>";
	$results_array["estimated_work_to_be_done"] = timeFormat($estimated_work_to_be_done);
	
	//print "\$estimated_completion_date: " . gmdate("d/M/Y", $estimated_completion_date) . "<br>";
	$results_array["estimated_completion_date"] = gmdate("d/M/Y", $estimated_completion_date);
	
	$results_array["user_sessions"] = $user_sessions_organized;
	$results_array["user_rankings"] = $user_rankings;
	
	if($results_array){
		echo json_encode($results_array);
		//print_r(json_last_error());
		//echo $collobj;
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}



