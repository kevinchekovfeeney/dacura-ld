<?php
require_once("LDDocument.php");

/*
 * A candidate is basically an ld document with a bunch of dacura state management information tagged on
 */

class Candidate extends LDDocument {
	//maps to candidates db structure
	var $cid;
	var $did;
	var $type;
	var $version;
	var $latest_version;
	var $type_version;
	var $status;
	var $report_id;
	var $created;
	var $modified;
	var $schema;
	
	function __construct($id){
		parent::__construct($id);
		$this->created = time();
		$this->modified = time();
	}
	
	function setContext($cid, $did){
		$this->cid = $cid;
		$this->did = $did;
	}
	
	function loadSchema($base_url){
		$this->schema = new Schema($this->cid, $this->did, $base_url);
		$this->cwurl = $this->schema->instance_prefix.$this->id;
	}
	
	function expandNS(){
		$this->expandNamespaces($this->contents);
	}

	function compressNS(){
		$this->applyNamespaces($this->contents);
	}
	
	function html($service, $vstr){
		$params = array();
		$params['id'] = $this->applyLinkHTML("local:".$this->id, $vstr);
		$params['type'] = $this->applyLinkHTML($this->type, $vstr);
		$params['label'] = $this->getLabel();
		$cnts = $this->getCandidateContents();
		$params['contents'] = $cnts ? $this->getPropertiesAsHTMLTable($vstr, $cnts) : ""; ;
		$params['provenance'] = "";
		$params['annotation'] = "";
		$c = 0;
		if(isset($this->contents['provenance'])){
			foreach($this->contents['provenance'] as $id => $prov){
				$params['provenance'] .= "<div class='provenance-record'>Provenance record $id</div>";
				$params['provenance'] .= $this->getPropertiesAsHTMLTable($vstr, $this->contents['provenance'][$id], 0, "p".$c++);
			}
		}
		$c = 0;
		if(isset($this->contents['annotation'])){
			foreach($this->contents['annotation'] as $id => $ann){
				$params['annotation'] .= "<div class='annotation-record'>Annotation record $id</div>";
				$params['annotation'] .= $this->getPropertiesAsHTMLTable($vstr, $this->contents['annotation'][$id], 0, "a".$c++);
			}
		}
		return $service->renderScreenAsString("html", $params);
	}
	
	function getPropertiesAsHTMLTable($vstr, $props, $depth = 0, $obj_id_prefix = ""){
		if($depth % 2 == 1){
			$cls_extra = "even_depth";
		}
		else {
			$cls_extra = "odd_depth";
		}
		if(!is_array($props)){
			return "$props is not an array of properties";
		}
		$html = "<table class='ld-properties emb-$depth $cls_extra'>";
		if($depth == 0) $html .= "<tr class='$cls_extra'><th class='prop-ph $cls_extra'>Property</th><th class='prop-vh $cls_extra'>Value</th></tr>";
		$depth = $depth+1;
		$pcount = 0;
		foreach($props as $p => $v){
			$pcount++;
			$np = $this->applyLinkHTML($p, $vstr, true);
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->literal()){
				if(isURL($v) || isNamespacedURL($v)){
					$nv = $this->applyLinkHTML($v, $vstr);
				}
				else {
					$nv = $this->applyLiteralHTML($v);
				}
				$html .= "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>$nv</td></tr>";
				
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isURL($val) || isNamespacedURL($val)){
						$nv[] = $this->applyLinkHTML($val, $vstr);
					}
					else {
						$nv[] = $this->applyLiteralHTML($val, $vstr);
					}
					$html .= "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>".explode(", ", $nv)."</td></tr>";
				}
			}
			elseif($pv->embeddedlist()){
				$count = 0;
				foreach($v as $id => $obj){
					$nid = $this->applyLinkHTML($id, $vstr);
					$obj_id = $obj_id_prefix."_".$depth."_".$pcount."_".$count;
					if($count == 0){
						$html .= "<tr class='firstp'><td class='prop-pd p-embedded $cls_extra'>$np</td><td class='prop-pv $cls_extra'><div id='$obj_id' class='pidembedded embobj_id $cls_extra'>$nid</div></td></tr>";
					}
					else {
						$html .= "<tr><td class='prop-pd prop-empty $cls_extra'>&nbsp;</td><td class='prop-pv prop-embedded $cls_extra'><div  id='$obj_id' class='pidembedded $cls_extra'>";
						//ids should always be URLs or namespaced URLs
						$html .= $nid."</div></td></tr>";
					}
					$count++;
					$html .= "<tr id='$obj_id"."_objrow' class='embedded-object'><td class='container' colspan='2'>";
					$html .= $this->getPropertiesAsHTMLTable($vstr, $obj, $depth, $obj_id_prefix);
					$html .= "</tr>";
				}
			}
		}	
	
		$html .= "</table>";
		return $html;
	}
	
	function applyLinkHTML($ln, $vstr, $is_prop = false){
		if($is_prop){
			$cls = "dacura-property";
		}
		else {
			$cls = "dacura-property-value";
		}
		if(isURL($ln)){
			if($this->isDocumentLocalLink($ln)){
				$cls .= " document_local_link";
				$lh = "<a class='$cls' href='$ln".$vstr."'>$ln</a>";
			}
			else {
				$lh = "<a class='$cls' href='$ln'>$ln</a>";
			}
		}
		elseif(isNamespacedURL($ln)){
			if($this->isDocumentLocalLink($ln)){
				$cls .= " document_local_link";
				$expanded = $this->schema->expand($ln);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace'>$ln</span>";
				}
				else {
					$lh = "<a class='$cls' href='$expanded".$vstr."'>$ln</a>";
				}
			
			}
			else {
				$expanded = $this->schema->expand($ln);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace'>$ln</span>";
				}
				else {
					$lh = "<a class='$cls' href='$expanded'>$ln</a>";
				}
			}
		}
		else {
			$lh = "<span class='$cls not-a-url'>$ln</span>";
		}
		return $lh;
	}
	
	function applyLiteralHTML($ln){
		return "<span class='dacura-property-value dacura-literal'>$ln</span>";
	}
	
	function getCandidateContents(){
		if(isset($this->contents['candidate']["local:".$this->id."/candidate"])){
			return $this->contents['candidate']["local:".$this->id."/candidate"];
		}
		else {
			$expanded = $this->schema->expand("local:".$this->id."/candidate");
			if($expanded && isset($this->contents['candidate'][$expanded])){
				return $this->contents['candidate'][$expanded];	
			}
		}
		return false;
	}
	
	function getLabel(){
		$props = $this->getCandidateContents();
		if(!$props) return false;
		foreach($props as $p => $v){
			if($p == "rdfs:label" or $p == "dc:title"){
				return $v;
			}
		}
		return false;
	}
	
	function setContentsToFragment($fragment_id){
		$this->contents = getFragmentInContext($fragment_id, $this->contents, $this->cwurl);
	}
	
	function getFragmentPaths($fid, $html = false){
		$paths = getFragmentContext($fid, $this->contents, $this->cwurl);
		return $paths;
	}
	
	
	function linkify($vstr, $props=false){
		if($props === false){
			$props = $this->contents;
		}
		$nprops = array();
		foreach($props as $p => $v){  
			//properties should always be URLs or namespaced URLs
			$np = $this->applyLinkHTML($p, $vstr, true);	
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->literal()){
				if(isURL($v) || isNamespacedURL($v)){
					$nv = $this->applyLinkHTML($v, $vstr);	
				}
				else {
					$nv = $this->applyLiteralHTML($v);
				}
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isURL($val) || isNamespacedURL($val)){
						$nv[] = $this->applyLinkHTML($val, $vstr);
					}
					else {
						$nv[] = $this->applyLiteralHTML($val);
					}
				}
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					//ids should always be URLs or namespaced URLs
					$nid = $this->applyLinkHTML($id, $vstr);
					$nv[$nid] = $this->linkify($vstr, $obj);	
				}
			}
			else {
				$nv = $v;
			}
			$nprops[$np] = $nv;			
		}
		return $nprops;
	}
	
	function expandNamespaces(&$props){
		foreach($props as $p => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->link() && isNamespacedURL($v) && ($expanded = $this->schema->expand($v))){
				$nv = $expanded;
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isNamespacedURL($val) && ($expanded = $this->schema->expand($val))){
						$nv[] = $expanded;
					}
					else {
						$nv[] = $val;
					}
				}
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					if(isNamespacedURL($id) && ($expanded = $this->schema->expand($id))){
						$nv[$expanded] = $obj;
						$this->expandNamespaces($nv[$expanded]);
					}
					else {
						$nv[$id] = $obj;
						$this->expandNamespaces($nv[$id]);
					}
				}
			}
			else {
				$nv = $v;
			}
			if(isNamespacedURL($p) && ($expanded = $this->schema->expand($p))){
				unset($props[$p]);
				//echo "expanding $p $"
				$props[$expanded] = $nv;
			}
			else {
				$props[$p] = $nv;
			}
		}
	}
	
	function applyNamespaces(&$props){
		foreach($props as $p => $v){
			//first compress property values
			$pv = new LDPropertyValue($v);
			if($pv->link() && ($compressed = $this->schema->compress($v))){
				$nv = $compressed;
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isURL($val) && ($compressed = $this->schema->compress($val))){
						$nv[] = $compressed;
					}
					else {
						$nv[] = $val;
					}
				}
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					if(isURL($id) && ($compressed = $this->schema->compress($p))){
						$nv[$compressed] =& $obj;
					}
					else {
						$nv[$id] =& $obj;
					}
					$this->applyNamespaces($obj);
				}
			}
			else {
				$nv = $v;
			}
			//then compress properties
			if(isURL($p) && ($compressed = $this->schema->compress($p))){
				unset($props[$p]);
				$props[$compressed] = $nv;
			}
			else {
				$props[$p] = $nv;
			}
		}
	}
	
	function setSchema($schema){
		$this->schema = $schema;
	}
	
	function version(){
		return $this->version;
	}
	
	function get_class(){
		return $this->type;
	}
		
	function get_class_version(){
		return $this->type_version;
	}
	
	function get_status(){
		return $this->status;
	}
	
	function get_report(){
		return $this->report_id;
	}
	
	function setAnnotation($an){
		$this->contents['annotation'] = $an;
	}

	function setProvenance($s){
		$this->contents['provenance'] = $s;
	}
	
	function reportString(){
		return "Not yet implemented";
	}
	
	function getAgentKey(){
		return true;
		//$ag = $this->prov->getAgent("dacura:dacuraAgent");
		//if(!$ag)
		//{
		//	return false;
		//}
		//return true;
	}
	
	function set_version($v, $is_latest = false){
		$this->version = $v;
		if($is_latest){
			$this->latest_version = $v;
		}
	}
	
	function set_class($c, $v){
		$this->type = $c;
		$this->type_version = $v;
	}
	
	function set_report($r){
		$this->report = $r;
	}

	
	/**
	 * Called when the object is loaded from the database
	 * @param unknown $cand
	 * @param string $source
	 * @param string $note
	 * @return mixed
	 */
	function loadFromJSON($cand, $source = false, $note = false){
		if($source){
			$this->contents['provenance'] = json_decode($source, true);
		}
		if($note){
			$this->contents['annotation'] = json_decode($note, true);
		}
		$this->contents['candidate'] = json_decode($cand, true);
		$this->buildIndex();
		return ($this->contents['provenance'] && $this->contents['candidate']);
	}
}

