<div class='dacura-screen' id='users-home'>
	<?php if(in_array("list-users", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='list-users' title="List Users">
		<table id="users-table" class="dacura-api-listing">
			<thead>
			<tr>
				<th id="duo-id" title="The internal ID of the collection - a component in all collection internal URLs">ID</th>
				<th id="duo-email" title="The users primary email address.">Email</th>
				<th id="duo-name" title="The users handle - expressed in natural language">Handle</th>
				<th id="duo-status" title="Only users with status 'accept' are active.">Status</th>
				<th id="dfn-getRoleCount" title="How many roles does this user have within this collection">Roles</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<?php } if(in_array("add-user", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='add-user' title="Add User">
		<div class='subscreen-intro-message'><?=$params['add_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("user-details", "create", $params['create_user_fields']);?>
		<div class="subscreen-buttons">
			<button id='usercreate' class='dacura-create subscreen-button'>Create New User</button>
		</div>
	</div>
	<?php } if(in_array("invite-users", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='invite-users' title="Invite Users">
		<div class='subscreen-intro-message'><?=$params['invite_intro_msg']?></div>
	
		<?php echo $service->getInputTableHTML("uinvites", "create", $params['invite_users_fields']);?>
		<div class="subscreen-buttons">
			<button id='usersinvite' class='dacura-update subscreen-button'>Invite Users</button>
		</div>
	</div>
	<?php } ?>
</div>

<script>
	function getRoleCount(obj){
		if(typeof obj.roles == "undefined") return 0;
		return size(obj.roles);
	}

	function inputError(obj){
		if(typeof obj.email == "undefined" || typeof obj.password == "undefined"){
			return "bad reading of object from input";
		}
		if(obj.email.length < 5){
			return "The ID must be at least 3 characters long and the password must be at least 6 characters";
		}
		return false;
	}

	function showCreateSuccess(txt, targets){
		refreshUserList();
		var usrurl = "<?= $service->my_url()."/"; ?>" + txt.id;
		dacura.system.showSuccessResult("The users account has been created at: <a href='" + usrurl + "'>" + usrurl + "</a>", false, "User with id: " + txt.id + " successfully created", targets.resultbox);
	}

	function drawInviteResultTable(res){
		var html = "<ul>";
		for(var key in res){
			html += "<li>" + key + " " + res[key] + "</li>";
		}
		html += "</ul>";
		return html;
	}

	function refreshUserList(){
		dacura.system.refreshDacuraListingTable("users-table");
	}

	function showInviteResult(json, targets){
		if(size(json.issued) > 0){
			refreshUserList();
			var msg = size(json.issued) + " invitations successfully issued";
			html = "<h3>Invitations Issued</h3>" + drawInviteResultTable(json.issued);
			if(size(json.failed) > 0){
				msg  += ", " + size(json.failed) + " addresses failed";
				html += "<h3>Failed</h3>" + drawInviteResultTable(json.failed);				
				dacura.system.showWarningResult(msg, html, "Invitations processed but errors encountered", targets.resultbox);
			}
			else {
				dacura.system.showSuccessResult(msg, html, "Invitations processed successfully", targets.resultbox);			
			}
		}
		else {
			msg  = "all " + size(json.failed) + " addresses failed";
			html = drawInviteResultTable(json.failed);				
			dacura.system.showErrorResult(msg, html, "Invite failed", targets.resultbox);
		}
	}
	
	$(function() {
		dacura.system.init({
			"mode": "tool", 
			"tabbed": 'users-home', 
			"listings": {
				"users-table": {
					"screen": "list-users", 
					"fetch": dacura.users.getUsers,
					"settings": <?=$params['dacura_table_settings']?>
				}
			}, 
			"buttons": {
				"usercreate": {
					"screen": "add-user",
					"source": "user-details",
					"validate": inputError, 
					"submit": dacura.users.addUser, 
					"result": showCreateSuccess
				},
				"usersinvite":{
					"screen": "invite-users",
					"source": "uinvites",
					"submit": dacura.users.inviteUsers,
					"result": showInviteResult				
				}
			}
		});
	});
	
	
</script>
