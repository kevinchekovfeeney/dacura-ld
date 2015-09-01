<?php
class FakeTripleStore {
	var $fname;
	var $ts = array();

	function __construct($fname){
		$this->fname = $fname;
		$this->loadTS();
	}

	function loadTS(){
		$this->ts = json_decode(file_get_contents($this->fname), true);
		if(!$this->ts){
			$this->ts = array();
		}
	}

	function save(){
		file_put_contents($this->fname, json_encode($this->ts));
	}


	function includesQuad($quad){
		if(isset($this->ts[$quad[3]][$quad[0]][$quad[1]])){
			foreach($this->ts[$quad[3]][$quad[0]][$quad[1]] as $oq){
				if($oq && $quad[2] && json_encode($oq) == json_encode($quad[2])) return true;
			}
		}
		return false;
	}

	function removeQuad($quad){
		if(isset($this->ts[$quad[3]][$quad[0]][$quad[1]])){
			foreach($this->ts[$quad[3]][$quad[0]][$quad[1]] as $i => $oq){
				if($oq && $quad[2] && json_encode($oq) == json_encode($quad[2])){
					unset($this->ts[$quad[3]][$quad[0]][$quad[1]][$i]);
					if(count($this->ts[$quad[3]][$quad[0]][$quad[1]]) == 0){
						unset($this->ts[$quad[3]][$quad[0]][$quad[1]]);
					}
					if(count($this->ts[$quad[3]][$quad[0]]) == 0){
						unset($this->ts[$quad[3]][$quad[0]]);
					}
					return true;
				}
			}
		}
		return false;
	}

	function addQuad($quad){
		if($this->includesQuad($quad)) return false;
		if(!isset($this->ts[$quad[3]])){
			$this->ts[$quad[3]] = array();
		}
		if(!isset($this->ts[$quad[3]][$quad[0]])){
			$this->ts[$quad[3]][$quad[0]] = array();
		}
		if(!isset($this->ts[$quad[3]][$quad[0]][$quad[1]])){
			$this->ts[$quad[3]][$quad[0]][$quad[1]] = array();
		}
		$this->ts[$quad[3]][$quad[0]][$quad[1]][] = $quad[2];
		return true;
	}

	function update($iquads, $dquads, $is_test){
		$errors = array();
		//$errors[] = array("type" => "Some Weird Shite1", "quads" => $iquads);
		//$errors[] = array("type" => "Some Weird Shite2", "quads" => $dquads);
		foreach($dquads as $dq){
			if(!$this->removeQuad($dq)){
				$errors[] = array("type" => "DeleteNonExistentQuad", "quads" => $dq);
			}
		}
		foreach($iquads as $iq){
			if(!$this->addQuad($iq)){
				$errors[] = array("type" => "AddAlreadyExistingQuad", "quads" => $iq);
			}
		}
		if(!$is_test && count($errors) == 0){
			$this->save();
		}
		return $errors;
	}

	function html(){
		$html = "<table><tr><th>Graph</th><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		foreach($this->ts as $gname => $trips){
			foreach($trips as $sub => $preds){
				foreach($preds as $pred => $val){
					if(is_array($val)) $val = json_encode($val);
					$html .= "<tr><td>$gname</td><td>$sub</td><td>$pred</td><td>$val</td></tr>";
				}
			}
		}
		$html .= "</table>";
		return $html;
	}

}
