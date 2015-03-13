<script>
dacura.candidate = {}
dacura.candidate.apiurl = "<?=$service->get_service_url('candidate', array(), true)?>";
dacura.candidate.api = {};
dacura.candidate.api.create = function (data){
	var xhr = {};
	xhr.url = dacura.candidate.apiurl;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;
}

dacura.candidate.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.candidate.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + "/" + id;
	return xhr;
}


dacura.candidate.api.update = function (id, data){
	var xhr = {};
	xhr.url = dacura.candidate.apiurl + "/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;	
}

</script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<div id='pagecontent-container'>
	<div id='pagecontent'>
		<div id="pagecontent-nopadding">
			<div class="pctitle">Candidate API Service <span id="screen-context"></span></div>
			<h2 id="whatsinbox"></h2>
			<div id='jsoninput'><textarea id='jsoninput_ta'></textarea></div>
			<br>
		</div>
	  	<div class="tool-buttons">
	   		<button class="dacura-button get-ngas" id="create-test">Test Candidate create</button>
	      	<button class="dacura-button get-ngas" id="update-test">Test Candidate update</button>
	      	<input id='viewid'></input>
	      	<button class="dacura-button get-ngas" id="candidate-view">View Candidate</button>
        </div>
	   	</div>
</div>
<script>
var sco = {};
sco.candidate = { 
	"rdf:type" : "seshat:Polity",
	"seshat:duration" : [{ start: "100BCE", end: "1BCE"}], 
	"seshat:population" : [{ "@id": "_:p1", value: "10000", start: "100BCE", end: "100BCE"}, { "@id": "_:p2", value: "20000", start: "100BCE", end: "100CE" }], 
	"seshat:capital" 	: [{ value: "seshat:Rome", start: "500BCE", end: "300CE"}, { value: "seshat:Byzantium", start: "300BCE", end: "800CE"}],
	"seshat:name" 	: [{value: "Roman Empire"}]
};

var suo = {};
suo.candidate = { 
	"rdf:type" 			: "seshat:Polity",
	"seshat:duration" 	: [{ start: "100BCE", end: "1BCE"}, { start: "1CE", end: "100CE"}], //added second one
	"seshat:population" : [{ "@id": "_:p1", value: "10000", start: "100BCE", end: "100BCE"}, { "@id": "_:p2", value: "2000", start: "100BCE", end: "100CE" }], //change second one 
	"seshat:capital" 	: [{ value: "seshat:Rome", start: "50CE", end: "300CE"}], //delete one value and change the other
	"seshat:name" 		: [] //delete property
};


suo.provenance = {
	prefix: { 
		"dacura" : "http://dacura.scss.tcd.ie/data/provenance" 
	},
	agent: {
		"dacura:dacuraAgent": {"prov:type": "prov:SoftwareAgent", "dacura:key" : "agentkey"},
		"dacura:jim": {"prov:type": "prov:Person"}
	},
	activity: {
		"_:a1" : {
			"startTime": "2011-11-16T16:05:00", 
			"endTime": "2011-11-16T16:06:00", 
			"prov:type": "dacura:candidateEditing"
		}
	},
	wasGeneratedBy: {"_:g1" : 
		{"entity": "_:candidate", "activity": "_:a1"}
	},
	wasAssociatedWith: {
	   "_:ag1" : {"agent": "dacura:jim", "activity": "_:a1"}, 
	   "_:ag2" : {"agent": "dacura:dacuraAgent", "activity": "_:a1"}
	}
};

sco.provenance = {
	prefix: { 
		"dacura" : "http://dacura.scss.tcd.ie/data/provenance" 
	},
	agent: {
		"dacura:dacuraAgent": {"prov:type": "prov:SoftwareAgent", "dacura:key" : "agentkey"},
		"dacura:john": {"prov:type": "prov:Person"}
	},
	activity: {
		"_:a1" : {
			"startTime": "2011-11-16T16:05:00", 
			"endTime": "2011-11-16T16:06:00", 
			"prov:type": "dacura:candidateCreation"
		}
	},
	wasGeneratedBy: {
		"_:g1" : {"entity": "_:candidate", "activity": "_:a1"}
	},
	wasAssociatedWith: {
	   "_:ag1" : {"agent": "dacura:jim", "activity": "_:a1"}, 
	   "_:ag2" : {"agent": "dacura:dacuraAgent", "activity": "_:a1"}
	}
};

suo.annotation = {
	'_:ano1': {
		"oa:hasTarget" : "_:candidate", 
		"oa:hasBody": { "rdf:type" : "dctypes:Text", "cnt:chars": "This data is completely wrong"},
		"oa:annotatedBy": "dacura:john"
	},
};



sco.annotation = {
	'_:ano1': {
		"oa:hasTarget" : "_:candidate", 
		"oa:hasBody": { "rdf:type" : "dctypes:Text", "cnt:chars": "This data is completely wrong"},
		"oa:annotatedBy": "dacura:john"
	},
	'_:body': {"rdf:type" : "dctypes:Text", "cnt:chars": "This data is completely right"}
};


var pvco = {};
pvco.candidate = { 
	"rdf:type" : "pv:Event",
	"pv:fatalities": 100,
	"pv:committedBy" : "pv:IRA",
	"pv:location": { id: "l1", "geo:lat" : 1.21112, "geo:long" : -0.1212 }
};

pvco.provenance = {
	prefix: { "dacura" : "http://dacura.scss.tcd.ie/data/provenance" },
	agent: {
		"dacura:dacuraAgent": {"prov:type": "prov:SoftwareAgent", "dacura:key" : "agentkey"},
		"dacura:jane": {"prov:type": "prov:Person"}
	},
	activity: {
		"_:a1" : {
			"startTime": "2011-11-16T16:05:00", 
			"endTime": "2011-11-16T16:06:00", 
			"prov:type": "dacura:candidateCreation"
		}
	},
	wasGeneratedBy: [{"entity": "_:candidate", "activity": "_:a1"}], 
	wasAssociatedWith: [
	   {"agent": "dacura:jane", "activity": "_:a1"}, {"agent": "dacura:dacuraAgent", "activity": "_:a1"}
	] 
};
pvco.annotation = {
	'_:ano1': {"oa:hasTarget" : "_:candidate", "oa:hasBody": "_:body", "oa:annotatedBy": "dacura:jane"},
	'_:body': {"rdf:type" : "dctypes:Text", "cnt:chars": "This data is completely right"}
};



$('document').ready(function(){
	$('#jsoninput_ta').val(JSON.stringify(sco));
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#jsoninput_ta"), "800", "400");
    j.doTruncation(true);
    j.showText();
	j.showFunctionButtons();

	
	
	$("#create-test").button().click(function(){
		var ajs = dacura.candidate.api.create(sco);
		ajs.beforeSend = function(){
			
		};
		ajs.complete = function(){
			
		};

		$.ajax(ajs)
		.done(function(response, textStatus, jqXHR) {	
			$('#whatsinbox').html("Create result");
			$('#jsoninput_ta').val(JSON.stringify(response));	
			j.setJsonFromText();
		    j.rebuild();
			
			//alert(response);
		});	
	});
	$("#candidate-view").button().click(function(){
		var i = $('#viewid').val();
		if(i == ""){
			alert("You must specify a candidate id");
			return;
		}
		var ajs = dacura.candidate.api.view(i);
		ajs.beforeSend = function(){
			
		};
		ajs.complete = function(){
			
		};
		$.ajax(ajs)
		.done(function(response, textStatus, jqXHR) {	
			$('#whatsinbox').html("View " + i + " result");
			$('#jsoninput_ta').val(response);	
			j.setJsonFromText();
		    j.rebuild();
		});	
	});
	$("#update-test").button().click(function(){
		var i = $('#viewid').val();
		if(i == ""){
			alert("You must specify a candidate id");
			return;
		}
		var uobj = $('#jsoninput_ta').val();
		try {
			uobj = JSON.parse(uobj);
		}
		catch(e){
			alert("JSON does not parse "+ e.message);
			return;
		}
		var ajs = dacura.candidate.api.update(i, uobj);
		ajs.beforeSend = function(){
			
		};
		ajs.complete = function(){
			
		};

		$.ajax(ajs)
		.done(function(response, textStatus, jqXHR) {	
			$('#whatsinbox').html("Update " + i + " result");
			//ert(response);
			$('#jsoninput_ta').val(JSON.stringify(response));	
			j.setJsonFromText();
		    j.rebuild();
		});	
	});
	
});
</script>