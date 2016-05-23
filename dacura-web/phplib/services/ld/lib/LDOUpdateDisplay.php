<?php 
require_once("LDODisplay.php");

class LDOUpdateDisplay extends LDODisplay {
	
	function cwurl(){
		return $this->ldo->cwurl();
	}
	function getChangeViewHTML(){
		$cstruct = array("meta" => $this->ldo->getMetaUpdates());
		if(count($this->ldo->forward) == 1 && isset($this->ldo->forward['meta'])){
			$cstruct['contents'] = array();
		}
		else {
			//opr($this->ldo->backward);
			$cstruct['contents'] = $this->showChanges($this->ldo->changed->ldprops, $this->ldo->backward);
		}
		return $cstruct;
	}
	
	function showChanges($props, $dprops){
		$allprops = array();
		foreach($props as $subj => $ldobj){
			$cprops = array();
			foreach($ldobj as $prop => $v){
				$pv = new LDPropertyValue($v, $this->cwurl());
				if($pv->illegal()){
					return $this->failure_result($pv->errmsg, $pv->errcode);
				}
				if(!isset($dprops[$subj][$prop])){
					//$xprop = $this->applyLinkHTML($prop, "unchanged", true);
					//$cprops[$xprop] = $this->getUnchangedJSONHTML($v, $pv);
					//ignore.
				}
				else {
					$nv = $dprops[$subj][$prop];
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
						$x = $pv->ldtype(true);
						if($x == 'scalar' || $x == "objectliteral"){
							if($v != $nv){
								$xprop = $this->applyLinkHTML($prop, "updated", true);
								$cprops[$xprop] = $this->getValueChangeHTML($v, $nv);
							}
							else {
								//$xprop = $this->applyLinkHTML($prop, "unchanged", true);
								//$cprops[$xprop] = $this->applyLiteralHTML($v, "unchanged");
							}
						}
						elseif($x == 'valuelist'){
							$changed = false;
							$entries = array();
							foreach($v as $i => $val){
								if(in_array($val, $nv)){
									//$entries[] = $this->applyLiteralHTML($val, "unchanged");
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
								//$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							}
							echo "<P>this is for $xprop";
							opr($entries);
							$cprops[$xprop] = $entries;
						}
						elseif($x == "objectliterallist"){
							$changed = false;
							$entries = array();
							foreach($v as $i => $val){
								$found = false;
								foreach($nv as $k => $v2){
									if(compareObjLiterals($v2, $val)){
										$found = true;
										//$entries[] = $this->applyLiteralHTML($val, "unchanged");
										break;	
									}
								}
								if(!$found){
									$entries[] = $this->applyLiteralHTML($val, "added");
									$changed = true;								
								}
							}
							foreach($nv as $i => $val){
								$found = false;
								foreach($v as $k => $v2){
									if(compareObjLiterals($v2, $val)){
										$found = true;
										//$entries[] = $this->applyLiteralHTML($val, "unchanged");
										break;
									}
								}
								if(!$found){
									$entries[] = $this->applyLiteralHTML($val, "deleted");
									$changed = true;
								}
							}
							if($changed){
								$xprop = $this->applyLinkHTML($prop, "changed", true);
							}
							else {
								//$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							}
							$cprops[$xprop] = $entries;
							
						}
						elseif($x == 'embeddedobjectlist') {
							$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							$cprops[$xprop] = array();
							foreach($v as $id => $obj){
								if(!isset($nv[$id])){
									//$tprop = $this->applyLinkHTML($id, "unchanged");
									//$cprops[$xprop][$tprop] = $this->getUnchangedJSONHTML($v, $pv);
								}
								else {
									$subchanges = $this->showChanges($obj, $nv[$id]);
									if($subchanges){
										$tprop = $this->applyLinkHTML($id, "changed");
										$cprops[$xprop][$tprop]= $subchanges;
									}
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
			if(count($cprops) > 0){
				$allprops[$subj] = deepArrCopy($cprops);
			}
		}
		foreach($dprops as $s2 => $ldobj){
			if($s2 == "meta") continue;
  			$cprops = array();
			foreach($ldobj as $dprop => $dv){
				$dpv = new LDPropertyValue($dv, $this->cwurl());
				if(!isset($props[$s2][$dprop])){
					$xprop = $this->applyLinkHTML($dprop, "deleted");
					$cprops[$xprop] = $this->getDeletedJSONHTML($v, $pv);
				}
			}
			if(count($cprops) > 0){
				$allprops[$s2] = deepArrCopy($cprops);				
			}
		}
		return $allprops;
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
			if($ll = $this->documentLocalLink($this->ldo->original, $ln)){
				$cls .= " document_local_link";
				$lh = "<a class='$cls' href='$ln'>$ll</a>";
			}
			else {
				$lh = "<a class='$cls' href='$ln'>$ln</a>";
			}
		}
		elseif(isNamespacedURL($ln)){
			if($lk = $this->documentLocalLink($this->ldo->original, $ln)){
				$cls .= " document_local_link";
				$expanded = $this->ldo->nsres->expand($lk);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace' title='warning: unknown namespace'>$lk</span>";
				}
				else {
					$lh = "<a class='$cls' href='$expanded'>$lk</a>";
				}
			}
			else {
				$expanded = $this->ldo->nsres->expand($ln);
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
	
	/**
	 * Is the passed value an id of an internal node in the document?
	 * @param string $val
	 * @return boolean true if the value is an internal link
	 */
	function documentLocalLink($ldo, $val){
		global $dacura_server;
		$durl = $dacura_server->durl();
		if($val == $ldo->cwurl) return $val;
		if(isBlankNode($val)) return $ldo->cwurl."/".substr($val, 2);
		if($ldo->ldtype() == "ontology"){
			$ourl = isset($ldo->meta['url']) ? $ldo->meta['url'] : $ldo->cwurl;
			if(substr($val, 0, strlen($ourl)) == $ourl) {
				$str = $durl . ($ldo->cid() == "all" ? "" : $ldo->cid()."/")."ontology/".$ldo->id;
				return $str."#".substr($val, strlen($ourl));
			}
		}
		if(isInternalLink($val, $ldo->cwurl)){
			return $val;
		}
		return false;
	}	

	function applyLiteralHTML($ln, $tp){
		if(is_array($ln)){
			$html = "<span class='dacura-property-value $tp dacura-objectliteral'>";
			foreach($ln as $k => $v){
				$html .= "<span class='$tp dacura-objectliteral-index'>$k</span>[<span class='$tp dacura-objectliteral-value'>$v</span>]";
			}
			$html .= "</span>";
		}
		else {
			$html = "<span class='dacura-property-value $tp dacura-literal'>$ln</span>";
		}
		return $html;
	}
}
	/*

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