<?php


function testComet(){
	global $service;
	$sdas = new ScraperDacuraServer($service);
	$sdas->start_comet_output();
	$i = 100;
	while($i-- > 0){
		$sdas->write_comet_update("success", "$i is the loop<br>");
		usleep(200000);
	}
	$sdas->end_comet_output();
}
