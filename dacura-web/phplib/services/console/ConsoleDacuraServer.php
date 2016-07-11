<?php 
class ConsoleDacuraServer extends LdDacuraServer {
	function getURLConnections($url){
		$ret = array("connectors" => array(), "locators" => array());
		return $ret;
	}
}