<?php
$settings = array(
	"tables" => array(
		"history" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => true, "info" => true, "order" => array(0, "desc"), 
				"aoColumns" => array(null, null, array("iDataSort" => 3), array("bVisible" => false), null, null))),
		"ld" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(8, "desc"), 
				"aoColumns" => array(null, null, null, array("bVisible" => false), null, null, array("iDataSort" => 7), array("bVisible" => false), array("iDataSort" => 9), array("bVisible" => false)))),
		"updates" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(10, "desc"), 
				"aoColumns" => array(null, null, null, null, array("bVisible" => false), null, null, null, array("iDataSort" => 9), array("bVisible" => false), array("iDataSort" => 11), array("bVisible" => false)))),
	),
	"pending_datatable_init_string" => '{ 
		"order": [3, "desc"], 
		"info": true,
		"jQueryUI": true,
		 "scrollX": true,
		 "aoColumns": [
            null,
            null,
            null,
            {"iDataSort": 4},
          	{"bVisible": false},
			{"iDataSort": 2},
          	{"bVisible": false},
			null,
			null
		]						
	}',	
	"opending_datatable_init_string" => '{ 
		"order": [0, "desc"], 
		"info": true,
		"jQueryUI": true,
		 "scrollX": true,
		 "aoColumns": [
            null,
            {"iDataSort": 2},
          	{"bVisible": false},
			null,
			null,
			null
		]						
	}',	
	);