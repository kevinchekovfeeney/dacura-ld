<script>
dacura.candidate = {}
dacura.candidate.apiurl = "<?=$service->get_service_url('candidate', array(), true)?>";
dacura.candidate.api = {};
dacura.candidate.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl;
	xhr.type = "POST";
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
}


dacura.candidate.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + "/" + id;
	xhr.type = "POST";
	return xhr;
}

</script>

<div id='pagecontent-container'>
<div id='pagecontent'>
	<div id="pagecontent-nopadding">
	<div class="pctitle">Candidate API Service <span id="screen-context"></span></div>
	<br>
	</div>
      	<div class="tool-buttons">
   			<button class="dacura-button get-ngas" id="get-ngas">1. Choose NGAs to Export &gt;&gt;</button>
   	   	</div>
	</div>
</div>
<script>
$('document').ready(function(){
	$("button").button().click(function(){
		var candidate = {"class" : "b"};
		var source = {};
		source.agent = {};
		source.agent.dacuraAgent = { "key" : "dsfasd" };
		var annotations = {};
		annotations.target = "something else";
		var ajs = dacura.candidate.api.update("5");
		ajs.data.candidate = JSON.stringify(candidate);
		ajs.data.source = JSON.stringify(source);
		ajs.data.annotations = JSON.stringify(annotations);
		ajs.beforeSend = function(){
			
		};
		ajs.complete = function(){
			
		};

		$.ajax(ajs)
		.done(function(response, textStatus, jqXHR) {	
			alert(response);
		});	
	});
});
</script>