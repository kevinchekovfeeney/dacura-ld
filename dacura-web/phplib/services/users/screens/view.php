<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<div id="user-pane-holder">
	 <ul id="user-pane-list" class="dch">
	 	<li><a href="#user-update">Details</a></li>
	 	<li><a href="#user-roles">Roles</a></li>
		<li><a href="#user-profile">Profile</a></li>
		<li><a href="#user-password">Password</a></li>
		<li><a href="#user-history">History</a></li>
	</ul>
		<div id="roles-holder">
			<div id="user-roles" class="user-pane dch">
				<div class="tab-top-message-holder">
					<div class="tool-tab-info" id="urolesmsg"></div>
				</div>
				<div id="roles-table-holder"></div>
				<div id="user-role-add" class="pcsection pcbuttons">
					<select id="rolecollectionip"></select>
					<select id="roledatasetip"></select>
					<select id="rolenameip">
						<?php 
						foreach($dacura_server->userman->getAvailableRoles("", "", "") as $rname){
							echo "<option value='$rname'>$rname</option>\n";	
						}
						?>
					</select>
					<a class="button2" href="javascript:dacura.users.createRole()">Add New Role</a>
				</div>			
			</div>
		</div>
		<div id="profile-holder">		
			<div id="user-profile" class="user-pane dch">
				<div class="tab-top-message-holder">
					<div class="tool-tab-info" id="uprofilemsg"></div>
				</div>
				<div id='userconfig'></div>
				<div id="user-profile-buttons" class="pcsection pcbuttons">
					<a class="button2" href="javascript:dacura.users.updateProfile()">Update Profile</a>
				</div>
			</div>
		</div>
		<div id="password-holder">		
			<div id="user-password" class="user-pane dch">
				<div class="tab-top-message-holder">
					<div class="tool-tab-info" id="upasswordmsg"></div>
				</div>
				<table class="dc-wizard" id="password_table">
					<thead><tr><th class='left'></th><th class='right'></th></tr></thead>				
					<tbody>
						<tr>
							<th>New Password</th><td id='password1'><input type="password" id="password1ip" value=""></td>
						</tr>
						<tr>		
							<th>Confirm New Password</th><td id='password2'><input type="password" id="password2ip" value=""></td>
						</tr>
					</tbody>
				</table>
				<div id="user-profile-buttons" class="pcsection pcbuttons">
					<a class="button2" href="javascript:dacura.users.updatePassword()">Update Password</a>
				</div>
			</div>
		</div>	
		<div id="update-holder">
			<div id="user-update" class="user-pane dch pcdatatables">
				<div class="tab-top-message-holder">
					<div class="tool-tab-info" id="udetailsmsg"></div>
				</div>
				<table class="dc-wizard" id="user_table">
					<thead><tr><th class='left'></th><th class='right'></th></tr></thead>				
					<tbody>
						<tr>
							<th>Name</th><td id='username'><input id="usernameip" value=""></td>
						</tr>
						<tr>		
							<th>Email</th><td id='useremail'><input id="useremailip" value=""></td>
						</tr>
						<tr>
							<th>Status</th><td id='userstatus'><input id="userstatusip" value=""></td>
						</tr>
					</tbody>
				</table>
				<div id="updaterolesbuttons" class="pcsection pcbuttons">
					<a class="button2" href="javascript:dacura.users.deleteUser()">Delete User</a>
					<a class="button2" href="javascript:dacura.users.updateUserDetails()">Update User Details</a>
				</div>
			</div>
		</div>
		<div id="history-holder">
			<div id="user-history" class="user-pane dch">
				<div class="tab-top-message-holder">
					<div class="tool-tab-info" id="uhistorymsg"></div>
				</div>
				<div id="history-table-holder"></div>
			</div>
		</div>		
	</div>
</div>

<script>

dacura.users.writeBusyMessage  = function(msg) {
	dacura.system.showBusyOverlay('#user-pane-holder', msg);
}

dacura.users.clearBusyMessage = function(){
	dacura.system.removeBusyOverlay();
};

dacura.users.writeSuccessMessage = function(msg){
	$('#users-result-message').html("<div id='mysux' class='dacura-user-message-box dacura-success'>"+ msg + "</div>").show();
	var ta = setTimeout(function(){$('#mysux').fadeOut(400)}, 1000);
	
};

dacura.users.writeErrorMessage = function(msg){
	$('#users-result-message').html("<div class='dacura-user-message-box dacura-error'>"+ msg + "</div>").show();
};
					
dacura.users.roleoptions = <?=json_encode($params['role_options'])?>;
dacura.users.profileed = false;

dacura.users.showuser = function(id){
	var ajs = dacura.users.api.view(id);
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("Retrieving User Details");
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0 ){
				dacura.users.currentuser = id;
				try{
					var user = JSON.parse(data);
					dacura.users.writeSuccessMessage("Retrieved details for user "+ id);
					dacura.users.drawUserView(user);
				}
				catch(e){
					dacura.users.writeErrorMessage("Error: failed to parse JSON returned by api call: "+ e.message);
				}
			}
			else {
				dacura.users.writeErrorMessage("Error: no data returned from api call");
			}   
		})
		.fail(function (jqXHR, textStatus){
			dacura.users.writeErrorMessage("Error: " + jqXHR.responseText );
		}
	);	
}

dacura.users.drawUserView = function(data){
	$('.pctitle').html(data.email + " (user "+data.id + " - " + data.status +")").show();
	$('.pcbreadcrumbs').show();
	//first get the details
	$('#usernameip').val(data.name);
	$('#useremailip').val(data.email);
	$('#userstatusip').val(data.status);
	var profile = "{}";
	//next the profile
	if(typeof data.profile == "object" && data.profile != null){
		profile = JSON.stringify(data.profile);
	}
	$('#userconfig').html("<textarea id='userconfig_ta'>" + profile + "</textarea>");
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#userconfig_ta"), "740", "220");
    j.doTruncation(true);
	j.showFunctionButtons();
	dacura.users.jsoneditor = j;
	//then the roles and history
	if(typeof data.roles == "object" && data.roles != null) {
		dacura.users.drawRoleTable(data.roles);
	}
	if(typeof data.history == "object" && data.history != null) {
		dacura.users.drawHistoryTable(data.history);
	}
}

dacura.users.drawRoleTable = function(roles){
	$('#roles-table-holder').html('<table class="display" id="roles_table"><thead><tr><th>Collection</th><th>Dataset</th><th>Role</th><th></th></tr></thead><tbody></tbody></table>');
	for (var i in roles) {
		var obj = roles[i];
		$('#roles_table tbody').append("<tr id='role_" + obj.id + "'	><td>" + obj.collection_id + "</td><td>" + obj.dataset_id + 
			"</td><td>" + obj.role + "</td><td>" + 
			"<a href='javascript:dacura.users.deleteRole(" + obj.id + ")'>delete</a>" + "</td></tr>");
	}
	$('#roles_table').dataTable(<?=$dacura_server->getServiceSetting('roles_datatable_init_string', "{}");?>).show();
};

dacura.users.drawHistoryTable = function(ses){
	$('#history-table-holder').html('<table class="display" id="history_table"><thead><tr><th>Start</th><th>End</th><th>Duration</th><th>Service</th></tr></thead><tbody></tbody></table>');
	for (var i=0; i<ses.length; i++) {
		var obj = ses[i];
		$('#history_table tbody').append("<tr id='session_" + obj.service + "_" + obj.start + "'>" + 
				"<td>" + obj.start + "</td><td>" + obj.end + "</td><td>" + obj.duration + "</td><td>" + obj.service + "</td></tr>");
	}
	$('#history_table').dataTable(<?=$dacura_server->getServiceSetting('history_datatable_init_string', "{}");?>).show();
};

dacura.users.updateUserDetails = function(){
	var ds = {};
	$('#udetailsmsg').empty();
	ds.name = $('#usernameip').val();
	ds.email= $('#useremailip').val();
	ds.status = $('#userstatusip').val();	
	var ajs = dacura.users.api.update(dacura.users.currentuser);
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("Updating User Details");
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		try{
			var user = JSON.parse(data);
			dacura.users.drawUserView(user);
			dacura.users.writeSuccessMessage("Updated user " + dacura.users.currentuser);
		}
		catch(e){
			dacura.system.writeErrorMessage("", '#udetailsmsg', "", jqXHR.responseText + " " + e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		dacura.system.writeErrorMessage("", '#udetailsmsg', "", textStatus + ":" + jqXHR.responseText );
	});	
}

dacura.users.updateProfile = function(){
	var ds = {};
	ds.profile = JSON.stringify(dacura.users.jsoneditor.getJSON());
	var ajs = dacura.users.api.update(dacura.users.currentuser);
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("Updating User Profile");
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.users.writeSuccessMessage("Profile Updated Successfully");
	})
	.fail(function (jqXHR, textStatus){
		dacura.system.writeErrorMessage("", '#uprofilemsg', "", "Error: " + jqXHR.responseText );
	});	
}

dacura.users.updatePassword = function(){
	var pw1 = $('#password1ip').val();
	var pw2 = $('#password2ip').val();
	if(pw1 == "" || pw1.length < 3){
		return dacura.system.writeErrorMessage("", '#upasswordmsg', "", "Error: the password must be at least 3 characters long" );
	}
	if(pw1 != pw2){
		return dacura.system.writeErrorMessage("", '#upasswordmsg', "", "Error: the passwords do not match!");
	}
	var ds = {};
	ds.password = pw1;
	var ajs = dacura.users.api.updatepassword(dacura.users.currentuser);
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("Updating User Password");
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.users.writeSuccessMessage("Password Updated Successfully");
	})
	.fail(function (jqXHR, textStatus){
		dacura.system.writeErrorMessage("", '#upasswordmsg', "", "Error: " + jqXHR.responseText );
	});	
};	

dacura.users.deleteUser = function(){
	dacura.users.clearscreens();
	var ajs = dacura.users.api.del(dacura.users.currentuser);
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("Deleting user " + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.users.writeBusyMessage("User " + dacura.users.currentuser + " deleted");
		setTimeout(function(){window.location.href = dacura.system.pageURL()}, 1000);
	})
	.fail(function (jqXHR, textStatus){
		dacura.users.writeErrorMessage("Error: " + jqXHR.responseText );
	});	
}

dacura.users.deleteRole = function(id){
	var ajs = dacura.users.api.delrole(dacura.users.currentuser, id);
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("Deleting role of user " + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		$('#role_' + id).remove();
		self.showuser(dacura.users.currentuser);
	})
	.fail(function (jqXHR, textStatus){
		dacura.system.writeErrorMessage("", '#userhelp', "", "Error: " + jqXHR.responseText );
	});	
};

dacura.users.createRole = function(){
	var cid = $('#rolecollectionip option:selected').val();
	var did = $('#roledatasetip option:selected').val();
	var rname = $('#rolenameip option:selected').val();
	var lvel = "0";
	var ajs = dacura.users.api.createrole(dacura.users.currentuser);
	var payload = {
		"collection" : cid,
		"dataset" : did, 
		"role" : rname,
		"level" : lvel
	};
	ajs.data.payload = JSON.stringify(payload);
	var self=this;
	ajs.beforeSend = function(){
		dacura.users.writeBusyMessage("creating role for user " + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.users.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		self.showuser(dacura.users.currentuser);
	})
	.fail(function (jqXHR, textStatus){
		dacura.system.writeErrorMessage("", '#userhelp', "", "Error: " + jqXHR.responseText );
	});	
};

dacura.users.updateDatasetRoleOptions = function(isupdate){
	var cid = $('#rolecollectionip option:selected').val();
	var datasets = dacura.users.roleoptions[cid].datasets;
	var num = 0;
	$('#roledatasetip').html("");
	$.each(datasets, function(i, obj) {
		num++;
		var selected = "";
		if(num == 1){
			selected = "selected ";
		}
		$('#roledatasetip').append("<option " + selected  + "value='"+ i + "'>" + obj + "</option>");
	});
}

$(function() {
	$("#user-pane-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
	$("#user-pane-list").show();
	if(dacura.users.roleoptions){
		$('#rolecollectionip').change(function(){
			dacura.users.updateDatasetRoleOptions(true);
		});
		var num = 0;
		$.each(dacura.users.roleoptions, function(i, obj) {
			num++;
			var selected = "";
			if(num == 1){
				selected = "selected ";
			}
			$('#rolecollectionip').append("<option " + selected  + "value='"+ i + "'>" + obj.title + "</option>");
		});
		dacura.users.updateDatasetRoleOptions(false);
	}
	else {
		$('#user-role-add').empty();
	}
	dacura.users.showuser('<?=$params['userid']?>');
});
</script>

