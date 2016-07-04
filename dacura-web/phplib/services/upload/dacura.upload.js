dacura.upload = {};

dacura.upload.uploadFile = function(payload, onwards, pconf){
	xhr = {};
	xhr.url = dacura.system.apiURL("upload") + "/files";
	xhr.type = "POST";
	xhr.data = payload;
	xhr.processData= false;
	xhr.contentType = payload.type;
	xhr.handleResult = onwards;
	//turn off always as this call is always used in a chain - means the handling function can still use busy messages and the buttons remain disabled
	xhr.always = function(){};
	var msgs = {busy: "Uploading file to server", success: "File uploaded to server", fail: "Failed to upload file"};
	dacura.system.invoke(xhr, msgs, pconf);
}