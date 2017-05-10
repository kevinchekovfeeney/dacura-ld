
/**
 * Ontology class
 */
function DacuraOntology(config){
	this.entity_tag = "dacura:Entity";
	this.normals = ["rdfs:subClassOf", "rdf:type", "rdfs:label", "rdfs:comment", "owl:oneOf"];
	this.normalprops = ["rdfs:domain", "rdf:type", "rdfs:label", "rdfs:comment", "rdfs:range"];
	this.properties = {};
	this.classes = {};
	this.others = {};//assertions that are not properties or classes
	this.bncounter = 0;
	this.updated = {};//updated properties/classes id => rdf
	this.updated_meta = {};//updated metadata elements
	this.deleted = [];
}

DacuraOntology.prototype.loadFromLDO = function(ldo){
	this.id = ldo.id;
	this.meta = ldo.meta;
	this.contents = ldo.contents;
	this.fragment_id = ldo.fragment_id;
	this.format = ldo.format;
	this.options = ldo.options;
	for(var id in ldo.contents){
		if(this.isProperty(this.contents[id])){
	    	this.properties[id] = this.contents[id];				
		}
		else if(this.isClass(this.contents[id])){
	    	this.classes[id] = this.contents[id];
	    }
		else {
			this.others[id] = this.contents[id];
		}
	}
	this.tree = this.buildNodeTree();
	this.entity_classes = this.calculateEntityClasses();	
}

/* inherited functions */
//DacuraOntology.prototype.getLabel = DacuraLDO.prototype.getLabel;
//DacuraOntology.prototype.getComment = DacuraLDO.prototype.getComment;
DacuraOntology.prototype.cid = DacuraLDO.prototype.cid;

/* Ontology specific functions */
DacuraOntology.prototype.isEntityClass = function(cls){
	var ecls = this.getEntityClasses();
	return ecls.indexOf(cls) != -1;
}

//building internal structure
DacuraOntology.prototype.buildNodeTree = function() {
	nodes = {};
	for(var i in this.classes){
		if(typeof nodes[i] == "undefined"){
			nodes[i] = {parents: [], children: []};
		}
		var x = this.classes[i]['rdfs:subClassOf'];
		if(typeof x == "string"){
			if(nodes[i].parents.indexOf(x) == -1){
				nodes[i].parents.push(x);
				if(typeof nodes[x] == "undefined"){
					nodes[x] = {parents: [], children: [i]};
				}
				else {
					if(nodes[x].children.indexOf(i) == -1){
						nodes[x].children.push(i);
					}
				}
			}
		}
		else if(typeof x == "object"){
			for(var j = 0; j< x.length; j++){
				if(nodes[i].parents.indexOf(x[j]) == -1){
					nodes[i].parents.push(x[j]);
					if(typeof nodes[x[j]] == "undefined"){
						nodes[x[j]] = {parents: [], children: [i]};
					}
					else {
						if(nodes[x[j]].children.indexOf(i) == -1){
							nodes[x[j]].children.push(i);
						}
					}
				}
			}
		}
	}
	return nodes;
}

DacuraOntology.prototype.calculateEntityClasses = function(){
	var cls = [];
	var totry = [this.entity_tag];
	while(totry.length > 0){
		var ec = totry.shift();
		if(cls.indexOf(ec) == -1){
			cls.push(ec);
			if(typeof this.tree[ec] == "object"){
				for(var i = 0; i<this.tree[ec].children.length; i++){
					totry.push(this.tree[ec].children[i]);
				}
			}
		}
	}
	return cls;
}

DacuraOntology.prototype.getEntityClasses = function(){
	if(typeof this.entity_classes != "object"){
		this.entity_classes = this.calculateEntityClasses();
	}
	return this.entity_classes;	
}

DacuraOntology.prototype.getBoxTypes = function(){
	return this.boxtypes;
}

DacuraOntology.prototype.isDacuraBoxType = function(bt){
	return (this.hasBoxedTypes() && typeof this.boxtypes[bt] != "undefined");
}

DacuraOntology.prototype.hasBoxedTypes = function(){
	return (size(this.boxtypes) > 0);
}

//transforms a simple array [] into an rdf first, rest style linked list
DacuraOntology.prototype.listToRDF = function(choices, bnids){
	if(choices.length == 0) return "rdf:nil";
	//choice to obj
	var choice = choices.shift();
	var bnid = ((bnids && bnids.length) ? bnids.shift() : false);
	var rdffirst = {};
	if(choice.id){
		var nid = choice.id;
		delete(choice["id"]);
		rdffirst[nid] = choice;
	}
	else {
		rdffirst = choice;//id will be automagically generated
	}
	var rdfl = {"rdf:first": jQuery.extend({}, rdffirst)};
	rdfl["rdf:rest"] = this.listToRDF(choices, bnids); 
	if(bnid){
		var ret = {};
		ret[bnid] = rdfl;
		return ret;
	}
	else {	
		return rdfl;
	}
}

DacuraOntology.prototype.RDFToList = function(rdflist, depth){
	var nlist = [];
	if(rdflist['rdf:first']){
		nlist.push(rdflist['rdf:first']);
	}
	else {
		alert("rdf list error: no first at depth " + depth);
		jpr(rdflist);
	}
	if(rdflist['rdf:rest']){
		if(typeof rdflist['rdf:rest'] == "string"){
			if(rdflist['rdf:rest'] != "rdf:nil"){
				var json = this.contents[rdflist['rdf:rest']];
				if(json){
					nlist = nlist.concat(this.RDFToList(json, depth+1));
				}
				else {
					alert("nothing found for "+ rdflist['rdf:rest'] + " " + depth);
				}
			}
		}
		else if(typeof rdflist['rdf:rest'] == "object"){
			var fk = firstKey(rdflist['rdf:rest']);
			nlist = nlist.concat(this.RDFToList(rdflist['rdf:rest'][fk]));
		}
		else {
			alert("no type for " + rdflist['rdf:rest'] +  " in rdf list at depth " + depth);
		}
	}
	else {
		alert("rdf:list error - no rdf:rest element found at depth " + depth);
	}
	return nlist;
}

DacuraOntology.prototype.getClassRDF = function(cid, label, comment, type, parent, choices, others){
	var nclass = {"rdfs:label": label, "rdfs:comment": comment, "rdf:type": "owl:Class"};
	if(type == "enumerated"){
		nclass["rdfs:subClassOf"] = "dacura:Enumerated";
		var oclass = this.classes[cid];
		var bnids = ((oclass && oclass["owl:oneOf"]) && size(oclass["owl:oneOf"])) ? this.extractStructuralBNIDsFromRDFList(oclass["owl:oneOf"]) : false;
		nclass["owl:oneOf"] = this.getEnumeratedTypeChoicesAsRDF(cid, choices, bnids);			
	}
	else if(parent && parent.length){
		nclass["rdfs:subClassOf"] = parent;
	}
	if(others){
		for(var k in others){
			nclass[k] = others[k];
		}
	}
	return nclass;
}

DacuraOntology.prototype.getPropertyRDF = function(label, comment, domain, range, others){
	var nprop = {"rdfs:label": label, "rdfs:comment": comment};
	if(range.substring(0,3) == "xsd" || range.substring(0,3) == "xdd"){
		nprop["rdf:type"] = "owl:DatatypeProperty";
	}
	else {
		nprop["rdf:type"] = "owl:ObjectProperty";
	}
	nprop['rdfs:range'] = range;
	nprop['rdfs:domain'] = domain;
	if(others){
		for(var k in others){
			nprop[k] = others[k];
		}
	}
	return nprop;
}

/*
 * Retrieves the stuctural bnids used to construct the rdf list (not the element ids - the ids that owl:oneOf and rdf:rest point to
 */
DacuraOntology.prototype.extractStructuralBNIDsFromRDFList = function(oldOneOf, follow_unembedded){
	var lst = [];
	if(!oldOneOf || oldOneOf == "rdf:nil"){
		return lst;
	}
	if(typeof oldOneOf == "object"){
		var id = firstKey(oldOneOf);
		lst.push(id);
		var listelem = oldOneOf[id];
	}
	else {
		lst.push(oldOneOf);
		this.deleted.push(oldOneOf);
		var listelem = this.contents[oldOneOf];
		if(typeof listelem['rdf:first'] == "string"){
			this.deleted.push(listelem['rdf:first']);
		}
	}
	if(listelem && typeof listelem == "object" && typeof listelem['rdf:rest'] != "undefined"){
		var nlst = this.extractStructuralBNIDsFromRDFList(listelem['rdf:rest']);
		lst = lst.concat(nlst);
	}			
	return lst;
}


DacuraOntology.prototype.getEnumeratedTypeChoicesAsRDF = function(cid, choices, bnidlist){
	var rdflist = [];
	for(var i = 0; i<choices.length; i++){
		var onec = {"rdfs:label": choices[i].label, "rdfs:comment": choices[i].comment, "rdf:type": cid};
		if(choices[i].id){
			if(choices[i].id.indexOf(':') == -1){
				onec.id = this.id + ":" + choices[i].id;//could also use bn
				//onec.id = "_:" + choices[i].id;
			}
			else {
				onec.id = choices[i].id;
			}
		}
		rdflist.push(onec);
	}
	return this.listToRDF(rdflist, bnidlist);
}

DacuraOntology.prototype.addMetaUpdate = function(id, meta){
	if(typeof this.updated_meta != "object"){
		this.updated_meta = {};
	}
	if(typeof this.updated_meta.elements != "object"){
		this.updated_meta.elements = {};
	}
	if(typeof this.updated_meta.elements[id] != "object"){
		this.updated_meta.elements[id] = {};
	}
	for(var k in meta){
		if(typeof this.updated_meta.elements[id][k] == "undefined"){
			this.updated_meta.elements[id][k] = meta[k];
		}
		else {
			this.updated_meta.elements[id][k] = this.updated_meta.elements[id][k].concat(meta[k]);
		}
	}
}

DacuraOntology.prototype.hasProperty = function(id){
	var cid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	return (typeof this.properties[cid] != "undefined")
}


DacuraOntology.prototype.hasClass = function(id){
	var cid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	return (typeof this.classes[cid] != "undefined")
}

DacuraOntology.prototype.addClass = function(id, label, comment, type, parent, choices, meta){
	var cid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	this.updated[cid] = this.getClassRDF(cid, label, comment, type, parent, choices);
	if(meta){
		this.addMetaUpdate(cid, meta);
	}
}

DacuraOntology.prototype.updateClass = function(id, label, comment, type, parent, choices, others, meta){
	this.updated[id] = this.getClassRDF(id, label, comment, type, parent, choices, others);
	if(meta){
		this.addMetaUpdate(id, meta);
	}
}

DacuraOntology.prototype.addProperty = function(id, label, comment, domain, range, meta){
	var pid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	this.updated[pid] = this.getPropertyRDF(label, comment, domain, range);
	if(meta){
		this.addMetaUpdate(pid, meta);
	}
}

DacuraOntology.prototype.updateProperty = function(id, label, comment, domain, range, others, meta){
	this.updated[id] = this.getPropertyRDF(label, comment, domain, range, others);
	if(meta){
		this.addMetaUpdate(id, meta);
	}
}

DacuraOntology.prototype.deleteClass = function(id){
	this.deleted.push(id);
}

DacuraOntology.prototype.deleteProperty = function(id){
	this.deleted.push(id);
}


DacuraOntology.prototype.getRDF = function(ignore_updates){
	var rdf = {};
	for(var i in this.classes){
		rdf[i] = this.classes[i];
	}
	for(var i in this.properties){
		rdf[i] = this.properties[i];
	}
	for(var i in this.others){
		rdf[i] = this.others[i];
	}
	if(!ignore_updates){
		for(var j in this.updated){
			rdf[j] = this.updated[j];
		}
		for(var i in rdf){
			if(this.deleted.indexOf(i) != -1){
				delete(rdf[i]);
			}
		}
	}
	return rdf;
}


/* functions for extracting information from predicates in the ontology */

DacuraOntology.prototype.isProperty = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:DatatypeProperty" || json['rdf:type'] == "owl:ObjectProperty" || json['rdf:type'] == "rdf:Property") return true;
	}
	if(typeof json['rdfs:subPropertyOf'] != "undefined") return true;
	if(typeof json['rdfs:range'] != "undefined") return true;
	if(typeof json['rdfs:domain'] != "undefined") return true;
	return false;
}

DacuraOntology.prototype.isClass = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:Class" || json['rdf:type'] == "rdfs:Class") return true;
	}
	if(typeof json['rdfs:subClassOf'] != "undefined") return true;
	return false;
}

DacuraOntology.prototype.getPropertyRangeType = function(prop) {
	var json = this.properties[prop];
	if(typeof json == "object"){
		if(typeof json['rdf:type'] != "undefined"){
			if(json['rdf:type'] == "owl:ObjectProperty"){
				if(this.isDacuraBoxType(json['rdfs:range'])){
					return "boxed";
				}
				return "object";
			}
			if(json['rdf:type'] == "owl:DatatypeProperty"){
				return "literal";
			}	
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyDomain = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:domain']  != "undefined"){
			return json['rdfs:domain'];
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyRange = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:range']  != "undefined"){
			return json['rdfs:range'];
		}
	}
	return "";
}

DacuraOntology.prototype.getElementMetadata = function(elid, key){
	if(typeof this.meta.elements == "object" && typeof this.meta.elements[elid] == "object" && typeof this.meta.elements[elid][key] != "undefined"){
		return this.meta.elements[elid][key];
	}
	return false;
}

DacuraOntology.prototype.getPropertyMetadata = function(prop, defs){
	defs = (defs ? defs : {});
	var md = {};
	var hd = this.getElementMetadata(prop, "harvested");
	if(hd !== false){
		md.harvested = jQuery.extend([], hd, true);
	}
	else if(defs.harvested){
		md.harvested = jQuery.extend([], defs.harvested, true);
	}
	var hs = this.getElementMetadata(prop, "harvests");
	if(hs !== false){
		md.harvests = jQuery.extend([], hs, true);
		if(typeof defs.harvests == "object"){
			for(var i = 0; i<defs.harvests.length; i++){
				var wl = new webLocator(defs.harvests[i]);
				var has = false;
				for(var j=0; j<hs.length; j++){
					var wl2 = new webLocator(hs[j]);
					if(wl2.sameAs(wl)){
						has = true;
						continue;
					}
					else {
						//jpr({orig: hs[j], n: defs.harvests[j]});
					}
				}
				if(!has){
					md.harvests.push(defs.harvests[i]);
				}
			}
		}
	}
	else if(defs.harvests){
		md.harvests = jQuery.extend([], defs.harvests, true);		
	}
	return md; 
}

DacuraOntology.prototype.getRelevantConnectors = function(url){
	var rels = {};
	for(var elid in this.meta.elements){
		for(var ctype in this.meta.elements[elid]){
			if(this.meta.elements[elid][ctype].length){
				for(var i=0; i<this.meta.elements[elid][ctype].length; i++){
					var wl = new webLocator(this.meta.elements[elid][ctype][i]);
					if(wl.matchesURL(url)){
						if(wl.pagelocator){
							var coid = wl.pagelocator.uniqid();
							var connector = new pageConnector(wl.pagelocator, ctype, elid);
							if(typeof rels[coid] == "undefined"){
								rels[coid] = [];
							}
							rels[coid].push(connector);
						}
					}
				}
			}
		}
	}
	return rels;
}

DacuraOntology.prototype.getClassMetadata = function(cls, defs){
	var md = (defs ? defs : {});
	var hd = this.getElementMetadata(cls, "harvested");
	if(hd !== false){
		md.harvested = hd;
	}
	return md; 
}

DacuraOntology.prototype.getMetaUpdate = function(){
	var mu = jQuery.extend({}, this.meta, true);
	if(typeof this.updated_meta.elements == "object"){
		if(typeof mu.elements == "undefined"){
			mu.elements = {};
		}
		for(var el in this.updated_meta.elements){
			if(size(this.updated_meta.elements[el])){
				mu.elements[el] = this.updated_meta.elements[el];
			}
			else if(mu.elements[el]){
				delete(mu.elements[el]); 
			}
		}
	}
	return mu;
}

DacuraOntology.prototype.getComment = function(json){
	if(typeof json == "object"){
		if(typeof json['rdfs:comment'] != "undefined"){
			return json['rdfs:comment']['data'];
		}
		if(typeof json['dc:description'] != "undefined"){
			return json['dc:description']['data'];
		}
	}
	return "";
}

DacuraOntology.prototype.getLabel = function(json){
	if(typeof json == "object"){
		if(typeof json['rdfs:label'] != "undefined"){
			return json['rdfs:label']['data'];
		}
		if(typeof json['dc:title'] != "undefined"){
			return json['dc:title']['data'];
		}
	}
	return "";
}

DacuraOntology.prototype.getClassLabel = function(cls){
	return this.getLabel(this.classes[cls]);
}

DacuraOntology.prototype.getClassComment = function(cls){
	return this.getComment(this.classes[cls]);
}

DacuraOntology.prototype.getPropertyComment = function(cls){
	return this.getComment(this.properties[cls]);
}

DacuraOntology.prototype.getPropertyLabel = function(cls){
	return this.getLabel(this.properties[cls]);
}

DacuraOntology.prototype.getParentClasses = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:subClassOf'] == "object"){
			return json['rdfs:subClassOf'];
		}
		else if(typeof json['rdfs:subClassOf'] == "string"){
			return [json['rdfs:subClassOf']];
		}
	}
	return [];
}

DacuraOntology.prototype.getEntityParent = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:subClassOf'] != "undefined"){
			var x = json['rdfs:subClassOf'];
			if(typeof x == "object") x = x[0];
			return x;
		};
	}
	return "";
};

DacuraOntology.prototype.getOtherPropertyAssertions = function(prop){
	var json = this.properties[prop];
	var others = {};
	if(typeof json == "object"){
		for(var i in json){
			if(this.normalprops.indexOf(i) == -1){
				others[i] = json[i];
			}
		}
	}
	return others;	
}


DacuraOntology.prototype.getOtherAssertions = function(cls){
	var json = this.classes[cls];
	var others = {};
	if(typeof json == "object"){
		for(var i in json){
			if(this.normals.indexOf(i) == -1){
				others[i] = json[i];
			}
		}
	}
	return others;	
}

DacuraOntology.prototype.getClassType = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		for(var i in json){
			if(this.normals.indexOf(i) == -1){
				return "complex";
			}
		}
		if(typeof json['rdfs:subClassOf'] != "undefined"){
			if(typeof json['rdfs:subClassOf'] == "object"){
				if(json['rdfs:subClassOf'].length > 1){
					return "complex";
				}
				else if(json['rdfs:subClassOf'].length == 1){
					if(this.isEntityClass(cls)) {
						return "entity";
					}
					else {
						return "complex";
					}
				}
			}
			else {
				if(json['rdfs:subClassOf'] == "dacura:Enumerated"){
					return "enumerated";
				}
				else if(this.isEntityClass(json['rdfs:subClassOf'])) {
					return "entity";
				}
				return "complex";
			}
		}
		else {
			return "simple";
		}
	}
	else {
		alert(cls + " is empty");
	}
	return "";
}

DacuraOntology.prototype.getEnumeratedChoices = function(cls){
	var json = this.classes[cls];
	var choices = [];
	if(typeof json == "object"){
		if(typeof json['owl:oneOf'] == "string"){
			if(this.contents[json['owl:oneOf']]){
				var set = this.contents[json['owl:oneOf']];
			}
			else {
				alert("not supposed to be string: " + json['owl:oneOf'])
			}
		}
		else if(typeof json['owl:oneOf'] == "object"){
			var set = json['owl:oneOf'][firstKey(json['owl:oneOf'])]; 
		}
		else {
			return [];
		}
		var choices = this.RDFToList(set, 0);
		var nchoices = [];
		for(var i=0; i<choices.length; i++){
			if(typeof choices[i] == "string"){
				nchoice = {id: choices[i]};
				if(this.contents[choices[i]]){
					nchoice.label = this.getLabel(this.contents[choices[i]]);
					nchoice.comment = this.getComment(this.contents[choices[i]]);
				}
				else {
					alert("No classes for " + choices[i]);
				}
			}
			else if(typeof choices[i] == "object"){
				var id = firstKey(choices[i]);
				nchoice = {id: id, label: this.getLabel(choices[i][id]), comment: this.getComment(choices[i][id])};
			}
			else {
				nchoice = {id: "??", label: "??", comment: "??"};
			}
			nchoices.push(nchoice);
		}
		
		return nchoices;
	}
	else {
		alert(json);
	}
	return [];
}


DacuraOntology.prototype.getRangeObjectType = function(range){
	if(!range || !range.length) return "";
	return (range.split(":")[0] == this.id ? "local" : "remote");
}

DacuraOntology.prototype.wrapEmpty = function(text){
	return "<span class='console-element-empty'>" + text + "</span>";
}

DacuraOntology.prototype.validateUpdateClassInput = function(input) {
	this.errors = [];
	var cls = ((input.id.indexOf(':') == -1) ? this.id + ":" + input.id : input.id);
	var orig = this.classes[cls];
	if(typeof orig != "object"){
		this.errors.push({field: "id", message: "Attempt to update class " + cls + " which does not exist"});
		return false;
	}
	return this.validateClassFormInput(input);
}

DacuraOntology.prototype.validateCreatePropertyInput = function(input) {
	this.errors = [];
	if(!this.validateFormBasics(input)){
		return false;
	}
	if(!input.domain.length){
		this.errors.push({field: "domain", message: "Missing domain class"});
		return false;	
	}
	if(typeof input.range == "undefined" || !input.range.length){
		this.errors.push({field: "range", message: "Missing range"});
		return false;	
	}
	return true;
}

DacuraOntology.prototype.validateUpdatePropertyInput = function(input) {
	if(!this.validateCreatePropertyInput(input)){
		return false;
	}
	var pid = ((input.id.indexOf(':') == -1) ? this.id + ":" + input.id : input.id);
	var orig = this.properties[pid];
	if(typeof orig != "object"){
		this.errors.push({field: "id", message: "Attempt to update property " + pid + " which does not exist"});
		return false;
	}
	return true;
}

DacuraOntology.prototype.validateCreateClassInput = function(input) {
	this.errors = [];
	var cid = ((input.id.indexOf(':') == -1) ? this.id + ":" + input.id : input.id);
	if(typeof this.contents[cid] != "undefined"){
		this.errors.push({field: "id", message: "An entity with the id " + input.id + " already exists in the ontology"});
		return false;	
	}
	return this.validateClassFormInput(input);
}

DacuraOntology.prototype.validateFormBasics = function(input){
	var id = ((input.id.indexOf(':') == -1) ? input.id : input.id.split(":")[1]);
	if(!id.length){
		this.errors.push({field: "id", message: "Missing identifier"});
		return false;
	}
	if(id.length < 2){
		this.errors.push({field: "id", message: "Identifiers must be at least 2 characters long"});
		return false;
	}
	if(/[^a-zA-Z0-9_\-]/.test(id)){
		this.errors.push({field: "id", message: "Identifiers can only consist of alphanumerics"});
		return false;
	}
	if(!input.label.length){
		this.errors.push({field: "label", message: "Missing label"});
		return false;
	}
	if(input.label.trim().length < 3){
		this.errors.push({field: "label", message: "Class labels must be at least 3 characters long"});
		return false;
	}
	if(!input.comment.length){
		this.errors.push({field: "comment", message: "Missing class comment"});
		return false;
	}
	return true;
}

DacuraOntology.prototype.validateClassFormInput = function(input){
	if(!this.validateFormBasics(input)){
		return false;
	}
	if(input.ctype == "entity"){
		if(input.parents && typeof input.parents == 'object' && input.parents.length != 1){
			this.errors.push({field: "entity", message: "Entity classes should have only one parent class, " + input.parents.length + " found"});
			return false;
		}
	}
	else if(input.ctype == "enumerated"){
		if(!input.choices || input.choices.length == 0){
			this.errors.push({field: "enumerated", message: "No choices selected - you must create at least one choice for an enumerated type class."});
			return false;
		}
		var entries = 0;
		for(var i = 0; i < input.choices.length; i++){
			var choice = input.choices[i];
			if(choice.label || choice.comment || choice.id){
				entries++;
				if(!choice.label){
					this.errors.push({field: "enumerated", message: "Choice " + (i+1) + " is missing a label - every choice needs a label"});
					return false;
				}
				if(choice.label.length < 3){
					this.errors.push({field: "enumerated", message: "Choice " + (i+1) + " has a label that is too short - it must be at least 3 characters long"});
					return false;
				}
			}
		}
		if(entries == 0){
			this.errors.push({field: "enumerated", message: "No choices selected - you must create at least one choice for an enumerated type class."});
			return false;
		}
	}
	return true;
};

/**
 * These guys are not currently used - gone for simpler option of updating whole ontology at one go
 
DacuraOntology.prototype.getUpdateAsRDF = function(input){
	var rdf = this.getInputAsRDF(input, "update");
	var cid = this.id + ":" + input.id;
	//need to figure out which bits we changed
	var orig = this.classes[cid];
	for(var i in orig){
		if(typeof rdf[cid][i] == "undefined"){
			rdf[cid][i] = []; //code for deleting it in linked data land
		}
	}
	if(input.ctype == "enumerated"){
		var choices = this.getEnumeratedChoices(cid);
		for(var i = 0; i < choices.length; i++){
			if(typeof rdf[cid]["owl:oneOf"][choices[i].id] == "undefined"){
				rdf[cid]["owl:oneOf"][choices[i].id] = []; //code for deleting it in linked data land
			}
		}
	}
	return rdf;
} 

DacuraOntology.prototype.getUpdatePropertyAsRDF = function(input){
	return this.getInputPropertyAsRDF(input);
}


DacuraOntology.prototype.getInputPropertyAsRDF = function(input){
	var rdf = {};
	var cid = this.id + ":" + input.id;
	rdf[cid] = {};
	rdf[cid]["rdfs:label"] = input.label;
	if(input.comment.length){
		rdf[cid]["rdfs:comment"] = input.comment;
	}
	if(input.units){
		rdf[cid]["dacura:units"] = input.units;
	}
	rdf[cid]["rdfs:domain"] = input.domain;
	rdf[cid]["rdfs:range"] = input.range;
	if(input.rtype == "object" || input.rtype == "boxed"){
		rdf[cid]["rdf:type"] = "owl:ObjectProperty"; 
	}
	else {
		rdf[cid]["rdf:type"] = "owl:DatatypeProperty"; 
	}
	return rdf;
}

DacuraOntology.prototype.getInputAsRDF = function(input, mode){
	var rdf = {};
	var mode = (mode ? mode : "create");
	var cid = this.id + ":" + input.id;
	rdf[cid] = {};
	rdf[cid]["rdf:type"] = "owl:Class";
	rdf[cid]["rdfs:label"] = input.label;
	if(input.comment.length){
		rdf[cid]["rdfs:comment"] = input.comment;
	}
	if(typeof input.parents == "object" && input.parents.length > 0){
		rdf[cid]["rdfs:subClassOf"] = input.parents; 
	}
	else if(input.parents && input.parents.length){
		rdf[cid]["rdfs:subClassOf"] = input.parents; 
	}
	if(input.ctype == "enumerated"){
		rdf[cid]["rdfs:subClassOf"] = "dacura:Enumerated";
		if(mode == "update"){
			var orig_choices = this.getEnumeratedChoices(cid);
			rdf[cid]["owl:oneOf"] = {}; 
			for(var i = 0; i< input.choices.length ; i++){
				if(input.choices[i].label.length == 0) continue;
				var choice = {"rdf:type": cid, "rdfs:label": input.choices[i].label};
				if(input.choices[i].comment.length){
					choice['rdfs:comment'] = input.choices[i].comment;
				}
				var bnid = ((input.choices[i].id.substring(0, 2) == "_:") ? input.choices[i].id.substring(2) : input.choices[i].id);
				if(bnid.length){
					var new_one = true;
					for(var k = 0; k<orig_choices.length; k++){
						if(orig_choices[k].id == "_:" + bnid){
							new_one = false;
							break;
						}
					}
					if(new_one){
						choice[this.request_id_token] = bnid;
					}
					rdf[cid]["owl:oneOf"]["_:" + bnid] = choice;
				}
				else {
					rdf[cid]["owl:oneOf"]["_:" + this.id + "_" + ++this.bnid] = choice;			
				}
			}						
		}
		else {
			rdf[cid]["owl:oneOf"] = []; 
			for(var i = 0; i< input.choices.length ; i++){
				var choice = {"rdf:type": cid, "rdfs:label": input.choices[i].label};
				if(input.choices[i].comment.length){
					choice['rdfs:comment'] = input.choices[i].comment;
				}
				if(input.choices[i].id.length){
					choice[this.request_id_token] = input.choices[i].id;
				}
				rdf[cid]["owl:oneOf"].push(choice);
			}			
		}
	}
	if(input.ctype == "complex"){
		var assertions = this.readOtherAssertionsFromForm();
		for(var a in assertions){
			try {
				var val = JSON.parse(assertions[a]);
			}
			catch(e){
				val = assertions[a];				
			}
			rdf[cid][a] = val; 
		}
	}
	return rdf;
}
*/
