/**
 * @file Javascript client code for user management service
 * @author Chekov
 * @license GPL V2
 */

 /** 
 * @namespace users
 * @memberof dacura
 * @summary dacura.users
 * @description Dacura javascript users service module. provides client functions for accessing the dacura user management api
 */
dacura.users = {}
dacura.users.apiurl = dacura.system.apiURL();

/**
 * @function getUsers
 * @memberof dacura.users
 * @summary retrieve list of users in context
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.getUsers = function(onwards, pconf){
	var ajs = dacura.users.api.listing();
	var msgs = {"busy": "Retrieving list of users from server", "fail": "Failed to retrive list of users from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};	

/**
 * @function inviteUsers
 * @memberof dacura.users
 * @summary retrieve list of users in context
 * @param [Object] data - the payload with information about the users to invite
 * @param {string} data.role - the role to be given to the users
 * @param {string} data.emails - a commma and/or whitespace seperated list of email addresses to be invited
 * @param {string} data.message - the invitation message to be sent to the users. 
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.inviteUsers = function(data, onwards, pconf){
	var ajs = dacura.users.api.invite(data);
	var msgs = {"busy": "Submitting list of users to invite to server", "fail": "Failed to create invitations"};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
}

/**
 * @function addUser
 * @memberof dacura.users
 * @summary add a user to the system
 * @param {Object} data - the payload with user meta-data
 * @param {string} data.email - the user's email address
 * @param {string} data.password - the user's password 
 * @param {string} [data.name] - the user's handle
 * @param {string} [data.status] - the status of the new user 
 * @param {Object} [data.profile] - the user's profile
 * @param {Object} [data.roles] - array of roles to be given to the user
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.addUser = function(data, onwards, pconf){
	var ajs = dacura.users.api.create(data);
	var msgs = {"busy": "Creating new user", "fail": "Failed to create new user"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function fetchUser
 * @memberof dacura.users
 * @summary retrieve user object
 * @param {string} id - the user id
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.fetchUser = function(id, onwards, pconf){
	var ajs = dacura.users.api.view(id);
	var msgs = {"busy": "Retrieving user profile from server", "fail": "Failed to retrive user profile from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function fetchUserHistory
 * @memberof dacura.users
 * @summary retrieve user session history object
 * @param {string} id - the user id
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.fetchUserHistory = function(id, onwards, pconf){
	var ajs = dacura.users.api.gethistory(id);
	var msgs = {"busy": "Retrieving user history from server", "fail": "Failed to retrieve history"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
}

/**
 * @function updateUser
 * @memberof dacura.users
 * @summary update user meta-data
 * @param {Object} data - the payload with user's id and metadata to be updated
 * @param {string} data.uid - the user's id
 * @param {string} [data.email] - the user's updated email address 
 * @param {string} [data.name] - the user's handle
 * @param {string} [data.status] - 
 * @param {Object} [data.profile] - the id of the collection that the role applies to
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */

dacura.users.updateUser = function(data, onwards, pconf){
	var ajs = dacura.users.api.update(data);
	var msgs = {"busy": "Updating user " + data.id + " details", "fail": "Failed to update users settings for user " + data.id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function updatePassword
 * @memberof dacura.users
 * @summary update the user's password
 * @param {Object} data - the payload, object has fields: uid (userid) and password 
 * @param {string} data.uid - the user's id
 * @param {string} data.password - the new password
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object with details of where to write messages
 */
dacura.users.updatePassword = function(data, onwards, pconf){
	var ajs = dacura.users.api.updatepassword(data);
	var msgs = {"busy": "Updating password for user " + data.uid, "fail": "Failed to update password for user " + data.uid};
	ajs.handleResult = onwards;
	ajs.handleTextResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
}

/**
 * @function deleteUser
 * @memberof dacura.users
 * @summary delete user from system (set status to deleted)
 * @param {string} id - the user id
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.deleteUser = function(id, onwards, pconf){
	var ajs = dacura.users.api.del(id);
	var msgs = {"busy": "Deleting user " + id, "fail": "Failed to delete user " + id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function createRole
 * @memberof dacura.users
 * @summary create a new role for a user
 * @param {Object} data - the payload with user's id and role details
 * @param {string} data.uid - the user's id
 * @param {string} data.role - the name of the role to give the user
 * @param {string} data.collection - the id of the collection that the role applies to
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.createRole = function(data, onwards, pconf){
	var ajs = dacura.users.api.createrole(data);
	var msgs = {"busy": "Creating role for user " + data.uid, "fail": "Failed to create new role for user " + data.uid};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
}

/**
 * @function deleteRoles
 * @memberof dacura.users
 * @summary delete a set of roles from a user
 * @description calls the api in sequence for each of the role ids in the passed array
 * @param {Object} obj - the payload with user's id and role details
 * @param {string} data.uid - the user's id
 * @param {Object} data.rids - an array of role ids to be deleted 
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.users.deleteRoles = function(obj, onwards, pconf){
	rid = obj.rids.shift();
	var ajs = dacura.users.api.delrole(obj.uid, rid);
	var msgs = {"busy": "Deleting role " + rid, "fail": "Failed to delete role " + rid};
	ajs.handleResult = function(robj, pconf){
		if(obj.rids.length > 0){
			dacura.users.deleteRoles(obj, onwards, pconf);
		}
		else {
			onwards(robj, pconf);
		}
	}	
	dacura.system.invoke(ajs, msgs, pconf);
}

/**
 * @function header
 * @memberof dacura.users
 * @summary draws the tool header for the manage user page
 * 
 * @param {Object} obj user object
 * @param {string} obj.id - current user's id
 * @param {string} obj.email - current user's email address
 * @param {string} obj.status - the user's status
 */
dacura.users.header = function(obj, isprofile){
	var params = {id: obj.id, email: obj.email, status: obj.status};
	var msg = isprofile ? obj.handle + " profile" : "User " + obj.handle;
	dacura.tool.header.showEntityHeader(msg, params);	
}

/**
 * @function validatePasswords
 * @memberof dacura.users
 * @summary validates that the password and the password confirm are valid passwords 
 * 
 * @description returns "" (valid) if the passwords are >= 6 characters and identical
 * 
 * @param {Object} obj - the new password object
 * @param {string} obj.password - new password 
 * @param {string} obj.confirmpassword - confirm password box (to check that passwords are identical)
 * @returns {string} - error string for user, password is valid if string is empty
 */
dacura.users.validatePasswords = function(obj){
	if(obj.password.length < 6) {
		return "The password must be at least six characters long";
	}
	if(obj.password != obj.confirmpassword){
		return "The two passwords do not match";
	}
	return "";
}

/**
 * @function eventTable
 * @memberof dacura.users
 * @summary draws the table of events returned by a fetch history api call 
 * 
 * @param {Object} evs - an array of event time => event object
 * @params{string} evs[i].action - the name of the event action
 */
dacura.users.eventTable = function(evs){
	if(typeof(evs) != "object" || size(evs) == 0) return "<P>no events</p>";
	var html = "<table class='dacura-session-events'><thead><tr><th>Time</th><th>Elapsed</th><th>Action</th><th>Parameters</th></thead><tbody>";
	var lasttime = false;
	var firsttime = false;
	for(etime in evs){
		html += "<tr><td>" + timeConverter(etime) + "</td>";
		if(lasttime === false){
			html += "<td>0</td>";
		}
		else {
			html += "<td>" + durationConverter(etime - lasttime) + "</td>";
		}
		
		lasttime = etime;
		html += "<td>" + evs[etime].action + "</td>";
		delete(evs[etime].action);
		if(size(evs[etime]) > 0){
			json = JSON.stringify(evs[etime], 0, 4);
			html += "<td class='rawjson'>" + json + "</td>";
		}
		else {
			html += "<td></td>";
		}
		html += "</tr>";
	}
	html += "</tbody></table>";
	return html;
}

/**
 * @namespace roles
 * @memberof dacura.users
 * @summary dacura.users.roles
 * @description The dacura javascript helper functions for deal with users' roles, drawing them etc, 
 */
dacura.users.roles = {
	/* has a set of roles already been loaded */
	loaded: false,	
	/**
	 * @function button
	 * @memberof dacura.users.roles
	 * @summary produces the html for a role button
	 * @param {string} name - the name of the role
	 * @param {string} title - the title on the role button 
	 * @param {string} cls - the css class of the button [active|inactive]
	 * @param {string} id - the role id 
	 * @return {string} html - html representation of role
	 */
	button: function(name, title, cls, id, isedit){
		var html = "<div class='dacura-role-button "+ cls + "'>"; 
		html += "<span class='dacura-role " + name + "' title='"+title +"'></span><span class='dacura-role-label'>" + title + "</span>";
		if(isedit){
				if(typeof id == "string"){
				html += "<a href='javascript:dacura.users.roles.remove(\"" + id+"\")' class='dacura-role-remove'>Remove Role</a>";
			}
			else if (cls == 'active'){
				html += "<a href='javascript:dacura.users.roles.add(\"" + name +"\")' class='dacura-role-add'>Add Role</a>";
			}
		}
		html += "</div>";
		return html;
	},

	/**
	 * @function bycollection
	 * @memberof dacura.users.roles
	 * @summary produces the html for a role button
	 * @param {Object} cols - array of roles as table columns [collection id => roles]
	 * @return {string} html - html representation of roles
	 */
	bycollection: function(cols){
		if(typeof cols != "object") return "?";
		if(size(cols) == 0){
			return "";
		}
		var html = "<div class='dacura-collections-summary'>";
		if(typeof cols['all'] != "undefined") {
			html += "<span class='dacura-platform-role' title='platform roles: " + cols['all'].join(", ") + "'>system</span>";
		}
		
		for(var i in cols){
			if(i != 'all') {
				html += "<span class='dacura-collection-role' title='" + i + " collection roles: " + cols[i].join(", ") + "'>" + i + "</span>";
			}
		}
		html += "</div>";
		return html;
	},
	
	/**
	 * @namespace createbox
 	 * @memberof dacura.users.roles
	 * @summary helper functions to draw "create new role" html box
	 */
	createbox: {
		/**
		 * @function init
		 * @memberof dacura.users.roles.createbox
		 * @summary initialises the create role box with the options passed in
		 * @description writes available options to #rolecollectionip and #rolenameip jquery ids and sets up the change events
		 * @param {Object} opts - an array of options for the collection ids and names of the roles available 
		 */
		init: function (opts){
			var num = 0;
			if(typeof(opts) == "object") {
				$('#rolecollectionip').append("<option selected value=''>Choose a collection</option>");
				$.each(opts, function(i, obj) {
					$('#rolecollectionip').append("<option value='"+ i + "'>" + obj.title + "</option>");
				});
				var ropts = "<option value=''>Choose a role</option>";
				$('#rolenameip').html(ropts).selectmenu( "refresh" );						
				$('#rolecollectionip').selectmenu( "refresh" );						
			}
			$('#rolecollectionip').on('selectmenuchange', function() {
				var ropts = opts[this.value].options;
				ropts[""] = "Choose a role";
				$('#rolenameip').html(nvArrayToOptions(ropts)).selectmenu( "refresh" );		
			});
		},
	
		/**
		 * @function vals
		 * @memberof dacura.users.roles.createbox
		 * @return {Object} vals - current values of create box options [vals.collection, vals.role]
		 */
		vals: function(){
			var obj = {};
			obj.collection = $('#rolecollectionip option:selected').val();
			obj.role = $('#rolenameip option:selected').val();
			return obj;
		}
	},
	
	/**
	 * @function implicits
	 * @memberof dacura.users.roles
	 * @summary produces the list of roles that are implicitly contained by the passed role
	 * @param {string} rname - name of the role
	 * @return {Object} implicits - array of roles that are implicitly contained by rname
	 */
	implicits: function (rname){
		var roles = [];
		if(rname == "nobody") return roles;
		roles.push("nobody");
		if(rname == "user") return roles;
		roles.push("user");
		if(rname == "admin") {
			roles.push("harvester", "architect", "expert");
		}
		return roles;
	},
	
	/**
	 * @function onehtml
	 * @memberof dacura.users.roles
	 * @summary get a html span representation of a single role
	 * @param {string} rname - the name of the role in question
	 * @returns {String} - html span snippet of the role
	 */
	onehtml: function(rname){
		return "<span class='dacura-role " + rname + "' title='"+rname +"'></span><span class='dacura-role-label'>"+rname+"</span>";
	},
	
	/**
	 * @function refresh
	 * @memberof dacura.users.roles
	 * @summary refresh the html screen showing the roles of the user with the passed roles
	 * @param {string} key - jquery id of the table to be refreshed 
	 * @param {Object} roles - array of roles to draw into table 
	 * @param {string} screen - jquery id of the screen to use for the table configuration
	 */
	refresh: function(key, roles, screen, isedit){
		if(typeof roles == "undefined" || roles.length == 0){
			dacura.system.showWarningResult("No roles configured in this context", "No roles", dacura.tool.subscreens[screen].resultbox, false, dacura.tool.subscreens[screen].mopts);
			$('#'+key).hide();
		}
		$('#'+key).show();
		if(dacura.system.cid() == "all"){
			var tconfig = dacura.tool.tables[key];
			dacura.users.roles.table(key, roles, tconfig);
		}
		else {
			dacura.users.roles.simpletable(key, roles, {"screen": screen}, isedit);			
		}
	},
	
	/**
	 * @function show
	 * @memberof dacura.users.roles
	 * @summary show the html screen with the passed roles in a table
	 * @param {string} key - jquery id of the table to write to 
	 * @param {Object} roles - array of roles to draw into table 
	 * @param {DacuraTableConfig} tconfig - table configuration object
	 */
	show: function(key, roles, tconfig, isedit){
		if(typeof roles == "undefined" || roles.length == 0){
			dacura.system.showWarningResult("No roles configured in this context", "No roles", dacura.tool.subscreens[tconfig.screen].resultbox, false, dacura.tool.subscreens[tconfig.screen].mopts);
			$('#'+key).hide();
		}
		$('#'+key).show();
		if(dacura.system.cid() == "all"){
			dacura.users.roles.table(key, roles, tconfig, isedit);
		}
		else {
			dacura.users.roles.simpletable(key, roles, tconfig, isedit);			
		}
	},
	
	/**
	 * @function simpletable
	 * @memberof dacura.users.roles
	 * @summary draws a simple role table
	 * @param {string} key - jquery id of the table to write to 
	 * @param {Object} roles - array of roles to draw into table 
	 * @param {DacuraTableConfig} tconfig - table configuration object
	 */
	simpletable: function(key, roles, tconfig, isedit){
		if(dacura.users.roles.loaded){
			$('#available-roles').html("");
	        $('#possessed-roles').html("");			
		}
		dacura.users.roles.loaded = true;
		var rimplicits = [];
		var rincluded = [];
		for(var i=0; i< roles.length; i++){
			var rname = roles[i].role;
			var rid = roles[i].id;
			rincluded.push(rname);
			var html = dacura.users.roles.button(rname, allroles[rname], "active", rid, isedit);
			$('#possessed-roles').append(html);			
			rimplicits = rimplicits.concat(dacura.users.roles.implicits(rname));
		}
		if(typeof allroles == "object"){
			for(var i in allroles){
				if(rincluded.indexOf(i) == -1 && rimplicits.indexOf(i) == -1){
					var html = dacura.users.roles.button(i, allroles[i], "active", false, isedit);
					$('#available-roles').append(html);
					
				}
				else if(rimplicits.indexOf(i) != -1 && rincluded.indexOf(i) == -1){
					var html = dacura.users.roles.button(i, allroles[i], "inactive", false, isedit);
					$('#implicit-roles').append(html);
				}
			}
		}
		$('.dacura-role-remove').button({
			icons: { primary: "ui-icon-closethick"},
		  	text: false}
	  	);
		$('.dacura-role-add').button({
			icons: { primary: "ui-icon-plusthick"},
		  	text: false}
	  	);	 
	},
	
	/**
	 * @function summary
	 * @memberof dacura.users.roles
	 * @summary returns html summarising the list of passed roles
	 * @param {Object} roles - array of roles to be summarized
	 * @return {string} - html div containing summary list of roles
	 */
	summary: function(roles){
		if(typeof roles == "undefined") return "?";
		var html = "<div class='dacura-role-list'>";
		if(typeof roles == 'undefined' || size(roles) == 0) {
			html += "<span class='dacura-role zombie' title='Zombie user has no roles'></span>";
		}
		else if(typeof roles == 'object') {
			for(var i in roles){
				html += "<span class='dacura-role " + i + "' title='"+roles[i] +"'></span>";
			}
		}
		html += "</div>";
		return html;
	},
	
	/**
	 * @function table
	 * @memberof dacura.users.roles
	 * @summary creates a dacura datatable for displaying the roles
	 * @param {string} key - jquery id of the table to write to 
	 * @param {Object} roles - array of roles to populate table
	 * @return {string} - html div containing summary list of roles
	 */
	table: function (key, roles, tconfig){
		if(dacura.users.roles.loaded){		
			dacura.tool.table.reincarnate(key, roles, tconfig);		
		}
		else {
			dacura.tool.table.init(key, tconfig, roles);
		}
		dacura.users.roles.loaded = true;
	}
};

/** 
 * @namespace api
 * @memberof dacura.users
 * @summary dacura.users.api
 * @description Dacura user service api - each one returns an object with url, type and data set, ready for ajaxing
 */
dacura.users.api = {};

/**
 * @function create
 * @memberof dacura.users.api
 * @summary POST to users api /
 * @param {Object} data user meta-data object to be created 
 */
dacura.users.api.create = function (data){
	var xhr = {};
	xhr.url = dacura.users.apiurl;
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

/**
 * @function listing
 * @memberof dacura.users.api
 * @summary GET users api /
 */
dacura.users.api.listing = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl;
	return xhr;
};

/**
 * @function view
 * @memberof dacura.users.api
 * @summary GET users api /userid
 * @param {string} id - id of the user object to fetch
 */
dacura.users.api.view = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" + id;
	return xhr;
};

/**
 * @function update
 * @memberof dacura.users.api
 * @summary POST data parameter to users api /userid
 * @param {Object} data user meta-data object to send to update api
 * @param {string} data.id id of the user to be updated
 */
dacura.users.api.update = function (data){
	var xhr = {};
	xhr.url = dacura.users.apiurl + "/" +  data.id;
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

/**
 * @function del
 * @memberof dacura.users.api
 * @summary DELETE data parameter to users api /userid
 * @param {string} id - id of the user to be deleted
 */
dacura.users.api.del = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
};

/**
 * @function createrole
 * @memberof dacura.users.api
 * @summary POST data parameter to users api /userid/rol
 * @param {Object} data - role object to be passed to api
 * @param {string} data.uid id of the user that the role is given to
 */
dacura.users.api.createrole = function (data){
	xhr = {};
	xhr.url = dacura.users.apiurl + "/" + data.uid + "/role";
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

/**
 * @function viewrole
 * @memberof dacura.users.api
 * @summary GET users api /userid/roleid
 * @param {string} uid - user id that owns the role
 * @param {string} rid - id of the role itself
 */
dacura.users.api.viewrole = function (uid, rid){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" +  uid + "/role/" + rid;
	return xhr;
};

/**
 * @function delrole
 * @memberof dacura.users.api
 * @summary DELETE users api /userid/roleid
 * @param {string} uid - user id that owns the role
 * @param {string} rid - id of the role itself
 */
dacura.users.api.delrole = function (uid, rid){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" + uid + "/role/" + rid;
	xhr.type = "DELETE";
	return xhr;
};

/**
 * @function updatepassword
 * @memberof dacura.users.api
 * @summary POST data parameter to users api /userid/password
 * @param {Object} data user meta-data object to send to update password api
 * @param {string} data.uid id of the user whose password is being updated
 */
dacura.users.api.updatepassword = function (data){
	xhr = {};
	xhr.data = data;
	xhr.url = dacura.users.apiurl + "/" +  data.uid + "/password";
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

/**
 * @function gethistory
 * @memberof dacura.users.api
 * @summary GET user's session histroy from api /userid
 * @param {string} uid - user id 
 */
dacura.users.api.gethistory = function(uid){
	xhr = {};
	xhr.url = dacura.users.apiurl + "/" + uid + "/history";
	return xhr;
}

/**
 * @function getRoleOptions
 * @memberof dacura.users.api
 * @summary GET set of roles available to user in context from api /userid/roleoptions
 * @param {string} uid - user id 
 */
dacura.users.api.getRoleOptions = function(uid){
	xhr = {};
	xhr.url = dacura.users.apiurl + "/" + uid + "/roleoptions";
	return xhr;
};

/**
 * @function invite
 * @memberof dacura.users.api
 * @summary POST to users api /invite
 * @param {Object} data user meta-data object to send to invite api
 */
dacura.users.api.invite = function (data){
	var xhr = {};
	xhr.url = dacura.users.apiurl + "/invite";
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

