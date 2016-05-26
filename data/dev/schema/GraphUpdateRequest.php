<?php

class GraphUpdateRequest extends LDOUpdate {
	
	function importsChanged(){
		$a = $this->importsAdded();
		if(count($a) > 0) return true;
		$b = $this->importsDeleted();
		if(count($b) > 0) return true;
		return false;
	}
	
	function importsAdded(){
		$oimports = isset($this->original->meta['imports']) ? $this->original->meta['imports'] : array();
		$nimports = isset($this->changed->meta['imports']) ? $this->changed->meta['imports'] : array();
		$adds = array();
		foreach($nimports as $nimp){
			if(!in_array($nimp, $oimports)){
				$adds[] = $nimp;
			}
		}
		return $adds;
	}
	function importsDeleted(){
		$oimports = isset($this->original->meta['imports']) ? $this->original->meta['imports'] : array();
		$nimports = isset($this->changed->meta['imports']) ? $this->changed->meta['imports'] : array();
		$dels = array();
		foreach($oimports as $oimp){
			if(!in_array($oimp, $nimports)){
				$dels[] = $oimp;
			}
		}
		return $dels;
	}

}