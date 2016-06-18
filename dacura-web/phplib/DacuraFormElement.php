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
	private static $valid_types = array("text", "file", "url", "image", "password", "boolean", "choice", "status", "section", "email", "complex", "placeholder");
	/** @var string[] the list of valid input types of form element */
	private static $valid_input_types = array("input", "file", "select", "radio", "textarea", "checkbox", "password", "custom");
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
	/** @var DacuraForm containing the sub-elements of this element (e.g. for elements of type "section" */
	var $subfields;
	/** @var array containing the actions that are available with this element */
	var $actions;
	
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
		$this->hidden = isset($row['hidden']) || isset($settings['hidden']) ? true : false;
		$this->element_size = isset($row['length']) ? $row['length'] : 'regular';
		$this->default_value = isset($row['default_value']) ? $row['default_value'] : "";
		$this->value = isset($row['value'])  ? $row['value'] : $this->default_value;
		$this->update_disabled = isset($row['disabled']) || isset($settings['disabled'])? true : false;
		$this->help = isset($row['help']) ? $row['help'] : "";
		$this->options = isset($row['options']) ? $row['options'] : array();
		$this->id = $row['id'];
		$this->actions = isset($row['actions']) ? $row['actions'] : array();
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
		elseif($this->type == 'file'){
			$this->input_type = "file";
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
		elseif($this->type == 'complex'){
			$this->input_type = "custom";
		}
		else {
			$this->input_type = "input";
		}
		if($this->type == 'complex' && isset($row['extras'])){
			$this->complex = $row['extras'];
		}
		if($this->type == "section"){
			$this->input_type = "section";
			//subforms inherit disabled and hidden from parent forms
			if($this->update_disabled){
				if(isset($settings['disabled_view']) && $settings['disabled_view'] == "form"){
					$settings['disabled'] = true;	
				}
				else {
					$settings['display_type'] = "view";
				}
				if($this->hidden){
					$settings['hidden'] = true;
				}
			}
			$this->subfields = new DacuraForm($this->id, $settings);
			if(isset($row['fields'])){
				$this->subfields->addElements($row['fields']);
			}
		}
		return $this->hasValidSettings();
	}
	
	/**
	 * Adds a metadata column to the row
	 * @param string $name the name of the metadata variable
	 * @param unknown $val the value of the variable
	 * @param unknown $type the variables type
	 * @param unknown $options the options that are passed into to define the meta-variable
	 */
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
	
	function isPlaceholder(){
		return $this->type == "placeholder";		
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
		if($this->isPlaceholder()){
			$html = "<tr class='dacura-presection-spacer'></tr>";
			$html .= "<tr class='dacura-property-section dacura-property-placeholder' id='row-".$prefix.$this->id."'>";
			$html .= "<th colspan='2' class='dacura-property-label'>".$this->label;
			if($this->help) $html .= " <span class='dacura-property-help'>".$this->help."</span>";
			$html .= "</th></tr>";				
		}
		elseif(isset($settings['embedstyle']) && $settings['embedstyle'] == "flat" && $this->isSection() && count($context) % 2 != 0 && count($context) != 0){
			$html = "<tr class='dacura-presection-spacer'></tr>";
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
	
	/**
	 * Generates the html to represent a metadata variable
	 * @param string $mk metadata key - the name of the variable
	 * @param mixed $mv - metadata value - the value of the variable
	 * @param string $prefix - the prefix that is applied to ids in this context
	 * @param array $settings - a settings array that contains various settings for the variable
	 * @return string - the html string
	 */
	function getMetaHTML($mk, $mv, $prefix = "", $settings = array()){
		$html = "";
		if(isset($settings['display_type']) && $settings['display_type'] == "update"){
			$disabled = $this->update_disabled ? "disabled" : "";
			$id = $this->id."-".$mk;
			if($mv[1] == "choice"){
				$html = "<select class='property-meta' $disabled id='$prefix"."$id'>";
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
				$html = "<input class='property-meta' $disabled type='checkbox' id='$prefix"."$id' ";
				if($mv[0]) $html .= " checked";
				$html .= ">";	
			}
			else {
				$html = "<input type='text' id='$prefix".$this->id."-".$mk."' value='".$mv[0]."' $disabled>";
			}
		}
		elseif(isset($settings['show_meta'])) {
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
		$html = "<table class='dacura-property-value-bundle'><tr>";
		$html .= ($this->display_type == 'view') ? "<td class='dacura-property-display'>" : "<td class='dacura-property-input'>";
		if(isset($settings['display_type']) &&  $settings['display_type'] == "view"){
			$html .= $this->getDisplayElementHTML($settings, $context);
		}
		else {
			$html .= $this->getInputElementHTML($settings, $context);
		}
		$html .= "</td>";
		if($this->actions){
			$fid = $context[count($context)-1]."-".$this->id;
			$html .= "<td class='dacura-property-actions'>";
			foreach($this->actions as $actid => $rules){
				$html .= "<button class='dacura-formelement-action' id='$fid"."-".$actid."'>".$rules['title']."</button>";
			}
			$html .= "</td>";
				
		}
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
			$html = "<div id='$prefix"."$this->id' class='dacura-display-json raw-json'>" . json_encode($this->value, JSON_PRETTY_PRINT)."</div>";
		}
		elseif($this->value === ""){
			$html = "<span id='$prefix"."$this->id' class='dacura-display-value'></span>";
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
				else $v = htmlspecialchars($v);
				$html = "<span class='dacura-display-value'>$v</span><input id='$prefix"."$this->id' type='hidden' value='$v'>";
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
		$fid = $prefix.$this->id;
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
				$html = $this->drawImageInputField($settings, $context);
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
		elseif ($this->input_type == "file"){
			$val = htmlspecialchars($this->value);
			$html = "<input id=\"$prefix"."$this->id\" class=\"$cls\" $disabled type=\"file\" value=\"$val\"/>";
		}
		elseif( $this->input_type == "checkbox"){
			$val = $this->value ? "checked" : "" ;
			$html = "<input id=\"$prefix"."$this->id\" class=\"$cls\" $disabled type=\"checkbox\" $val/>";				
		}
		elseif($this->input_type == "radio"){
			$opts = $this->options;
			if(!isAssoc($opts)){
				$opts = array();
				foreach($this->options as $v){
					$opts[$v] = $v;
				}
			}
			$html = "<span class='dacura-radio' id=\"$fid\">";
			$i = 0;
			foreach($opts as $k => $v){
				$checked = ($k == $this->value || ($i++ == 0 && $this->value == "")) ? 'checked="checked" ' : "";  
				$html .= '<input type="radio" value="' . $k.'" class="'.$cls.'" id="'.$fid.'-'.$k.'" name="'.$fid.'-radio" '.$checked.'><label for="'.$fid.'-'.$k.'">'.$v.'</label>';				
			}
			$html .= "</span>";
		}		
		elseif($this->input_type == "select"){
			$html = "<select id=\"$prefix"."$this->id\" class=\"dacura-select $cls\" $disabled>";
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
			$html .= "</select>";
		}
		else {
			$html = "<span class='dacura-error'>Value: $this->value</span>";
		}
		return $html;
	}
	
	/**
	 * Draw an image input field - basic text field with a few wrappings 
	 * @param array $settings - the optional settings for the form element
	 * @param array $context - an array with the ids of the parent forms that this element is within
	 * @return string - the html representation of the field
	 */
	function drawImageInputField($settings, $context){
		$val = htmlspecialchars($this->value);
		$prefix = $context[count($context)-1]."-";
		$html = "<div class='dacura-image-input'>";
		$html .= "<span id=\"$prefix".$this->id."-preview\" class='image-input-preview'>";
		if($this->update_disabled){
			$html .= "<img id=\"$prefix".$this->id."-img\" class='input-preview-disabled' src='$val'>";				
		}
		else {
			$html .= "<img id=\"$prefix".$this->id."-img\" class='input-preview' src='$val' title='Click to change the file'>";
		}
		$html .= "</span>";
		$cls = 'dacura-'.$this->element_size.'-input';
		$disabled = $this->update_disabled ? "disabled" : "";
		$html .= "<span class='image-input-text'>";
		$html .= "<input id=\"$prefix"."$this->id\" class=\"$cls image-input\" $disabled type='text' value=\"$val\">";
		$html .= "</span></div>";
		return $html;
	}
	
	/**
	 * The read only view of the display field
	 * @param array $settings the options for the field
	 * @param array $context - an array with the ids of the parent forms that this element is within
	 * @return string - the html representation of the field
	 */
	function drawCustomDisplayField($settings, $context){
		if(is_array($this->value)){
			$html = "<div class='dacura-display-json raw-json'>" . json_encode($this->value, JSON_PRETTY_PRINT)."</div>";
		}
		else {
			$html = "<span class='dacura-display-value dacura-display-json'>Unknown thing to display (".$this->value.")</span>";
		}
		return $html;
	}

	/**
	 * Draw a non-standard field - complex fields with special rules
	 * @param array $settings - the optional settings for the form element
	 * @param array $context - an array with the ids of the parent forms that this element is within
	 * @return string - the html representation of the field
	 */
	function drawCustomInputField($settings, $context){
		if($this->id == "facets"){
			$html = $this->drawFacetsInput($settings, $context);	
		}
		elseif($this->id == "ldcontents"){
			$html = $this->drawLDContentsInput($settings, $context);
		}
		elseif($this->id == "imports" || $this->id == "schema_imports"){
			$html = $this->drawOntologyImportsInput($settings, $context);
		}
		else {
			$prefix = $context[count($context)-1]."-";
			$cls = 'dacura-'.$this->element_size.'-input dacura-custom-'.$this->id;
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
	
	/**
	 * Generates the html to represent a facet on the facet editing control
	 * @param string $rname the name of the role 
	 * @param string $fname the name of the facet
	 * @param boolean $active if true, the facet has links included
	 * @param string $extra - extra text to be added to the end of the facet button (for added descriptions)
	 * @return string - the html representation
	 */	
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
	
	/**
	 * Draws the form input element to represent facets
	 * @param array $settings the settings options for this element
	 * @param array $context - an array with the ids of the parent forms that this element is within
	 * @return string the html representation of the element
	 */
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
	
	function drawLDContentsInput($settings, $context){
		$prefix = $context[count($context)-1]."-";
		$fid = $prefix.$this->id;
		//fires up an LDOViewer object which takes over the form element
		//$html = "<script>writeLDImportToForm('$fid');</script>";
		$html = "<span class='dch'>mommy, I made a thing</span>";
		return $html;
	}
	
	function drawOntologyImportsInput($settings, $context){
		return "<H1>ontology imports</h1>";
	}
	
}