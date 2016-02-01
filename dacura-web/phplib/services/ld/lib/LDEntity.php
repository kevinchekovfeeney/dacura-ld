<?php
//require_once("LDDocument.php");
include_once("phplib/libs/easyrdf-0.9.0/lib/EasyRdf.php");
include_once("LDUtils.php");

/*
 * The basic variables required by the editor
 */
class LDEntity extends DacuraObject {
	var $id = false;
	var $index = false; //obj_id => &$obj
	var $bad_links = array(); //bad links in various categories in the document
	var $idmap = array(); //blank nodes that have been mapped to new names in the document
	var $cwurl = "";//closed world URL of the document. If present, encapsulated entities will have ids that start with this.
	var $compressed = false;
	var $cid;
	var $version;
	var $latest_version;
	var $status;
	var $latest_status;
	var $created;//time entity was created
	var $modified;//time entity was last updated
	var $version_created;//time this version was created
	var $version_replaced;//when was this version replaced
	var $nsres;
	var $ldprops; //associative array in Dacura LD format
	var $meta;
	var $logger;//shortcut to logger object
	
	static $entity_types = array("ontology" => "Ontology", "graph" => "Named Graph", "widget" => "User Interface Widget", "candidate" => "Instance Data Object", "import" => "Data Import Process", "publish" => "Data Publication Process", "task" => "Data Processing Task");
	
	function __construct($id, $cwbase = false, $logger = false){
		$this->id = $id;
		$this->created = time();
		$this->modified = time();
		if($cwbase){
			$this->cwurl = $cwbase."/".$id;
		}
		else {
			$this->cwurl = false;
		}
		$this->logger = $logger;
	}
	
	function copyBasics($other){
		$this->cid = $other->cid;
		$this->did = $other->did;
		$this->version = $other->version;
		$this->created = $other->created;
		$this->status = $other->status;
		$this->modified = $other->modified;
	}
	
	
	function loadFromDBRow($row, $latest = true){
		$this->setContext($row['collectionid']);
		$this->version = $row['version'];
		$this->ldprops = json_decode($row['contents'], true);
		$this->meta = json_decode($row['meta'], true);
		$this->created = $row['createtime'];
		$this->status = $row['status'];
		if(!isset($this->meta['status'])){
			$this->meta['status'] = $this->status;
		}
		else {
			$this->status = $this->meta['status'];
		}
		$this->modified = $row['modtime'];
		if($latest){
			$this->version_created = $this->modified;
			$this->version_replaced = 0;
			$this->latest_status = $this->status; 
			$this->latest_version = $this->version; 
		}
	}


	function __clone(){
		$this->ldprops = deepArrCopy($this->ldprops);
		$this->index = false;
		$this->bad_links = deepArrCopy($this->bad_links);
		$this->meta = deepArrCopy($this->meta);
	}
	
	function load($arr){
		$this->ldprops = $arr;
	}
	
	function get_json($key = false){
		if($key){
			if(!isset($this->ldprops[$key])){
				return "{}";
			}
			return json_encode($this->ldprops[$key]);
		}
		return json_encode($this->ldprops);
	}
	
	function get_json_ld(){
		$ld = $this->ldprops;
		$ld["@id"] = $this->id;
		return $ld;
	}
	
	
	function setContext($cid){
		$this->cid = $cid;
	}
	
	function version(){
		return $this->version;
	}
	
	function isLatestVersion(){
		return $this->version == $this->latest_version;
	}
	
	function set_version($v, $is_latest = false){
		$this->version = $v;
		if($is_latest){
			$this->latest_version = $v;
		}
	}	

	function get_status(){
		return $this->status;
	}
	
	function set_status($v, $is_latest = false){
		$this->status = $v;
		if(!isset($this->meta) or !is_array($this->meta)){
			$this->meta = array();
		}
		$this->meta['status'] = $v;
		if($is_latest){
			$this->latest_status = $v;
		}
	}

	function expandNS(){
		$this->compressed = false;
		return expandNamespaces($this->ldprops, $this->nsres, $this->cwurl);
	}
	
	function compressNS(){
		$this->compressed = true;
		compressNamespaces($this->ldprops, $this->nsres, $this->cwurl);
	}
	
	function getNS(){
		return getNamespaces($this->ldprops, $this->nsres, $this->cwurl, $this->compressed);
	}
	
	function setNamespaces($nsres){
		$this->nsres = $nsres;
	}

	/*
	 * Calculates the transforms necessary to get to current from other
	 */
	function compare($other){
		$aprops = $this->ldprops;
		$aprops['meta'] = $this->meta;
		$bprops = $other->ldprops;
		$bprops['meta'] = $other->meta;
		$cdelta = compareLDGraphs($this->id, $aprops, $bprops, $this->cwurl, true);
		//opr($aprops);
		//opr($cdelta);
		if($cdelta->containsChanges()){
			$cdelta->setMissingLinks($this->missingLinks(), $other->missingLinks());
		}
		return $cdelta;
	}
	
	function update($update_obj, $is_force=false, $demand_id_allowed = false){
		if(isset($update_obj['meta'])){
			$umeta = $update_obj['meta'];
			unset($update_obj['meta']);
		}
		else {
			$umeta = false;
		}
		if($this->applyUpdates($update_obj, $this->ldprops, $this->idmap, $is_force, $demand_id_allowed)){
			if($umeta === false || $this->applyUpdates($umeta, $this->meta, $this->idmap, true, false, true)){
				if(count($this->idmap) > 0){
					$unresolved = updateBNReferences($this->ldprops, $this->idmap, $this->cwurl);
					if($unresolved === false){
						return false;
					}
					elseif(count($unresolved) > 0){
						$this->bad_links = $unresolved;
					}
				}
				$this->buildIndex();
				return true;
			}
		}
		return false;
	}

	/**
	 * Apply changes specified in props to properties in dprops
	 * Generates new ids for each blank node and returns mapping in idmap.
	 *
	 * @param array $uprops - the update instructions
	 * @param array $dprops - the properties to be updated (delta)
	 * @param array $idmap - map of local ids to newly generated IDs
	 * @return boolean
	 */
	function applyUpdates($uprops, &$dprops, &$idmap, $id_set_allowed = false, $demand_id_allowed = false, $ignore_bad_deletes = false){
		foreach($uprops as $prop => $v){
			if(!is_array($dprops)){
				$dprops = array();
			}
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()){
				return $this->failure_result($pv->errmsg, $pv->errcode);
			}
			elseif($pv->scalar() or $pv->objectliteral()){
				$dprops[$prop] = $v;
			}
			elseif($pv->valuelist() or $pv->objectliterallist()){
				$dprops[$prop] = $v;
			}
			elseif($pv->isempty()){ // delete property or complain
				if(isset($dprops[$prop])){
					unset($dprops[$prop]);
				}
				elseif(!$ignore_bad_deletes) {
					return $this->failure_result("Attempted to remove non-existant property $prop", 404);
				}
			}
			elseif($pv->objectlist()){ //list of new objects (may have @ids inside)
				foreach($v as $obj){
					addAnonObj($this->id, $obj, $dprops, $prop, $idmap, $this->cwurl, $demand_id_allowed);
				}
			}
			elseif($pv->embedded()){ //new object to add to the list - give her an id and insert her
				$rep = expandLD($this->id, $v, $this->cwurl, $demand_id_allowed);
				if($rep === false){
					return $this->failure_result("Failed to expand blank nodes", 400);
				}
				if(isset($rep["missing"])){
					$this->bad_links = array_merge($this->bad_links, $rep["missing"]);
				}
				$idmap = array_merge($idmap, $rep['idmap']);
				addAnonObj($this->id, $v, $dprops, $prop, $idmap, $this->cwurl, $demand_id_allowed);				
			}
			elseif($pv->embeddedlist()){
				$bnids = $pv->getbnids();//new nodes
				foreach($bnids as $bnid){
					addAnonObj($this->id, $v[$bnid], $dprops, $prop, $idmap, $this->cwurl, $demand_id_allowed, $bnid);
				}
				$delids = $pv->getdelids();//delete nodes
				foreach($delids as $did){
					if(isset($dprops[$prop][$did])){
						unset($dprops[$prop][$did]);
					}
					else {
						if(!$ignore_bad_deletes){
							return $this->failure_result("Attempted to remove non-existant embedded object $did from $prop", 404);
						}
					}
				}
				$update_ids = $pv->getupdates();
				foreach($update_ids as $uid){
					if(!isset($dprops[$prop])){
						$dprops[$prop] = array();
					}
					//echo "<h5>$prop $uid</h5>";
					//opr($dprops[$prop]);
					if(!isset($dprops[$prop][$uid])){
						//echo "<h1>$prop $uid</h1>";
						if($id_set_allowed){
							$dprops[$prop][$uid] = array();
						}
						else {
							return $this->failure_result("Attempted to update non existent element $uid of property $prop", 404);
						}
					}
					//opr($dprops[$prop][$uid]);
					if(!$this->applyUpdates($uprops[$prop][$uid], $dprops[$prop][$uid], $idmap, $id_set_allowed, $demand_id_allowed, $ignore_bad_deletes)){
						return false;
					}
					//opr($dprops[$prop][$uid]);
					if(isset($dprops[$prop][$uid]) && is_array($dprops[$prop][$uid]) and count($dprops[$prop][$uid]) == 0){
						unset($dprops[$prop][$uid]);
					}
				}
			}
			if(isset($dprops[$prop]) && is_array($dprops[$prop]) && count($dprops[$prop])==0) {
				unset($dprops[$prop]);
			}
		}
		return true;
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
	
	function getFragment($fid, $merge = false){
		if($this->index === false){
			$this->buildIndex();
		}
		if($merge){
			$merged = array();
			foreach($this->index[$fid] as $i => $frag){
				//$this->
			}
		}
		//echo "<P>$fid is the fragment id</p>";
		//opr($this->index[$fid]);
		return isset($this->index[$fid]) ? $this->index[$fid] : false;
	}
	
	function hasFragment($frag_id){
		if($this->index === false){
			$this->buildIndex($this->ldprops, $this->index);
		}
		return isset($this->index[$frag_id]);
	}
	
	function isDocumentLocalLink($val){
		return isInternalLink($val, $this->id, $this->cwurl);
	}
	
	function getFragmentPaths($fid, $html = false){
		$paths = getFragmentContext($fid, $this->ldprops, $this->cwurl);
		return $paths;
	}
	
	function setContentsToFragment($fragment_id){
		$this->ldprops = getFragmentInContext($fragment_id, $this->ldprops, $this->cwurl);
	}
	
	function buildIndex(){
		$this->index = array();
		indexLD($this->ldprops, $this->index, $this->cwurl);
	}
	

	function getObjectType($obj){
		if(!isset($obj['rdf:type']) and !isset($obj[$this->nsres->getURL("rdf")])){
			return false;
		}
		return isset($obj['rdf:type']) ? $obj['rdf:type'] : $obj[$this->nsres->getURL("rdf")];
	}

	/*
	 * Some state may be duplicated between the meta ld field and the object properties
	 * In such cases the meta field is authoritative (as it is part of state-management)
	 */
	function readStateFromMeta(){
		$this->status = $this->meta['status'];
	}
	
	function importERDF($type, $arg, $gurl = false, $format = false){
		try {
			if($type == "url"){
				$graph = EasyRdf_Graph::newAndLoad($arg, $format, $this->id);
			}
			elseif($type == "text"){
				$graph = new EasyRdf_Graph($gurl, $arg, $format, $this->id);
			}
			elseif($type == "file"){
				$graph = new EasyRdf_Graph($gurl);
				$graph->genid = $this->id;
				$graph->parseFile($arg, $format);//the slow one
				$this->logger && $this->logger->timeEvent("Load Graph", "debug");
			}
			if($graph->isEmpty()){
				return $this->failure_result("Graph loaded from $type was empty.", 500);
			}
			return $graph;
		}
		catch(Exception $e){
			//opr($graph);
			return $this->failure_result("Failed to load graph from $type. ".$e->getMessage(), $e->getCode());
		}
	}
	
	function getERDFSupportedNamespaces(){
		return EasyRdf_Namespace::namespaces();		
	}
	
	function import($type, $arg, $gurl = false, $format = false){
		$graph = $this->importERDF($type, $arg, $gurl, $format);
		$op = $graph->serialise("php");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->expand();
		$errs = validLD($this->ldprops, $this->cwurl);
		if(count($errs) > 0){
			$msg = "<ul><li>".implode("<li>", $errs)."</ul>";
			return $this->failure_result("Graph had ". count($errs)." errors. $msg", 400);
		}
		return true;
	}
	
	function export($format, $nsobj = false){
		$easy = exportEasyRDFPHP($this->id, $this->ldprops);
		try{
			foreach($this->nsres->prefixes as $id => $url){
				EasyRdf_Namespace::set($id, $url);
			}
				
			$graph = new EasyRdf_Graph($this->id, $easy, "php", $this->id);
			if($graph->isEmpty()){
				return $this->failure_result("Graph was empty.", 400);
			}
			if($nsobj){
				$nslist = $this->getNS($nsobj);
				if($nslist){
					foreach($nslist as $prefix => $full){
						EasyRdf_Namespace::set($prefix, $full);
					}
				}
			}
			$res = $graph->serialise($format);
			if(!$res){
				return $this->failure_result("failed to serialise graph", 500);
			}
			return $res;
		}
		catch(Exception $e){
			return $this->failure_result("Graph croaked on input. ".$e->getMessage(), $e->getCode());
		}
	}
	
	function expand($allow_demand_id = false){
		$rep = expandLD($this->id, $this->ldprops, $this->cwurl, $allow_demand_id);
		if($rep === false){
			return $this->failure_result("Failed to expand blank nodes", 400);
		}
		if(isset($rep["missing"])){
			$this->bad_links = $rep["missing"];
		}
		$this->idmap = $rep['idmap'];
		return true;
	}
	
	function problems(){
		if(count($this->bad_links) > 0){
			return $this->bad_links;
		}
		return false;
	}
	
	function missingLinks(){
		if(isset($this->bad_links)){
			return $this->bad_links;
		}
		return $this->findMissingLinks();
	}
	
	function findMissingLinks(){
		if($this->index === false){
			$this->buildIndex($this->ldprops, $this->index, $this->cwurl);
		}
		$ml = findInternalMissingLinks($this->ldprops, array_keys($this->index), $this->id, $this->cwurl);
		$x = count($ml);
		if($x > 0){
			$this->bad_links = $ml;
		}
		return $ml;
	}
	
	function compliant(){
		$errs = validLD($this->ldprops, $this->cwurl);
		if(count($errs) == 0){
			return true;
		}
		else {
			$errmsg = "Errors in input formatting:<ol> ";
			foreach($errs as $err){
				$errmsg .= "<li>".$err[0]." ".$err[1];
			}
			$errmsg .= "</ol>";
			return $this->failure_result($errmsg, 400);
		}
	}
	
	function typedTriples(){
		return getObjectAsTypedTriples($this->id, $this->ldprops, $this->cwurl);
	}
	
	function triples(){
		return getObjectAsTriples($this->id, $this->ldprops, $this->cwurl);
	}
	
	function internalTriples(){
		return getPropertiesAsArray($this->id, $this->ldprops, $this->cwurl, array($this, "showTriples"));
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
	
	function getPropertyAsQuads($prop, $gname){
		if(!isset($this->ldprops[$prop])) return array();
		$quads = array();
		$trips = getEOLAsTypedTriples($this->ldprops[$prop], $this->cwurl);
		foreach($trips as $trip){
			$trip[] = $gname;
			$quads[] = $trip;
		}
		return $quads;
	}
	
	function displayTriples($flags, $vstr, $srvr){
		$this->display = $this->typedTriples($flags, $vstr);
	}
	
	function displayQuads($flags, $vstr, $srvr){
		$this->display = "<h2>need to write display Quads</h2>";
	}
	
	function displayHTML($flags, $vstr, $srvr){
		$allsubjs = $this->ldprops[$this->id];
		$html = "";
		foreach($allsubjs as $k => $v){
			$html .= "<h3>$k</h3>".$this->getPropertiesAsHTMLTable($vstr, $v);
		}
		$this->display = $html;
	}
	
	function displayJSON($flags, $vstr, $srvr, $jsonld = false){
		$base = $this->ldprops;
		if($jsonld){
			$base = toJSONLD($base, $this->cwurl);			
		}
		if(in_array("links", $flags)){
			$this->display = $this->linkify($vstr, $base);				
		}
		else {
			$this->display = $base;				
		}
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
	
	function forAPI(){
		$other = clone $this;
		unset($other->index);
		unset($other->nsres);
		unset($other->idmap);
		unset($other->bad_links);
		return $other;
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
					$props_html[] = $this->getPropertiesAsHTMLTable($vstr, $obj, $depth, $obj_id_prefix);
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
	
	/*
	 * For specifiying dqs configs
	 */
	function dqsSpecified(){
		return isset($this->meta['imports']) && is_array($this->meta['imports']) &&
		(isset($this->meta['schema_dqs']) || isset($this->meta['instance_dqs']));
	}
	
	function getDQSTests($which){
		if(isset($this->meta[$which.'_dqs'])){
			return $this->meta[$which.'_dqs'];
		}
		return false;
	}
	
	function getImportedOntologies(){
		if(isset($this->meta['imports'])){
			return $this->meta['imports'];
		}
		return false;
	}
	
	
	
}