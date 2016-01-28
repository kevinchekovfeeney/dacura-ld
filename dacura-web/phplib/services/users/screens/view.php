<div class='dacura-screen' id='user-home'>
	<?php if(in_array("user-details", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='user-details' title="Details">
		<div class='subscreen-intro-message'><?=$params['details_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("udetails", $params['update_details_fields'], array("display_type" => $params['update_form_type']));?>
		<div class="subscreen-buttons">
			<?php if($params['showdelete']){?>
			<button id='userdelete' class='dacura-delete subscreen-button'>Delete User</button>
			<?php }if($params['showupdate']){?>
			<button id='userupdate' class='dacura-update subscreen-button'><?=$params['update_button_text']?></button>
			<?php }?>
		</div>
	</div>
	<?php } if(in_array("collection-roles", $params['subscreens'])) { ?>	
	<div class='dacura-subscreen' id='collection-roles' title="Roles">
		<div class='subscreen-intro-message'><?=$params['roles_intro_msg']?></div>		
		<table class='collection-role-table'>
			<thead>
				<tr>
					<th>Roles possessed by user</th>
					<th>Available Roles</th>
					<th class='dch'>Implicitly possessed</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td id='possessed-roles'></th>
					<td id='available-roles'></th>
					<td class='dch' id='implicit-roles'></th>
				</tr>
			</tbody>
		</table>
	</div>
	<?php } if(in_array("user-roles", $params['subscreens'])) { ?>	
	<div class='dacura-subscreen' id='user-roles' title="Roles">
		<div class='subscreen-intro-message'><?=$params['roles_intro_msg']?></div>		
		<?php if(count($params['role_options']) > 0){?>
			<div id="user-role-add">
				<table class='create-role-table'><tr>
					<td class='option'><select id="rolecollectionip"></select></td>
					<td class='option'><select id="rolenameip"></select></td>
					<td><button id='rolecreate' class='dacura-create subscreen-button'>Add Role</button>
				</tr></table>
			</div>	
		<?php }?>
		<div id='roles-listing'>
			<div class='tholder' id='roles-table-holder'>
				<table id="roles-table" class="dacura-api-listing">
					<thead>
					<tr>
						<th id="dxo-id" title="The internal ID of the role">Role ID</th>
						<th id="dxo-collection_id" title="The collection id that the role is associated with">Collection</th>
						<th id="dfx-showRole" title="The dacura role allocated to the user.">Role</th>
						<th id="dfn-rowselector">Select</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<div class="subscreen-buttons" id="role-buttons">
			</div>
		</div>		
	</div>
	<?php } if(in_array("user-password", $params['subscreens'])) { ?>	
	<div class='dacura-subscreen' id='user-password' title="Password">
		<div class='subscreen-intro-message'><?=$params['password_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("upassword", $params['update_password_fields'], array("display_type" => "create"));?>		
		<div class="subscreen-buttons">
			<button id='passwordupdate' class='dacura-update subscreen-button'>Update Password</button>
		</div>
	</div>
	<?php } if(in_array("user-history", $params['subscreens'])) { ?>		
	<div class='dacura-subscreen' id='user-history' title="History">
		<div class='subscreen-intro-message'><?=$params['history_intro_msg']?></div>
		<div class='tholder' id='history-table-holder'>
			<table id="history-table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id="dlo-id" title="The session id">ID</th>
					<th id="dlo-service" title="The Dacura service involved">Service</th>
					<th id="dlo-collection" title="The dacura collection involved">Collection</th>
					<th id="dlo-event_count" title="The number of actions that took place in the session">Actions</th>
					<th id="dlo-start"></th>
					<th id="dfn-printStart" title="The timestamp when the session started">Session Started</th>
					<th id="dlo-duration"></th>
					<th id="dfn-printDuration" title="The session's duration">Duration</th>
					<th id="dlo-end"></th>
					<th id="dfn-printEnd" title="The timestamp when the session ended">Session Ended</th>
					<th id="dfn-sevents" class='rawjson' title="The events themselves">Events</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>	
	<?php } ?>	
</div>

<script>
/* just some flags to indicate that things have already been initialised */
var user_loaded = false;
var roles_loaded = false;
var history_loaded = false;
/* we keep the set of events from a session in state here */
var session_events = {};
/* list of all roles */
var allroles = <?=isset($params['all_roles']) ? json_encode($params['all_roles']) : "{}"?>;

/* update the set of session events with the passed object */
function sevents(obj){
	session_events[obj.id] = obj;
}

/* simple callback functions for drawing data in table cells */
function printEnd(obj){
	return timeConverter(obj.end);
}
function printStart(obj){
	return timeConverter(obj.start);
}
function printDuration(obj){
	return durationConverter(obj.duration);
}
function showRole(obj){
	return dacura.users.roles.onehtml(obj.role);
}

/* Functions to report the successful results of api actions */
function showCreateRoleResult(obj, targets){
	drawRoles(obj);
	dacura.system.showSuccessResult("New role successfully added", "User roles updated", targets.resultbox, false, targets.mopts);
}

function showDeleteRoleResult(obj, targets){
	drawRoles(obj);
	dacura.system.showSuccessResult("Roles successfully removed", "User roles updated", targets.resultbox, false, targets.mopts);
}

function showDeleteResult(obj, targets){
	dacura.system.showWarningResult("This user has been deleted", "User <?=$params['userid']?> deleted", targets.resultbox, false, targets.mopts);
}

function showUpdatePasswordResult(obj, targets){
	dacura.system.showSuccessResult("This user's password has been successfully updated", "User <?=$params['userid']?> password updated", targets.resultbox, false, targets.mopts);
}

/* redraws user page after update */ 
function showUpdatedUser(obj, targets){
	if(obj.status == "deleted"){
		showDeleteResult(obj, targets);
	}
	else {
		dacura.users.header(obj);
		drawDetails(obj);
		<?php if(in_array("user-roles", $params['subscreens']) || in_array("collection-roles", $params['subscreens'])) {?>
			drawRoles(obj);
		<?php } ?>		
		dacura.system.showSuccessResult("Updates successfully saved", "User " + obj.handle + " updated", targets.resultbox, false, targets.mopts);
	}
}
/* draw the basic details of the user */ 
function drawDetails(obj){
 	var prof = obj.profile;
	if(isEmpty(obj.profile)){
		prof = {};	
	}
	prof["id"] = obj.id;
	prof["name"] = obj.name;
	prof["status"] = obj.status;
	prof["email"] = obj.email;
	dacura.tool.form.populate("udetails", prof);
	$('#udetails select.dacura-select').selectmenu("refresh");
		
}

<?php if(in_array("user-roles", $params['subscreens']) || in_array("collection-roles", $params['subscreens'])) {?>
		/* <collectionid:[roles]> which roles in which collections are available to the user to dole out */
var roleoptions = <?=isset($params['role_options']) ? json_encode($params['role_options']) : "{}"?>;

/* add passed role to user */
dacura.users.roles.add = function(rname){
	var data = {
		uid: "<?=$params['userid']?>", 
		collection: dacura.system.cid(),
		role: rname
	};
	dacura.users.createRole(data, showCreateRoleResult, dacura.tool.subscreens['user-roles']);
}

/* remove role with passed id from user */
dacura.users.roles.remove = function(id){
	var data = {
		uid: "<?=$params['userid']?>", 
		rids: [id]
	};
	dacura.users.deleteRoles(data, showDeleteRoleResult, dacura.tool.subscreens['user-roles']);
}

/* draw user roles screen */
function drawRoles(obj){
	if(roles_loaded){
		dacura.users.roles.refresh("roles-table", obj.roles, "user-roles");
	}
	else {
		var tconfig = {
			"dtsettings": <?=$params['roles_table_settings']?>,
			"screen": "user-roles",
			"multiselect": {
				label: "Delete Selected Roles",
				container: "role-buttons",
				update: deleteRoles 
			},				
		};					
		dacura.users.roles.show("roles-table", obj.roles, tconfig, <?php echo ($params['showupdate'] == "true" ? "true": "false");?>);
		roles_loaded = true;
	}
}

/* delete roles with passed ids from user */
function deleteRoles(ids){
	obj = { uid: "<?=$params['userid']?>", rids: ids };
	dacura.users.deleteRoles(obj, showDeleteRoleResult, dacura.tool.subscreens['user-roles']);	
}

<?php } if(in_array("user-history", $params['subscreens'])){?>


/* draw user history screen */
function drawHistory(obj){
	if(history_loaded){
		dacura.tool.table.refresh("history-table");
	}
	else {
		dacura.tool.table.init("history-table", {
			"fetch": function(onwards, conf){ dacura.users.fetchUserHistory("<?=$params['userid']?>", onwards, conf);},
			"empty": function(key, conf){ dacura.system.showWarningResult(obj.handle + " has no registered no sessions with the system", "No history", dacura.tool.subscreens['user-history'].resultbox);},
			"refresh": { label: "Refresh History List" },
			"rowClick": showSessionEvents,
			"dtsettings": <?=$params['history_table_settings']?>,
			"screen": "user-history"					
		});
		history_loaded = true;
	}	
}

/* show the events for a particular session */ 
function showSessionEvents(e, entid){
	if(typeof entid != "string"){
		alert("no entity id");
	}
	obj = session_events[entid];
	var title = "Session ID:" + obj.start + " Service: " + obj.service;
	if(dacura.system.cid() == "all") title += " Collection: " + obj.collection; 
	dacura.system.showInfoResult(dacura.users.eventTable(obj.events), title, dacura.tool.subscreens["user-history"].resultbox, false, {icon: true, closeable: true, scrollTo: true});
	dacura.system.styleJSONLD();	
}
<?php } ?>

/* draws user page on load*/ 
function drawUser(obj, targets){
	if(!user_loaded){
		dacura.tool.initScreens("user-home");
		if(typeof roleoptions != "undefined"){
			dacura.users.roles.createbox.init(roleoptions);
		}
		else {
			$('#user-role-add').empty();
		}
		dacura.tool.header.addBreadcrumb("<?=$service->my_url()."/".$params['userid']?>", "User " + obj.handle, "selected-user");		
	}
	dacura.users.header(obj);
	drawDetails(obj);
	<?php if(in_array("user-history", $params['subscreens'])) {?>
		drawHistory(obj);
	<?php } if(in_array("user-roles", $params['subscreens']) || in_array("collection-roles", $params['subscreens'])) {?>
		drawRoles(obj);
	<?php } ?>
	user_loaded = "<?=$params['userid']?>";
}

/* on page load initialise buttons, fetch user object from api and send results to drawUser*/
$(function() {
	dacura.tool.init({
		"buttons": {
			"userupdate": {
				"screen": "user-details",
				"source": "udetails",
				"submit": dacura.users.updateUser, 
				"result": showUpdatedUser
			},
			"userdelete": {
				"screen": "user-details",
				"gather": function(){ return "<?=$params['userid']?>"},
				"submit": dacura.users.deleteUser, 
				"result": showDeleteResult
			},
			"rolecreate": {
				"screen": "user-roles",
				"gather": function() { var obj = dacura.users.roles.createbox.vals(); obj.uid = "<?=$params['userid']?>"; return obj;},
				"submit": dacura.users.createRole,
				"result": showCreateRoleResult
			},
			"passwordupdate":  {
				"screen": "user-password",
				"source": "upassword",
				"submit": function (obj, onwards, targets) { obj.uid = "<?=$params['userid']?>", dacura.users.updatePassword(obj, onwards, targets)},
				"validate": dacura.users.validatePasswords,
				"result": showUpdatePasswordResult				
			}
		}
	});
	
	var pconf = { resultbox: ".tool-info", busybox: "#user-home"};
	dacura.users.fetchUser("<?=$params['userid']?>", drawUser, pconf);
});
</script>

