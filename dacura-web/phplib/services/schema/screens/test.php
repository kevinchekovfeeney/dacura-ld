<?php 
	$params = array("format" => "jsonld", "ns" => true, "links" => true, "problems" => true, "typed" => true, 
			"status_options" => "<option value='something'>something</option>",
			"update_status_options" => "<option value='something'>something</option>");
	$service->showLDEditor($params);
?>
