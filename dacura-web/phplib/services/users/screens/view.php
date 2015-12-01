<div class='dacura-screen' id='user-home'>
	<?php if(in_array("user-details", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='user-details' title="Details">
		<div class='subscreen-intro-message'><?=$params['details_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("udetails", "update", $params['update_details_fields']);?>
		<div class="subscreen-buttons">
			<?php if($params['showdelete']){?>
			<button id='userdelete' class='dacura-delete subscreen-button'>Delete User</button>
			<?php }?>
			<button id='userupdate' class='dacura-update subscreen-button'><?=$params['update_button_text']?></button>
		</div>
	</div>
	<?php } if(in_array("user-roles", $params['subscreens'])) { ?>	
	<div class='dacura-subscreen' id='user-roles' title="Roles">
		<div class='subscreen-intro-message'><?=$params['roles_intro_msg']?></div>		
		<div id="user-role-add">
			<table class='create-role-table'><tr>
				<td class='option'><select class='dacura-select' id="rolenameip"><?=$params['role_name_options']?></select></td>
				<td class='option'><select class='dacura-select' id="rolecollectionip"></select></td>
				<td class='dch option'><select class='dacura-select' id="roledatasetip"></select></td>
				<td><button id='rolecreate' class='dacura-create subscreen-button'>Add Role</button>
				
			</tr></table>
		</div>	
		<div id='roles-listing'>
			<table id="roles-table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id="dxo-id" title="The internal ID of the role">Role ID</th>
					<th id="dxo-collection_id" title="The collection id that the role is associated with">Collection</th>
					<th id="dxo-dataset_id" title="Only datasets with status 'accept' are in use.">Dataset</th>
					<th id="dxo-role" title="Only datasets with status 'accept' are in use.">Role</th>
					<th id="dfn-rowselector">Select</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
			<div class="subscreen-buttons role-buttons">
				<button id='deleteroles' class='dacura-delete subscreen-button'>Delete Selected Roles</button>
			</div>
		</div>		
	</div>
	<?php } if(in_array("user-password", $params['subscreens'])) { ?>	
	<div class='dacura-subscreen' id='user-password' title="Password">
		<div class='subscreen-intro-message'><?=$params['password_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("upassword", "create", $params['update_password_fields']);?>		
		<div class="subscreen-buttons">
			<button id='passwordupdate' class='dacura-update subscreen-button'>Update Password</button>
		</div>
	</div>
	<?php } if(in_array("user-history", $params['subscreens'])) { ?>		
	<div class='dacura-subscreen' id='user-history' title="History">
		<div class='subscreen-intro-message'><?=$params['history_intro_msg']?></div>
		<table id="history-table" class="dacura-api-listing">
			<thead>
			<tr>
				<th id="dlo-start"></th>
				<th id="dfn-printStart" title="The timestamp when the session started">Session Started</th>
				<th id="dlo-end"></th>
				<th id="dfn-printEnd" title="The timestamp when the session ended">Session Ended</th>
				<th id="dlo-duration"></th>
				<th id="dfn-printDuration" title="The session's duration">Duration</th>
				<th id="dlo-service" title="The Dacura service involved">Service</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>	
	<?php } ?>	
</div>

<script>
var user_loaded = false;
var roles_loaded = false;

var roleoptions = <?=json_encode($params['role_options'])?>;

function printEnd(obj){
	return timeConverter(obj.end);
}

function printStart(obj){
	return timeConverter(obj.start);
}

function printDuration(obj){
	return durationConverter(obj.duration);
}

function showCreateRoleResult(obj, targets){
	drawRoles(obj);
	dacura.system.showSuccessResult("New role successfully added", obj, "User roles updated", targets.resultbox);
}

function showDeleteRoleResult(obj, targets){
	drawRoles(obj);
	dacura.system.showSuccessResult("Roles successfully removed", obj, "User roles updated", targets.resultbox);
}

function showDeleteResult(obj, targets){
	dacura.system.showWarningResult("This user has been deleted", false, "User <?=$params['userid']?> deleted", targets.resultbox);
}

function showUpdatePasswordResult(obj, targets){
	dacura.system.showSuccessResult("This user's password has been successfully updated", false, "User <?=$params['userid']?> password updated", targets.resultbox);
}

function showUpdatedUser(obj, targets){
	drawDetails(obj);
	dacura.system.showSuccessResult("Updates successfully saved", obj, "User " + obj.handle + " updated", targets.resultbox);
}

function drawDetails(obj){
	var nobj = obj.profile;
	nobj.id = obj.id;
	nobj.name = obj.name;
	nobj.status = obj.status;
	nobj.email = obj.email;
	dacura.system.drawDacuraUpdateObject("udetails", nobj);	
}

function drawHistory(obj){
	if(typeof obj.history == "undefined" || obj.history.length == 0){
		dacura.system.showWarningResult(obj.handle + " has no registered no sessions with the system", false, "No history", "#user-history-msgs");
	}
	else {
		dacura.system.drawDacuraListingTable("history-table", obj.history, <?= $params['history_table_settings']?>, function(){}, null, user_loaded);
	}	
}

function drawRoles(obj){
	if(typeof obj.roles == "undefined" || obj.roles.length == 0){
		dacura.system.showWarningResult(obj.handle + " has no roles configured in this context", false, "No roles", "#user-roles-msgs");
		$('#roles-listing').hide();
	}
	else {
		$('#roles-listing').show();
		dacura.system.drawDacuraListingTable("roles-table", obj.roles, <?= $params['roles_table_settings']?>, dacura.system.listingRowSelected, null, roles_loaded);
		roles_loaded = true;
	}
}

function drawUser(obj, targets){
	if(!user_loaded){
		dacura.system.addServiceBreadcrumb("<?=$service->my_url()."/".$params['userid']?>", "User " + obj.handle, "selected-user");
	}
	drawDetails(obj);
	drawHistory(obj);
	drawRoles(obj);
	user_loaded = "<?=$params['userid']?>";
}

function getRoleDeletes(){
	var obj = {};
	obj.rids = [];
	obj.uid = "<?=$params['userid']?>";
	$('#roles-table input:checkbox').each(function(){
		if($(this).is(":checked")){
	        obj.rids.push(this.id.substring(4));
	    }
	});
	return obj;
}

function validatePasswords(obj){
	if(obj.password.length < 6) {
		return "The password must be at least six characters long";
	}
	if(obj.password != obj.confirmpassword){
		return "The two passwords do not match";
	}
	return "";
}

function validateRoleDeletes(obj){
	if(obj.rids.length == 0){
		return "You have not selected any roles to delete";
	}
	return "";
}

function getRoleInputs(){
	var obj = {};
	obj.collection = $('#rolecollectionip option:selected').val();
	obj.dataset = $('#roledatasetip option:selected').val();
	obj.role = $('#rolenameip option:selected').val();
	obj.uid = "<?=$params['userid']?>";
	obj.level = "0";
	return obj;
}

function setupRoleOptions(){
	var num = 0;
	$.each(roleoptions, function(i, obj) {
		num++;
		var selected = "";
		if(num == 1){
			selected = "selected ";
		}
		$('#rolecollectionip').append("<option " + selected  + "value='"+ i + "'>" + obj.title + "</option>");
		$('#rolecollectionip').selectmenu( "refresh" );
	});
}

$(function() {
	dacura.system.init({
		"mode": "tool", 
		"tabbed": 'user-home', 
		"entity_id": "<?=$params['userid']?>",
		"load": dacura.users.fetchUser,
		"draw": drawUser,
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
				"gather": getRoleInputs,
				"submit": dacura.users.createRole,
				"result": showCreateRoleResult
			},
			"deleteroles": {
				"screen": "user-roles",
				"gather": getRoleDeletes,
				"submit": dacura.users.deleteRoles,
				"validate": validateRoleDeletes,
				"result": showDeleteRoleResult				
			},
			"passwordupdate":  {
				"screen": "user-password",
				"source": "upassword",
				"submit": function (obj, onwards, targets) { obj.uid = "<?=$params['userid']?>", dacura.users.updatePassword(obj, onwards, targets)},
				"validate": validatePasswords,
				"result": showUpdatePasswordResult				
			}
		}
	});
	if(roleoptions){
		setupRoleOptions();
	}
	else {
		$('#user-role-add').empty();
	}
	
});
</script>

