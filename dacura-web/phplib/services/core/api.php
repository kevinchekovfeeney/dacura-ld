<?php
/**
 * API provided by core dacura service
 * @package core/api
 * @author chekov
 * @license GPL V2
 */

getRoute()->get('/available_context', 'contexts');
getRoute()->get('/available_context/(\w+)', 'contexts');

/**
 * returns a list of the collection contexts available to a user
 * 
 * GET /available_context/$role
 * @api
 * @param string [$role] if present, only return collections where the user has at least this role 
 * @return 
 */
function contexts($role = false){
	global $service;
	$hsds = new DacuraServer($service);
	$choices = $hsds->getUserAvailableContexts($type);
	if($choices){
		echo json_encode($choices);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}