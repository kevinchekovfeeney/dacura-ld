<?php 
class LDODisplay extends DacuraObject {
	var $cwurl;
	
	function __constructor($id, $cwurl = false){
		parent::__constructor($id);
		$this->cwurl = $cwurl;
	}
	
	
	function displayTriples($trips, $options){
		return $trips;
	}
	
	function displayQuads($quads, $options){
		return $quads;
	}
	
	function displayJSON($json, $options){
		if(isset($options['links']) && $options['links']){
			return $this->linkify($options, $json);				
		}
		else {
			return $json;				
		}
	}
	
	function displayJSONLD($jsonld, $options){
		//require_once("JSONLD.php");
		//print JsonLD::toString($expanded, true);
		//return $this->displayJSON(ML\JsonLD\JsonLD::toString($jsonld, true), $options);
		return $this->displayJSON($jsonld, $options);
		
	}
	
	function displayHTML($props, $options){
		$html = "";
		foreach($props as $k => $v){
			$html .= "<h3>$k</h3>".$this->getPropertiesAsHTMLTable($v, $options);
		}
		return $html;
	}
	
	function displayNQuads($text, $options){
		return htmlspecialchars($text);
	}
	
	function displayExport($exported, $format, $options){
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
	
	function decorated(){
		return $this->ldprops;
	}
	
	function decoratedTriples(){
		return array();
	}	
	
	function linkify($vstr, $props=false){
		if($props === false){
			$props = $this->ldprops;
		}
		$nprops = array();
		if($props && is_array($props)) {
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
		}
		return $nprops;
	}
	
	/**
	 * Is the passed value an id of an internal node in the document?
	 * @param string $val
	 * @return boolean true if the value is an internal link
	 */
	function isDocumentLocalLink($val){
		return isInternalLink($val, $this->cwurl);
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
	

	function getPropertiesAsHTMLTable($props, $options = array(), $depth = 0, $obj_id_prefix = ""){
		$vstr = "";//should be loaded from $options...
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
			$np = $this->applyLinkHTML($p, $vstr, true);
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->literal()){
				if(isURL($v) || isNamespacedURL($v)){
					$nv = $this->applyLinkHTML($v, $vstr);
				}
				else {
					$nv = $this->applyLiteralHTML($v);
				}
				array_unshift($props_html, "<tr class='firstp $cls_extra'><td class='prop-pd $cls_extra'>$np</td><td class='prop-vd $cls_extra'>$nv</td></tr>");
			}
			elseif($pv->link()){
				$nv = $this->applyLinkHTML($v, $vstr);				
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
						$nv[] = $this->applyLinkHTML($val, $vstr);
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
					$nid = $this->applyLinkHTML($id, $vstr);
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
				$html .= "<span class='dacura-objectliteral-index>$k</span> <span class='dacura-objectliteral-value'>$v</span> ";
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
	
	function showTriples($s, $p, $o, $t, $g = false){
		if($t == 'literal'){
			$o = '"'.$o.'"';
		}
		return array(array($s, $p, $o));
	}
}