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
	

	input#dacura-collection-title {
		width: 380px;
	}
	input#dacura-collection-id {
		width: 160px;
	}
</style>
<div id="pagecontent">
	<div id="create-collection">
<?php if(!$service->getCollectionID()) {?>
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
		<br clear='both'>
	 	<div class='wizard-directions'><a id="wizard-previous" href="#" class="button2">Previous</a><a id="wizard-next" href="#" class="button2">Next</a>
	 	</div>
<?php }else {?>
		<div id="create-collection-header">Create a new dataset in collection <?=$service->getCollectionID()?></div>
		<div class='wizard-screen' id="wizard1">
	 		<div class="dacura-wizard-title">1. Choose your dataset's name</div>
			<div class="dacura-wizard-options">
				<table class="dc-wizard">
					<tr><th></th><td></td></tr>
					<tr><th>Dataset Short Name</th><td><input class="dc-input" id="dacura-dataset-id" type="text" size=12 value=""></td></tr>
					<tr><th></th><td class="input-help">Your datasets short name should be a short string, containing only letters, numbers and underscores.</td></tr>
					<tr><th>Dataset Full Name</th><td><input class="dc-input" id="dacura-dataset-title" type="text" value=""></td></tr>
					<tr><th></th><td class="input-help">The title of the dataset, as it will appear on web pages, etc.</td></tr>
					</table>
			</div>
	 		<div class="dacura-wizard-help">Your dataset's short name appears in URLs - the web address of your dataset - so keep it simple. 
	 		</div>
	 	</div>

	 	<br clear='both'>
	 	<div class='wizard-directions'><a id="wizard-previous" href="#" class="button2">Previous</a><a id="wizard-next" href="#" class="button2">Next</a>
	 	</div>
<?php } ?>
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


function title_is_acceptable(jqid){
	if($(jqid).val() != ""){
		return true;
	}
	return true;
}

function id_is_acceptable(jqid){
	if(!has_acceptable_characters($(jqid).val())){
		alert($(jqid).val() + "has unacceptable characters");
		return true;
	} 
	if(!has_acceptable_length($(jqid).val())){
		alert($(jqid).val() + "has unacceptable length");
		return true;
	}
	return true;
}

var getAfterCreateLink = function(id){
<?php if($service->getCollectionID()){?>
	return dacura.toolbox.getServiceURL("<?=$service->settings['install_url']?>", "", "<?=$service->getCollectionID()?>", id, "config", "");
<?php } else {?>
	return dacura.toolbox.getServiceURL("<?=$service->settings['install_url']?>", "", id, "all", "config", "");
<?php }?>
};

var create_collection = function(){
	disableNext();
	var col = {};
	col.id = $('#dacura-collection-id').val();
	col.title = $('#dacura-collection-title').val();
	var ajs = dacura.config.api.create();
	ajs.data = col;
	ajs.data.payload = JSON.stringify(col);
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.dacura-wizard-help', "Checking credentials...");
	};
	ajs.complete = function(){
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {				
			window.location.href = getAfterCreateLink(col.id);
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('.dacura-wizard-help', "Error: " + jqXHR.responseText );
		}
	);		
}

var create_dataset = function(){
	disableNext();
	var col = {};
	col.id = $('#dacura-dataset-id').val();
	col.title = $('#dacura-dataset-title').val();
	var ajs = dacura.config.api.create();
	ajs.data = col;
	ajs.data.payload = JSON.stringify(col);
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.dacura-wizard-help', "Checking credentials...");
	};
	ajs.complete = function(){
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {				
			//alert(data);
			window.location.href = getAfterCreateLink(col.id);
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('.dacura-wizard-help', "Error: " + jqXHR.responseText );
		}
	);	
	
}

$('#dacura-dataset-title').change(function () { 
	if(!title_is_acceptable('#dacura-dataset-title')){
		disableNext();
		alert("title is not acceptable");
	}
	else if(id_is_acceptable('#dacura-dataset-id')){
		enableNext(create_dataset);
	}			
});

$('#dacura-dataset-id').change(function () { 
	if(!id_is_acceptable('#dacura-dataset-id')){
		disableNext();
	}
	else if(title_is_acceptable('#dacura-dataset-title')){
		enableNext(create_dataset);
	}			
});


$('#dacura-collection-title').change(function () { 
	if(!title_is_acceptable('#dacura-collection-title')){
		disableNext();
		alert("title is not acceptable");
	}
	else if(id_is_acceptable('#dacura-collection-id')){
		enableNext(create_collection);
	}			
});

$('#dacura-collection-id').change(function () { 
	if(!id_is_acceptable('#dacura-collection-id')){
		disableNext();
	}
	else if(title_is_acceptable('#dacura-collection-title')){
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
