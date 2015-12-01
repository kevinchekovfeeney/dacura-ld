dacura.users = {}
dacura.users.apiurl = dacura.system.apiURL();

dacura.users.api = {};
dacura.users.api.create = function (data){
	var xhr = {};
	xhr.url = dacura.users.apiurl;
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

dacura.users.api.invite = function (data){
	var xhr = {};
	xhr.url = dacura.users.apiurl + "/invite";
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

dacura.users.api.update = function (data){
	var xhr = {};
	xhr.url = dacura.users.apiurl + "/" +  data.id;
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

dacura.users.api.del = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
};

dacura.users.api.createrole = function (data){
	xhr = {};
	xhr.url = dacura.users.apiurl + "/" + data.uid + "/role";
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

dacura.users.api.view = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" + id;
	return xhr;
};

dacura.users.api.listing = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl;
	return xhr;
};

dacura.users.api.updatepassword = function (data){
	xhr = {};
	xhr.data = data;
	xhr.url = dacura.users.apiurl + "/" +  data.uid + "/password";
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	return xhr;
};

dacura.users.api.delrole = function (uid, rid){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" + uid + "/role/" + rid;
	xhr.type = "DELETE";
	return xhr;
};

dacura.users.api.viewrole = function (uid, rid){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.users.apiurl + "/" +  uid + "/role/" + rid;
	return xhr;
};

dacura.users.api.getRoleOptions = function(uid){
	xhr = {};
	xhr.url = dacura.users.apiurl + "/" + uid + "/roleoptions";
	return xhr;
};

dacura.users.updatePassword = function(data, onwards, targets){
	var ajs = dacura.users.api.updatepassword(data);
	var msgs = {"busy": "Updating password for user " + data.uid, "fail": "Failed to update password for user " + data.uid};
	ajs.handleResult = onwards;
	ajs.handleTextResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.users.createRole = function(data, onwards, targets){
	var ajs = dacura.users.api.createrole(data);
	var msgs = {"busy": "Creating role for user " + data.uid, "fail": "Failed to create new role for user " + data.uid};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.users.updateUser = function(data, onwards, targets){
	var ajs = dacura.users.api.update(data);
	var msgs = {"busy": "Updating user " + data.id + " details", "fail": "Failed to update users settings for user " + data.id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.users.deleteUser = function(id, onwards, targets){
	var ajs = dacura.users.api.del(id);
	var msgs = {"busy": "Deleting user " + id, "fail": "Failed to delete user " + id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.users.deleteRoles = function(obj, onwards, targets){
	rid = obj.rids.shift();
	var ajs = dacura.users.api.delrole(obj.uid, rid);
	var msgs = {"busy": "Deleting role " + rid, "fail": "Failed to delete role " + rid};
	ajs.handleResult = function(robj, targets){
		if(obj.rids.length > 0){
			dacura.users.deleteRoles(obj, onwards, targets);
		}
		else {
			onwards(robj, targets);
		}
	}	
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.users.fetchUser = function(id, onwards, targets){
	var ajs = dacura.users.api.view(id);
	var msgs = {"busy": "Retrieving user profile from server", "fail": "Failed to retrive user profile from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};	

dacura.users.addUser = function(data, onwards, targets){
	var ajs = dacura.users.api.create(data);
	var msgs = {"busy": "Creating new user", "fail": "Failed to create new user"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.users.getUsers = function(onwards, targets){
	var ajs = dacura.users.api.listing();
	var msgs = {"busy": "Retrieving list of users from server", "fail": "Failed to retrive list of users from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};	

dacura.users.inviteUsers = function(data, onwards, targets){
	var ajs = dacura.users.api.invite(data);
	var msgs = {"busy": "Submitting list of users to invite to server", "fail": "Failed to create invitations"};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}
