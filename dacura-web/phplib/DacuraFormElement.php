<?php 
/**
 * Class representing an element within a user-interface form
 *
 * Creation Date: 15/11/2015
 * @author Chekov
 * @license GPL V2
 */
class DacuraFormElement extends DacuraObject {
	/** @var string[] the list of valid types of form element */
	private static $valid_types = array("text", "url", "image", "password", "boolean", "choice", "status", "section", "email", "complex");
	/** @var string[] the list of valid input types of form element */
	private static $valid_input_types = array("input", "select", "radio", "textarea", "checkbox", "password", "custom");
	/** @var string[] the list of valid sizes of form elements */
	private static $valid_element_sizes = array("long", "regular", "short", "tiny");
	/** @var string[] the list of valid display types of forms and form elements */
	private static $valid_display_types = array("view", "update", "create");
	/** @var string the element type - must be one of DacuraFormElement::$valid_types */
	var $type; 
	/** @var string the input html element associated with this element type. must be one of DacuraFormElement::$valid_input_types */
	var $input_type; 
	/** @var string the size of the ui element must be one of DacuraFormElement::$valid_element_sizes */
	var $element_size; 
	/** @var string the display type of the element must be one of DacuraFormElement::$valid_display_types */
	var $display_type;
	/** @var string a human readable label */
	var $label;
	/** @var string passage of text to help the user in undertstanding this element */
	var $help;
	/** @var mixed The value of the input element */
	var $value;
	/** @var mixed The default value (if any) of the input element */
	var $default_value;
	/** @var boolean if true, the input element is disabled */
	var $update_disabled;
	/** @var boolean if true, the element is hidden */
	var $hidden;
	/** @var boolean if true, the element can be submitted independently of the form */
	var $options;
	/** @var array associative name value array of state for complex fields */
	var $complex; 
	/** @var DacuraFormElement[] an array of sub-elements of this element (e.g. for elements of type "section" */
	var $subfields;
	/** @var array associative name value field of meta-data about this form field */
	var $meta = array();

	/**
	 * Loads the form element from an associative name-value array of settings 
	 * 
	 * The settings are generally specified in service settings files
	 * @param array $row an associative array with fields for:
	 * [label, hidden, length, value, default_value, submit, disabled, type, id, options, input_type, fields]
	 * @param string $type one of DacuraFormElement::$valid_display_types
	 * @return boolean
	 */
	function load(array $row, $settings){
		if(!isset($row['id'])){
			return $this->failure_result("Dacura form elements must have a form id associated", 400);
		}
		$this->label = isset($row['label']) ? $row['label'] : false;
		$this->hidden = isset($row['hidden']) ? $row['hidden'] : false;
		$this->element_size = isset($row['length']) ? $row['length'] : 'regular';
		$this->default_value = isset($row['default_value']) ? $row['default_value'] : "";
		$this->value = isset($row['value'])  ? $row['value'] : $this->default_value;
		$this->update_disabled = isset($row['disabled']) ? $row['disabled'] : false;
		$this->help = isset($row['help']) ? $row['help'] : "";
		$this->options = isset($row['options']) ? $row['options'] : array();
		$this->id = $row['id'];
		$this->type = isset($row['type']) ? $row['type'] : 'text';
		if(isset($row['selected'])){
			$this->selected = $row['selected'];
		}
		if(isset($row['input_type'])){
			$this->input_type = $row['input_type'];
		}
		elseif(is_array($this->value)){
			$this->input_type = "textarea";
		}
		elseif($this->type == 'password'){
			$this->input_type = "password";
		}
		elseif($this->type == 'status'){
			$this->options = DacuraObject::$valid_statuses;
			$this->input_type = "select";
		}
		elseif($this->type == 'choice'){
			$this->input_type = "select";
		}
		elseif($this->type == 'boolean'){
			$this->input_type = "checkbox";
		}
		else {
			$this->input_type = "input";
		}
		if($this->type == "section"){
			$this->input_type = "section";
			$this->subfields = new DacuraForm($this->id, $settings);
			if(isset($row['fields'])){
				$this->subfields->addElements($row['fields']);
			}
		}
		if($this->type == "complex" && isset($row['extras'])){
			$this->complex = $row['extras'];			
		}
		return $this->hasValidSettings();
	}
	
	function addMeta($name, $val, $type, $options){
		$this->meta[$name] = array($val, $type, $options);
		if($this->type == "section"){
			$this->subfields->addMetaDataColumn($name, $val, $type, $options);
		}
	}
	
	/**
	 * Sections are special types of form elements that contain lists of form elements
	 * @return boolean true if the element is a section (container) element, false otherwise
	 */
	function isSection(){
		return $this->type == "section";
	}
	
	/**
	 * Tests the element to ensure that it has been given valid settings 
	 * 
	 * Just checks that type, input_type, display_type and element_size are among the valid entries defined 
	 * for those type
	 * @return boolean true if valid, false if invalid
	 */
	function hasValidSettings(){
		if(!in_array($this->type, DacuraFormElement::$valid_types)){
			return $this->failure_result("Form element $this->id has invalid type: $this->type", 400);
		}
		if(!in_array($this->input_type, DacuraFormElement::$valid_input_types) && $this->input_type != "section"){
			return $this->failure_result("Form element $this->id has invalid input type: $this->input_type", 400);
		}		
		if(!in_array($this->element_size, DacuraFormElement::$valid_element_sizes)){
			return $this->failure_result("Form element $this->id has invalid element size: $this->element_size", 400);
		}			
		return true;
	}
	
	/**
	 * Returns a HTML string as a representation of this form element in table rows
	 * @param object settings name value array of settings from form
	 * @param array context - array of ids of elements that this is embedded inside 
	 * @return string html as a TR element
	 */
	function tr($settings, $context, $rownum, $islast = false){
		$cls_extra = $rownum == 1 ? "first-row row-1" : "row-$rownum";
		if($islast){
			$cls_extra .= " last-row";
		}
		$prefix = $context[count($context)-1]."-";
		if(isset($settings['embedstyle']) && $settings['embedstyle'] == "flat" && $this->isSection() && count($context) % 2 != 0 && count($context) != 0){
			$html = "<tr class='dacura-property-spacer'></tr>";
			$html .= "<tr class='dacura-property-section'>";
			$html .= "<th colspan='2' class='dacura-property-label'>".$this->label." <span class='dacura-property-help'>".$this->help."</span></th>";
			foreach($this->meta as $mk => $mv){
				$html .= "<th class='dacura-property-meta' id='meta-".$prefix.$this->id."-".$mk."'>";
				$html .= $this->getMetaHTML($mk, $mv, $prefix, $settings);
				$html .= "</th>";
			}
			$html .= "</tr>";
			$html .= "<tr class='dacura-property dacura-embedded-property $cls_extra' id='row-".$prefix.$this->id."'><td colspan='". (2 + count($this->meta)) . "' class='dacura-embedded-property'>";
			$html .= $this->getValueTable($settings, $context);
			$html .= "</tr>";	
			$html .= "<tr class='dacura-section-spacer'></tr>";
		}
		else {
			$html = "";
			if($this->type == "complex" || $this->type == "section"){
				$html = "<tr class='dacura-property-spacer'></tr>";
			}
			$html .= "<tr class='dacura-property $cls_extra' id='row-".$prefix.$this->id."'>";
			$html .= "<td class='dacura-property-label'>".$this->label."</td>";
			$html .= "<td class='dacura-property-value'>";
			$html .= $this->getValueTable($settings, $context, $this->help);
			$html .= "</td>";
			foreach($this->meta as $mk => $mv){
				$html .= "<td class='dacura-property-meta' id='meta-".$prefix.$this->id."-".$mk."'>";
				$html .= $this->getMetaHTML($mk, $mv, $prefix, $settings);
				$html .= "</td>";
			}
			$html .= "</tr>";
			if($this->type == "complex" || $this->type == "section"){
				$html .= "<tr class='dacura-property-spacer'></tr>";
			}
		}
		return $html;
	}
	
	function getMetaHTML($mk, $mv, $prefix = "", $settings = array()){
		if(isset($settings['display_type']) && $settings['display_type'] == "update"){
			$id = $this->id."-".$mk;
			if($mv[1] == "choice"){
				$html = "<select class='property-meta' id='$prefix"."$id'>";
				foreach($mv[2] as $v => $l){
					if($mv[0] == $v){
						$html .= "<option selected value='$v'>$l</option>";						
					}
					else {
						$html .= "<option value='$v'>$l</option>";						
					}
				}
				$html .= "</select>";
			}
			elseif($mv[1] == "checkbox"){
				$html = "<input class='property-meta' type='checkbox' id='$prefix"."$id' ";
				if($mv[0]) $html .= " checked";
				$html .= ">";	
			}
			else {
				$html = "<input type='text' id='$prefix".$this->id."-".$mk."' value='".$mv[0]."'>";
			}
		}
		else {
			$html = $mv[0];
		}
		return $html;
	}
	
	/**
	 * Returns a representation of the element's value as a HTML table 
	 * 
	 * The table has css class dacura-property-value-bundle and cells for: 
	 * * the value (css class dacura-property-input)
	 * * a submit cell - when the value can be submitted in isolation (css class: dacura-property-submit)
	 * * a help cell (css class: dacura-property-help)
	 * @param string $display_type one of $DacuraFormElement::$valid_display_types
	 * @return string The TABLE html
	 */
	function getValueTable($settings, $context, $help = false){
		$html = "<table class='dacura-property-value-bundle'><tr><td class='dacura-property-input'>";
		if(isset($settings['display_type']) &&  $settings['display_type'] == "view"){
			$html .= $this->getDisplayElementHTML($settings, $context);
		}
		else {
			$html .= $this->getInputElementHTML($settings, $context);
		}
		$html .= "</td>";
		if($help){
			$html .= "<td class='dacura-property-help'>".$help."</td>";
		}
		$html .= "</tr></table>";
		return $html;
	}
	
	/**
	 * Generates the HTML to display the element value when display_type = "view"
	 * 
	 * @return string the element HTML 
	 */
	function getDisplayElementHTML($settings, $context){
		$prefix = $context[count($context)-1]."-";
		if($this->isSection()){
			$section_id = implode("-", $context)."-".$this->id;
			$html = $this->subfields->html($section_id, $context);
		}
		elseif($this->type == "complex"){
			return $this->drawCustomDisplayField($settings, $context);
		}
		elseif(is_array($this->value)){
			$html = "<div class='dacura-display-json raw-json'>" . json_encode($this->value, JSON_PRETTY_PRINT)."</div>";
		}
		elseif($this->value === ""){
			$html = "<span class='dacura-display-empty'>empty</span>";
		}
		else {
			$prefix = $context[count($context)-1]."-";
			$v = $this->value;
			if($this->type == "image"){
				$html = "<img class='form-display-image' src='$v'>";
			}
			else {
				if($v === true) $v = "true";
				if($v === false) $v = "false";
				$html = "<span id='$prefix"."$this->id' class='dacura-display-value'>$v</span>";
			}
		}
		return $html;
	}
	
	/**
	 * Generates the HTML to display the element value when display_type = "update" or "create"
	 *
	 * @return string the element input HTML
	 */
	function getInputElementHTML($settings, $context){
		$prefix = $context[count($context)-1]."-";
		if($this->input_type == "custom"){
			return $this->drawCustomInputField($settings, $context);
		}
		if($this->isSection()){
			$section_id = implode("-", $context)."-".$this->id;
			return $this->subfields->html($section_id, $context);
		}
		$cls = 'dacura-'.$this->element_size.'-input';
		$disabled = $this->update_disabled ? "disabled" : "";
		if($this->input_type == "input"){
			$val = htmlspecialchars($this->value);
			if($this->type == "image"){
				$html = "<input onclick=\"openKCFinder(this)\" id=\"$prefix"."$this->id\" class=\"$cls image-input\" $disabled type='text' value=\"$val\">";
			}
			else {
				$html = "<input id=\"$prefix"."$this->id\" class=\"$cls\" $disabled type='text' value=\"$val\">";				
			}		
		}
		elseif($this->input_type == "textarea"){
			if(is_array($this->value)){
				$val = json_encode($this->value, JSON_PRETTY_PRINT);
				$cls .= " dacura-display-json";
			}
			else {
				$val = htmlspecialchars($this->value);
			}			
			$html = "<textarea id=\"$prefix"."$this->id\" class=\"$cls\" $disabled>$val</textarea>";
		}
		elseif ($this->input_type == "password"){
			$val = htmlspecialchars($this->value);
			$html = "<input id=\"$prefix"."$this->id\" class=\"$cls\" $disabled type=\"password\" value=\"$val\"/>";
		}
		elseif( $this->input_type == "radio"){
			$html = "";
		}
		elseif( $this->input_type == "checkbox"){
			
		}		
		elseif($this->input_type == "select"){
			$html = "<select id=\"$prefix"."$this->id\" class=\"dacura-select $cls\" $disabled>";
			if(isAssoc($this->options)){
				foreach($this->options as $v => $title){
					$selected = ($this->value && $this->value == $v) ? " selected" : "";
					$val = htmlspecialchars($v);
					$title = htmlspecialchars($title);
					$html .= "<option value=\"$val\" $selected>$title</option>";
				}
			}
			else {
				if(isAssoc($this->options)){
					foreach($this->options as $k => $v ){
						$selected = ($this->value && $this->value == $k) ? " selected" : "";
						$k = htmlspecialchars($k);
						$v = htmlspecialchars($v);
						$html .= "<option value=\"$k\" $selected>$v</option>";
					}						
				}
				else {
					foreach($this->options as $v ){
						$selected = ($this->value && $this->value == $v) ? " selected" : "";
						$v = htmlspecialchars($v);
						$html .= "<option value=\"$v\" $selected>$v</option>";
					}
				}
			}
			$html .= "</select>";
		}
		else {
			$html = "<span class='dacura-error'>Value: $this->value</span>";
		}
		return $html;
	}

	function drawCustomInputField($settings, $context){
		if($this->id == "facets"){
			$html = $this->drawFacetsInput($settings, $context);	
		}
		else {
			$prefix = $context[count($context)-1]."-";
			$cls = 'dacura-'.$this->element_size.'-input';
			$disabled = $this->update_disabled ? "disabled" : "";
			if(is_array($this->value)){
				$val = json_encode($this->value, JSON_PRETTY_PRINT);
				$cls .= " dacura-display-json";
			}
			else {
				$val = htmlspecialchars($this->value);
			}			
			$html = "<textarea id=\"$prefix"."$this->id\" class=\"$cls\" $disabled>$val</textarea>";
		}
		return $html;
	}
	
	function drawCustomDisplayField($settings, $context){
		if(is_array($this->value)){
			$html = "<div class='dacura-display-json raw-json'>" . json_encode($this->value, JSON_PRETTY_PRINT)."</div>";
		}
		else {
			$html = "Unknown thing to display";
		}
		return $html;
	}
	
	
	function getFacetButtonHTML($rname, $fname, $active = false, $extra = ""){
		$type = $active ? 'active' : "";
		$divid = $rname."-".$fname."-".$this->id;
		$rtitle = htmlspecialchars(UserRole::$extended_dacura_roles[$rname]);
		$html = "<div class='dacura-facet-button $type' id='$divid'>";
		$html .= "<span class='dacura-role $rname' title='$rtitle'></span>";
		$html .= "<span class='dacura-role-label'>$rtitle</span>";
		$html .= "<span class='facet-text'>$extra</span>";
		if($active){
			$html .= "<span class='dacura-facet-action dacura-facet-$active'>";
			$html .= "<a href='javascript:facet_".$active."(\"$divid\", \"$rname\", \"$fname\")' class='dacura-role-$active'>".ucfirst($active)." Access</a>";
			$html .= "</span>";
		}
		$html .= "</div>";
		return $html;
	}
	
	function drawFacetsInput($settings, $context){
		$prefix = $context[count($context)-1]."-";
		if(count($this->value) == 0){
			$html = "<div class='dacura-facets-listing dacura-empty'>No access currently permitted</div>";
		}
		else {
			$html = "<div class='dacura-facets-listing'>";
			foreach($this->value as $f){
				$active = (isset($f['default']) ? false : "remove");
				if(isset($this->options[$f['facet']])){
					$extra = "Permission to ".$this->options[$f['facet']];						
					if(isset($f['default'])){
						$extra .= " (default setting)";
					}
					$html .= $this->getFacetButtonHTML($f['role'], $f['facet'], $active, $extra);	
				}
				else {
					$extra = "Unknown permission ".$f['facet'];
				}
			}
			$html .= "</div>";			
		}
		if($this->options && $this->complex){
			$facetlist = "<select id=\"$prefix.".$this->id."-facets\" class=\"facets\">";
			if(isAssoc($this->options)){
				foreach($this->options as $k => $v){
					$facetlist .= "<option value=\"".htmlspecialchars($k)."\">".htmlspecialchars($v)."</option>";
				}
			}
			else {
				foreach($this->options as $v){
					$facetlist .= "<option value=\"".htmlspecialchars($v)."\">".htmlspecialchars($v)."</option>";
				}
			}
			$facetlist .= "</select>";
			$rhtml = "<select id='$prefix.".$this->id."-roles' class='roles'>";
			foreach($this->complex as $r => $t){
				$rhtml .= "<option value='$r'>$t</option>";
			}
			$rhtml .= "</select>";
			$bhtml = "<button id='$prefix.".$this->id."-add' class='addfacet'>Grant Access</button>";
				
			$thtml = "<div class='facet-maker'>";
			$thtml .= "<table class='facet-maker-table'><tr><td></td><td class='option'>$rhtml</td>";
			$thtml .= "<td class='option'>$facetlist</td><td>$bhtml</td></tr></table>";
			$html .= "<div id='$prefix"."-".$this->id."-update-table' class='facet-table-container'>$thtml</div>";
			return $html;
		}
		else {
			$html .= "<b>Could not do anything with $this->id no facets/roles</b>";
		}
		
		
	}
	

}

