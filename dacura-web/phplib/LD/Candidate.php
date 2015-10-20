<?php
require_once("LDEntity.php");

/*
 * A candidate is basically an ld document with a schema and 
 * a bunch of dacura state management information tagged on
 */

class Candidate extends LDEntity {
	//maps to candidates db structure
	var $type;
	var $type_version;
	var $replaced; //when this version was replaced

	/*
	 * Called to hide whatever internal parts of the object we do not wish to send as json through the api
	 */
	function getDisplayFormat() {
		$other = clone($this);
		unset($other->index);
		unset($other->dacura_props);
		unset($other->implicit_add_to_valuelist);
		unset($other->errmsg);
		$other->label = $other->getLabel() ? $other->getLabel() : $other->id;
		return $other;
	}
		
	function getObjectType($obj){
		if(!isset($obj['rdf:type']) and !isset($obj[$this->nsres->getURL("rdf")])){
			return false;
		}
		return isset($obj['rdf:type']) ? $obj['rdf:type'] : $obj[$this->nsres->getURL("rdf")];
	}
	
	function get_class_version(){
		return $this->type_version;
	}
	
	function reportString(){
		return "Not yet implemented";
	}
	
	function set_class($c, $v){
		$this->type = $c;
		$this->type_version = $v;
	}
	
	
	/**
	 * Functions for producing various different representations of the candidate
	 */

	function triples($use_ns = false){
		$triples = parent::triples();
		foreach($triples as $i => $trip){
			if($trip[0] == $this->id){
				$triples[$i][0] = ($use_ns) ? "local:".$this->id : $this->cwurl;
			}
		}
		return $triples;
	}
	

	function jazzUpTriples($s, $p, $o, $t, $g = false){
		if($s == $this->id){
			$s = "local:".$this->id;
		}
		else {
			$sc = $this->nsres->compress($s);
			$s = $sc ? $sc : $s;
		}
		if(in_array($p, $this->dacura_props) or $p == "status"){
			$p = "dacura:". $p;
		}
		else {
			$pc = $this->nsres->compress($p);
			$p = $pc ? $pc : $p;
		}
		if($t == 'literal'){
			$o = '"'.$o.'"';
		}
		else {
			$oc = $this->nsres->compress($o);
			$o = $oc ? $oc : $o;				
		}
		return array(array($s, $p, $o));		
	}
	

	function typedTriples($use_ns = false, $use_dacura_ns = true){
		$triples = parent::typedTriples();
		if($use_dacura_ns){
			foreach($triples as $i => $trip){
				if($trip[0] == $this->id){
					$triples[$i][0] = ($use_ns) ? "local:".$this->id : $this->cwurl;
				}
			}
		}
		return $triples;
	}
	
		
	function html($service, $vstr){
		$params = array();
		$params['id'] = $this->applyLinkHTML($this->cwurl, $vstr);
		$params['type'] = $this->applyLinkHTML($this->type, $vstr);
		$params['label'] = $this->getLabel();
		$cnts = $this->getCandidateContents();
		$params['contents'] = $cnts ? $this->getPropertiesAsHTMLTable($vstr, $cnts) : "";
		$meta = &$this->getCandidateMeta();
		$params['meta'] = $meta ? $this->getPropertiesAsHTMLTable($vstr, $meta) : "";
		$params['provenance'] = "";
		$params['annotation'] = "";
		$c = 0;
		if(isset($this->ldprops['provenance'])){
			foreach($this->ldprops['provenance'] as $id => $prov){
				$params['provenance'] .= "<div class='provenance-record'>Provenance record $id</div>";
				$params['provenance'] .= $this->getPropertiesAsHTMLTable($vstr, $this->ldprops['provenance'][$id], 0, "p".$c++);
			}
		}
		$c = 0;
		if(isset($this->ldprops['annotation'])){
			foreach($this->ldprops['annotation'] as $id => $ann){
				$params['annotation'] .= "<div class='annotation-record'>Annotation record $id</div>";
				$params['annotation'] .= $this->getPropertiesAsHTMLTable($vstr, $this->ldprops['annotation'][$id], 0, "a".$c++);
			}
		}
		return $service->renderScreenAsString("html", $params);
	}
	
	function getLabel(){
		$props = $this->getCandidateContents();
		if(!$props) return false;
		foreach($props as $p => $v){
			if($this->nsres->match($p, "rdfs", "label") or $this->nsres->match($p, "dc", "title")){
				return $v;
			}
		}
		return false;
	}
}

