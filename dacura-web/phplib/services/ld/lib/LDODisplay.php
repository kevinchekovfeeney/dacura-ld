<?php 
/**
 * Utility Class that contains some functionality for displaying linked data objects
 * @author chekov
 *
 */
class LDODisplay extends DacuraObject {
	/** @var $ldo the linked data object in question */
	var $ldo;
	/** @var $options display options in effect. */
	var $options;
	
	/**
	 * 
	 * @param LDO $ldo linked data object to be displayed
	 * @param array $options display options
	 */
	function __construct(LDO $ldo, $options){
		$this->ldo = $ldo;
		$this->options = $options;
	}
	
	/**
	 * Display the ldo in a particular format
	 * @param string $format
	 * @return depends on format - json, html, triples, 
	 */
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
	
	/**
	 * Adds links and various flourishes to an array of triples
	 * @return array<triples>
	 */
	function displayTriples(){
		$payload = isset($this->options['typed']) && $this->options['typed'] ? $this->ldo->typedTriples() : $this->ldo->triples();
		if(!isset($this->options['plain']) || !$this->options['plain']){
			$this->linkifyTriples($payload, true, $this->getLinkExtras("triples"));
		}
		return $payload;
	}
	
	/**
	 * Adds links and various flourishes to an array of triples
	 * @return array<quads>
	 */
	function displayQuads(){
		$payload = isset($this->options['typed']) && $this->options['typed'] ? $this->ldo->typedQuads() : $this->ldo->quads();
		if(!isset($this->options['plain']) || !$this->options['plain']){
			$this->linkifyTriples($payload, true, $this->getLinkExtras("quads"));
		}
		return $payload;
	}
	
	/**
	 * Add links and html to json array
	 * @return json
	 */
	function displayJSON(){
		if(isset($this->options['plain']) && $this->options['plain']){
			return $this->ldo->ldprops;
		}
		else {
			return $this->linkify($this->getLinkExtras("json"), $this->ldo->ldprops);				
		}
	}
	
	/**
	 * Displays the object as json ld
	 * @return json
	 */
	function displayJSONLD(){
		require_once("JSONLD.php");
		$ns =  isset($this->options['ns']) && $this->options['ns'] ? $this->ldo->getNS() : false;
		$jsonld = toJSONLD($this->ldo->ldprops, $ns, $this->ldo->cwurl, $this->ldo->is_multigraph());
		if(isset($this->options['plain']) && $this->options['plain']){
			return $jsonld;
		}
		else {
			return $jsonld;//no flourishes!
			//return $this->linkify($this->getLinkExtras("jsonld"), $jsonld);
		}
	}
	
	/**
	 * Displays the object as html
	 * @return string
	 */
	function displayHTML(){
		$html = "";
		foreach($this->ldo->ldprops as $k => $v){
			$nk = $this->applyLinkHTML($k, $this->getLinkExtras("html"), "property-subject");
			$html .= "<h3>$nk</h3>".$this->getPropertiesAsHTMLTable($v, $this->options);
		}
		return $html;
	}
	
	/**
	 * Displays the ldo as nquads (string)
	 * @return string
	 */
	function displayNQuads(){
		$payload = $this->ldo->nQuads();
		return htmlspecialchars($payload);
	}
	
	/**
	 * Display the ldo in one of the easy rdf formats
	 * @param string $format
	 * @return string the ldo displayed in the format in question...
	 */
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

	/**
	 * produces the necessary query string to be appended to local links
	 * @param string $format
	 * @return string the query string to be appended
	 */
	function getLinkExtras($format){
		$str = "?format=$format";
		foreach($this->options as $opt => $v){
			$str .= "&option[$opt]=$v";
		}
		return $str;
	}
	
	/**
	 * Turns the various elements of a json ld array into html links
	 * @param string $vstr query string to be appended to links
	 * @param string $props ld properties array
	 * @return array props updated array
	 */
	function linkify($vstr, $props){
		$nprops = array();
		if($props && is_array($props)) {
			foreach($props as $s => $ldobj){
				$ns = $this->applyLinkHTML($s, $vstr, "property-subject");
				$nprops[$ns] = array();
				if(is_array($ldobj)){
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
							$nv =  $this->linkify($vstr, $v);
						}
						else {
							$nv = $v;
						}
						$nprops[$ns][$np] = $nv;
					}
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
	
	/**
	 * generates the html to represent a dacura link (or false if the link is not a local dacura link)
	 * @param string $ln the link
	 * @param string $vstr the query string
	 * @param string $position the position in the assertion (subject, predicate, object)
	 * @return string|boolean the link or false for not a local link or not a link
	 */
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
	
	/**
	 * Applies html to the passed link for display
	 * @param string $ln the link
	 * @param string $vstr query string 
	 * @param string $position the position in the assertion (subject, predicate, object)
	 * @return string
	 */
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
	
	/**
	 * Generates a HTML table to represent the passed properties array
	 * @param array $props the properties array
	 * @param array $options the options array
	 * @param number $depth how deep in the table are we (recursive calls deep)
	 * @param string $obj_id_prefix a html id prefix to prepend to the object id
	 * @return string the html table
	 */
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
	
	/**
	 * Extracts the RDF type from the property
	 * @param array $props the property array
	 * @return unknown|boolean
	 */
	function extractTypeFromProps($props){
		if(isset($props['rdf:type'])){
			return $props['rdf:type'];
		}
		return false;
	}
	
	/**
	 * Applies html to the passed link
	 * @param string $ln the link
	 * @return string htmlified link
	 */
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
	
	/**
	 * Applies html to the passed object literal
	 * @param string $olit the object literal
	 * @return string the htmlified version
	 */
	function applyObjectLiteralHTML($olit){
		if(!isset($olit['data'])){
			$x = json_encode($olit);
			return "<div class='broken-object-literal'>$x</div>";
		}
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

	/**
	 * adds html links to triple array
	 * @param array $trips triples array
	 * @param string $alink link
	 * @param string $vstr 
	 */
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
	
	/**
	 * Display triple 
	 * @param string $s subject
	 * @param string $p predicate
	 * @param string $o object
	 * @param string $t type 
	 * @param string $g graph
	 * @return multitype:multitype:unknown string
	 */
	function showTriples($s, $p, $o, $t, $g = false){
		if($t == 'literal'){
			$o = '"'.$o.'"';
		}
		return array(array($s, $p, $o));
	}
}
