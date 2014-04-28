<?php

require_once("SparqlBridge.php");
require_once("NSURI.php");

class Widgetizer {
	var $sparql;
	var $schema_graph_name;
	var $endpoint_url;
	var $widget_details = array();
	var $ns;
	var $pvns; 
	
	function __construct($gname, $endp){
		$this->schema_graph_name = $gname;
		$this->endpoint_url = $endp;
		$this->sparql = new SparqlBridge($endp);
		$this->ns = new NSURI();
		$this->pvns = $this->ns->getnsuri("pv");
	}
	
	/*
	 * Initial parameters for Dialogs (width, buttons)
	 */
	function setWidgetDetails($ar){
		foreach($ar as $n => $v){
			$this->widget_details[] = "$n: '$v'";
		}
	}

	//The main tool screen (with form, etc)
	function getWidgetOptionString(){
		$str = '{';
		$str .= implode(",\n", $this->widget_details);
		$str .= ",\nbuttons: { 
				'Add Report': function() { dacura_widget.submitForm(); }, 
				'Cancel': function() { $( this ).dialog( 'close' ); } }";
		$str .= "}";
		return $str;
	}
	
	//The confirm page
	function getWidgetConfirmOptionString(){
		$str = '{ ';
		$str .= implode(",\n", $this->widget_details);
		$str .= ",
		buttons: {
			'Confirm': function() { dacura_widget.submitConfirmedForm(data); }, 
			'Return to Input': function() { dacura_widget.switchToInputForm();}, 
			'Cancel': function() { $( this ).dialog( 'close' ); }  
		}, 
		 autoOpen: false 		
		}";
		return $str;
	}


	function getLoginWidget(){
		$contents_str = '<table class="dc-dialog">
			<tr><th>Username</th><td><input id="dacura-login-email" type="text" value=""></td>
			<tr><th>Password</th><td><input id="dacura-login-password" type="password" value=""></td>
			<tr><td colspan="2" id="loginbox-status" class="dacura-status"></td></tr>
			</table>';
		$html = "<div class='dacura-widget' id='dacura-widget-login'>$contents_str</div>";
		$html .= "<script>
		var dacura_login = function(){
			var ajs = dacura_widget.getAjaxSettings('login');
			ajs.beforeSend = function(){
				$('#loginbox-status').html('checking credentials...');
				$('.ui-dialog button:nth-child(1)').button('disable');
				$('.ui-dialog button:nth-child(2)').button('disable');	
			};
			ajs.complete = function(){
				$('.ui-dialog button:nth-child(1)').button('enable');
				$('.ui-dialog button:nth-child(2)').button('enable');	
			};
			ajs.data['login-email'] =  $('#dacura-login-email').val();
			ajs.data['login-password'] = $('#dacura-login-password').val();
			$.ajax(ajs)
				.done(function(data, textStatus, jqXHR) {
					$('#dacura-widget-login' ).dialog( 'close' );
					$('#dacura-widget-login' ).remove();
				 	$('body').append(data);
				})
				.fail(function (jqXHR, textStatus){
					$('#loginbox-status').html('<span class=\"control-failure\">Error: ' + jqXHR.responseText + '</span>');
			});	
		};
		$(function() {
			$('#dacura-login-password').keypress(function(e) {
    			if (e.keyCode == $.ui.keyCode.ENTER) {
					dacura_login();
    			}
			});
			$('#dacura-widget-login').show().dialog({'title': 'Log in to DaCura', 'width' : 350,
				'buttons': {
					'Log in': function(e) {
					    e.preventDefault();
						dacura_login();
					},
					'Cancel': function() { $( this ).dialog( 'close' ); $(this).remove();}
				}
			});
		});
	</script>";
		return $html;
	}
	
	
	
	/*
	 * The session controls pane for remote sessions / play / pause / 
	 */
	function getRemoteSessionWidget($u, $msg){
		$x = "<div id='dc-remote-session'>
				<input type='submit' value='pause' id='dc-remote-session-pause'>
				<input type='submit' value='resume' id='dc-remote-session-play'>
				<input type='submit' value='end' id='dc-remote-session-end'>
				<input type='submit' value='show' id='dc-remote-session-show'>
				<input type='submit' value='hide' id='dc-remote-session-hide'> ";
		if($u->getName() == 'abc' or $u->getName() == 'manualGS'){
				$x .= "<input type='submit' class='dc-debug' value='load' id='dc-remote-session-load'>
				<input type='text' class='dc-debug' value='' size='3' id='dc-remote-session-load-id'>
				<input type='submit' class='dc-debug' value='populate' id='dc-remote-session-populate'>
				<input type='submit' class='dc-debug' value='clear' id='dc-remote-session-clear'>";						
		}
		$x .= "<input type='submit' value='logout' id='dc-remote-session-logout'>
				<div class='dacura-session-status'>$msg</div>
				</div>";
		$x .= "<script>
		$(function() {
		    dacura_widget.setSessionMessage = function(msg){
				$('.dacura-session-status').html(msg);
			};
			dacura_widget.loadTool(true);
			$(dacura_widget.toolselector).bind('dialogclose', function(event) {
	 				$('#dc-remote-session-hide').hide();
	 				$('#dc-remote-session-show').show();
 			});
			$(dacura_widget.toolselector).bind('dialogopen', function(event) {
	 				$('#dc-remote-session-hide').show();
	 				$('#dc-remote-session-show').hide();
 			});
 			$('#dc-remote-session-hide').button().click(function(e){
				e.preventDefault();
				dacura_widget.closeTool();				
			}).hide();
 			$('#dc-remote-session-show').button().click(function(e){
				e.preventDefault();
				dacura_widget.openTool();				
			}).hide();
			
			$('#dc-remote-session-clear').button().click(function(e){
				e.preventDefault();
				dacura_widget.openTool();	
				dacura_widget.clearTool();			 	
			});
			$('#dc-remote-session-load').button().click(function(e){
				e.preventDefault();
				dacura_widget.openTool();	
				dacura_widget.clearTool();			 	
				var ajs = dacura_widget.getAjaxSettings('get_report');
				ajs.data.id = $('#dc-remote-session-load-id').val();
				$.ajax(ajs)
				.done(function(data, textStatus, jqXHR) {
					data = JSON.parse(data);
					dacura_widget.load(data);
					if('session' in data){
						$('.dacura-session-status').html(data.session);
					}
	 				$('#dc-remote-session-play').hide();
	 				$('#dc-remote-session-pause').show();
				})
				.fail(function (jqXHR, textStatus){
 					dacura_widget.setSessionMessage('error resuming session: ' + jqXHR.responseText, 'error');
				});						
			});
			$('#dc-remote-session-populate').button().click(function(e){
				e.preventDefault();
				dacura_widget.openTool();	
				dacura_widget.clearTool();			 	
				dacura_widget.loadToolFromContext();			 	
			});
					
				
				
 			$('#dc-remote-session-play').button().click(function(e){
				e.preventDefault();
				$.ajax(dacura_widget.getAjaxSettings('resume_session'))
				.done(function(data, textStatus, jqXHR) {
				 	dacura_widget.openTool();
				 	dacura_widget.setSessionMessage(data);
					$('.dacura-session-status').html(data);
	 				$('#dc-remote-session-play').hide();
	 				$('#dc-remote-session-pause').show();
				})
				.fail(function (jqXHR, textStatus){
 					dacura_widget.setSessionMessage('error resuming session: ' + jqXHR.responseText, 'error');
				});						
			}).hide();
			$('#dc-remote-session-pause').button().click(function(e){
				e.preventDefault();
				$.ajax(dacura_widget.getAjaxSettings('pause_session'))
				.done(function(data, textStatus, jqXHR) {
				 	dacura_widget.closeTool();
				 	dacura_widget.setSessionMessage(data);
					$('#dc-remote-session-pause').hide();
 					$('#dc-remote-session-play').show();
				})
				.fail(function (jqXHR, textStatus){
 					dacura_widget.setSessionMessage('error pausing session: ' + jqXHR.responseText, 'error');
				});						
			});
			$('#dc-remote-session-end').button().click(function(e){
				e.preventDefault();
				$.ajax(dacura_widget.getAjaxSettings('end_session'))
				.done(function(data, textStatus, jqXHR) {
				 	dacura_widget.setSessionMessage('Session Finished');
					dacura_widget.removeAll();
				 	$('#dc-remote-session').hide({
		            	effect: 'fade',
		            	duration: 3000
		        	});
					window.setTimeout(function() { $('#dc-remote-session').remove();}, 3000);
				
				})
				.fail(function (jqXHR, textStatus){
 					dacura_widget.setSessionMessage('error terminating session: ' + jqXHR.responseText, 'error');
				});
			});
			$('#dc-remote-session-logout').button().click( function(e){
				e.preventDefault();
				$.ajax(dacura_widget.getAjaxSettings('logout'))
				.done(function(data, textStatus, jqXHR) {
					dacura_widget.removeAll();
				 	dacura_widget.setSessionMessage('Logged out');
					$('#dc-remote-session').hide({
		            	effect: 'fade',
		            	duration: 3000
		        	}); 				
					window.setTimeout(function() { $('#dc-remote-session').remove();}, 3000);
				})
				.fail(function (jqXHR, textStatus){
 					dacura_widget.setSessionMessage('error logging out: ' + jqXHR.responseText, 'error');
				});
			});
		});
		</script>";
		return $x;
	}
	
	function getToolHTML($id, $settings){
		if(file_exists("phplib/tools/".$id.".php")){
			include_once("genericToolDisplay.php");
			include_once("phplib/tools/".$id.".php");
			$classname = $id."ToolDisplay";
			$tool = new $classname($this);
			return $tool->getHTML();
		}
		else {
			return "<P class='error'>Failed to load tool $id</P>";
		}
	}

	
/*
	function getCustomWidget(){
		$option_str = $this->getWidgetOptionString();
		$option_str2 = $this->getWidgetConfirmOptionString();
		$str = $this->getWidgetSubsection("dc-details-section", "details", $this->getLocationChunk() .  $this->getDetailsChunk());
		$str .= $this->getWidgetSubsection("dc-motivations-section", "motivations", $this->getMotivationChunk());
		$str .= $this->getWidgetSubsection("dc-actors-section", "actors", $this->getActorsChunk());
		$str .= $this->getWidgetSubsection("dc-description-section", "description", "<textarea id='event-description-input'></textarea>");
		$str .= $this->getWidgetSubsection("dc-citation-section", "citation", $this->getCitationChunk());
		$html = "<div class='dacura-widget' id='dacura-pv-widget'>
		<ul id='dacura-pv-widget-message'></ul>
		<div id='dacura-pv-widget-form'>$str</div>
		</div>
		<div class='dacura-widget' id='dacura-pv-widget-confirm'></div>
		<script>
		$(function() {
		$('#dacura-pv-widget').dialog($option_str);
		$('#dacura-pv-widget-confirm').dialog($option_str2);
		$('.dc-widget-section-header').click(function(e){
		e.preventDefault();
		$(this).toggleClass('dc-section-hidden');
		$(this).toggleClass('dc-section-displayed');
		$(this).next().toggle();
	});
	});
	</script>";
	return $html;
	}*/
	

	
	function getClassWidget($classuri, $mode){
		if($mode == 'capture') return $this->getCustomWidget();
		$classes_to_render = array();
		$classes_to_render = $this->flatten_super_array($this->sparql->getSuperClasses($classuri, $this->schema_graph_name));
		$classes_to_render[] = $classuri;
		$classes_to_render = array_merge($classes_to_render, $this->flatten_sub_array($this->sparql->getSubClasses($classuri, $this->schema_graph_name)));
		$contents_str = "";
		$contents_str .= "<table class='dacura-widget'>";
		foreach($classes_to_render as $cls){
			//$contents_str .= "<tr><th colspan='2'>$cls</th></tr>";
			$props = $this->sparql->getEntityProperties($cls, $this->schema_graph_name);
			foreach($props as $prop){
				$contents_str .= $this->propertyListAsRowHTML($prop, $mode);
			}
		}
		$contents_str .= "</table>";
		$contents_str .= "<div class='dacura-user-message'></div>";
		$contents_str .= $this->get_submit_buttons("dacura-submit", $mode);
		$option_str = $this->getWidgetOptionString();
		$widget_id = $this->URI2ID($classuri);
		$html = "<script>
			$(function() {
				$('.dacura-widget').remove();
				$('body').append('<div class=\"dacura-widget\" id=\"$widget_id\">$contents_str</div>');
				$('#$widget_id').dialog($option_str);
			});
		</script>";
		return $html;
	}
	
	
	
	

	

	



	
	
	function get_submit_buttons($cls, $mode){
		$str = "<div class='$cls'>";
		if($mode != 'display'){
			if($mode == 'capture'){
				$str .= "<input type='submit' class='dacura-submit-button' value='Add Record' id='$cls-add'>";
			}
			else {
				$str .= "<input type='submit' class='dacura-submit-button' value='Update Record' id='$cls-update'>";
				$str .= "<input type='submit' class='dacura-submit-button' value='Delete Record' id='$cls-delete'>";
			}
			$str .= "<input type='submit' class='dacura-cancel' value='Cancel' id='$cls-cancel'>";
		}
		$str .= "</div>";
		return $str;
	}
	
	function flatten_super_array($cls_tree){
	/*
	* Turns a class hierarchy into a flat list - branch by branch
	 */
		$super_flat = array();
		foreach($cls_tree as $parent => $branch){
			$super_flat[] = $parent;
			if(count($branch) > 0){
				$super_flat = array_merge($this->flatten_super_array($branch), $super_flat);
			}
		}
		return $super_flat;
	}

	function flatten_sub_array($subs){
	/*
	* Currently only return immediate sub-classes of the actual class...
		*/
		return array_keys($subs);
	}
	
	

	
	function propertyValueAsTDInput($propobj, $valobj = false){
		if($propobj->range == $this->ns->getnsuri("pv")."atTime"){
			//if($propobj->url == $this->ns->getnsuri("pv")."startDate"){
			//	return "<input type=text value='".$propobj->url."'>";
			//}
			return $propobj->url;
				
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Agent"){
			//
			return $propobj->url;
				
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Motivation"){
			$vals = $this->sparql->getSchemaValues($this->schema_graph_name, $propobj->range);
			return $this->makeSelect("event-motivation", $vals, true);
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Category"){
			$vals = $this->sparql->getSchemaValues($this->schema_graph_name, $propobj->range);
			return $this->makeSelect("event-category", $vals);
		}
		elseif($propobj->url == $this->pvns."description"){
			return "<textarea id='event-description'></textarea>";
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Fatalities"){
			return $this->getFatalitiesInput("event-fatalities", array());
			//return "<input type='text'></input>";
		}		
		elseif($propobj->range == $this->ns->getnsuri("pv")."Location"){
			return "<input type='text' id='event-location'></input>";
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Source"){
			return "<textarea id='event-source'></textarea>";
		}
		elseif($propobj->range == $this->ns->getnsuri("pv")."Report"){
			return "<input type='text' id='event-duplicate'></input>";
		}
		return $propobj->range;
	}
	
	
	
	
	
	
	function propertyListAsRowHTML($propobj, $mode){
		if($mode == 'capture'){
			$str = "<tr><td class='dacura-property-label'>".ucfirst($propobj->label);
			$str .= "</td><td class='dacura-property-input'>";
			$str .= $this->propertyValueAsTDInput($propobj);
			//$str .= $propobj->range;
			$str .= "</td><td><input type='checkbox'></td><td>?</td></tr>";
		}
		elseif($propobj->url == $this->pvns."atTime"){
			$str = "<tr><td class='dacura-property-label'>Date</td><td class='dacura-property-input'>";
			$str .= $this->getDateRangeChooserInput("event-date", array());
			//$str .= $this->propertyValueAsTDInput($propobj);
			//$str .= $propobj->range;
			$str .= "</td><td><input type='checkbox'></td><td>?</td></tr>";
		}
		else {
			$str = "<tr><td class='dacura-property-label'>".ucfirst($propobj->label);
			$str .= "</td><td class='dacura-property-input'>";
			$str .= $this->propertyValueAsTDInput($propobj);
			//$str .= $propobj->range;
			$str .= "</td><td><input type='checkbox'></td><td>?</td></tr>";
		}
		return $str;
	}

	
	function URI2ID($uri){
		return "DCR1";
	}
	
	
}
