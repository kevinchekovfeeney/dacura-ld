<?php
require_once("LDDocument.php");


/*
 * The basic variables required by the editor
 */
class LDEntity extends LDDocument {
	var $cid;
	var $did;
	var $type;
	var $version;
	var $latest_version;
	var $status;
	var $latest_status;
	var $created;
	var $modified;
	var $nsres;
	
	function __construct($id, $cwbase = false){
		parent::__construct($id);
		$this->created = time();
		$this->modified = time();
		if($cwbase){
			$this->cwurl = $cwbase."/".$id;
		}
		else {
			$this->cwurl = false;
		}
	}
	
	function isLatestVersion(){
		return $this->version == $this->latest_version;
	}
	
	function setContext($cid, $did){
		$this->cid = $cid;
		$this->did = $did;
	}
	
	function displayJSON($flags, $vstr, $srvr){
		$this->display = $this->ldprops;
	}
	
	function displayExport($format, $flags, $vstr, $srvr){
		$exported = $this->export($format);
		if(!$exported){
			return false;
		}
		if($format != "svg" && $format != "dot" && $format != "png" && $format != "gif"){
			$this->display = htmlspecialchars($exported);
		}
		else {
			if($format == "png" or $format == "gif"){
				$this->display = '<img src="data:image/png;base64,'.base64_encode ( $exported).'"/>';
			}
			else {
				$this->display = $exported;
			}
		}
	}
	
	function displayTriples($flags, $vstr, $srvr){
		$this->display = "<h2>need to write display triples</h2>";
	}
	function displayQuads($flags, $vstr, $srvr){
		$this->display = "<h2>need to write display Quads</h2>";
	}
	function displayHTML($flags, $vstr, $srvr){
		$this->display = "<h2>need to write display HTML</h2>";
	}
	
	function setNamespaces($nsres){
		$this->nsres = $nsres;
	}
	
	function expandNS(){
		parent::expandNamespaces($this->nsres);
	}
	
	function compressNS(){
		parent::compressNamespaces($this->nsres);
	}
	
	function getNS(){
		parent::getNamespaces($this->nsres);
	}
	
	function export($format){
		return parent::export($format, $this->nsres);
	}
	
	
	
	function getTypedQuads($gname){
		$triples = $this->typedTriples();
		$quads = array();
		if(count($triples) > 0){
			foreach($triples as $trip){
				$trip[] = $gname;
				$quads[] = $trip;
			}
		}
		return $quads;
	}
	
	

	function getFragIDForExtension($f, $ext){
		if(isset($this->ldprops[$f][$this->cwurl."/".$ext])){
			return $this->cwurl."/".$ext;
		}
		if(isset($this->ldprops[$f]["local:".$this->id."/".$ext])){
			return "local:".$this->id."/".$ext;
		}
		if(isset($this->ldprops[$f]["_:".$ext])){
			return "_:".$ext;
		}
		return false;
	}
	
	function getMetaFragID(){
		return $this->getFragIDForExtension("meta", "meta");
	}
	
	function &getMeta(){
		$fid = $this->getMetaFragID();
		if($fid){
			return $this->ldprops['meta'][$fid];
		}
		return $fid;
	}
	
	/*
	 * Some state may be duplicated between the meta ld field and the object properties
	 * In such cases the meta field is authoritative (as it is part of state-management)
	 */
	function readStateFromMeta(){
		$meta = $this->getMeta();
		if($meta && isset($meta['status'])){
			$this->status = $meta['status'];
		}
	}
	
	function version(){
		return $this->version;
	}
	

	function get_status(){
		return $this->status;
	}
	
	function set_version($v, $is_latest = false){
		$this->version = $v;
		if($is_latest){
			$this->latest_version = $v;
		}
	}
	
	function set_status($v, $is_latest = false){
		$this->status = $v;
		$meta = &$this->getMeta();
		if(!$meta === false){
			$this->ldprops["meta"] = array($this->cwurl."/meta" => array("status" => $v));
		}
		else {
			$meta['status'] = $v;
		}
		if($is_latest){
			$this->latest_status = $v;
		}
	}
	
	function linkify($vstr, $props=false){
		if($props === false){
			$props = $this->ldprops;
		}
		$nprops = array();
		foreach($props as $p => $v){
			//properties should always be URLs or namespaced URLs
			$np = $this->applyLinkHTML($p, $vstr, true);
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->literal() or $pv->objectliteral()){
				$nv = $this->applyLiteralHTML($v);
			}
			elseif($pv->link()){
				$nv = $this->applyLinkHTML($v, $vstr);
			}
			elseif($pv->valuelist() or $pv->objectliterallist()){
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
					$lh = "<span class='$cls unknown-namespace' title='warning: unknown namespace'>$ln</span>";
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
			elseif($pv->objectliteral()){
				$nv = $this->applyLiteralHTML($v);
				$html .= "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>$nv</td></tr>";
			}
			elseif($pv->objectliterallist()){
				$nv = array();
				foreach($v as $val){
					$nv[] = $this->applyLiteralHTML($val, $vstr);
				}
				$html .= "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>".explode(", ", $nv)."</td></tr>";
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
	
	
	function applyLiteralHTML($ln){
		if(is_array($ln)){
			$html = "<span class='dacura-property-value dacura-objectliteral'>";
			foreach($ln as $k => $v){
				$html .= "<span class='dacura-objectliteral-index>$k</span> <span class='dacura-objectliteral-value'>$v</span> ";
			}
			$html .= "</span>";
		}
		else {
			$html = "<span class='dacura-property-value dacura-literal'>$ln</span>";
		}
		return $html;
	}
	
	function decorated(){
		return $this->ldprops;
	}
	
	function decoratedTriples(){
		return array();
	}
	
	
	function linkifyTriples(&$trips, $alink, $vstr){
		foreach($trips as $i => $v){
			foreach($v as $j => $k){
				if(is_array($k)){
					$nv = json_encode($k);
				}
				elseif((isURL($k) || isNamespacedURL($k))){
					if($alink){
						$k = $this->applyLinkHTML($k, $vstr, $j==1);
					}
					$nv = "&lt;".$k."&gt;";
				}
				elseif($alink) {
					if($j == 1 or $j == 0){
						$nv = $this->applyLinkHTML($k, $vstr, $j==1);
					}
					else {
						$nv = '"'.$this->applyLiteralHTML($k).'"';
					}
				}
				else {
					$nv = '"'.$k.'"';
				}
				$trips[$i][$j] = $nv;
			}
		}
	}
	
	/*
	 */
	function compare($other){
		$delta = compareLDGraphs($this->id, $this->ldprops, $other->ldprops, $this->cwurl, true);
		if($delta->containsChanges()){
			$delta->setMissingLinks($this->missingLinks(), $other->missingLinks());
		}
		return $delta;
	}
	

	
	
	
}