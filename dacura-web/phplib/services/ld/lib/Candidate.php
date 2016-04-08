<?php 
class Candidate extends LDO {

	function loadFromDBRow($row, $latest = true){
		parent::loadFromDBRow($row);
		if(count(array_keys($this->ldprops)) > 1){
			$this->multigraph = true;
		}
	}
	
	function importFromDacuraJSON($json, $srvr){
		$vgraphs = $srvr->getValidGraphURLs();
		$graphs = array();
		foreach($vgraphs as $pgurl){
			if(isset($json[$pgurl])){
				$graphs[$pgurl] = $json[$pgurl];
				unset($json[$pgurl]);
			}							
		}
		if(count($graphs) == 0 && count($json) > 0){
			$this->ldprops = array($this->cwurl => $json);
		}
		elseif(count($json) > 0 && !isset($json[$this->cwurl])){
			$graphs[$srvr->getDefaultGraphURL()] = array($this->cwurl => $json);
		}
		elseif(isset($graphs[$srvr->getDefaultGraphURL()]) ){
			if(!isset($graphs[$srvr->getDefaultGraphURL()][$this->cwurl])){
				$graphs[$srvr->getDefaultGraphURL()] = array($this->cwurl => $graphs[$srvr->getDefaultGraphURL()]);
			}
		}
		if(count($graphs) > 0){
			$this->ldprops = $graphs;
			$this->multigraph = true;
		}
	}
	
	function typedQuads($graphid = false){
		if($this->is_multigraph()){
			if($graphid && isset($this->ldprops[$graphid])){
				return getPropsAsTypedQuads($graphid, $this->ldprops[$graphid], $this->rules);				
			}
			elseif($graphid === false){
				$quads = array();
				foreach($this->ldprops as $gid => $gbits){
					$quads = array_merge($quads, getPropsAsTypedQuads($gid, $gbits, $this->rules));
				}
				return $quads;
			}
		}
		else {
			return parent::typedQuads($graphid);
		}
	}
	
}