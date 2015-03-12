<script>
dacura.candidate = {}
dacura.candidate.apiurl = "<?=$service->get_service_url('candidate', array(), true)?>";
dacura.candidate.api = {};
dacura.candidate.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + "/" + id;
	return xhr;
}

</script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<div id='pagecontent-container'>
	<div id='pagecontent'>
		<div id="pagecontent-nopadding">
			<div class="pctitle">Candidate API Service <span id="screen-context">View <?php echo $params["id"];?></span></div>
			<div id="show-candidate"></div>
			<br>
		</div>
   	</div>
</div>
<script>


$('document').ready(function(){
	var ajs = dacura.candidate.api.view("<?=$params['id']?>");
	ajs.beforeSend = function(){
		
	};
	ajs.complete = function(){
		
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {	
		$('#show-candidate').html(response);	
	});	
});
</script>