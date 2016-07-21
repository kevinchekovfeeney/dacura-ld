<html>
<head>
	<link rel='stylesheet' href='<?=$service->durl()?>media/css/jquery-ui.css' />
	<link rel='stylesheet' href='<?=$service->durl()?>phplib/services/console/files/font-awesome-4.6.3/css/font-awesome.min.css' />
	<link rel='stylesheet' href='<?=$service->durl()?>phplib/services/console/files/console.css' />
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
	<script src = "<?=$service->durl()?>media/js/jquery-2.1.4.min.js"></script>
	<script src = "<?=$service->durl()?>media/js/jquery-ui.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
	<script src = "<?=$service->durl()?>console/init"></script>
	<script src = "<?=$service->durl()?>phplib/services/core/dacura.utils.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/ld/jslib/ldlibs.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/console/dclient.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/console/dconsole.js"></script>
	
	
<!--
 	<script src = "http://chekasaurusrex/dacura/phplib/services/candidate/dacura.frame.js"></script>
	<script src = "http://chekasaurusrex/dacura/phplib/services/console/dontology.js"></script>
	<script src = "http://chekasaurusrex/dacura/phplib/services/console/dpagescanner.js"></script>
	<script src = "http://chekasaurusrex/dacura/phplib/services/console/dconsole.js"></script> -->

<script>
//id, label, comment, type, parent, choices
//(id, label, comment, domain, range)
var dac;

function loadOntAndAddStupidClass(){
	dac.current_collection = "seshat";
	var changeOnt = function(ont){
		var dgraphs = dac.getGraphsToRedeployForOntologyUpdate(ont.id);
		for(var gid in dgraphs){
			dac.updateGraph(gid, dgraphs[gid], false, false, fail);
		}
	}
	dac.get("ontology", "seshat", changeOnt, fail);
}

var fail = function(x, y, z){
	//alert(x + y + z);
}

jQuery(document).ready(function(){
	//dac = new DacuraClient("http://chekasaurusrex/dacura/");
	//dac.init(loadOntAndAddStupidClass, fail);
	var console_config = {id: "dacura-console", autoload: true, durl: "<?=$service->durl()?>"};
	dacuraConsole = new DacuraConsole(console_config);
	function success(caps){
		//alert("success");
		//dacuraConsole.showMenu();
	}
	dacuraConsole.init(success, fail);
		//;
});

</script>
</head>
<body>
	<h1>The dacura client libraries</h1>
 	<div id='dacura-console' class='uninitialised'> 
		<div class='dacura-console-topbar'>
			<div class='menu-area console-browser-buttons'>
				<span class='browser-button browser-button-back'>
  					<i class="fa fa-arrow-circle-left fa-lg"></i>
  				</span>
				<span class='browser-button browser-button-forward'>
  					<i class="fa fa-arrow-circle-right fa-lg"></i>
  				</span>
  			</div>
			<div class='menu-area console-branding'>
				<a class='console-icon phone-home' title="Click to visit Dacura's home page" href='http://chekasaurusrex/dacura/'>
				<img class='console-icon' src='images/dlogo-white.png'></a>
			</div>
  			<div class='menu-area console-collection'>
				<span class='context-select-holder'>
					<select class='context-collection-picker'></select>
				</span>
				<span class='context-picked context-collection-picked'>
					<span class='collection-title-icon'></span>	
					<span class='collection-title-text'></span>
					<span class='collection-title-changer'>
						<i class="fa fa-angle-down fa"></i>
					</span>						
				</span>	
			</div>
			<div class='menu-area console-toolname'>
				<span class='console-tool browser-tool'>
					<img src='images/browser-tool.png' class='console-icon mode-browser' title='Data Browser Tool'>
					<span class='console-toolname'>Data Browser</span> 
				</span>
				<span class='console-tool harvester-tool'>
					<img src='images/harvester-tool.png' class='console-icon mode-harvester' title='Data Harvester Tool'> 
					<span class='console-toolname'>Data Harvester</span> 					
				</span>
				<span class='console-tool expert-tool'>
					<img src='images/expert-tool.png' class='console-icon mode-expert' title='Expert Annotation Tool'> 
					<span class='console-toolname'>Expert Annotator</span> 					
				</span>
				<span class='console-tool architect-tool'>
					<img src='images/architect-tool.png' class='console-icon mode-architect' title='Architect Tool'> 
					<span class='console-toolname'>Model Viewer</span> 					
				</span>
			</div>
			<div class='menu-area console-context'>
				<div class='context-element context-data entityclass'>
					<span class='context-element-item context-select-holder'>
						<select class='context-entityclass-picker'></select>
					</span>
					<span class='context-element-item context-empty context-entityclass-empty' title="There are no entity classes associated with this collection">
						<i class="fa fa-battery-empty fa-2x"></i>					
					</span>	
					<span class='context-element-item context-add-element'>
						<i class="fa fa-plus-circle fa-2x"></i>					
					</span>
				</div>
				<div class='context-element context-data candidate'>
					<span class='context-select-holder'>
						<select class='context-candidate-picker'></select>
					</span>
					<span class='context-picked context-candidate-picked'>
						<span class='candidate-title-icon'></span>	
						<span class='candidate-title-text'></span>
						<span class='candidate-title-changer'>
							<i class="fa fa-angle-down fa"></i>
						</span>						
					</span>	
					<span class='context-candidate-picked'></span>	
				</div>
				<div class='context-element context-data candidate-properties'>
					<span class='context-select-holder'>
						<select class='context-candidate-picker'></select>
					</span>
					<span class='context-candidate-picked'></span>	
				</div>
				<div class='context-element context-model ontology'>
					<span class='context-select-holder'>
						<select class='context-ontology-picker'></select>
					</span>
					<span class='context-picked context-ontology-picked'>
						<span class='ontology-title-icon'></span>	
						<span class='ontology-title-text'></span>
						<span class='ontology-title-changer'>
							<i class="fa fa-angle-down fa"></i>
						</span>						
					</span>	
				</div>
				<div class='context-element context-model modelclass'>
					<span class='context-select-holder'>
						<select class='context-modelclass-picker'></select>
					</span>
					<span class='context-modelclass-picked'></span>	
				</div>
				<div class='context-element context-model modelproperty'>
					<span class='context-select-holder'>
						<select class='context-modelproperty-picker'></select>
					</span>
					<span class='context-modelproperty-picked'></span>	
				</div>
			</div>
			<div id='dacura-console-menu-busybox' class='menu-area console-busybox'>
			    <i class="fa fa-spinner fa-spin fa-lg fa-fw"></i>
			</div>
			<div id='dacura-console-menu-message' class='menu-area console-resultbox'>
			</div>
			<div class='console-menubar-rhs'>
				<div class='console-controls menu-area'>
					<div class='console-context-actions'>
					</div>
					<div class='console-page-actions'>
					</div>
				</div>
				<div class='menu-area console-mode'>
					<span class='mode-icons data-mode'>
						<img src='images/data-mode-active.png' class='console-icon console-mode mode-data mode-active' title='Data Browser Mode'> 
						<img id='dacura-set-mode-data' src='images/data-mode-inactive.png' class='console-icon console-mode mode-user mode-inactive' title='Data Browser Mode'> 
					</span>
					<span class='mode-icons model-mode'>
						<img src='images/model-mode-active.png' class='console-icon console-mode mode-model mode-active' title='Model Browser Mode'> 
						<img id='dacura-set-mode-model' src='images/model-mode-inactive.png' class='console-icon console-mode mode-model mode-inactive' title='Model Builder Mode'> 
					</span>
				</div>
				<div class='console-user menu-area'>
					<div class='console-login'>
						<div class="input-group margin-bottom-sm">
						  <span class="input-group-addon"><i class="fa fa-envelope-o fa-fw"></i></span>
						  <input class="form-control" type="text" placeholder="Email address">
						</div>
						<div class="input-group">
						  <span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
						  <input class="form-control" type="password" placeholder="Password">
						</div>				
						<i class="fa fa-sign-in fa-lg" aria-hidden="true"></i>
					</div>
					<div class='console-user-logged-in'>
						<div class='console-user-roles'>
							<span class='role-icons harvester-role'>
								<img id='dacura-unset-role-harvester' src='images/harvester-active.png' class='console-icon console-role role-harvester role-active' title='Data Harvester'> 
								<img id='dacura-set-role-harvester' src='images/harvester-inactive.png' class='console-icon console-role role-harvester role-inactive' title='Data Harvester'> 
							</span>
							<span class='role-icons expert-role'>
								<img id='dacura-unset-role-expert' src='images/expert-active.png' class='console-icon console-role role-architect role-active' title='Expert Annotator'> 
								<img id='dacura-set-role-expert' src='images/expert-inactive.png' class='console-icon console-role role-architect role-inactive' title='Expert Annotator'> 
							</span>
							<span class='role-icons architect-role'>
								<img id='dacura-unset-role-architect' src='images/architect-active.png' class='console-icon console-role role-architect role-active' title='Model Architect'> 
								<img id='dacura-set-role-architect' src='images/architect-inactive.png' class='console-icon console-role role-architect role-inactive' title='Model Architect'> 
							</span>
						</div><div class='console-user-icon'>
							<img src='images/me.jpg' class='console-user-icon' title=''> 
						</div>
					</div>
				</div>
				<div class='console-user-close menu-area'>
					<i class="fa fa-angle-double-up fa-lg"></i>					
				</div>
			</div>
		</div>
		<div class='console-extra'>
			<div class='console-context-summary'></div>
			<div class='console-context-full'></div>
		</div>
	</div>
</body>
</html>