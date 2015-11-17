<?php
$settings = array(
	"ld_datatable_init_string" => '{
		"order": [4, "desc"],
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
			{"iDataSort": 5},
          	{"bVisible": false},
          	{"iDataSort": 7},
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
				{"iDataSort": 7},
                {"bVisible": false},
                {"iDataSort": 9},
                {"bVisible": false}
           ]

	}'
);