<?php
$settings = array(
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
	"ld_datatable_init_string" => '{
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
			null,
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
				null,
				null,
				{"iDataSort": 7},
                {"bVisible": false},
                {"iDataSort": 9},
                {"bVisible": false}
         ]

	}'
);