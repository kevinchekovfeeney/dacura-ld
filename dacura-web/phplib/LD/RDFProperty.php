<?php
/*
 * Class Representing an RDF property with a label.
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */



class RDFProperty {
	var $url;
	var $domain;
	var $range;
	var $label;

	function __construct($url, $label, $dom = "", $range = ""){
		$this->url = $url;
		$this->domain = $dom;
		$this->range = $range;
		$this->label = $label;
	}


}