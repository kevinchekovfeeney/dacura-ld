<style>
.dch { display: none;}
</style>
<div id="pagecontent-nopadding">
	<div class="pctitle dch"></div>
	<div class="pcbreadcrumbs dch">
		<div class="pccon">
			<?php $service->renderScreen("available_context", array("type" => "admin"), "core");?>
		</div>
		<?php echo $service->getBreadCrumbsHTML($params['userid'] );?>
	</div>
	<br>
	<div id="user-pane-holder">
	 <ul>
		<li><a href="#user-update">Details</a></li>
	 	<li><a href="#user-roles">Roles</a></li>
		<li><a href="#user-profile">Profile</a></li>
		<li><a href="#user-password">Password</a></li>
		<li><a href="#user-history">History</a></li>
	</ul>
		<div id="user-roles" class="user-pane dch pcdatatables">
			<div id="urolesmsg"></div><div class="pcbusy resultmsg"></div>
			<table id="roles_table">
				<thead>
	            <tr>
	                <th>Collection</th>
	                <th>Dataset</th>
	                <th>Role</th>
	                <th>Delete</th>
	            </tr>
        		</thead>
        		<tbody>
        		</tbody>
  			</table>
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
		
		<div id="user-profile" class="user-pane dch pcdatatables">
			<div id="uprofilemsg"></div><div class="pcbusy resultmsg"></div>
			<div id='userconfig'></div>
			<div id="user-profile-buttons" class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.users.updateProfile()">Update Profile</a>
			</div>
		</div>
		
		<div id="user-password" class="user-pane dch pcdatatables">
			<div id="upasswordmsg"></div><div class="pcbusy resultmsg"></div>
			<table class="dc-wizard" id="password_table">
				<tbody>
					<tr>
						<th>New Password</th><td id='password1'><input type="password" id="password1ip" value=""></td>
					</tr>
					<tr>		
						<th>Confirm New Password</th><td id='password2'><input type="password" id="password2ip" value=""></td>
					</tr
				</tbody>
			</table>
			<div id="user-profile-buttons" class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.users.updatePassword()">Update Password</a>
			</div>
		</div>
		
		<div id="user-update" class="user-pane dch pcdatatables">
			<div id="udetailsmsg"></div><div class="pcbusy resultmsg"></div>
			<table class="dc-wizard" id="user_table">
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
		<div id="user-history" class="user-pane dch pcdatatables">
			<div id="uhistorymsg"></div><div class="pcbusy resultmsg"></div>
			<table class="dc-wizard" id="session_table">
				<thead>
					<tr>
						<th>Session Start</th>
						<th>Session End</th>
						<th>Duration</th>
						<th>Service</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
		
	</div>
</div>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.dataTables.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />


<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<script>

dacura.users.roleoptions = <?=json_encode($params['role_options'])?>;
dacura.users.profileed = false;
dacura.users.clearscreens = function(){
	$('#userview').hide();
	$('#roleview').hide();
	$('.pctitle').html("").hide();
}

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
		dacura.toolbox.writeBusyMessage('.pcbusy', "Updating User Details");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		try{
			var user = JSON.parse(data);
			dacura.users.drawUserView(user);
			dacura.toolbox.writeSuccessMessage('#udetailsmsg', "Updated user " + dacura.users.currentuser);
		}
		catch(e){
			dacura.toolbox.writeErrorMessage('#udetailsmsg', jqXHR.responseText + " " + e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#udetailsmsg', textStatus + ":" + jqXHR.responseText );
	});	
}

dacura.users.updateProfile = function(){
	var ds = {};
	ds.profile = JSON.stringify(dacura.users.jsoneditor.getJSON());
	var ajs = dacura.users.api.update(dacura.users.currentuser);
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Updating User Details");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.toolbox.writeSuccessMessage('#uprofilemsg', "Profile Updated Successfully");
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#uprofilemsg', "Error: " + jqXHR.responseText );
	});	
}

dacura.users.updatePassword = function(){
	var pw1 = $('#password1ip').val();
	var pw2 = $('#password2ip').val();
	if(pw1 == "" || pw1.length < 3){
		return dacura.toolbox.writeErrorMessage('#upasswordmsg', "Error: the password must be at least 3 characters long" );
	}
	if(pw1 != pw2){
		return dacura.toolbox.writeErrorMessage('#upasswordmsg', "Error: the passwords do not match!");
	}
	var ds = {};
	ds.password = pw1;
	var ajs = dacura.users.api.updatepassword(dacura.users.currentuser);
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Updating User Password");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.toolbox.writeSuccessMessage('#upasswordmsg', "Password Updated Successfully");
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#upasswordmsg', "Error: " + jqXHR.responseText );
	});	
};	

dacura.users.createUser = function(){
	dacura.users.clearscreens();
	var ds = {};
	ds.name = $('#usernameip').val();
	ds.email= $('#useremailip').val();
	ds.status = $('#userstatusip').val();
	ds.password = $('#userpasswordip').val();
	ds.profile = JSON.stringify(dacura.users.jsoneditor.getJSON());
	var ajs = dacura.users.api.create();
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Updating User Details");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		u = JSON.parse(data);
		window.location.href = dacura.system.pageURL() + "/" + u.id;
		//self.showuser(u.id);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('.pcbusy', "Error: " + jqXHR.responseText );
	});	
}

dacura.users.deleteUser = function(){
	dacura.users.clearscreens();
	var ajs = dacura.users.api.del(dacura.users.currentuser);
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Deleting user " + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.toolbox.writeBusyMessage('.pcbusy', "User " + dacura.users.currentuser + " deleted");
		setTimeout(function(){window.location.href = dacura.system.pageURL()}, 1000);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('.pcbusy', "Error: " + jqXHR.responseText );
	});	
}

dacura.users.newUser = function(){
	dacura.users.clearscreens();
	$('.pctitle').html("Create New User").show();
	$('#userconfig').html("<textarea id='userconfig_ta'>{}</textarea>");
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#userconfig_ta"), "790", "300");
    j.doTruncation(true);
	j.showFunctionButtons();
	dacura.users.jsoneditor = j;
	dacura.users.setViewtoCreate();
	$('#userview').show();
}

dacura.users.setViewtoCreate = function(){
	$('#updaterolesbuttons').hide();
	$('#newrolesbuttons').hide();
	$('#pcroles').hide();
	$('#createuserbuttons').show();
};
dacura.users.setViewtoUpdate = function(){
	$('#updaterolesbuttons').show();
	$('#newrolesbuttons').show();
	$('#pcroles').show();
	$('#createuserbuttons').hide();
	
};

dacura.users.showuser = function(id){
	dacura.users.clearscreens();
	var ajs = dacura.users.api.view(id);
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving User Details");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0 ){
				dacura.users.currentuser = id;
				try{
					var user = JSON.parse(data);
					dacura.users.drawUserView(user);
				}
				catch(e){
					dacura.toolbox.writeErrorMessage('.pcbusy', "Error: failed to parse JSON returned by api call: "+ e.message);
				}
			}
			else {
				dacura.toolbox.writeErrorMessage('.pcbusy', "Error: no data returned from api call");
			}   
			dacura.users.setViewtoUpdate();  	
			$('#userview').show();
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('.pcbusy', "Error: " + jqXHR.responseText );
		}
	);	
}

dacura.users.drawUserView = function(data){
	$('.pctitle').html(data.email + " (user "+data.id + " - " + data.status +")").show();
	$('#usernameip').val(data.name);
	$('#useremailip').val(data.email);
	$('#userstatusip').val(data.status);
	$('.pcbreadcrumbs').show();
	var profile = "{}";
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
	$.each(data.roles, function(i, obj) {
		$('#roles_table tbody').append("<tr id='role_" + obj.id + "'	><td>" + obj.collection_id + "</td><td>" + obj.dataset_id + 
				"</td><td>" + obj.role + "</td><td>" + 
				"<a href='javascript:dacura.users.deleteRole(" + obj.id + ")'>delete</a>" + "</td></tr>");
	});	
	$('#roles_table').dataTable();//.row.add(["a", "2", "3", "4"]);;

	$.each(data.history, function(i, obj) {
		$('#session_table tbody').append("<tr id='session_" + obj.service + "_" + obj.start + "'>" + 
				"<td>" + obj.start + "</td><td>" + obj.end + 
				"</td><td>" + obj.duration + "</td><td>" + obj.service + "</td><td>um</td></tr>");
	});	
	$('#session_table').dataTable();//.row.add(["a", "2", "3", "4"]);;
	//dacura.users.showRoles(data.roles);
}

dacura.users.deleteRole = function(id){
	var ajs = dacura.users.api.delrole(dacura.users.currentuser, id);
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Deleting role of user " + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		$('#role_' + id).remove();
		//dt.row.add(["1", "b", "c", "d"] ).draw();
		//dacura.users.currentroles.row.add( ["1", "b", "c", "d"] ).draw();
	//self.showuser(dacura.users.currentuser);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
	});	
}

dacura.users.createRole = function(){
	var cid = $('#rolecollectionip option:selected').val();
	var did = $('#roledatasetip option:selected').val();
	var rname = $('#rolenameip option:selected').val();
	var lvel = "0";
	alert(cid + " " + did + " " + rname + " " + lvel);
	
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
		dacura.toolbox.writeBusyMessage('.pcbusy', "creating role for user " + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		self.showuser(dacura.users.currentuser);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
	});	
}

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
	$("#user-pane-holder").tabs();
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

