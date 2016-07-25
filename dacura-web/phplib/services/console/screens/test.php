<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge;" />
	<link rel='stylesheet' href='<?=$service->durl()?>media/css/jquery-ui.css' />
	<link rel='stylesheet' href='<?=$service->durl()?>phplib/services/console/files/font-awesome-4.6.3/css/font-awesome.min.css' />
	<link rel='stylesheet' href='<?=$service->durl()?>phplib/services/console/files/console.css' />
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
	<script src = "<?=$service->durl()?>media/js/jquery-2.1.4.min.js"></script>
	<script src = "<?=$service->durl()?>media/js/jquery-ui.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.full.js"></script>
	<script src = "<?=$service->durl()?>console/init"></script>
	<script src = "<?=$service->durl()?>phplib/services/core/dacura.utils.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/ld/jslib/ldlibs.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/console/dclient.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/console/dconsole.js"></script>
	<script src = "<?=$service->durl()?>phplib/services/candidate/dacura.frame.js"></script>
	
<script>
//id, label, comment, type, parent, choices
//(id, label, comment, domain, range)
var dacuraConsole;

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
	var console_config = {id: "dacura-console", autoload: false, durl: "<?=$service->durl()?>"};
	dacuraConsole = new DacuraConsole(console_config);
	function success(caps){
		//this is just in case we want a callback
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
				<span title="Go back to the last place you visited" class='browser-button browser-button-back'><i class="fa fa-arrow-circle-left fa-lg"></i></span><span title="Return to the next place you visited" class='browser-button browser-button-forward'><i class="fa fa-arrow-circle-right fa-lg"></i></span>
  			</div><div class='menu-area console-branding'>
				<a class='console-icon phone-home' title="Visit Dacura's home page at <?=$service->durl()?>" href='<?=$service->durl()?>'>
				<img class='console-icon' src='images/dlogo-yellow.png'></a>
			</div><div class='menu-area console-collection'>
				<span class='context-select-holder'>
					<select class='context-collection-picker'></select>
				</span>
				<span class='context-picked context-collection-picked'>
					<span class='collection-title-icon'></span><span class='collection-title-text'></span><span class='collection-title-changer' title='Select a new collection to connect to'><i class="fa fa-angle-down fa"></i></span>						
				</span>	
			</div><div class='menu-area console-mode'>
					<span class='mode-icons'>
						<span id='dacura-mode-data' class='amode-icon mode-data mode-active'>
							<img src='images/data-mode-active.png' class='console-icon console-mode mode-data mode-active' title='Data browser mode is currently active'> 
						</span><span id='dacura-mode-model' class='amode-icon mode-model mode-active'>
							<img src='images/model-mode-active.png' class='console-icon console-mode mode-model mode-active' title='Schema browser mode is currently active'> 
						</span><span id='set-dacura-mode-data' class='amode-icon mode-data mode-inactive'>
							<img src='images/data-mode-inactive.png' class='console-icon console-mode mode-data mode-inactive' title='Switch to Data Browser Mode'> 
						</span><span id='set-dacura-mode-model' class='amode-icon mode-model mode-inactive'>
							<img src='images/model-mode-inactive.png' class='console-icon console-mode mode-model mode-inactive' title='Switch to Schema Browser Mode'> 
						</span>	
					</span>
			</div>
			<div class='menu-area console-context'>
				<div class='context-element context-data entityclass'>
					<span class='context-element-item context-select-holder'>
						<select class='context-entityclass-picker'></select>
					</span>
					<span class='context-element-item context-empty context-entityclass-empty' title="There are no entity classes associated with this collection">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-sitemap fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span>	
					<span class='context-element-item context-add-element'>
						<i class="fa fa-plus-square fa-lg"></i>					
					</span>
				</div>
				<div class='context-element context-data entityproperty'>
					<span class='context-element-item context-select-holder'>
						<select class='context-entityproperty-picker'></select>
					</span><span class='context-element-item context-empty context-entityproperty-empty' title="This entity has no properties">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-puzzle-piece fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span><span class='context-element-item context-add-property'>
						<i class="fa fa-plus-square-o fa-lg"></i>					
					</span>
				</div>
				<div class='context-element context-data candidate'>
					<span class='context-element-item context-select-holder'>
						<select class='context-candidate-picker'></select>
					</span>
					<span class='context-element-item context-empty context-candidate-empty' title="No candidates of this type available">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-database fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span>
				</div>
				<div class='context-element context-data candidateproperty'>
					<span class='context-element-item context-select-holder'>
						<select class='context-candidateproperty-picker'></select>
					</span><span class='context-element-item context-empty context-candidateproperty-empty' title="This candidate has no properties">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-puzzle-piece fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span><span class='context-element-item context-add-property'>
						<i class="fa fa-plus-square-o fa-lg"></i>					
					</span>
				</div>
				<div class='context-element context-model ontology'>
					<span class='context-element-item context-select-holder'>
						<select class='context-ontology-picker'></select>
					</span><span class='context-element-item context-empty context-ontology-empty' title="There are no ontologies associated with this collection">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-cubes fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span><span class='context-element-item context-add-element'>
						<i class="fa fa-plus-square fa-lg"></i>					
					</span>
				</div>
				<div class='context-element context-model modelclass'>
					<span class='context-element-item context-select-holder'>
						<select class='context-modelclass-picker'></select>
					</span><span class='context-element-item context-empty context-modelclass-empty' title="There are no classes in this ontology">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-cube fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span><span class='context-element-item context-add-element'>
						<i class="fa fa-plus-square fa-lg"></i>					
					</span>
				</div>
				<div class='context-element context-model modelproperty'>
					<span class='context-element-item context-select-holder'>
						<select class='context-modelproperty-picker'></select>
					</span><span class='context-element-item context-empty context-modelproperty-empty' title="There are no properties in this ontology">
						<span class="fa-stack fa-1x">
						  <i class="fa fa-puzzle-piece fa-stack-1x"></i>
						  <i class="fa fa-ban fa-stack-2x text-danger"></i>
						</span>
					</span><span class='context-element-item context-add-element'>
						<i class="fa fa-plus-square fa-lg"></i>					
					</span>
				</div>
			</div>
			<div id='dacura-console-menu-busybox' class='menu-area console-busybox'>
			    <i class="fa fa-spinner fa-spin fa-lg fa-fw"></i>
			</div><div id='dacura-console-menu-message' class='menu-area console-resultbox'>
				<span class='dacura-result success'>
			    	<i class="fa fa-check-circle fa-lg"></i>
				</span>
				<span class='dacura-result info'>
			    	<i class="fa fa-info-circle fa-lg"></i>
				</span>
				<span class='dacura-result warning'>
			    	<i class="fa fa-warning fa-lg"></i>
				</span>
				<span class='dacura-result error'>
			    	<i class="fa fa-exclamation-circle fa-lg"></i>
				</span>
			</div>
			<div class='console-menubar-rhs'>
				<div class='console-controls menu-area'>
					<div class='console-context-actions'>
					</div>
					<div class='console-page-actions'>
					</div>
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
						<span class='dologin'>
							Sign in to Dacura
							<i class="fa fa-sign-in fa-lg" aria-hidden="true"></i>
						</span>
					</div>
					<div class='console-user-logged-in'>
						<div class='console-user-roles'>
							<span class='role-icons harvester-role'>
								<img id='dacura-unset-role-harvester' src='images/harvester-active.png' class='console-icon console-role role-harvester role-active' title='Data Harvester role is active - click to deactivate'> 
								<img id='dacura-set-role-harvester' src='images/harvester-inactive.png' class='console-icon console-role role-harvester role-inactive' title='Data Harvester role is inactive - click to activate'> 
							</span>
							<span class='role-icons expert-role'>
								<img id='dacura-unset-role-expert' src='images/expert-active.png' class='console-icon console-role role-architect role-active' title='Expert Annotator role is active - click to deactivate'> 
								<img id='dacura-set-role-expert' src='images/expert-inactive.png' class='console-icon console-role role-architect role-inactive' title='Expert Annotator role is inactive - click to activate'> 
							</span>
							<span class='role-icons architect-role'>
								<img id='dacura-unset-role-architect' src='images/architect-active.png' class='console-icon console-role role-architect role-active' title='Architect role is active - click to deactivate'> 
								<img id='dacura-set-role-architect' src='images/architect-inactive.png' class='console-icon console-role role-architect role-inactive' title='Architect role is inactive - click to activate'> 
							</span>
						</div>
						<div class='console-user-icon'>
							<img src='images/me.jpg' class='console-user-icon' title='Click on the image to set your active roles'> 
						</div>
					</div>
				</div><div class='console-user-close menu-area'>
					<i class="fa fa-angle-double-up fa-lg"></i>					
				</div>
			</div>
		</div>
		<div class='console-extra'>
			<div class='console-context-summary'>
				<div class='console-extra-topbar'>
					<div class='summary-element summary-actions'>
						<span class='summary-action summary-edit'>
							<i class="fa fa-edit fa-lg"></i>					
						</span>
						<span class='summary-action summary-delete'>
							<i class="fa fa-trash-o fa-lg"></i>					
						</span>
					</div>
					<div class='summary-element summary-icons'>
						<span class='summary-action summary-close'>
							<i class="fa fa-angle-double-up fa-lg"></i>					
						</span>
						<span class='summary-element-type'>
							<span class='summary-icon candidate' title="Candidate - unit of instance data">
								<i class="fa fa-database fa-lg"></i>											
							</span>
							<span class='summary-icon modelclass' title='Class'>
								<i class="fa fa-cube fa-lg"></i>											
							</span>
							<span class='summary-icon modelproperty' title='Property'>
								<i class="fa fa-puzzle-piece fa-lg"></i>											
							</span>
						</span><span class='summary-element-subtype'>
							<span class='candidate'></span>
							<span class='modelclass'>
								<span class='summary-icon simple' title='A simple standalone class with no parent'>
									<i class="fa fa-circle fa-lg"></i>																			
								</span>
								<span class='summary-icon complex' title='A complex class with multiple parents and/or non-standard assertions'>
									<i class="fa fa-first-order fa-lg"></i>																			
								</span>
								<span class='summary-icon enumerated' title="An enumerated type">
									<i class="fa fa-list fa-lg"></i>																			
								</span>
								<span class='summary-icon entity' title="An entity class - a primary unit of data collection">
									<i class="fa fa-star fa-lg"></i>																			
								</span>
							</span>
							<span class='modelproperty'>
								<span class='summary-icon object' title='An object property - the range is an object'>
									<i class="fa fa-circle fa-lg"></i>																			
								</span>
								<span class='summary-icon data' title="A datatype property - the range is a simple xsd datatype">
									<i class="fa fa-file-text-o fa-lg"></i>																			
								</span>
							</span><span class='summary-element-status'>
							<span class='summary-icon accept'>
								<i class="fa fa-thumbs-up fa-lg"></i>											
							</span>
							<span class='summary-icon pending'>
								<i class="fa fa-balance-scale fa-lg"></i>												
							</span>
							<span class='summary-icon reject'>
								<i class="fa fa-thumbs-down fa-lg"></i>											
							</span>
							<span class='summary-icon error'>
								<i class="fa fa-exclamation-circle fa-lg"></i>						
							</span>
							<span class='summary-icon warning'>
								<i class="fa fa-warning fa-lg"></i>						
							</span>
						</span></span>
					</div>
					<div class='summary-element summary-identifier'></div>
					<div class='summary-element summary-dacura-link'>
						<span class='summary-action summary-dacura'>
							<i class="fa fa-external-link fa-1x"></i>					
						</span>
					</div>
					<div class='summary-element summary-create'>
						<div class="input-group">
						  <span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
						  <input class="entity-id form-control" type="text" placeholder="ID">
						</div>
						<div class="input-group">
						  <span class="input-group-addon"><i class="fa fa-tag fa-fw"></i></span>
						  <input class="entity-label form-control" type="text" placeholder="Label">
						</div>	
						<div class="input-group">
						  <span class="input-group-addon"><i class="fa fa-comment-o fa-fw"></i></span>
						  <input class="entity-comment form-control" type="text" placeholder="Description">
						</div>
						<div class='create-buttons'>	
							<span class='testcreate create-button' title='test creating the entity'>
								<i class="fa fa-flask fa-lg"></i>
							</span>
							<span class='docreate create-button' title='create the entity'>
								<i class="fa fa-sign-in fa-lg"></i>
							</span>
						</div>
					</div>
					<div class='summary-element summary-details'></div>
				</div>
			</div>				
			<div class='console-context-full'>
				<div class='console-full-section frame-viewer'></div>
				<div class='console-full-section class-viewer'></div>
				<div class='console-full-section property-viewer'></div>
			</div>
		</div>
	</div>
</body>
</html>