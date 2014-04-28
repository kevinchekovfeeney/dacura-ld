<style>
	#create-collection-header {
		margin: -10px -10px 30px -10px;
		color: white;
		font-size: 1.2em;
		background: rgba(45, 33, 32, 0.9);
	}
	
	#create-collection {
		min-height: 140px;	
	}
	
	.dacura-wizard-title {
		font-weight: bold;
		text-align: left;
		margin-bottom: 12px;
	}
	
	.wizard-screen {
		margin-top: -10px;
	}
	
	.dacura-wizard-help {
		width: 190px;
		background: #fcfae4;
		float: right;
		margin-left: 6px;
	}
	
	.dacura-wizard-options {
		float: left;
		width: 600px;
	}
	
	.wizard-directions {
		break: both;
		width: 800px;
		margin-top: 10px; 
		height: 50px;
		margin-right: -6px;
		margin-left: -6px;
		margin-bottom: -4px;
	}
	
	a#wizard-previous{
		float: left;
		margin-left: 0px;
   }
	a#wizard-next{
		float: right;
		margin-right: 0px;
	}
	
	table.dc-wizard {
		border-top: 1px solid #888;
		border-collapse: collapse;
	}
	
	table.dc-wizard th {
		padding: 10px 4px 2px 4px;
		font-size: 0.85em;
		width: 150px;
		vertical-align: top;
		text-align: right;
		padding-right: 6px;
		border-right: 1px solid #888;
}
	
	table.dc-wizard td {
		padding: 4px 4px 0px 6px;
		text-align: left;
	}
	
	table.dc-wizard td.input-help {
		padding-top: 0px;
		text-align: left;
		font-size: 0.8em;
		padding-bottom: 10px;
	}
	
	input#dacura-collection-title {
		width: 380px;
	}
	input#dacura-collection-id {
		width: 160px;
	}
	</style>

<div id="pagecontent">
	<div id="create-collection">
		<div id="create-collection-header">Create a new collection of datasets</div>
	
		<div class='wizard-screen' id="wizard1">
	 		<div class="dacura-wizard-title">1. Choose your collection's name</div>
			<div class="dacura-wizard-options">
				<table class="dc-wizard">
					<tr><th></th><td></td></tr>
					<tr><th>Collection Short Name</th><td><input class="dc-input" id="dacura-collection-id" type="text" size=12 value=""></td></tr>
					<tr><th></th><td class="input-help">Your collection's short name should be a short string, containing only letters, numbers and underscores.</td></tr>
					<tr><th>Collection Full Name</th><td><input class="dc-input" id="dacura-collection-title" type="text" value=""></td></tr>
					<tr><th></th><td class="input-help">The title of the collection, as it will appear on web pages, etc.</td></tr>
					</table>
			</div>
	 		<div class="dacura-wizard-help">Your collection's short name appears in URLs - the web address of your collection - so keep it simple. 
	 		</div>
	 	</div>
		<div class='wizard-screen' id="wizard2">
	 		<div class="dacura-wizard-title">2. Choose your collection's images</div>
			<div class="dacura-wizard-options">
			</div>
	 		<div class="dacura-wizard-help">Choose a logo, background pictures and a color scheme for your collection</div>
 		</div>
	 	<div class='wizard-screen' id="wizard3">
	 		<div class="dacura-wizard-title">3. Describe your collection</div>
			<div class="dacura-wizard-options">
			</div>
	 		<div class="dacura-wizard-help">Write a short descriptive paragarph about your collection.</div>
	 	</div>
	 	<br clear='both'>
	 	<div class='wizard-directions'><a id="wizard-previous" href="#" class="button2">Previous</a><a id="wizard-next" href="#" class="button2">Next</a>
	 	</div>
	</div>
</div>
<script>
var wizard_state = {};
wizard_state.pagenum = 1;

var move_forward = function(){
	if(wizard_state.pagenum == 5){
		//submit the thing here...
	}
	else if(wizard_state.pagenum == 1){
		enablePrevious();
	}	
	$('#wizard-previous').show();
	$('#wizard'+wizard_state.pagenum).hide();
	wizard_state.pagenum++;
	$('#wizard'+wizard_state.pagenum).show();
	if(wizard_state.pagenum == 5){
		$('#wizard-next').html("Submit");
	}
	else {
		$('#wizard-next').html("Next");
	}
}

var move_backwards = function(){
	$('#wizard-next').html("Next");
	$('#wizard'+wizard_state.pagenum).hide();
	wizard_state.pagenum--;
	$('#wizard'+wizard_state.pagenum).show();
	if(wizard_state.pagenum == 1){
		disablePrevious();
	}
	else {
		//enablePrevious();
	}
}

function disableNext(){
	$('#wizard-next').addClass("disabled");
	$('#wizard-next').off();
}

function enableNext(func){
	if($('#wizard-next').hasClass("disabled")){
		$('#wizard-next').removeClass("disabled");
		$('#wizard-next').on("click", func);
	}
}


function disablePrevious(){
	$('#wizard-previous').addClass("disabled");
	$('#wizard-previous').off();
}

function enablePrevious(){
	if($('#wizard-previous').hasClass("disabled")){
		$('#wizard-previous').removeClass("disabled");
		$('#wizard-previous').on("click", move_backwards);
	}
}

function has_acceptable_characters(v){
    var regularExpression = /^[a-z_0-9]+$/;
    var valid = regularExpression.test(v);
    return valid;
}

function has_acceptable_length(v){
	return (v.length >= 2 && v.length <=24);
}


function title_is_acceptable(){
	if($('#dacura-collection-title').val() != ""){
		return true;
	}
	return false;
}

function id_is_acceptable(){
	if(!has_acceptable_characters($('#dacura-collection-id').val())){
		alert($('#dacura-collection-id').val() + "has unacceptable characters");
		return false;
	} 
	if(!has_acceptable_length($('#dacura-collection-id').val())){
		alert($('#dacura-collection-id').val() + "has unacceptable length");
		return false;
	}
	return true;
}

var create_collection = function(){
	disableNext();
	var col = {};
	col.id = $('#dacura-collection-id').val();
	col.title = $('#dacura-collection-title').val();
	var ajs = dacura.collection.api.create();
	ajs.data = col;
	ajs.data.payload = JSON.stringify(col);
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.dacura-wizard-help', "Checking credentials...");
	};
	ajs.complete = function(){
		self.enablelogin();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
	     	alert("success");
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('.dacura-wizard-help', "Error: " + jqXHR.responseText );
		}
	);	
	
}

$('#dacura-collection-title').change(function () { 
	if(!title_is_acceptable()){
		disableNext();
		alert("title is not acceptable");
	}
	else if(id_is_acceptable()){
		enableNext(create_collection);
	}			
});

$('#dacura-collection-id').change(function () { 
	if(!id_is_acceptable()){
		disableNext();
	}
	else if(title_is_acceptable()){
		enableNext(create_collection);
	}			

});

$(function() {
	$('.wizard-screen').hide();
	//$('#wizard-previous').click(move_backwards);	
	//$('#wizard-next').click(move_forward);
	$('#wizard-previous').hide();
	//disablePrevious();
	disableNext();
	$('#wizard-next').html("Submit");
	$('#wizard1').show();
	
});


</script>
