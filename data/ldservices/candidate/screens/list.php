<style>
/* Style the list */
ul.tab {
    list-style-type: none;
    margin: 0;
    padding: 0;
    overflow: hidden;
    border: 1px solid #ccc;
    background-color: #f1f1f1;
}

/* Float the list items side by side */
ul.tab li {float: left;}

/* Style the links inside the list items */
ul.tab li a {
    display: inline-block;
    color: black;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
    transition: 0.3s;
    font-size: 17px;
}

/* Change background color of links on hover */
ul.tab li a:hover {background-color: #ddd;}

/* Create an active/current tablink class */
ul.tab li a:focus, .active {background-color: #ccc;}

/* Style the tab content */
.tabcontent {
    display: none;
    padding: 6px 12px;
    border: 1px solid #ccc;
    border-top: none;
}
</style>

<div class='dacura-screen' id='candidate-page'>
	<div id="candidate-list" class='dacura-subscreen ld-list' title="Instance Data Entities">
		<table id="candidate_table" class="dcdt display">
			<thead>
			<tr>
				<th id='cpx-id'>ID</th>
				<th id='cpx-type'>Type</th>
				<th id='cpx-collectionid'>Collection ID</th>
				<th id='cpx-status'>Status</th>
				<th id='cpx-version'>Version</th>
				<th id='cpx-meta-schemaversion'>Schema Version</th>
				<th id='dfn-getPrintableCreated'>Created</th>
				<th id='cpx-createtime'>Sortable Created</th>
				<th id="dfn-getPrintableModified">Modified</th>
				<th id="cpx-modtime">Sortable Modified</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="update-list" class='dacura-subscreen ld-list' title="Updates to Instance Data">
		<table id="update_table" class="dcdt display">
			<thead>
			<tr>
				<th id='cpu-eurid'>ID</th>
				<th id='cpu-targetid'>Candidate</th>
				<th id='cpu-collectionid'>Collection ID</th>
				<th id='cpu-status'>Status</th>
				<th id='cpu-from_version'>From Version</th>
				<th id='cpu-to_version'>To Version</th>
				<th id='cpu-meta-schemaversion'>Schema Version</th>
				<th id='cpx-createtime'>Created</th>
				<th id='dfn-getPrintableCreated'>Sortable Created</th>
				<th id="cpx-modtime">Modified</th>
				<th id='dfn-getPrintableModified'>Sortable Modified</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class='dacura-subscreen' id="create-candidate" title="Create New Instance Data Entity">
		<?php echo $service->showLDResultbox($params);?>
		<?php echo $service->showLDEditor($params);?>		
	</div>
    <div id="def-query" class='dacura-subscreen' title="Data Export Query" name="query">
        <ul class="tab">
            <li><a href="#" class="tablinks" onclick="openQuery(event, 'Instances')">Instances</a></li>
            <li><a href="#" class="tablinks" onclick="openQuery(event, 'Time')">Time</a></li>
            <li><a href="#" class="tablinks" onclick="openQuery(event, 'Region')">Region</a></li>
        </ul>
        <div id="Instances" class="tabcontent">
            <p>Class</p>
            <input type="text" id="clsquery" list="classes-dl" size=50>
            <input type="button" value="Query" onclick="instQuery(document.getElementById('clsquery').value);">
        </div>
        <div id="Time" class="tabcontent">
            <p>Temporal Entity</p>
            <input type="text" id="tempquery" list="time-dl" size=50>
            <p>From:<br><input type="date" id="from"></p>            
            <p>To:<br><input type="date" id="to"></p>            
            <input type="button" value="Query" onclick="timeQuery(document.getElementById('tempquery').value, document.getElementById('from').value, document.getElementById('to').value);">
        </div>
        <div id="Region" class="tabcontent"></div>
        <datalist id="classes-dl"></datalist>
        <datalist id="instances-dl"></datalist>
        <datalist id="properties-dl"></datalist>
        <datalist id="time-dl"></datalist>
    </div>
</div>

<script>
    
function openQuery(evt,query) {
    var i,tabcontent,tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tabcontent.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(query).style.display = "block";
    evt.currentTarget.className += " active";
    if(query === 'Instances') { loadClasses();}
    else if(query === 'Time') { loadTimeEntities(); }
}
    
function instQuery(data) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                var lst = document.createElement('input');
                lst.setAttribute('id','instances');
                lst.setAttribute('list','instances-dl');
                lst.setAttribute('size','50');
                var btn = document.createElement('input');
                btn.setAttribute('type','button');
                btn.setAttribute('value','Query'); btn.setAttribute('onclick','getProperties(document.getElementById("instances").value);');
                document.getElementById('Instances').innerHTML += '<p>Instances</p>';
                document.getElementById('Instances').appendChild(lst);
                document.getElementById('Instances').innerHTML += ' ';
                document.getElementById('Instances').appendChild(btn); 
                var dataList = document.getElementById('instances-dl'); 
                var jsonOptions = JSON.parse(xhttp.responseText);
                jsonOptions.forEach(function(item) {
                   var option = document.createElement('option');
                    option.value = item;
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST","http://localhost:3020/dacura/def",true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=all "+data+" in http://tcd:3020/data/uploaded/seshat-test.ttl");
}
    
function timeQuery(data, from, to) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                document.getElementById('Time').innerHTML += '<p>' + xhttp.responseText;
            }
        }
    };
    xhttp.open("POST","http://localhost:3020/dacura/def",true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=all "+data+" in http://tcd:3020/data/uploaded/seshat-test.ttl from "+from+" to "+to);
}
    
function getProperties(data) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                var lst = document.createElement('input');
                lst.setAttribute('id','properties');
                lst.setAttribute('list','properties-dl');
                lst.setAttribute('size','50');
                var btn = document.createElement('input');
                btn.setAttribute('type','button');
                btn.setAttribute('value','Query');
                btn.setAttribute('onclick','_(document.getElementById("properties").value);');
                document.getElementById('Instances').innerHTML += '<p>Properties</p>';
                document.getElementById('Instances').appendChild(lst);
                document.getElementById('Instances').innerHTML += ' ';
                document.getElementById('Instances').appendChild(btn); 
                var dataList = document.getElementById('properties-dl'); 
                var jsonOptions = JSON.parse(xhttp.responseText);
                jsonOptions.forEach(function(item) {
                   var option = document.createElement('option');
                    option.value = item;
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST","http://localhost:3020/dacura/def",true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=show properties for "+data+" in http://tcd:3020/data/uploaded/seshat-test.ttl");
}
    
function loadClasses() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                var dataList = document.getElementById('classes-dl');
                var jsonOptions = JSON.parse(xhttp.responseText);
                jsonOptions.forEach(function(item) {
                   var option = document.createElement('option');
                    option.value = item; 
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST","http://localhost:3020/dacura/def",true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=show classes in http://tcd:3020/data/uploaded/seshat-test.ttl");
}
    
function loadTimeEntities() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                var dataList = document.getElementById('time-dl');
                var jsonOptions = JSON.parse(xhttp.responseText);
                jsonOptions.forEach(function(item) {
                   var option = document.createElement('option');
                    option.value = item; 
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST","http://localhost:3020/dacura/def",true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=subs http://dacura.cs.tcd.ie/data/seshat#TemporalEntity in http://tcd:3020/data/uploaded/seshat-test.ttl");
}

function getPrintableCreated(obj){
	return timeConverter(obj.createtime);
}

function getPrintableModified(obj){
	return timeConverter(obj.modtime);
}

$(function() {
	dacura.system.init({
		"mode": "tool", 
		"tabbed": "candidate-page", 
		"listings": {
			"ld_table": {
				"screen": "candidate-list", 
				"fetch": dacura.ld.fetchcandidatelist,
				"settings": <?=$params['candidate_datatable']?>
			},
			"update_table": {
				"screen": "update-list", 
				"fetch": dacura.ld.fetchupdatelist,
				"settings": <?=$params['update_datatable']?>				
			}
		}, 
	});
	dacura.editor.init({"editorheight": "400px", "targets": { resultbox: "#create-candidate-msgs", errorbox: "#create-candidate-msgs", busybox: '#create-holder'}, 
		"args": <?=json_encode($params['args']);?>});

	dacura.editor.getMetaEditHTML = function(meta){
		$('#meta-edit-table').show();
		return "";
	};
	dacura.editor.getInputMeta = function(){
		var meta = {"status": $('#entstatus').val()};
		return meta;
	};
	dacura.editor.load(false, false, dacura.candidate.create);
});
	
</script>