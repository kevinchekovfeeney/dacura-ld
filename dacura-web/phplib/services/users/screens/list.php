<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id="user-pane-holder">
	 <ul id="user-pane-list" class="dch">
		<li><a href="#users-list">List Users</a></li>
	 	<li><a href="#users-add">Add User</a></li>
		<li><a href="#users-invite">Invite Users</a></li>
	</ul>
	<div id="users-holder">
		<div id="users-list" class="user-pane dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="ulistmsg"></div>
			</div>
			<table id="users_table" class="display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Roles</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
	<div id="add-holder">
		<div id="users-add" class="user-pane dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="uaddmsg"></div>
			</div>
			<table class="dc-wizard" id="user_add">
				<thead><tr><th class='left'></th><th class='right'></th></tr></thead>
				<tbody>
					<tr>		
						<th>Email</th><td id='useremail'><input id="useremailip" value=""></td>
					</tr>
					<tr>
						<th>Password</th><td id='userpassword'><input type="password" id="userpasswordip" value=""></td>
					</tr>
				</tbody>
			</table>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.users.adduser()">Add User</a>
			</div>
		</div>
	</div>
	<div id="invite-holder">
		<div id="users-invite" class="user-pane dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="uinvitemsg"></div>
			</div>			
			<textarea class="dch" id="invitees"></textarea>
			<div class="pcsection pcbuttons dch">
				<a class="button2" href="<?=$service->my_url()?>/invite">Invite Users</a>
			</div>
		</div>
	</div>
</div>

<script>
dacura.users.writeBusyMessage  = function(msg) {
	dacura.toolbox.writeBusyOverlay('#user-pane-holder', msg);
}

dacura.users.clearBusyMessage = function(){
	dacura.toolbox.removeBusyOverlay(false, 0);
};

dacura.users.writeErrorMessage = function(jq, msg, extra){
	dacura.toolbox.writeErrorMessage(jq, msg, extra);
}

dacura.users.listusers = function(){
		var ajs = dacura.users.api.listing();
		var self=this;
		ajs.beforeSend = function(){
			dacura.users.writeBusyMessage("Retrieving users list");
		};
		ajs.complete = function(){
			dacura.users.clearBusyMessage();
		};
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				if(data.length > 0 ){
					try {
						dacura.users.drawListTable(JSON.parse(data));
						$('#users_table').show();
					}
					catch(e){
						dacura.users.writeErrorMessage("#ulistmsg", "Error parsing user list response from server: " + e.message, jqXHR.responseText);
						$('#users_table').hide();
						//dacura.users.drawListTable();
					}
				}
				else {
					dacura.users.drawListTable();
					$('#users_table').show();
				}    	
			})
			.fail(function (jqXHR, textStatus){
				dacura.users.writeErrorMessage("#ulistmsg", "Error retrieving user list.", jqXHR.responseText );
			}
		);	
	};

	dacura.users.adduser = function(){
		var ajs = dacura.users.api.create();
		var self=this;
		var ds = {};
		ds.email = $('#useremailip').val();
		ds.password = $('#userpasswordip').val();
		if(ds.email.length < 3 || ds.password.length < 3){
			return dacura.users.writeErrorMessage("#uaddmsg", "Error: email and password must be at least 3 characters long");
		}
		ajs.data = ds;
		ajs.beforeSend = function(){
			dacura.users.writeBusyMessage("Creating New User");
		};
		ajs.complete = function(){
		};
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				if(data.length > 0 ){
					try {
						var u = JSON.parse(data);
						dacura.toolbox.updateBusyMessage("User created ok. Loading new user profile");
						window.location.href = dacura.system.pageURL() + "/" + u.id;
					}
					catch(e){
						dacura.users.clearBusyMessage();
						dacura.users.writeErrorMessage("#uaddmsg", "Error parsing server message: " + e.message, jqXHR.responseText );
					}
				}
				else {
					dacura.users.clearBusyMessage();
					dacura.users.writeErrorMessage("#uaddmsg", "Error: server response was empty");
				}    	
			})
			.fail(function (jqXHR, textStatus){
				dacura.users.clearBusyMessage();
				dacura.users.writeErrorMessage("#uaddmsg", "Error adding user to system. " + jqXHR.responseText );
			}
		);	
	};

	dacura.users.drawListTable = function(data){		
		if(typeof data == "undefined" || data.length == 0){
			$('#users_table').dataTable(); 
			dacura.toolbox.writeErrorMessage('.dataTables_empty', "No Users Found");		
		}
		else {
			$('#users_table tbody').html("");
			for (var i in data) {
				var obj = data[i];
				<?php if(!$dacura_server->getServiceSetting('show_deleted_users', false)){
					echo 'if(obj.status == "deleted") {continue;}';
				}?>
				var profile = "";
				if(typeof obj.profile == "object"){
					profile = JSON.stringify(obj.profile);
				}
				var roles = obj.roles.length;
				$('#users_table tbody').append("<tr id='user" + obj.id + "'><td>" + obj.id + "</td><td>" + obj.name 
						+ "</td><td>" + obj.email + "</td><td>" + obj.status + "</td><td>" + roles + 
						 "</td></tr>");
				$('#user'+obj.id).hover(function(){
					$(this).addClass('userhover');
				}, function() {
				    $(this).removeClass('userhover');
				});
				$('#user'+obj.id).click( function (event){
					window.location.href = dacura.system.pageURL() + "/" + this.id.substr(4);
			    }); 
			}
			$('#users_table').dataTable(<?=$dacura_server->getServiceSetting('users_datatable_init_string', "{}");?>).show();
		}
	}
		

	$(function() {
		$("#user-pane-list").show();
		$("#user-pane-holder").tabs( {
	        "activate": function(event, ui) {
	            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
	        }
	    });
		dacura.users.writeErrorMessage("#uinvitemsg", "Not implemented yet...");
		
		
		dacura.users.listusers();
	});
	</script>
</script>