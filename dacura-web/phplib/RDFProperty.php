<?php
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