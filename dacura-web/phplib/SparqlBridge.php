<?php
/**
 * Extract the data structure from the RDFS
 * 
 * We need: 
 * 1. All the properties of which the entity is the domain
 * 2. The class hierarchy (if any in which the entity exists)
 */

require_once( "sparqllib.php" );
require_once( "RDFProperty.php" );
require_once( "NSURI.php" );



class SparqlBridge {
	
	var $sparqluri = "";
	var $prefixes = array(
		"rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
		"rdfs" => "http://www.w3.org/2000/01/rdf-schema#"	
	);
	
	var $errmsg;
	
	function SparqlBridge($uri){
		$this->sparqluri = $uri;
		$this->ns = new NSURI();
	}
	
	function getPrefixString(){
		$str = "";
		foreach($this->prefixes as $i => $p){
			$str .= "prefix $i: <$p>\n";
		}
		return $str;
	}

	function askSparql($sparql){
		$db = sparql_connect( $this->sparqluri);
		if( !$db ) { 
			$this->errmsg = sparql_errno() . ": " . sparql_error(). "\n"; 
			return false;
		}	
		$result = $db->query( $sparql );
		if( !$result ) { 
			$this->errmsg = $db->errno() . ": " . $db->error(). "\n";
			return false;
		}
		return $result;
	}

	function print_result_fields($result){
		$fields = $result->field_array( $result );
		print "<p>Number of rows: ".$result->num_rows( $result )." results.</p>";
		print "<table class='example_table'>";
		print "<tr>";
		foreach( $fields as $field )
		{
			print "<th>$field</th>";
		}
		print "</tr>";
		while( $row = $result->fetch_array() )
		{
			print "<tr>";
			foreach( $fields as $field )
			{
				print "<td>$row[$field]</td>";
			}
			print "</tr>";
		}
		print "</table>";
	}
	
	function getEntityProperties($entity, $graphname) {
		$propertystruct = array();
		$str = $this->getPrefixString();
		/* 
		 * Get all properties of the entity, the 
		 * type of the range and the label of the property 
		 */
		$str .= "SELECT ?Property ?propertylabel ?rangetype  { 
			GRAPH <$graphname> {
			?Property a rdf:Property;
				rdfs:domain <$entity>; 
				rdfs:range ?rangetype;  
				rdfs:label ?propertylabel.  
			} 
	    }";	
		$result = $this->askSparql($str);
		if($result === false){
			return false;
		}
		$fields = $result->field_array( $result );
		if(count($fields) == 0){
			return array();
		}
		while( $row = $result->fetch_array() ) {
			$fieldrecord = array();
			foreach($fields as $f){
				$fieldrecord[$f] = $row[$f];
			}
			$field_obj = new RDFProperty($fieldrecord['Property'], $fieldrecord['propertylabel'], "", $fieldrecord['rangetype']);
			$propertystruct[] = $field_obj;
		}
		
		return $propertystruct;
	}

	
	function getInstanceIDs($type, $graph, $start = 0, $num = 20){
		$ids = array();
		$sparql =  "SELECT ?id { 
			GRAPH <$graph> {
			?id a <$type>.
			}
	    }
		LIMIT $num
		OFFSET $start	    
		";
		$res = $this->askSparql($sparql);
		if($res === false){
			return false;
		}
		
		$fields = $res->field_array( $res );
		if(count($fields) == 0){
			return $ids;
		}
		else {
			while( $row = $res->fetch_array() ) {
				foreach($fields as $f){
					$ids[] = $row[$f];
				}
			}
		}
		return $ids;		
	}


	function getInstanceIDsORDERHACK($type, $graph, $key, $start = 0, $num = 20, $order = "ASC"){
		$ids = array();
		$sparql =  "SELECT ?id { 
			GRAPH <$graph> {
			?id a <$type>; $key ?order
			}
	    }
	    ORDER BY $order(?order)
		LIMIT $num
		OFFSET $start	    
		";
		$res = $this->askSparql($sparql);
		if($res === false){
			return false;
		}
		
		$fields = $res->field_array( $res );
		if(count($fields) == 0){
			return $ids;
		}
		else {
			while( $row = $res->fetch_array() ) {
				foreach($fields as $f){
					$ids[] = $row[$f];
				}
			}
		}
		return $ids;		
	}
	
	function getInstancePropertyValue($schema, $entitygraph, $id, $rp){
		$val = array();
		$val["type"] = $rp->range;
		$val["label"] = $rp->label;
		$isparql = 	"SELECT ?val {
		GRAPH <$entitygraph> {
		<$id> <".$rp->url."> ?val .
					}
				}";
		$res = $this->askSparql($isparql);
		if($res === false){
			return false;
		}
		
		//$val['sparql'] = $isparql;
		$nfields = $res->field_array( $res );
		if(count($nfields) == 0){
			$val['id'] = "NONE";
		}
		else {
			while( $row = $res->fetch_array() ) {
				$val['id'] = $row[$nfields[0]];
				$val['value'] = array();
				if($this->isLiteral($nfields[0])){
					$val["value"][] = $row[$nfields[0]];
				}
				else {
					//$val["id"] = $row[$nfields[0]];//"not a literal";
					$vfields = $this->getEntityProperties($rp->range, $schema);
					foreach($vfields as $onevf){
						$val["value"][] = $this->getInstancePropertyValue($schema, $entitygraph, $val['id'], $onevf);
					}
					//opr($vfields);
				}
			}
		}
		return $val;
	}
	
	function getSchemaValues($schemagraphname, $type){
		$vals = array();
		$isparql = $this->getPrefixString() .
			"SELECT ?val ?lab {
				GRAPH <$schemagraphname> {
					?val a <$type> .
					?val rdfs:label ?lab.
				}
			}";
		$res = $this->askSparql($isparql);
		if($res === false){
			return false;
		}
		
		//$val['sparql'] = $isparql;
		$nfields = $res->field_array( $res );
		if(count($nfields) > 0){
			while( $row = $res->fetch_array() ) {
				$vals[$row[$nfields[0]]] = $row[$nfields[1]];
			}
		}
		return $vals;
	}
	
	function getInstanceData($id, $entity, $schemagraphname, $entitygraph){
		//First we get the motivations and categories
		$pvns = $this->ns->getnsuri("pv");
		$motivation = $pvns . "motivation";
		$category = $pvns . "category";
		$fatalities = $pvns . "fatalities";
		$unstructuredfatalities = $pvns . "unstructuredFatalities";
		$minfatalities = $pvns . "minimumFatalities";
		$maxfatalities = $pvns . "maximumFatalities";
		$valfatalities = $pvns . "fatalitiesValue";
		$description = $pvns . "description";
		$unknownfatalities = $pvns. "unknownFatalities";
		$location = $pvns."location";
		$unstructuredlocation = $pvns."unstructuredLocation";
		$dbpedialocation = $pvns."dbpediaLocation";
		$Fatalities = $pvns . "Fatalities";
		
		
		$data = array(
			"motivation" => array(),
			"category" => array(),
			"fatalities" => array()		
		);
		
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?motivation_url ?motivation_label ?motivation_comment ?category_url ?category_label ?category_comment {
			GRAPH <$entitygraph> {
				<$id>  <$motivation> ?motivation_url;
				<$category> ?category_url.
			}
			GRAPH <$schemagraphname> {
				?motivation_url rdfs:label ?motivation_label;
				rdfs:comment ?motivation_comment.
				?category_url rdfs:label ?category_label;
				rdfs:comment ?category_comment.
			}
		}";
		$res = $this->askSparql($sparql);
		if($res === false){
			return false;
		}
		
		while( $row = $res->fetch_array() ) {
			if(!isset($data['motivation'][$row['motivation_url']])){
				$data['motivation'][$row['motivation_url']] = array(
					"type" => "select", 
					"label" => $row['motivation_label'], 
					"comment" => $row['motivation_comment']
				);
			}
			if(!isset($data['category'][$row['category_url']])){
				$data['category'][$row['category_url']] = array(
						"type" => "select",
						"label" => $row['category_label'],
						"comment" => $row['category_comment']
				);
			}
				
			//foreach($fields as $fld){
			//	$data[$fld] = $row[$fld];
			//}
		}
		//Next fatalities
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?fatalities_url ?fatalities_label ?fatalities_comment {
			GRAPH <$entitygraph> {
				<$id>  <$fatalities> ?fatalities_url.
			}
			GRAPH <$schemagraphname> {
				<$Fatalities> rdfs:label ?fatalities_label;
				rdfs:comment ?fatalities_comment.
			}
		}";
		$res = $this->askSparql($sparql);
		if($res === false){
			return false;
		}
		
		$row = $res->fetch_array();
		if($row){
			$fat_url = $row['fatalities_url'];
			if($fat_url == $unknownfatalities){
				$data['fatalities'][$fat_url]['type'] = "unknown";
			}
			else {
				$fat_comment = $row['fatalities_comment'];
				$fat_label = $row['fatalities_label'];
				$data['fatalities'][$fat_url] = array('comment' => $fat_comment, "label" => $fat_label);
				$sparql = $this->getPrefixString();
				$sparql .= "SELECT ?fatalities_unstruct {
					GRAPH <$entitygraph> {
						<$fat_url>  <$unstructuredfatalities> ?fatalities_unstruct.
					}
				}";
				$res = $this->askSparql($sparql);
				if($res === false){
					return false;
				}
				
				$row = $res->fetch_array();
				if($row){
					$data['fatalities'][$fat_url]['unstructured_value'] = $row["fatalities_unstruct"];
					$data['fatalities'][$fat_url]['type'] = 'unstructured';
				}		
			
				$sparql = $this->getPrefixString();
				$sparql .= "SELECT ?val_fatalities {
				GRAPH <$entitygraph> {
					<$fat_url>  <$valfatalities> ?val_fatalities;
					}
				}";
				$res = $this->askSparql($sparql);
				if($res === false){
					return false;
				}
				
				$row = $res->fetch_array();
				if($row){
					$data['fatalities'][$fat_url]['value'] = $row["val_fatalities"];
					$data['fatalities'][$fat_url]['type'] = 'value';
				}
					
				$sparql = $this->getPrefixString();
				$sparql .= "SELECT ?max_fatalities ?min_fatalities {
					GRAPH <$entitygraph> {
						<$fat_url>  <$maxfatalities> ?max_fatalities;
						<$minfatalities> ?min_fatalities.
					}
				}";
				$res = $this->askSparql($sparql);
				if($res === false){
					return false;
				}
				
				$row = $res->fetch_array();
				if($row){
					$data['fatalities'][$fat_url]['max_value'] = $row["max_fatalities"];
					$data['fatalities'][$fat_url]['min_value'] = $row["min_fatalities"];
					$data['fatalities'][$fat_url]['type'] = 'range';
				}
			}
		}
		//next description 
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?descr {
			GRAPH <$entitygraph> {
				<$id>  <$description> ?descr.
			}
		}";
		$res = $this->askSparql($sparql);
		if($res === false){
			return false;
		}
		
		$row = $res->fetch_array();
		if($row){
			$data['description']['unstructured_value'] = $row["descr"];
			$data['description']['type'] = 'unstructured';
		}
		
		//next location
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?loc_id {
			GRAPH <$entitygraph> {
				<$id>  <$location> ?loc_id.
			}
		}";
		$res = $this->askSparql($sparql);
		if($res === false){
			return false;
		}
		
		$row = $res->fetch_array();
		if($row){
			$loc_id = $row["loc_id"];
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?unstruc_loc {
				GRAPH <$entitygraph> {
					<$loc_id>  <$unstructuredlocation> ?unstruc_loc.
				}
			}";
			$res = $this->askSparql($sparql);
			if($res === false){
				return false;
			}
			
			$row = $res->fetch_array();
			if($row){
				$data['location']['unstructured_value'] = $row["unstruc_loc"];
				$data['location']['type'] = 'unstructured';
			}
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?dbp_loc {
			GRAPH <$entitygraph> {
				<$loc_id>  <$dbpedialocation> ?dbp_loc.
				}
			}";
			$res = $this->askSparql($sparql);
			if($res === false){
				return false;
			}
			
			$row = $res->fetch_array();
			if($row){
				$data['location']['dbpedia_value'] = $row["dbp_loc"];
				$data['location']['type'] = 'dbpedia';
			}
		}
		
		
		return json_encode($data);
	}
	
	function getInstanceArray($entity, $entitygraph, $schemagraphname, $key, $start = 0, $num = 20, $order = "ASC"){
		//first get property list from schema
		$instances = array();
		$fields = $this->getEntityProperties($entity, $schemagraphname);
		//next get the relevant instance ids
		//hack
		//$ids = $this->getInstanceIDs($entity, $entitygraph, $key, $start, $num, $order);
		$ids = $this->getInstanceIDs($entity, $entitygraph, $start, $num);
		foreach($ids as $id){
			$instances[$id] = array();
			foreach($fields as $field){
				$instances[$id][$field->url] = $this->getInstancePropertyValue($schemagraphname, $entitygraph, $id, $field);
			}
			
		}
		return $instances;
	}
	
	
	function isLiteral($clsname){
		if(($clsname == $this->prefixes['rdf']."Literal") or ($clsname == $this->prefixes['rdfs']."Literal")){
			return true;	
		}
		$ns = new NSURI();
		
		if(substr_compare($ns->getnsuri("xsd"), $clsname, 0) === 0){
			return true;
		}
		return false;
	}
	
	function getSuperClasses($entity, $graphname){
		$classes = array();
		$str = $this->getPrefixString();
		$str .= "SELECT ?Entity { 
			GRAPH <$graphname> {
		 		<$entity> rdfs:subClassOf ?Entity . 
			} 
		}";
		$result = $this->askSparql($str);
		if($result === false){
			return false;
		}
		$fields = $result->field_array( $result );
		if(count($fields) == 0){
			return $classes;
		}
		else {
			while( $row = $result->fetch_array() ) {
				foreach($fields as $f){
					$branch = $this->getSuperClasses($row[$f], $graphname);					
					if(!empty($branch)) $classes[$row[$f]] = $branch;
					else $classes[$row[$f]] = array();
				}
				//$classes[] = $branch;
			}
		}
	    return $classes;		
	}
	
	function getSubClasses($entity, $graphname){
		$classes = array();
		$str = $this->getPrefixString();
		$str .= "SELECT ?Entity { 		
			GRAPH <$graphname> {
		 		?Entity rdfs:subClassOf <$entity> .
	    	} 
		}";
		$result = $this->askSparql($str);
		if($result === false){
			return false;
		}
		$fields = $result->field_array( $result );
		if(count($fields) == 0){
			return $classes;
		}
		else {
			while( $row = $result->fetch_array() ) {
				foreach($fields as $f){
					$branch = $this->getSubClasses($row[$f], $graphname);					
					if(!empty($branch)) $classes[$row[$f]] = $branch;
					else $classes[$row[$f]] = array();
				}
			}
		}
	    return $classes;		
	}
	
	function getEntityClassHierarchy($entity, $namespaceid = "", $namespaceuri = ""){
		$str = $this->getPrefixString();
		if($namespaceid && $namespaceuri){
			$str .= "prefix $namespaceid: <$namespaceuri>\n";
		}
	}	
	
	function instanceIDAvailable($url, $graphname){
		$str = $this->getPrefixString();
		$str .= "SELECT ?p ?o {
			GRAPH <$graphname> {
		    	<$url> ?p ?o	
			}
		}";
		$result = $this->askSparql($str);
		if($result === false){
			return false;
		}
		if(sparql_num_rows( $result ) == 0){
			return true;
		}
		return false;
	}
	
}
