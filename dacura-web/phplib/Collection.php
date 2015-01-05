<?php

/*
 * Class representing a collection of datasets in the Dacura System
 * Collections are the highest level division of dacura context. 
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class Collection extends DacuraObject {
	var $id;
	var $name;
	var $config;
	var $status;
	var $datasets = array();
	
	function __construct($i, $n, $c, $s='active'){
		$this->id = $i;
		$this->name = $n;
		$this->config = $c;
		$this->status = $s;
	}
	
	function setDatasets($ds){
		$this->datasets = $ds;
	}
	
	function addDataset($i, $ds){
		$this->datasets[$i] = $ds;
	}
}

