<style>
.dch { display: none;}
</style>
<div id="pagecontent">
	<div class="pctitle dch"></div>
	<div class="pcbusy"></div>
	<div class="dch" id="userslisting">
		<div class="pcsection pcdatatables">
			<table id="users_table">
				<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Roles</th>
					<th>Profile</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="usershelp"></div>
		<div class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.users.newUser()">Create New User</a>
		</div>
	</div>
	<div class="dch" id="userview">
		<table id="user_table">
			<thead>
			</thead>
			<tbody>
				<tr>
					<th>Name</th><td id='username'><input type='text' id='usernameip' value=""></td>
				</tr>
				<tr>
					<th>Email</th><td id='useremail'><input type='text' id='useremailip' value=""></td>
				</tr>
				<tr>
					<th>Status</th><td id='userstatus'><input type='text' id='userstatusip' value=""></td>
				</tr>
			</tbody>
		</table>
		<div id="pcprofile" class="pcsection pcdatatables">
			<div class="pcsectionhead">Profile</div>	
			<div id='userconfig'></div>
		</div>
		<div id="updaterolesbuttons" class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.users.deleteUser()">Delete User</a>
			<a class="button2" href="javascript:dacura.users.updateUser()">Update User</a>
		</div>
		<div id="createuserbuttons" class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.users.createUser()">Create User</a>
		</div>
		
		<div id="pcroles" class="pcsection pcdatatables">
			<div class="pcsectionhead">Roles</div>	
			<table id="roles_table">
				<thead>
				<tr>
					<th>ID</th>
					<th>Role</th>
					<th>Level</th>
					<th>Collection</th>
					<th>Dataset</th>
					<th>Delete</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="usershelp"></div>
		<div id="newrolesbuttons" class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.users.showNewRole()">Add New Role</a>
		</div>			
	</div>
	<div class="dch" id="roleview">

		<table id="role_table">
			<thead>
				<tr> 
					<th class='ccol'>Collection</th><th class='cds'>Dataset</th><th class="cr">Role</th><th class="cl">Level</th>
				</tr>
			</thead>
			<tbody>	
			<tr>
				<td id='rolecollection' class='ccol'><select id='rolecollectionip'></select></td>
				<td id='roledataset' class="cds"><select id='roledatasetip'></select></td>
				<td id='rolename' class="cr"><select id='rolenameip' value="">
				<option value="admin">admin</option>
				<option value="architect">architect</option>
				<option value="harvester">harvester</option>
				<option value="expert">expert</option>
				<option value="user">user</option>
				</select></td>
				<td id='rolelevel' class="cl"><input type='text' id='rolelevelip' value=""></td>
			</tr>
			</tbody>
		</table>
		<div class="usershelp"></div>
		<div class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.users.createRole()">Create New Role</a>
		</div>
	</div>
</div>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.dataTables.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />


<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<script>

dacura.users.clearscreens = function(){
	$('#userslisting').hide();
	$('#userview').hide();
	$('#roleview').hide();
	$('.pctitle').html("").hide();
}

dacura.users.updateUser = function(){
	dacura.users.clearscreens();
	var ds = {};
	ds.name = $('#usernameip').val();
	ds.email= $('#useremailip').val();
	ds.status = $('#userstatusip').val();
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
		self.showuser(dacura.users.currentuser);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
	});	

}

dacura.users.createUser = function(){
	dacura.users.clearscreens();
	var ds = {};
	ds.name = $('#usernameip').val();
	ds.email= $('#useremailip').val();
	ds.status = $('#userstatusip').val();
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
		self.showuser(u.id);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
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
		alert(data);
		self.listusers();
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
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
				dacura.users.drawUserView(JSON.parse(data));
			}
			else {
				dacura.toolbox.writeErrorMessage('#userhelp', "Error: no data returned from api call");
			}   
			dacura.users.setViewtoUpdate();  	
			$('#userview').show();
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
		}
	);	
}

dacura.users.listusers = function(){
	dacura.users.clearscreens();
	dacura.users.currentuser = 0;
	var ajs = dacura.users.api.listing();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving Users List");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0 ){
				dacura.users.drawListTable(JSON.parse(data));
			}
			else {
				dacura.users.drawListTable();
			}    	
			$('#userslisting').show();
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.users.drawUserView = function(data){
	//alert(JSON.stringify(data));
	$('.pctitle').html("User "+data.id + " [" + data.status + "]").show();
	$('#usernameip').val(data.name);
	$('#useremailip').val(data.email);
	$('#userstatusip').val(data.status);
	var profile = "{}";
	if(typeof data.profile == "object" && data.profile != null){
		profile = JSON.stringify(data.profile);
	}
	$('#userconfig').html("<textarea id='userconfig_ta'>" + profile + "</textarea>");
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#userconfig_ta"), "790", "300");
    j.doTruncation(true);
	j.showFunctionButtons();
	dacura.users.jsoneditor = j;
	$('#roles_table tbody').html("");
	$.each(data.roles, function(i, obj) {
		$('#roles_table tbody').append("<tr><td>" + obj.id + "</td><td>" + obj.role + "</td><td>" + 
				  obj.level + "</td><td>" + obj.collection_id + "</td><td>" + 
				//			  obj.dataset_id + "</td><td><a href='javascript:dacura.users.showNewRole(" + obj.id + ")'>delete</a></td></tr>");
			 obj.dataset_id + "</td><td><a href='javascript:dacura.users.deleteRole(" + obj.id + ")'>delete</a></td></tr>");
	});
	$('#roles_table').dataTable();
}

dacura.users.deleteRole = function(id){
	dacura.users.clearscreens();
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
		self.showuser(dacura.users.currentuser);
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
	});	
}

dacura.users.createRole = function(){
	dacura.users.clearscreens();
	var cid = $('#rolecollectionip option:selected').val();
	var did = $('#roledatasetip option:selected').val();
	var rname = $('#rolenameip option:selected').val();
	var lvel = $('#rolelevelip').val();
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

dacura.users.showNewRole = function(){
	dacura.users.clearscreens();
	$('.pctitle').html("Create New Role for user ID " + dacura.users.currentuser).show();
	var ajs = dacura.users.api.getRoleOptions(dacura.users.currentuser, "0");
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Fetching Role options" + dacura.users.currentuser);
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		var colls = JSON.parse(data);
		$('#rolecollectionip').html("");
		$.each(colls, function(i, obj) {
			$('#rolecollectionip').append("<option value='"+ i + "'>" + obj.name + "</option>");
		});
		$('#rolecollectionip').change(function(){
			self.updateDatasetRoleOptions();
		});
		self.updateDatasetRoleOptions();
		$('#roleview').show();
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
	});		
}

dacura.users.updateDatasetRoleOptions = function(){
	var cid = $('#rolecollectionip option:selected').val()
	if(cid == "0"){
		$('#roledatasetip').append("<option value='0'>All Datasets</option>");
	}
	else {
		var ajs = dacura.users.api.getRoleOptions(dacura.users.currentuser, cid);
		var self=this;
		ajs.beforeSend = function(){
			dacura.toolbox.writeBusyMessage('.pcbusy', "Fetching Role options" + dacura.users.currentuser);
		};
		ajs.complete = function(){
			dacura.toolbox.clearBusyMessage('.pcbusy');	
		};
		$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			var dss = JSON.parse(data);
			$('#roledatasetip').html("");
			$.each(dss, function(i, obj) {
				$('#roledatasetip').append("<option value='"+ i + "'>" + obj.name + "</option>");
			});
			//alert($('#rolecollectionip option:selected').val());
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#userhelp', "Error: " + jqXHR.responseText );
		});		
		//run off and ask the server....
	}	
}

dacura.users.drawListTable = function(data){
	$('.pctitle').html("List of users").show();
	if(typeof data == "undefined"){
		$('#users_table').hide(); 
		dacura.toolbox.writeErrorMessage('.pcbusy', "No Users Found");		
	}
	else {
		$('#users_table tbody').html("");
		$.each(data, function(i, obj) {
			var profile = "";
			if(typeof obj.profile == "object"){
				profile = JSON.stringify(obj.profile);
			}
			var roles = obj.roles.length;
			if(obj.status != "deleted"){
				url='javascript:alert("hello world")';
				$('#users_table tbody').append("<tr id='user" + i + "'><td><a href='" + url + "'>" + i + "</a></td><td>" + obj.name 
						+ "</td><td>" + obj.email + "</td><td>" + obj.status + "</td><td>" + roles + 
						 "</td><td>" + profile + "</td></tr>");
				$('#user'+i).click( function (event){
					dacura.users.showuser(this.id.substr(4));
			        //alert(this.id);
			    }); 
			}
		});
		$('#users_table').dataTable();
	}
}

$(function() {
	<?php if(isset($params['userid'])){
		echo "dacura.users.showuser('".$params['userid']."');";
	}
	else {
		echo "dacura.users.listusers();";
	}?>
});
</script>

