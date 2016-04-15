<?php

/**
 * A fake DQS service which serves for testing consistency between dacura and the triples it outputs. 
 *
 * @author Chekov
 * @license GPL V2
 */
class FakeTripleStore {
	/** @var string the name of the file that the triples will be saved to*/
	var $fname;
	/** @var array the triples themsleves */
	var $ts = array();

	/**
	 * Constructs ts array from file
	 * @param string $fname the path to the file where the triples will be stored
	 */
	function __construct($fname){
		$this->fname = $fname;
		$this->loadTS();
	}

	/**
	 * Loads the triples from the file and parses their json structure to get the ts array
	 */
	function loadTS(){
		$this->ts = json_decode(file_get_contents($this->fname), true);
		if(!$this->ts){
			$this->ts = array();
		}
	}

	/**
	 * Saves triples to file json-encoded
	 */
	function save(){
		file_put_contents($this->fname, json_encode($this->ts));
	}

	/**
	 * Does the triple store contain this quad
	 * @param array $quad [s,p,o,g]
	 * @return boolean true if successfully added
	 */
	function includesQuad($quad){
		if(isset($this->ts[$quad[3]][$quad[0]][$quad[1]])){
			foreach($this->ts[$quad[3]][$quad[0]][$quad[1]] as $oq){
				if($oq && $quad[2] && json_encode($oq) == json_encode($quad[2])) return true;
			}
		}
		return false;
	}

	/**
	 * Removes a quad from the triple store
	 * @param array $quad [s, p, o, g]
	 * @return boolean true if successfully removed
	 */
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

	/**
	 * Adds a quad to the triple store
	 * @param array $quad [s, p, o, g]
	 * @return boolean true if successfully removed
	 */
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

	/**
	 * Updates the triple store by inserting and deleting the passed quads
	 * @param array $iquads array of quads to be inserted
	 * @param array $dquads array of quads to be deleted
	 * @param boolean $is_test true if this is just a test invocation
	 * @return DQSResult
	 */
	function update($iquads, $dquads, $is_test){
		$errors = array();
		//$errors[] = array("type" => "Some Weird Shite1", "quads" => $iquads);
		//$errors[] = array("type" => "Some Weird Shite2", "quads" => $dquads);
		if($dquads){
			foreach($dquads as $dq){
				if(!$this->removeQuad($dq)){
					$errors[] = array("type" => "DeleteNonExistentQuad", "quads" => $dq);
				}
			}
		}
		if($iquads){
			foreach($iquads as $iq){
				if(!$this->addQuad($iq)){
					$errors[] = array("type" => "AddAlreadyExistingQuad", "quads" => $iq);
				}
			}
		}
		if(!$is_test && count($errors) == 0){
			$this->save();
		}
		$dqsr = new DQSResult("Fake DQS", $is_test);
		if(count($errors) > 0) {
			$dqsr->errors = $content;
			return $dqsr->failure($errcode, "DQS call failed", "Service returned ".count($errors)." errors");
		}
		else {
			return $dqsr->accept();
		}
		return $dqsr;
	}

	/**
	 * Generates a simple html represention of the contents of the fake triple store
	 * @return string the html table
	 */
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
