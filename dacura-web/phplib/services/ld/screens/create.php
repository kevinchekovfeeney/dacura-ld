<div class='dacura-subscreen' id="ldo-create" title="<?=$params['ld_create_title']?>">
	<div class='subscreen-intro-message'><?=$params['create_intro_msg']?></div>
	<?php echo $service->getInputTableHTML("ldo-details", $params['create_ldo_fields'], $params['create_ldo_config']);?>
	<div class="subscreen-buttons">
		<?php if(isset($params['show_test_button']) && $params['show_test_button']) { ?>
		<button id='ldotestcreate' class='dacura-test-create subscreen-button'><?=$params['test_create_button_text']?></button>		
		<?php } if(isset($params['show_create_button']) && $params['show_create_button']) { ?>
		<button id='ldocreate' class='dacura-create subscreen-button'><?=$params['create_button_text']?></button>
		<?php } ?>
	</div>
</div>
<script>
var cphp = {};//php variable mapping object
cphp.demand_id_token = "<?php echo isset($params['demand_id_token']) ? $params['demand_id_token'] : "" ?>";
cphp.tooltip = <?php echo isset($params['help_tooltip_config']) ? $params['help_tooltip_config'] :  "{}" ?>;
cphp.fburl = "<?php echo $service->getFileBrowserURL()?>";
cphp.lconf = <?php echo isset($params['create_ldoviewer_config']) ? $params['create_ldoviewer_config'] : "{}" ?>;
cphp.show_create_button = <?php echo isset($params['show_create_button']) ? $params['show_create_button'] : false ?>;
cphp.show_test_button = <?php echo isset($params['show_test_button']) ? $params['show_test_button'] : false ?>;
cphp.create_options = <?php echo isset($params['create_options']) && $params['create_options'] ? $params['create_options'] : "{}";?>;
cphp.test_create_options = <?php echo isset($params['test_create_options']) && $params['test_create_options'] ? $params['test_create_options'] : "{}";?>;
cphp.importstooltip = <?php echo isset($params['tooltip_config']) ? $params['tooltip_config'] : "{}" ?>;
cphp.available_ontologies = <?php echo isset($params['available_ontologies']) ? $params['available_ontologies'] : "{}"?>;

function loadInputFromFrame(){
	var rdftype = $('#ldo-details-candtype').val();
	//var ipobj = dacura.frame.entityExtractor('#frame-container');
	var obj = {};
	var pop = $('#fupopulation').val();
	if(pop){
		obj[pref + "population"] = {data: $('#fupopulation').val(), type: "http://www.w3.org/2001/XMLSchema#integer"}; 
	}
	var nm = $('#funame').val();
	if(nm){
		obj[pref + "name"] = {data: nm, lang: "en"}; 
	}
	var an = $('#fualtname').val();
	if(an){
		obj[pref + "alternativeName"] = {data: an, lang: "en"};  
	}
	obj['rdf:type'] = rdftype;
	return {format: "json", contents: obj};
}

function testCreateLDO(data, result, pconf){
	dacura.system.clearResultMessage(pconf.resultbox);
	var input = cphp.ldov.readCreateForm(data, cphp.demand_id_token, cphp.test_create_options);	
	if(input){
		if(typeof cphp.importer == "object"){
			input = getImports(input);
		}
		else {
			v = $("#ldo-details-imptype :radio:checked").val();		
			if(v == "frame"){
				input = $.extend(true, input, loadInputFromFrame());	
			}
		}
		cphp.ldov.create(input, result, true);
	}
}

function createLDO(data, result, pconf){
	dacura.system.clearResultMessage(pconf.resultbox);
	var uinput = cphp.ldov.readCreateForm(data, cphp.demand_id_token, cphp.create_options);
	if(uinput){
		if(typeof cphp.importer == "object"){
			uinput = getImports(uinput);
		}
		else {
			v = $("#ldo-details-imptype :radio:checked").val();		
			if(v == "frame"){
				uinput = $.extend(true, uinput, loadInputFromFrame());	
			}
		}
		cphp.ldov.create(uinput, result);
	}
}

function getImports(data){
	imports = [];
	for(var k in cphp.importer.current){
		imports.push(importToURL(cphp.importer.current[k]));
	}
	if(size(imports)){
		if(typeof data.contents != "object"){
			data.contents = {};
		}
		data.contents["_:schema"] = {"owl:imports" : imports};
	}
	return data;
}

initarg.forms = { 
	ids: ['ldo-details'], 			
	tooltip: cphp.tooltip,
	fburl: cphp.fburl			
};

var initcreate = function(pconfig){
	cphp.lconf.ldtype = ldtype;
	cphp.lconf.target = '#ldcontentsinform';
	cphp.lconf.emode = 'import';
	cphp.ldov = new LDOViewer(false, pconfig, cphp.lconf);
	if(cphp.show_test_button){
		dacura.tool.button.init("ldotestcreate", {
			"test": true,
			"screen": "ldo-create",			
			"source": "ldo-details",
			"validate": function (obj) { return cphp.ldov.validateNew(obj) },		
			"submit": testCreateLDO,
			"result": function(data, pconf) { var x = new LDResult(data, pconf); x.show() }
		});
	}
	if(cphp.show_create_button){
		dacura.tool.button.init("ldocreate", {
			"screen": "ldo-create",			
			"source": "ldo-details",
			"validate": function (obj) { return cphp.ldov.validateNew(obj) },
			"submit": createLDO,
			"result": function(data, pconf) { var x = new LDResult(data, pconf); x.show() }
		});
	}
	if(document.getElementById("row-ldo-details-ldcontents") !== null){
		$('#row-ldo-details-ldcontents').after("<tr><td class='ldcontents-form extra-form-element' id='ldcontentsinform' colspan='3'></td></tr>");
		cphp.ldov.show();		
	}
	if(document.getElementById("ldo-details-candtype") !== null){
		$('#row-ldo-details-candtype').after("<tr><td class='candframe-form extra-form-element' id='candframeinform' colspan='3'></td></tr>");
		$('#ldo-details-candtype').selectmenu({
			change: drawCandidateFrame
		});
		drawCandidateFrame();
	}
	if(document.getElementById("ldo-details-imptype") !== null){
		$("#ldo-details-imptype :radio").click(function(){
			setImportType();
		});
		setImportType();
	}	
	if(document.getElementById("row-ldo-details-ontimports") !== null){
		$('#row-ldo-details-ontimports').after("<tr><td class='ontimports-form extra-form-element' id='ontimportsinform' colspan='3'></td></tr>");
		$('#row-ldo-details-ontimports').hide();
		cphp.importer = new OntologyImporter({}, cphp.available_ontologies, {}, "graph");
		$('#ontimportsinform').tooltip(cphp.importstooltip);
		cphp.importer.show_buttons = false;
		cphp.importer.draw('#ontimportsinform');					
	}
}

function drawCandidateFrame(){
	etype = $('#ldo-details-candtype').val();
	var tp = etype.substring(etype.lastIndexOf('#') + 1);
	pref = etype.substring(0, etype.lastIndexOf('#') + 1);
	if(tp == 'Polity'){
		showPhoneyPolityForm('#candframeinform');
	}
	else {
		cphp.ldov.showFrame(etype, '#candframeinform');			
	}
}

function setImportType(){
	v = $("#ldo-details-imptype :radio:checked").val();		
	if(v == "frame"){
		$('#row-ldo-details-ldcontents').hide();
		$('#ldcontentsinform').hide();
		$('#candframeinform').show();
		$('#row-ldo-details-candtype').show();
	}
	else {
		$('#row-ldo-details-ldcontents').show();
		$('#candframeinform').hide();
		$('#ldcontentsinform').show();
		$('#row-ldo-details-candtype').hide();
	}		
}


function showPhoneyPolityForm(target){
	var html = '</td></tr><tr class="dacura-property-spacer"></tr>';
	html += '<tr class="dacura-property row-5">';
	html += '<td class="dacura-property-label">Name</td><td class="dacura-property-value">';
	html += '<table style="border: 0" class="dacura-property-value-bundle"><tbody><tr><td class="dacura-property-input"><input class="dacura-long-input" type="text" id="funame">';
	html += '</td><td class="dacura-property-help"></td></tr></tbody></table></td></tr>';
	html += '<tr class="dacura-property row-6">';
	html += '<td class="dacura-property-label">Population</td><td class="dacura-property-value">';
	html += '<table style="border: 0" class="dacura-property-value-bundle"><tbody><tr><td class="dacura-property-input"><input type="text" id="fupopulation">';
	html += '</td><td class="dacura-property-help"></td></tr></tbody></table></td></tr>';
	html += '<tr class="dacura-property row-7">';
	html += '<td class="dacura-property-label">Alternative Name</td><td class="dacura-property-value">';
	html += '<table style="border: 0" class="dacura-property-value-bundle"><tbody><tr><td class="dacura-property-input"><input class="dacura-long-input" type="text" id="fualtname">';
	html += '</td><td class="dacura-property-help"></td></tr></tbody></table></td></tr>';
	html += '</tbody></table>';
	$(target).html(html);	
}

initfuncs["ldo-create"] = initcreate;
</script>