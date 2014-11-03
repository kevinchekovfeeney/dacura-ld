<?php
//include_once("SourcesDacuraServer.php");

class SourcesService extends DacuraService {
	//cid/did/users/userid

	function handlePageLoad(){



		if(count($this->servicecall->args)> 0) {
			foreach ( $this->servicecall->args as $field ) {
					
				if ($field=="AddDataSource") {
					$sourcesid = array_shift($this->servicecall->args);
					$this->renderScreen("AddDataSource", array("userid" => $sourcesid));
					break;
				}elseif ($field=="ViewDataSources") {
					$sourcesid = array_shift($this->servicecall->args);
					$this->renderScreen("ViewDataSources", array("userid" => $sourcesid));
					break;
				}elseif ($field=="DataSourceDetails") {
					$sourcesid = array_shift($this->servicecall->args);
					$this->renderScreen("DataSourceDetails", array("userid" => $sourcesid));
					break;
				}
					
			}
		}
		else {
				
			$sourcesid = array_shift($this->servicecall->args);
			$this->renderScreen("ViewDataSources", array("userid" => $sourcesid));
				
		}
		//parent::handlePageLoad();
	}

}
