<?php
getRoute()->get('/available_context', 'contexts');
getRoute()->get('/available_context/(\w+)', 'contexts');
//getRoute()->post('/', 'update');
//getRoute()->delete('/', 'delete');

function contexts($type = false){
	global $service;
	$hsds = new DacuraServer($service);
	$choices = $hsds->getUserAvailableContexts($type);
	if($choices){
		echo json_encode($choices);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}