<?php
require_once("EntityUpdate.php");

class GraphUpdateRequest extends EntityUpdate {
	function isGraph(){
		return true;
	}
	
	function importsChanged(){
		$oimports = isset($this->original->meta['imports']) ? $this->original->meta['imports'] : array();
		$nimports = isset($this->changed->meta['imports']) ? $this->changed->meta['imports'] : array();
		foreach($oimports as $oimp){
			if(!in_array($oimp, $nimports)){
				return true;
			}
		}
		foreach($nimports as $nimp){
			if(!in_array($nimp, $oimports)){
				return true;
			}
		}
		return false;
	}

}
