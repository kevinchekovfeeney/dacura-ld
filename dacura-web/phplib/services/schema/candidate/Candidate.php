<?php

class Candidate extends LDO {
	//maps to candidates db structure

	/*
	 * Called to hide whatever internal parts of the object we do not wish to send as json through the api
	 */
	function getDisplayFormat() {
		$other = clone($this);
		unset($other->index);
		unset($other->errmsg);
		return $other;
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
	
	function displayHTML($flags, $vstr, $srvr){
		$this->display = "";
		foreach($this->ldprops as $g => $ldprops){
			$this->display .= "<h2>Graph $g</h2>";
			foreach($ldprops as $sub => $props){
				$rdft = $this->extractTypeFromProps($props);
				if($rdft){
					if(is_array($rdft)) {
						$sub .= " " . implode(", ", $rdft);
					}
					else {
						$sub .= " " . $rdft;
					}
					unset($props['rdf:type']);
				}
				$this->display .= "<h3>$sub</h3>".$this->getPropertiesAsHTMLTable($vstr, $props);				
			}
		}
	}
	
	function displayQuads($flags, $vstr, $srvr){
		$quads = array();
		foreach($this->ldprops as $g => $props){
			$quads = array_merge($this->getPropertyAsQuads($g, $g));
		}
		$this->display = $quads;
	}
	
}

