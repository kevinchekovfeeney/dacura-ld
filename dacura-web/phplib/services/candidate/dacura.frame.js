
dacura.frame = {};
dacura.frame.api = {};
dacura.frame.apiurl = dacura.system.apiURL();
	
dacura.frame.api.getFrame = function (cls){
	var xhr = {};
	xhr.type = "POST";
	xhr.url = dacura.frame.apiurl + "/frame";
	xhr.data = {'class' : cls};
	xhr.dataType = "json";
	return xhr;
};

dacura.frame.api.getFilledFrame = function (candid){
	var xhr = {};
	xhr.type = "GET";
	xhr.url = dacura.frame.apiurl + "/frame/" + candid;
	return xhr;
};

function FrameViewer(cls, target, pconfig){
	this.cls = cls;
	this.target = target;
	this.pconfig = pconfig;
}

FrameViewer.prototype.draw = function(frames, mode){
	this.mode = mode;
	this.frames = frames;
	if(frames.length > 0){
		alert("drawing in " + mode + " mode " + frames.length + " frames in list");
		
	}
	else {
		
	}
};

FrameViewer.prototype.extract = function(){
	alert("called extract");
	return {};
}


