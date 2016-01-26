<?php
require_once("DacuraFormElement.php");
/**
 * Class representing a user interface (html) form 
 * 
 * Forms can be read-only (view), read-write (update) or write-only (create)
 * Creation Date: 15/11/2015
 * @author Chekov
 * @license GPL V2
 */
class DacuraForm extends DacuraObject {
	/** @var array(DacuraFormElement) an array of form elements, each representing a line in the form */
	var $elements = array();
	/** @var array() a name-value array of settings for this form */
	var $settings = array();
	
	/** 
	 * @param string $id Dacura ID
	 * @param string $type update | view | create - which type of form is this
	 * @param array $settings the settings for the form
	 */
	function __construct($id, $settings = array()){
		$this->id = $id;
		$this->settings = $settings;
	}
	
		
	
	function addMetaDataColumn($name, $val, $type, $options = array()){
		foreach($this->elements as $i => $el){
			$this->elements[$i]->addMeta($name, $val, $type, $options);
		}
	}
	
	/**
	 * Adds an array of elements to the form
	 * @param DacuraFormElement[] $rows form elements to be added to the form
	 * @return boolean true if the elements were all legal, false otherwise
	 */
	function addElements(array $rows){
		foreach($rows as $row){
			$dfe = new DacuraFormElement();
			if($dfe->load($row, $this->settings)){
				if(isset($this->settings['meta'])){
					foreach($this->settings['meta'] as $mf => $mvals){
						$dfe->addMeta($mf, $mvals[0], $mvals[1], $mvals[2]);		
					}
				}
				$this->elements[] = $dfe;
			}
			else {
				return $this->failure_result($dfe->errmsg, $dfe->errcode);
			}
		}
		return true;
	}
	
	/**
	 * Generage a html representation of the form
	 * @param string $jdid the html id of the table that will be created to hold the form
	 * @param array $context context in which the form is being serialised - an array of html element ids within which the form finds itself 
	 * @return string the html table of the form
	 */
	function html($jdid, $context = array()){
		$context[] = $jdid;
		$html = "<table class='dacura-property-table dacura-".$this->settings['display_type']."-table' id='$jdid'>";
		$html.= $this->table_header($context);
		$html .= "<tbody>";
		foreach($this->elements as $el){
			$html .= $el->tr($this->settings, $context);			
		}
		$html .="</tbody></table>";		
		return $html;
	}
	
	function table_header($context){
		$html = "<thead>";
		if(isset($this->settings['show-header']) && $this->settings['show-header'] > count($context)){
			$html .= "<tr><th colspan='". (2 + count($this->settings['meta'])) . "' class='dacura-property-label'>" . $this->settings['header-html']."</th></tr>";
			$html .= "</thead>";
		}
		return $html;
	}
}




