<?php 
class LDODisplay extends DacuraObject {
	var $ldo;
	var $options;
	
	function __construct($ldo, $options){
		$this->ldo = $ldo;
		$this->options = $options;
	}
	
	function display($format){
		if(!$format || $format == "json"){
			return $this->displayJSON();
		}
		elseif($format == "html"){
			return $this->displayHTML();
		}
		elseif($format == "triples"){
			return $this->displayTriples();
		}
		elseif($format == "quads"){
			return $this->displayQuads();
		}
		elseif($format == "jsonld"){
			return $this->displayJSONLD();
		}
		elseif($format == "nquads"){
			return $this->displayNQuads();
		}
		else {
			return $this->displayExport($format);
		}		
	}
	
	function displayTriples(){
		$payload = isset($this->options['typed']) && $this->options['typed'] ? $this->ldo->typedTriples() : $this->ldo->triples();
		if(!isset($this->options['plain']) || !$this->options['plain']){
			$this->linkifyTriples($payload, true, $this->getLinkExtras("triples"));
		}
		return $payload;
	}
	
	function displayQuads(){
		$payload = isset($this->options['typed']) && $this->options['typed'] ? $this->ldo->typedQuads() : $this->ldo->quads();
		if(!isset($this->options['plain']) || !$this->options['plain']){
			$this->linkifyTriples($payload, true, $this->getLinkExtras("quads"));
		}
		return $payload;
	}
	
	function displayJSON(){
		if(isset($this->options['plain']) && $this->options['plain']){
			return $this->ldo->ldprops;
		}
		else {
			return $this->linkify($this->getLinkExtras("json"), $this->ldo->ldprops);				
		}
	}
	
	function displayJSONLD(){
		require_once("JSONLD.php");
		$ns =  isset($this->options['ns']) && $this->options['ns'] ? $this->ldo->getNS() : false;
		$jsonld = toJSONLD($this->ldo->ldprops, $ns, $this->ldo->cwurl, $this->ldo->is_multigraph());
		if(isset($this->options['plain']) && $this->options['plain']){
			return $jsonld;
		}
		else {
			return $jsonld;
			//return $this->linkify($this->getLinkExtras("jsonld"), $jsonld);
		}
	}
	
	function displayHTML(){
		$html = "";
		foreach($this->ldo->ldprops as $k => $v){
			$nk = $this->applyLinkHTML($k, $this->getLinkExtras("html"), "property-subject");
			$html .= "<h3>$nk</h3>".$this->getPropertiesAsHTMLTable($v, $this->options);
		}
		return $html;
	}
	
	function displayNQuads(){
		$payload = $this->ldo->nQuads();
		return htmlspecialchars($payload);
	}
	
	function displayExport($format){
		$exported = $this->ldo->export($format, $this->options);
		if($exported === false){
			return false;
		}
		if($format != "svg" && $format != "dot" && $format != "png" && $format != "gif"){
			return htmlspecialchars($exported);
		}
		else {
			if($format == "png" or $format == "gif"){
				return '<img src="data:image/png;base64,'.base64_encode ( $exported).'"/>';
			}
			else {
				return $exported;
			}
		}
	}
	
	function showLDOViewer($params, $service){
		return $service->renderScreen("editor", $params, "ld");		
	}
	
	function getLinkExtras($format){
		$str = "?format=$format";
		foreach($this->options as $opt => $v){
			$str .= "&option[$opt]=$v";
		}
		return $str;
	}
	
	function linkify($vstr, $props){
		$nprops = array();
		if($props && is_array($props)) {
			foreach($props as $s => $ldobj){
				$ns = $this->applyLinkHTML($s, $vstr, "property-subject");
				$nprops[$ns] = array();
				foreach($ldobj as $p => $v){
					//properties should always be URLs or namespaced URLs
					$np = $this->applyLinkHTML($p, $vstr, "property");
					$pv = new LDPropertyValue($v, $this->ldo->cwurl);
					if($pv->literal() or $pv->objectliteral()){
						$nv = $this->applyLiteralHTML($v);
					}
					elseif($pv->link()){
						$nv = $this->applyLinkHTML($v, $vstr, "property-value");
					}
					elseif($pv->valuelist() or $pv->objectliterallist()){
						$nv = array();
						foreach($v as $val){
							if(isURL($val) || isNamespacedURL($val)){
								$nv[] = $this->applyLinkHTML($val, $vstr, "property-value");
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
							$nid = $this->applyLinkHTML($id, $vstr, "property-subject");
							$nv[$nid] = $this->linkify($vstr, $obj);
						}
					}
					else {
						$nv = $v;
					}
					$nprops[$ns][$np] = $nv;
				}
			}
		}
		return $nprops;
	}
	
	/**
	 * Is the passed value an id of an internal node in the document?
	 * @param string $val
	 * @return boolean true if the value is an internal link
	 */
	function documentLocalLink($val, $vstr, $durl){
		if($this->ldo->version != $this->ldo->latest_version){
			$vstr .= "&version=".$this->ldo->version;
		}
		if($val == $this->ldo->cwurl) return $val.$vstr;
		if(isBlankNode($val)) return $this->ldo->cwurl."/".substr($val, 2).$vstr;
		if($this->ldo->ldtype() == "ontology"){
			$ourl = isset($this->ldo->meta['url']) ? $this->ldo->meta['url'] : $this->ldo->cwurl;
			if(substr($val, 0, strlen($ourl)) == $ourl) {
				$str = $durl . ($this->ldo->cid() == "all" ? "" : $this->ldo->cid()."/")."ontology/".$this->ldo->id;
				return $str.$vstr."#".substr($val, strlen($ourl));
			}
		}
		if(isInternalLink($val, $this->ldo->cwurl)){
			return $val.$vstr;
		}
		return false;
	}
	
	function dacuraLink($ln, $vstr, $position){
		global $dacura_server;
		if(isNamespacedURL($ln)){
			if($this->ldo->ldtype() == "ontology" && getNamespacePortion($ln) == $this->ldo->id){
				if($this->ldo->version != $this->ldo->latest_version){
					$vstr .= "&version=". $this->ldo->version;
				}
				$lnkln = $this->ldo->cwurl.$vstr."#".getPrefixedURLLocalID($ln);
				$html = ($position == "property-subject" ? "<a name='".getPrefixedURLLocalID($ln)."'>" : "");
				$html .="<a class='dacura-$position document-local-link' title='$position: local link to $lnkln' href='$lnkln'>$ln</a>";				
				return $html;
			}
			elseif($expanded = $this->ldo->nsres->expand($ln)){
				$ext = $this->ldo->prefixToLocalOntologyURL(getNamespacePortion($ln));
				if($ext){
					$lnkln = $dacura_server->durl().$ext.$vstr."#".getPrefixedURLLocalID($ln);
					$tpid = "ontology ".getPrefixedURLLocalID($ln);				
					return "<a class='dacura-$position' title='$position: local link to $tpid' href='$lnkln'>$ln</a>";
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		}
		if(isURL($ln) || isBlankNode($ln)){
			if($lnkln = $this->documentLocalLink($ln, $vstr, $dacura_server->durl())){
				if(($x = strpos($lnkln, "#")) && ($position == "property-subject") && ($frag = substr($lnkln, $x + 1))){
					$html = "<a name='$frag'>";
				}
				else {
					$html = "";
				}
				$html .= "<a class='dacura-$position document-local-link' title='$position: local link to $lnkln' href='$lnkln'>$ln</a>";
				return $html;
			}
			elseif($compressed = $this->ldo->nsres->compress($ln)){
				$ext = $this->ldo->prefixToLocalOntologyURL(getNamespacePortion($compressed));
				if($ext){
					$lnkln = $dacura_server->durl().$ext.$vstr."#".getPrefixedURLLocalID($compressed);
					$tpid = "ontology ".getNamespacePortion($compressed)."#".getPrefixedURLLocalID($compressed);
					return "<a class='dacura-$position' title='$position: local link to $tpid' href='$lnkln'>$ln</a>";
				}
				else {
					return false;
				}			
			}
			elseif(!($parsed_url = $dacura_server->parseDacuraURL($ln))){
				return false;
			}
			else {
				if($parsed_url['collection'] = $this->ldo->cid()){
					$cls = "dacura-$position collection-local-link";
				}
				else {
					$cls = "dacura-$position collection-link";
				}
			}
			if(in_array($parsed_url['service'], array("ontology", "ld", "graph", "candidate")) && isset($parsed_url['args']) && count($parsed_url['args']) > 0){
				$tpid = $parsed_url['service'] . " " . implode("/", $parsed_url['args']);
				return "<a class='$cls' title='$position: local link to $tpid' href='$ln"."$vstr'>$ln</a>";
			}	
			else {
				$sv = $parsed_url['service'];
				return "<a class='$cls' title='$position: local link to $sv service' href='$ln'>$ln</a>";
			}
		}
	}
	
	function applyLinkHTML($ln, $vstr, $position){
		if(!($lh = $this->dacuraLink($ln, $vstr, $position))){
			$cls = "dacura-".$position;//property-subject, property, property-value, property-graph 
			if(isURL($ln)){
				$lh = "<a class='$cls remote-link' title='$position Remote Link to $ln' href='$ln'>$ln</a>";
			}
			elseif(isNamespacedURL($ln)){
				$expanded = $this->ldo->nsres->expand($ln);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace' title='$position: Broken Link to unknown namespaced link: $ln'>$ln</span>";
				}
				else {
					$lh = "<a class='$cls remote-link' title='$position: Remote link to $ln' href='$expanded'>$ln</a>";
				}
			}
			else {
				$lh = "<span title='$position: Broken link to $ln - not a url' class='$cls not-a-url'>$ln</span>";
			}
		}		
		return $lh;
	}
	

	function getPropertiesAsHTMLTable($props, $options = array(), $depth = 0, $obj_id_prefix = ""){
		$vstr = $this->getLinkExtras("html");
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
		//if($depth == 0) $html .= "<tr class='$cls_extra'><th class='prop-ph $cls_extra'>Property</th><th class='prop-vh $cls_extra'>Value</th></tr>";
		$depth = $depth+1;
		$pcount = 0;
		$props_html = array();
		foreach($props as $p => $v){
			$pcount++;
			$np = $this->applyLinkHTML($p, $vstr, "property");
			$pv = new LDPropertyValue($v, $this->ldo->cwurl);
			if($pv->literal()){
				if(isURL($v) || isNamespacedURL($v)){
					$nv = $this->applyLinkHTML($v, $vstr, "property-value");
				}
				else {
					$nv = $this->applyLiteralHTML($v);
				}
				array_unshift($props_html, "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>$nv</td></tr>");
			}
			elseif($pv->link()){
				$nv = $this->applyLinkHTML($v, $vstr, "property-value");				
				array_unshift($props_html, "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>$nv</td></tr>");
			}
			elseif($pv->objectliteral()){
				$nv = $this->applyObjectLiteralHTML($v);
				array_unshift($props_html, "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>$nv</td></tr>");
			}
			elseif($pv->objectliterallist()){
				$nv = array();
				foreach($v as $val){
					$nv[] = $this->applyObjectLiteralHTML($val, $vstr);
				}
				array_unshift($props_html, "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>".implode("<br>", $nv)."</td></tr>");
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isURL($val) || isNamespacedURL($val)){
						$nv[] = $this->applyLinkHTML($val, $vstr, "property-value");
					}
					else {
						$nv[] = $this->applyLiteralHTML($val, $vstr);
					}
					array_unshift($props_html, "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>".implode("<br>", $nv)."</td></tr>");
				}
			}
			elseif($pv->embeddedlist()){
				$count = 0;
				foreach($v as $id => $obj){
					$nid = $this->applyLinkHTML($id, $vstr, "property-subject");
					$obj_id = $obj_id_prefix."_".$depth."_".$pcount."_".$count;
					$rdft = $this->extractTypeFromProps($obj);
					if($rdft){
						if(is_array($rdft)) {
							$np .= " " . implode(", ", $rdft);
						}
						else {
							$np .= " " . $rdft;
						}
						unset($obj['rdf:type']);
					}
						
					if($count == 0){
						$props_html[] = "<tr class='firstp'><td class='prop-pd p-embedded $cls_extra'>$np</td><td class='prop-pv $cls_extra'><div id='$obj_id' class='dch pidembedded embobj_id $cls_extra'>$nid</div></td></tr>";
					}
					else {
						$props_html[] = "<tr><td class='prop-pd prop-empty $cls_extra'>&nbsp;</td><td class='prop-pv prop-embedded $cls_extra'><div  id='$obj_id' class='dch pidembedded $cls_extra'>";
						//ids should always be URLs or namespaced URLs
						$props_html[] .= $nid."</div></td></tr>";
					}
					$count++;
					$props_html[] = "<tr id='$obj_id"."_objrow' class='embedded-object'><td class='container' colspan='2'>";
					$props_html[] = $this->getPropertiesAsHTMLTable($obj, $options, $depth, $obj_id_prefix);
					$props_html[] = "</tr>";
				}
			}
			elseif($pv->objectlist()){
				//opr($v);
			}
			elseif($pv->embedded()){
				//opr($v);	
			}
		}
		$html = "<table class='ld-properties emb-$depth $cls_extra'>";
		$html .= implode("", $props_html);
		$html .= "</table>";
		return $html;
	}
	
	function extractTypeFromProps($props){
		if(isset($props['rdf:type'])){
			return $props['rdf:type'];
		}
		return false;
	}
	
	function applyLiteralHTML($ln){
		if(is_array($ln)){
			$html = "<span class='dacura-property-value dacura-objectliteral'>";
			foreach($ln as $k => $v){
				$html .= "<span class='dacura-objectliteral-index'>$k</span>[<span class='dacura-objectliteral-value'>$v</span>]";
			}
			$html .= "</span>";
		}
		else {
			$html = "<span class='dacura-property-value dacura-literal'>$ln</span>";
		}
		return $html;
	}
	
	function applyObjectLiteralHTML($olit){
		$data = "<input value='".$olit['data']."'><button class='update-html-literal'>Update Value</button>";
		$html = "<span class='dacura-property-value dacura-objectliteral'>";
		if(isset($olit['type'])){
			$html .= "<span class='dacura-objectliteral-index>".$olit['type']."</span> <input type='text' class='dacura-objectliteral-value'>$data</span>";
		}
		else {
			$html .= "<span class='dacura-objectliteral-index>".$olit['lang']."</span> <input type='text' class='dacura-objectliteral-value'>$data</span>";				
		}
		return $html;
		//opr($olit);
	}

	function linkifyTriples(&$trips, $alink, $vstr){
		foreach($trips as $i => $v){
			foreach($v as $j => $k){
				if(is_array($k)){
					$nv = json_encode($k);
				}
				elseif((isURL($k) || isNamespacedURL($k))){
					if($alink){
						if($j == 1){
							$pos = "property";
						}
						elseif($j == 0) {
							$pos = "property-subject";
						}
						elseif($j == 2){
							$pos = "property-value"; 
						}
						else {
							$pos = "property-graph";
						}
						$k = $this->applyLinkHTML($k, $vstr, $pos);
					}
					$nv = "&lt;".$k."&gt;";
				}
				elseif($alink) {
					if($j == 1 or $j == 0 or $j == 3){
						if($j == 1){
							$pos = "property";
						}
						elseif($j == 0) {
							$pos = "property-subject";
						}
						else {
							$pos = "property-graph";
						}
						$nv = $this->applyLinkHTML($k, $vstr, $pos);
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
	
	function showTriples($s, $p, $o, $t, $g = false){
		if($t == 'literal'){
			$o = '"'.$o.'"';
		}
		return array(array($s, $p, $o));
	}
}

/*
 *	from ldoupdate 	
	function getChangeViewHTML(){
		return $this->showChanges($this->changed->ldprops, $this->backward);
	}

	function showChanges($props, $dprops){
		$cprops = array();
		foreach($props as $prop => $v){
			$pv = new LDPropertyValue($v, $this->cwurl());
			if($pv->illegal()){
				return $this->failure_result($pv->errmsg, $pv->errcode);
			}
			if(!isset($dprops[$prop])){
				$xprop = $this->applyLinkHTML($prop, "unchanged", true);
				$cprops[$xprop] = $this->getUnchangedJSONHTML($v, $pv);
				//ignore.
			}
			else {
				$nv = $dprops[$prop];
				$dpv = new LDPropertyValue($nv, $this->cwurl());
				if($dpv->isempty()){
					$xprop = $this->applyLinkHTML($prop, "added", true);
					$cprops[$xprop] = $this->getAddedJSONHTML($v, $pv);
				}
				if(!$pv->sameLDType($dpv)){
					$xprop = $this->applyLinkHTML($prop, "structural", true);
					$cprops[$xprop] = $this->getAddedTypeJSONHTML($v, $pv);
					$cprops[$xprop] = array_merge($cprops[$xprop], $this->getDeletedTypeJSONHTML($nv, $dpv));
				}
				else {
					switch($pv->ldtype(true)){
						case 'scalar':
							if($v != $nv){
								$xprop = $this->applyLinkHTML($prop, "updated", true);
								$cprops[$xprop] = $this->getValueChangeHTML($v, $nv);
							}
							else {
								$xprop = $this->applyLinkHTML($prop, "unchanged", true);
								$cprops[$xprop] = $this->applyLiteralHTML($v, "unchanged");
							}
							break;
						case 'valuelist':
							$changed = false;
							$entries = array();
							foreach($v as $i => $val){
								if(in_array($val, $nv)){
									$entries[] = $this->applyLiteralHTML($val, "unchanged");
								}
								else {
									$changed = true;
									$entries[] = $this->applyLiteralHTML($val, "added");
								}
							}
							foreach($nv as $j => $val2){
								if(!in_array($val2, $v)){
									$changed = true;
									$entries[] = $this->applyLiteralHTML($val2, "deleted");
								}
							}
							if($changed){
								$xprop = $this->applyLinkHTML($prop, "changed", true);
							}
							else {
								$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							}
							$cprops[$xprop] = $entries;
							break;
						case 'embeddedobjectlist':
							$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							$cprops[$xprop] = array();
							foreach($v as $id => $obj){
								if(!isset($nv[$id])){
									$tprop = $this->applyLinkHTML($id, "unchanged");
									$cprops[$xprop][$tprop] = $this->getUnchangedJSONHTML($v, $pv);
								}
								else {
									$tprop = $this->applyLinkHTML($id, "unchanged");
									$cprops[$xprop][$tprop]= $this->showChanges($obj, $nv[$id]);
								}
							}
							foreach($nv as $nid => $nobj){
								if(!isset($v[$nid])){
									$tprop = $this->applyLinkHTML($nid, "deleted");
									$cprops[$xprop][$tprop] = $this->getDeletedJSONHTML($v, $pv);
								}
							}
							break;
					}
				}
			}
		}
		foreach($dprops as $dprop => $dv){
			$dpv = new LDPropertyValue($dv, $this->cwurl());
			if(!isset($props[$dprop])){
				$xprop = $this->applyLinkHTML($dprop, "deleted");
				$cprops[$xprop] = $this->getDeletedJSONHTML($v, $pv);
			}
		}
		return $cprops;
	}

	function getValueChangeHTML($v, $nv){
		return array($this->applyLiteralHTML($v, "updated added"), $this->applyLiteralHTML($nv, "updated deleted"));
	}

	function getDeletedTypeJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "deleted structural", $pv);
	}

	function getAddedTypeJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "added structural", $pv);
	}

	function getAddedJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "added", $pv);
	}

	function getDeletedJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "deleted", $pv);
	}

	function getUnchangedJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "unchanged", $pv);
	}

	function getJSONHTML($v, $t, $pv){
		$nv = array();
		if($pv->literal() or $pv->objectliteral()){
			$nv = $this->applyLiteralHTML($v, $t);
		}
		elseif($pv->link()){
			$nv = $this->applyLinkHTML($v, $t);
		}
		elseif($pv->objectliterallist()){
			$nv = array();
			foreach($v as $val){
				$nv[] = $this->applyLiteralHTML($val, $t);
			}
		}
		elseif($pv->valuelist()){
			$nv = array();
			foreach($v as $val){
				if(isURL($val) || isNamespacedURL($val)){
					$nv[] = $this->applyLinkHTML($val, $t);
				}
				else {
					$nv[] = $this->applyLiteralHTML($val, $t);
				}
			}
		}
		elseif($pv->embeddedlist()){
			$nv = array();
			foreach($v as $id => $obj){
				$nid = $this->applyLinkHTML($id, $t);
				$nv[$nid] = array();
				foreach($obj as $p2 => $val2){
					$pv2 = new LDPropertyValue($val2, $this->cwurl());
					$np2 = $this->applyLinkHTML($p2, $t, true);
					$nv[$nid][$np2] = $this->getJSONHTML($val2, $t, $pv2);
				}
			}
		}
		return $nv;
	}

	function applyLinkHTML($ln, $t, $is_prop = false){
		if($is_prop){
			$cls = "dacura-property $t";
		}
		else {
			$cls = "dacura-property-value $t";
		}
		if(isURL($ln)){
			if($this->original->isDocumentLocalLink($ln)){
				$cls .= " document_local_link";
				$lh = "<a class='$cls' href='$ln".$vstr."'>$ln</a>";
			}
			else {
				$lh = "<a class='$cls' href='$ln'>$ln</a>";
			}
		}
		elseif(isNamespacedURL($ln)){
			if($this->original->isDocumentLocalLink($ln)){
				$cls .= " document_local_link";
				$expanded = $this->nsres->expand($ln);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace' title='warning: unknown namespace'>$ln</span>";
				}
				else {
					$lh = "<a class='$cls' href='$expanded".$vstr."'>$ln</a>";
				}
			}
			else {
				$expanded = $this->nsres->expand($ln);
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

	function applyLiteralHTML($ln, $tp){
		return "<span class='dacura-property-value $tp dacura-literal'>$ln</span>";
	}

	function reportString(){
		return $this->delta ? $this->delta->reportString() : "No delta calculated - nothing to report";
	}

	function display($format, $options, $srvr){
		$lddisp = new LDODisplay($this->id, $this->cwurl);
		if($format == "json"){
			$this->display = $lddisp->displayJSON($this->forward, $options);
		}
		elseif($format == "html"){
			$this->display = $lddisp->displayHTML($this->forward, $options);
		}
		elseif($format == "triples"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedTriples() : $this->triples();
			$this->display = $lddisp->displayTriples($payload, $options);
		}
		elseif($format == "quads"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedQuads() : $this->quads();
			$this->display = $lddisp->displayQuads($payload, $options);
		}
		elseif($format == "jsonld"){
			require_once("JSONLD.php");
			$jsonld = toJSONLD($this->forward, $this->getNS(), array("cwurl" => $this->cwurl));
			$this->display = $lddisp->displayJSONLD($jsonld, $options);
		}
		elseif($format == "nquads"){
			$payload = $this->nQuads();
			$this->display = $lddisp->displayNQuads($payload, $options);
		}
		else {
			$exported = $this->export($format);
			if($exported === false){
				return false;
			}
			$this->display = $lddisp->displayExport($exported, $format, $options);
		}
		if($this->changed){
			$this->changed->display($format, $options, $srvr);
		}
		if($this->original){
			$this->original->display($format, $options, $srvr);
		}
		
		return true;
	}

	function displayExport($format, $srvr){
		$vstr = "?format=".$format;
		$flags = array("ns", "links", "problems", "typed");
		$this->changed->displayExport($format, $flags, $vstr, $srvr);
		$this->original->displayExport($format, $flags, $vstr, $srvr);
		//$temp = new LDDocument($this->id);
		//$temp->load($this->forward);
		//$exported = $temp->export($format, $this->nsres);
		//if($exported){
		//	if($format != "svg" && $format != "dot" && $format != "png" && $format != "gif"){
		//		$this->display = htmlspecialchars($exported);
		//	}
		//	else {
		//		if($format == "png" or $format == "gif"){
		//			$this->display = '<img src="data:image/png;base64,'.base64_encode ( $exported).'"/>';
			//	}
				//else {
				//	$this->display = $exported;
				//}
			//}
		//}
		//else {
		//	$this->display = $this->getChangeViewHTML();
		//}
	}

	function displayJSON($srvr){
		$vstr = "?format=json";
		$this->changed->display = $this->changed->ldprops;
		$this->original->display = $this->original->ldprops;
		$this->display = $this->forward;
	}

	function displayHTML($srvr){
		$vstr = "?format=html";
		$flags = array("ns", "links");
		$this->changed->displayHTML($flags, $vstr, $srvr);
		$this->original->displayHTML($flags, $vstr, $srvr);
		$this->display = $this->getChangeViewHTML();
	}

	function displayTriples($srvr){
		$vstr = "?format=triples";
		$flags = array("ns", "links");
		$this->changed->displayTriples($flags, $vstr, $srvr);
		$this->original->displayTriples($flags, $vstr, $srvr);
		$this->display = $this->deltaAsTriples($orig_upd);
	}

	function displayQuads($srvr){
		$vstr = "?format=quads";
		$flags = array("ns", "links");
		$this->changed->displayQuads($flags, $vstr, $srvr);
		$this->original->displayQuads($flags, $vstr, $srvr);
		$this->display = $this->deltaAsTriples();
	}

 */