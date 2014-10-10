<?php 
	$datasets = array("uspv" => "United States of America Political Violence Data Set", "ukipv" => "UK and Ireland Political Violence Data Set");
	$provenances = array("test" => "Test Widget", "web" => "Website");
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>DaCura Data Curation Tool - Test Interface</title>
        <script type="text/javascript" src="jquery-1.9.1.min.js"></script>
        <script type="text/javascript" src="json2.js"></script>
        <script type="text/javascript" src="jquery-ui-1.10.2.custom.min.js"></script>
        <link rel="stylesheet" type="text/css" href="jquery-ui-1.10.2.custom.min.css" />
        <link rel="stylesheet" type="text/css" href="dacura.css" />
        <script>

        	var rest_server = "/RDC/resources/update";

        
            function escapeHtml(uns) {
                return uns.replace(/&/g, "&amp;")
                   .replace(/</g, "&lt;")
                   .replace(/>/g, "&gt;")
                   .replace(/"/g, "&quot;")
                   .replace(/'/g, "&#039;");
            }

            function parseDate(dt, h, m, s, ms){
            	  var parts = dt.match(/(\d+)/g);
            	  return new Date(parts[2], parts[1]-1, parts[0], h, m, s, ms); // months are 0-based
            }

            
            function json2Display(json, hdrTxt){
                var rows = "<tr><th colspan='2'>" + hdrTxt + "</th></tr>";
                for (var key in json) {
                    if (json.hasOwnProperty(key)) {
                        var val = json[key];
                        if(typeof val == "string"){
                           val = escapeHtml(val); 
                           if(val.match("\n")){
                                val = "<PRE>"+ val + "</PRE>";
                           }    
                        }
                        
                        rows += "<tr><th>" + key + "</th><td>"+val+"</td></tr>\n";
                    }
                }
                return rows;
            }
            
            
            
            $(function() {
            	$( "#ts_date" ).datepicker();
            	$( "#tstype" ).buttonset();
                $('#tstypenow').click(function() {
                    $('#ts_set').hide();
                });
                $('#tstypeset').click(function() {
                    $('#ts_set').show();
                });
                
                $( "#payloadtype" ).buttonset(); 
                $( "#rdc-send" ).button().click(function(e) {
                    e.preventDefault();
                    var update_type = $('#updatetype :selected').val();
                    var test_flag = $('#testflag :selected').val();
                    var ds_id = $('#datasetid :selected').val();
                    var ts_type = $('#tstype :checked').val();
                    if(ts_type == "now"){
                        ts = new Date();
                    }
                    else {
                        ts = parseDate($('#ts_date').val(), $('#ts_hour :selected').val(), 
                                $('#ts_min :selected').val(), $('#ts_sec :selected').val(), $('#ts_ms').val());
                        alert(ts);
                    }
                    var user_type = $('#usertype :selected').val();
                    var provenance = $('#provenance :selected').val();
                    var payload_type = $('#payloadtype :checked').val();
                    var payload = $('#payload').val();
                    var json_msg = {
                        "update_type": update_type, 
                        "test_flag": test_flag,
                        "dataset_id" : ds_id,
                        "timestamp" : ts,
                        "user_type" : user_type,
                        "provenance_id" : provenance,
                        "payload_type" : payload_type,
                        "payload" : payload
                    }
                    $("#rdc-ro-contents").html('<table class="rdc-input-form">' + json2Display(json_msg, "Sent Message") + '</table>');
                    $.post(rest_server, {payload: JSON.stringify(json_msg)})
                    	.done(function(data) {
                        	$("#rdc-ro-contents").append('<table class="rdc-input-form">' + json2Display(data, "Received Message") + '</table>');
                    	})
                    	.fail( function(xhr, textStatus, errorThrown) {
                            alert(textStatus + " " + xhr.responseText);
                        })                ;
                });
            });
	</script>
    </head>
    <body>
        <div class="rdcpage">
        <div id="rdc-uo" class="ui-widget">
            <div class ="ui-widget-header ui-corner-top">RDC Client Request Generator</div>
            <div class="ui-widget-content ui-corner-bottom">
                <table class="rdc-input-form">
                    <tr>
                        <th>Update Type</th>
                        <td>
                            <select id="updatetype">
                                <option value="">None</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                                <option value="raw_delete">Raw Triple Delete</option>
                                <option value="raw_create">Raw Triple Create</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Test Flag</th>
                        <td>
                            <select id="testflag">
                                <option value="">Off</option>
                                <option value="on">On</option>
                                <option value="verbose">Debug</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Data Set ID</th>
                        <td>
                            <select id="datasetid">
                                <option value="">Choose Dataset</option>
                            	<?php foreach($datasets as $dn => $ds) { ?>
                            	    <option value="<?php echo $dn?>"><?php echo $ds?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Timestamp</th>
                        <td>
                            <div id="tstype">
                                <input type="radio" name="tstype" id="tstypenow" value="now" checked><label for="tstypenow">Now</label> 
                                <input type="radio" name="tstype" id="tstypeset" value="set"><label for="tstypeset">Specify</label> 
                            </div>
                            <div id="ts_set">
                                Time: 
                                <select id="ts_hour">
                                	<option value="">h</option>
                                	<?php for($i = 0; $i < 24; $i++){?>
                                		<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                	<?php }?>
                                </select>:<select id="ts_min">
                                	<option value="">m</option>
                                	<?php for($i = 0; $i < 60; $i++){?>
                                		<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                	<?php }?>
                                </select>:<select id="ts_sec">
                                	<option value="">s</option>
                                	<?php for($i = 0; $i < 60; $i++){?>
                                		<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                	<?php }?>
                                </select>:<input type="text" size="3" id="ts_ms">
                            	<br>Date: <input type="text" id="ts_date">
					        </div>
                        </td>
                    </tr>
                    <tr>
                        <th>User Type</th>
                        <td>
                            <select id="usertype">
                                <option value="">Select User Role</option>
                                <option value="admin">Administrator</option>
                                <option value="mod1">Schema Moderator</option>
                                <option value="mod2">Instance Moderator</option>
                                <option value="user">User</option>                                
                            </select>
                        </td>
                    </tr>                    
                    <tr>
                        <th>Input Source</th>
                        <td>
                            <select id="provenance">
                                <option value="">Select Provenance</option>
                                <?php foreach($provenances as $i => $p){?>
                                    <option value="<?php echo $i?>"><?php echo $p?></option>
                                <?php } ?> 
                            </select>
                        </td>
                    </tr>                         
                    <tr>
                        <th>RDF Payload</th>
                        <td>
                            <textarea id="payload"></textarea>
                            <div id="payloadtype">
                                <input type="radio" checked name="rdfformat" id="rdfformat-turtle" value="turtle" /><label for="rdfformat-turtle">Turtle</label>
                                <input type="radio" name="rdfformat" id="rdfformat-xml" value="xml" /><label for="rdfformat-xml">RDF/XML</label>
                                <input type="radio" name="rdfformat" id="rdfformat-sparql" value="sparql" /><label for="rdfformat-sparql">SPARQL</label>
                            </div>
                        </td>
                    </tr>                         
                </table>
                <div class="rdc-submit">
                    <input id="rdc-send" type="submit" value="Submit Update Request Object"></input>
                </div>
            </div>
        </div>
        <div id="rdc-ro" class="ui-widget">
            <div class ="ui-widget-header ui-corner-top">RDC Server Response</div>
            <div id="rdc-ro-contents" class="ui-widget-content ui-corner-bottom">
                <img id="rdc-ro-empty" src="empty.gif">
            </div>
        </div>
    </div>
    </body>
</html>
