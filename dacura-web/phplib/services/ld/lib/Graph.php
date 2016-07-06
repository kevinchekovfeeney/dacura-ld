<?php
include_once("Ontology.php");

/**
 * Class representing a graph - one of dacura's three basic linked data object types
 *
 * @author Chekov
 * @license GPL V2
 */
class Graph extends LDO {
	/** array - containing the dqs test configuration **/
	var $dqs = array();
	
	/**
	 * Adds rules for loading dependences and replacing blank ids 
	 * @see LDO::setLDRules()
	 */
	function setLDRules(&$srvr){
		parent::setLDRules($srvr);
		$this->rules->setRule("import", "load_dependencies", true);
		$this->rules->setRule("import", "replace_blank_ids", false);	
		$this->rules->setRule("update", "replace_blank_ids", false);	
	}
	
	/**
	 * Extends method to load the dependencies of graphs upon import 
	 * @see LDO::importLD()
	 */
	function importLD($mode, $srvr){
		if(!parent::importLD($mode, $srvr)){
			return false;
		}
		if($this->rule($mode, "import", 'load_dependencies')){
			if(!$this->processImports($mode, $srvr)){
				return false;
			}
		}
		$this->loadDQSTestConfiguration($srvr);
		return true;
	}
	
	/**
	 * Loads the dqs configuration after the graph is loaded from the DB
	 * @see LDO::deserialise()
	 */
	function deserialise(LdDacuraServer &$srvr){
		$this->loadDQSTestConfiguration($srvr);
	}
	
	/**
	 * @see LDO::getValidMetaProperties()
	 */
	function getValidMetaProperties(){
		return array("status", "title", "two_tier_schemas", "image", "explicit_schema_imports", "instance_dqs_tests", "schema_dqs_tests", "selected_ontologies");
	}
	
	/**
	 * @see LDO::getDefaultGraphURL()
	 */
	function getDefaultGraphURL(){
		return $this->instanceGname();
	}
	

	/**
	 * The url/id of the named graph where this graph's instance data is stored
	 * @return string graph url
	 */
	function instanceGname(){
		return $this->cwurl;
	}
	
	/**
	 * The url/id of the named graph where this graph's schema data is stored
	 * @return string schema url
	 */
	function schemaGname(){
		return $this->cwurl ."/schema";
	}
	
	/**
	 * The url/id of the named graph where this graph's schema schema data is stored
	 * @return string schema/schema url 
	 */
	function schemaSchemaGname(){
		return $this->cwurl ."/schema/schema";
	}
	
	/**
	 * Graphs can be configured to have 2-tier schemas with a schema/schema graph and a schema graph
	 * @return boolean - true if the graph is configured to have a two tier schema
	 */
	function hasTwoTierSchema(){
		return isset($this->meta['two_tier_schemas']) && $this->meta['two_tier_schemas'];
	}
	
	/**
	 * Returns the data about the graph that is necessary for the console to do graph reading and updating
	 */
	function getConsoleData($dacura_server){
		$cdata = array("url" => $dacura_server->getGraphAPIEndpoint($this->id), "instance" => $this->instanceGname(), "schema" => $this->schemaGname());
		$imports = $this->getSchemaImports($dacura_server->durl());
		if(count($imports) > 0){
			$cdata['imports'] = array();
			foreach($imports as $oid => $ostruct){
				$ostruct['url'] =  Ontology::getOntologyURL($ostruct, $dacura_server->durl());
				$cdata['imports'][$oid] = $ostruct;
			}
			$exps = $this->meta['explicit_schema_imports'];
			if(count($exps) > 0){
				$deploys = array();
				foreach($exps as $exp){
					if(is_array($exp)){
						if(isset($exp['version']) && $exp['version'] == 0){
							if(!isset($exp['collection']) || !$exp['collection']){
								$exp['collection'] = $dacura_server->getOntologyCollection($exp['id']);
							}
							$deploys[] = Ontology::getOntologyURL($exp, $dacura_server->durl());
						}
					}
					else {
						$deploys[] = Ontology::getOntologyURL(array("id" => $exp, "collection" => $dacura_server->getOntologyCollection($exp)), $dacura_server->durl());						
					}
				}
				if(count($deploys) > 0){
					$cdata['deploy'] = array("_:schema" => array("owl:imports" => $deploys));
				}
			}
		}
		return $cdata;
	}
	
	/**
	 * Loads the dqs test configurations for the named graphs of this graph (instance, schema, schema/schema)
	 * @param LdDacuraServer $srvr - the active server (for config information)
	 */
	function loadDQSTestConfiguration(&$srvr){
		if(isset($this->meta['instance_dqs_tests'])){
			$this->dqs['instance'] = $this->meta['instance_dqs_tests'];
		}
		else {
			$this->dqs['instance'] = $srvr->getServiceSetting("create_dqs_instance_tests", array_keys(RVO::getInstanceTests(false)));
		}
		if(isset($this->meta['schema_dqs_tests'])){
			$this->dqs['schema'] = $this->meta['schema_dqs_tests'];
		}
		else {
			$this->dqs['schema'] = $srvr->getServiceSetting("create_dqs_schema_tests", array_keys(RVO::getSchemaTests(false)));
		}
		if($this->meta && !isset($this->meta['two_tier_schemas'])){
			$this->meta['two_tier_schemas'] = $srvr->getServiceSetting("two_tier_schemas", true);
		}
	}
	
	/**
	 * The set of tests that will be run when a new instance data object is written to this graph
	 * @return array|string either an array of the instance tests or the string "all"
	 */
	function getCreateInstanceTests() {
		return $this->dqs['instance'];
	}

	/**
	 * The set of tests that will be run when the graph schema is created 
	 * @return array|string either an array of the schema tests or the string "all"
	 */
	function getCreateSchemaTests() {
		return $this->dqs['schema'];
	}

	/**
	 * The set of tests that will be run when the graph schema is updated
	 * @return array|string either an array of the schema tests or the string "all"
	 */
	function getUpdateSchemaTests() {
		return $this->getCreateSchemaTests();
	}
	
	/**
	 * The set of tests that will be run when an instance data object (candidate) is updated
	 * @return array|string either an array of the instance tests or the string "all"
	 */
	function getUpdateInstanceTests() {
		return $this->getCreateInstanceTests();
	}

	/**
	 * The set of tests that will be run when validating instance data 
	 * @return array|string either an array of the instance / schema tests or the string "all"
	 */
	function getValidateInstanceTests() {
		return array_merge($this->dqs['instance'], $this->dqs['schema']);
	}
	
	/**
	 * The set of tests that will be run when validating schema deletes;
	 * @return array|string either an array of the schema tests or the string "all"
	 */
	function getDeleteSchemaTests(){
		return array();
	}
	
	/**
	 * The set of tests that will be run when validating instance deletes;
	 * @return array|string either an array of the instance tests or the string "all"
	 */
	function getDeleteInstanceTests(){
		return array();
	}
	
	/**
	 * The set of tests that will be run when validating schema rollbacks in emergencies;
	 * @return array|string either an array of the schema tests or the string "all"
	 */
	function getRollbackSchemaTests(){
		return array();
	}
	
	/**
	 * Returns a list of the local ontologies imported by the graph's schema 
	 * @param string $durl the dacura baseline url - for parsing ontology urls more easily
	 * @return array<string:<array>> the ontology id to a structure describing the version and collection id of the imported ontology
	 */
	function getSchemaImports($durl){
		$urls = $this->getPredicateValues("_:schema", "imports", "owl");
		$imports = array();
		if($urls){
			foreach($urls as $url){
				if($parsed_url = Ontology::parseOntologyURL($url, $durl)){
					$imports[$parsed_url['id']] = $parsed_url;
				}
			}
		}
		return $imports;
	}
	
	/**
	 * Returns a list of the local ontologies imported by the graph's schema schema
	 * @param string $durl the dacura baseline url - for parsing ontology urls more easily
	 * @return array<string:<array>> the ontology id to a structure describing the version and collection id of the imported ontology
	 */
	function getSchemaSchemaImports($durl){
		$urls = $this->getPredicateValues("_:schema/schema", "imports", "owl");
		$imports = array();
		if($urls){
			foreach($urls as $url){
				if($parsed_url = Ontology::parseOntologyURL($url, $durl)){
					$imports[$parsed_url['id']] = $parsed_url;
				}
			}
		}
		return $imports;
	}
	
	/**
	 * Analyses the graph by invoking schema validation, then instance validation, also identifies entity classes
	 * @see LDO::analyse()
	 */
	function analyse(LdDacuraServer &$srvr){
		$astruct = parent::analyse($srvr);
		if($this->is_accept()){
			$astruct['schema_validation'] = $srvr->graphman->validateSchema($this);
		}
		else {	
			$astruct['schema_validation'] = $srvr->objectPublished($this, true);
		}
		$astruct['instance_validation'] = $srvr->graphman->validateGraph($this);
		$ar = $srvr->graphman->invokeDCS($this->schemaGname());
		if($ar->is_accept()){
			$clss = $ar->result;
			$ncls = array();
			foreach($clss as $i => $c){
				if(is_array($c)){
					$ncls[$i] = $c;
					if($comp = $this->nsres->compress($c['class'])){
						$ncls[$i]['shorthand'] = $comp;
					}					
				}
				else {
					if($comp = $this->nsres->compress($c)){
						$ncls[] = $comp;
					}
					else {
						$ncls[] = $c;
					}
				}
			}
			$astruct['entity_classes'] = $ncls;
		}
		else {
			$astruct['entity_classes'] = false;
		}
		return $astruct;
	}
	
	/**
	 * Called when a graph is updated - 
	 * 
	 * processes the imports specified in the update and includes them in the graph's schema or schema/schema graph
	 * 
	 * Directly specified imports (having no version associated with them) 
	 * are saved in a meta-data field explicit_schema_imports to enable the UI to hide the implicit inclusions	 
	 * @param string $mode - the mode (replace|create|update)
	 * @param LdDacuraServer $srvr
	 * @return boolean
	 */
	function processImports($mode, LdDacuraServer $srvr){
		$simports = $this->getSchemaImports($srvr->durl());
		$nimporturls = array();
		$changed_schema = false;
		foreach($simports as $id => $simport){
			if(!isset($simport['version']) || $simport['version'] == 0){
				if(!$changed_schema || !isset($this->meta['explicit_schema_imports'])){
					$this->meta['explicit_schema_imports'] = array();
				}
				$changed_schema = true;
				$this->meta['explicit_schema_imports'][] = $id;
				$cid = isset($simport['collection']) && $simport['collection'] ? $simport['collection'] : $srvr->getOntologyCollection($id);
				if(!$ont = $srvr->loadLDO($id, "ontology", $cid)){
					return $this->failure_result($srvr->errmsg, $srvr->errcode);
				}
				$nimporturls[$id] = $ont->getImportURL();
				$deps = $ont->getDependencies($srvr, "schema", array_keys($nimporturls), true);
				foreach($deps as $oid => $rec){
					if(!isset($simports[$oid])){
						$nimporturls[$oid] = Ontology::getOntologyURL($rec, $srvr->durl());						
					}
				}
			}
			else {
				$nimporturls[$id] = Ontology::getOntologyURL($simport, $srvr->durl());
			}
		}
		if(count($nimporturls) > 0){
			$this->ldprops["_:schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));
		}
		if($this->hasTwoTierSchema()){
			if($changed_schema){
				$this->generateSchemaSchemaImports($srvr);				
			}
			else {
				$simports = $this->getSchemaSchemaImports($srvr->durl());
				$nimporturls = array();
				$changed_schema_schema = false;
				foreach($simports as $id => $simport){
					if(!isset($simport['version']) || $simport['version'] == 0){
						if(!$changed_schema_schema || !isset($meta['explicit_schema_schema_imports'])){
							$meta['explicit_schema_schema_imports'] = array();
						}
						$changed_schema_schema = true;
						$meta['explicit_schema_schema_imports'][] = $simport['id'];
						$cid = isset($simport['collection']) && $simport['collection'] ? $simport['collection'] : $srvr->getOntologyCollection($id);
						$nimporturls[$id] = $ont->getImportURL();						
						$ont = $srvr->loadLDO($id, "ontology", $cid);
						$deps = $ont->getDependencies($srvr, "schema/schema", array_keys($nimporturls), true);
						foreach($deps as $oid => $rec){
							if(!isset($simports[$oid])){
								$nimporturls[$id] = Ontology::getOntologyURL($rec, $srvr->durl());						
							}
						}
					}
					else {
						$nimporturls[$id] = Ontology::getOntologyURL($simport, $srvr->durl());
					}
				}
				if(count($nimporturls) > 0){
					$this->ldprops["_:schema/schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));			
				}	
			}
		}
		$this->buildIndex();
		return true;
	}
	
	/**
	 * Generates the imports from imported urls and writes them to the object
	 * @param LdDacuraServer $srvr
	 */
	function generateSchemaImports(LdDacuraServer &$srvr){
		$parsed_urls = $this->getSchemaImports($srvr->durl());
		$dependencies = $this->generateDependenciesFromURLs($srvr, $parsed_urls, "schema");
		$nimporturls = array();
		foreach($dependencies as $id => $ont){
			$nimporturls[$id] = $ont->getImportURL();
		}
		$this->ldprops["_:schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));
	}
	
	/**
	 * Generates the schema schema imports from update urls and writes them to the object
	 * @param LdDacuraServer $srvr
	 */
	function generateSchemaSchemaImports(&$srvr){
		$parsed_urls = array_merge($this->getSchemaImports($srvr->durl()), $this->getSchemaSchemaImports($srvr->durl()));
		$dependencies = $this->generateDependenciesFromURLs($srvr, $parsed_urls, "schema/schema");
		$nimporturls = array();
		foreach($dependencies as $id => $ont){
			$nimporturls[$id] = $ont->getImportURL();
		}
		$this->ldprops["_:schema/schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));
	}
	
	/**
	 * Generates the set of dependencies from urls specified explicitly in an imported object
	 * @param LdDacuraServer $srvr
	 * @param array $parsed_urls an array of urls structures with (id, collection, fragment_id, version) fields
	 * @return array an array containing of all the dependendent ontologies. 
	 */
	function generateDependenciesFromURLs(&$srvr, $parsed_urls, $type = 'schema'){
		$dependencies = array();
		foreach($parsed_urls as $parsed_url){
			if(!isset($dependencies[$parsed_url['id']])){
				$sont = $srvr->loadLDO($parsed_url['id'], "ontology", $parsed_url['collection'], $parsed_url['fragment'], $parsed_url['version']);
				if($sont){
					$dependencies[$parsed_url['id']] = $sont;
				}
				else {
					return $this->failure_result("Failed to load ontology ".$parsed_ur['id']. $srvr->errmsg, $srvr->errcode);
				}
				$dependencies = array_merge($dependencies, $sont->getDependentOntologies($srvr, $type, array_keys($dependencies)));
			}
			else {
				return $this->failure_result("Could not translate parsed url into dacura schema dependency", 404);
			}
		}
		return $dependencies;
	}
}
