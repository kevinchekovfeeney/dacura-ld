<div class='dacura-screen' id='users-home'>
	<?php if(in_array("list-users", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='list-users' title="List Users">
		<div class='tholder' id='user-table-container'>
			<table id="users-table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id="duo-id" title="The internal ID of the collection - a component in all collection internal URLs">ID</th>
					<th id="duo-email" title="The users primary email address.">Email</th>
					<th id="duo-name" title="The users handle - expressed in natural language">Handle</th>
					<th id="duo-status" title="Only users with status 'accept' are active.">Status</th>
					<th id="dfn-getRoleSummary" title="What roles does this user have within this context">Roles</th>
					<th id="dfn-getCollectionSummary" title="How many collections does the user have roles in?">Collections</th>
					<th id="dfn-rowselector" title="Select a group of users">Select</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="subscreen-buttons" id='user-table-updates'></div>
	</div>
	<?php } if(in_array("add-user", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='add-user' title="Add User">
		<div class='subscreen-intro-message'><?=$params['add_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("user-details", $params['create_user_fields'], array("display_type" => "create"));?>
		<div class="subscreen-buttons">
			<button id='usercreate' class='dacura-create subscreen-button'>Add New User</button>
		</div>
	</div>
	<?php } if(in_array("invite-users", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='invite-users' title="Invite Users">
		<div class='subscreen-intro-message'><?=$params['invite_intro_msg']?></div>
	
		<?php echo $service->getInputTableHTML("uinvites", $params['invite_users_fields'], array("display_type" => "create"));?>
		<div class="subscreen-buttons">
			<button id='usersinvite' class='dacura-update subscreen-button'>Invite Users</button>
		</div>
	</div>
	<?php } ?>
</div>

<script>
/*
 * Page Configuration Settings for each subscreen 
 */

	/* List users subscreen */
	
	/* Updates each of the selected users' statuses in sequence */
	function updateUsersStatus(ids, status, cnt, pconf){
		dacura.tool.clearResultMessages();
		var nid = ids.shift();
		var obj = {"status": status, "id": nid};
		var onwards = function(){
			if(!isEmpty(ids)){
				updateUsersStatus(ids, status, cnt, pconf);
			}
			else {
				showUpdateStatusSuccess(status, cnt, pconf);
				refreshUserList();
			}
		}
		dacura.users.updateUser(obj, onwards, pconf);
	}

	/* shows a message to the user after an update status on the list page */
	function showUpdateStatusSuccess(status, cnt, targets){          
		dacura.system.showSuccessResult(cnt + " users updated to status " + status, "Users Update OK", targets.resultbox, false, {'scrollTo': true, "icon": true, "closeable": true});
	}
	
	/*Gets the number of roles for each user*/ 	
	function getRoleSummary(obj){
		return dacura.users.roles.summary(obj.roles);
	}

	/* gets a summary of the users collection memberships */
	function getCollectionSummary(obj){
		return dacura.users.roles.bycollection(obj.collections);
	}

	//refreshes the list of users from the server - called whenever a user is created or users have their status updated 
	function refreshUserList(){
		dacura.tool.table.refresh("users-table");
	}
	
	/* Create user subscreen */

	//checks to ensure that there are no egregious input errors on the create user subscreen
	function inputError(obj){
		if(typeof obj.email == "undefined" || typeof obj.password == "undefined"){
			return "bad reading of object from input";
		}
		if(obj.email.length < 5){
			return "The ID must be at least 3 characters long and the password must be at least 6 characters";
		}
		return false;
	}

	//displays a message to the user when they have successfully create a user
	function showCreateSuccess(txt, targets){
		dacura.tool.clearResultMessages();
		refreshUserList();
		var usrurl = "<?= $service->my_url()."/"; ?>" + txt.id;
		dacura.system.showSuccessResult("The users account has been created at: <a href='" + usrurl + "'>" + usrurl + "</a>", "User with id: " + txt.id + " successfully created", targets.resultbox);
		dacura.tool.form.clear("user-details");
	}

	/* Invite users subscreen */
	
	//A table showing the outcomes of each invited user...
	function drawInviteResultTable(res){
		var html = "<ul>";
		for(var key in res){
			html += "<li>" + key + " " + res[key] + "</li>";
		}
		html += "</ul>";
		return html;
	}

	//Display the result of an invitation request
	function showInviteResult(json, targets){
		dacura.tool.clearResultMessages();
		if(size(json.issued) > 0){
			refreshUserList();
			var msg = size(json.issued) + " invitations successfully issued";
			html = "<h3>Invitations Issued</h3>" + drawInviteResultTable(json.issued);
			if(size(json.failed) > 0){
				msg  += ", " + size(json.failed) + " addresses failed";
				html += "<h3>Failed</h3>" + drawInviteResultTable(json.failed);				
				dacura.system.showWarningResult(msg, "Invitations processed but errors encountered", targets.resultbox, html);
			}
			else {
				dacura.system.showSuccessResult(msg, "Invitations processed successfully", targets.resultbox, html);			
			}
			dacura.tool.form.clear("uinvites");			
		}
		else {
			msg  = "all " + size(json.failed) + " addresses failed";
			html = drawInviteResultTable(json.failed);				
			dacura.system.showErrorResult(msg, "Invite failed", targets.resultbox, html);
		}
	}

	/* on page load initialise tool, table and buttons */
	$(function() {
		dacura.tool.init({"tabbed": 'users-home'});
		<?php if(isset($params['admin']) && $params['admin']){?>
			dacura.tool.table.init("users-table", {
				"screen": "list-users", 
				"fetch": dacura.users.getUsers,
				"multiselect": {
					options: <?=$params['selection_options']?> , 
					intro: "Update Selected Users, Set Status to ", 
					container: "user-table-updates",
					label: "Update",
					update: updateUsersStatus 
				},
				"refresh": {label: "Refresh User List"},
				"cellClick": function(event, entid) {window.location.href = dacura.system.pageURL() + "/" + entid},
				"dtsettings": <?=$params['admin_table_settings']?>
			});
			dacura.tool.button.init("usercreate", {
				"screen": "add-user",
				"source": "user-details",
				"validate": inputError, 
				"submit": dacura.users.addUser, 
				"result": showCreateSuccess
			});
			dacura.tool.button.init("usersinvite", {
				"screen": "invite-users",
				"source": "uinvites",
				"submit": dacura.users.inviteUsers,
				"result": showInviteResult				
			});
		<?php } else { ?>
		dacura.tool.table.init("users-table", {
			"screen": "list-users", 
			"fetch": dacura.users.getUsers,
			"refresh": {label: "Refresh User List"},
			<?php if($params['clickable_users']){ ?>
			"cellClick": function(event, entid) {window.location.href = dacura.system.pageURL() + "/" + entid},
			<?php } else {?>
			"nohover": true,
			<?php }?>
			"dtsettings": <?=$params['list_table_settings']?>
		});
		<?php } ?>
});
</script>
