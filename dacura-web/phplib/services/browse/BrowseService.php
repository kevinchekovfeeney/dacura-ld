<?php

include_once("BrowseDacuraServer.php");

class BrowseService extends DacuraService {
	
	var $collection_context;
	var $dataset_context;
	var $public_screens = array();
	var $protected_screens = array("view" => array("user"));
	
	function getServiceContextLinks(){
		return false;
	}
	
	function handlePageLoad($bds){
		$params = array('collection' => $this->collection_id, "dataset" => $this->dataset_id);
				
		//$sc->screen = "browse";
		//parent::handlePageLoad($sc);
		?>
		<div id='dashboard-container'>
		<div id='dashboard-header'></div>
		<?php $this->renderScreen("menu", $bds->getMenuPanelParams($params))?>
			<div id='dashboard-content'>
				<div id='dashboard-tasks'>
					<?php if($bds->userHasRole("admin", "all", "alL")){
							$this->renderScreen("managementpanel", $params); }?>				
					<?php 	$dparams = $bds->getDataPanelParams($params);
							if($dparams) $this->renderScreen("datapanel", $dparams);?>				
					<?php $this->renderScreen("toolpanel", $bds->getToolPanelParams($params))?>				
					<?php $this->renderScreen("taskpanel", $bds->getTaskPanelParams($params))?>				
				</div>
				<div id='dashboard-stats'>
					<?php $this->renderScreen("statspanel", $bds->getStatsPanelParams($params))?>				
				</div>
				<div id='dashboard-graph'>
					<?php $this->renderScreen("graphpanel", $bds->getGraphPanelParams($params))?>				
				</div>
			</div>	
		</div>
		<?php 
	}
	
	
	
}