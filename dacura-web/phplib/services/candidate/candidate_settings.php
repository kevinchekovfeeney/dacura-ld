<?php
$settings = array(
	"service-button-title" => "Data",
	"tables" => array(
			"history" => array("datatable_options" => array(
					"jQueryUI" => true, "scrollX" => true, "info" => true, "order" => array(0, "desc"),
					"aoColumns" => array(null, null, array("iDataSort" => 3), array("bVisible" => false), null, null))),
			"candidate" => array("datatable_options" => array(
					"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100),
					"info" => true, "order" => array(8, "desc"),
					"aoColumns" => array(null, null, null, array("bVisible" => false), null, null, array("iDataSort" => 7), array("bVisible" => false), array("iDataSort" => 9), array("bVisible" => false)))),
			"updates" => array("datatable_options" => array(
					"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100),
					"info" => true, "order" => array(10, "desc"),
					"aoColumns" => array(null, null, null, null, array("bVisible" => false), null, null, null, array("iDataSort" => 9), array("bVisible" => false), array("iDataSort" => 11), array("bVisible" => false)))),
	),
		
	"history_datatable_init_string" => '{ 
		"order": [0, "desc"], 
		"info": true,
		"jQueryUI": true,
		 "scrollX": true,
		 "aoColumns": [
            null,
			null,
			{"iDataSort": 3},
          	{"bVisible": false},
			null,
			null
    	]						
	}',
	"pending_datatable_init_string" => '{ 
		"order": [4, "desc"], 
		"info": true,
		"jQueryUI": true,
		 "scrollX": true,
		 "aoColumns": [
            null,
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
	"candidate_datatable_init_string" => '{
		"order": [5, "desc"],
		"info": true,
		"jQueryUI": true,
		 "scrollX": false,
	 	 "pageLength": 20,
	     "lengthMenu": [ 10, 20, 50, 75, 100 ],
		 "aoColumns": [
            null,
			null,
			null,
        	null,
		  	{"bVisible": false},
			{"iDataSort": 6},
          	{"bVisible": false},
          	{"iDataSort": 8},
          	{"bVisible": false}
    	]
	}',
		
	"updates_datatable_init_string" => '{
		"order": [7, "desc"],
		"info": true,
		"jQueryUI": true,
		 "scrollX": true,
	 	 "pageLength": 20,
	     "lengthMenu": [ 10, 20, 50, 75, 100 ],
		 "aoColumns": [
          		null,
			 	null,
				null,
				null,
				null,
		 		{"bVisible": false},
				{"iDataSort": 7},
                {"bVisible": false},
                {"iDataSort": 9},
                {"bVisible": false}
         ]

	}'
);