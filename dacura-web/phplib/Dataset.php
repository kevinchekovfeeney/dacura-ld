<?php

/*
 * Class representing a dataset in the Dacura System
 * Datasets are the secondary level division of dacura context: datasets belong to collections.
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class Dataset {
	var $id;
	var $name;
	var $config;
	var $collection_id;
	var $status;

	function __construct($i, $n, $c, $st, $ci){
		$this->id = $i;
		$this->name = $n;
		$this->config = $c;
		$this->status = $st;
		$this->collection_id = $ci;
	}
}