<div class='dacura-subscreen ld-export' id="ldo-export" title="<?=$params['ld_export_query_title']?>" name="query">
        <ul class="tab">
            <li><a href="#" class="tablinks" onclick="openQuery(event, 'Instances')">Instances</a></li>
            <li><a href="#" class="tablinks" onclick="openQuery(event, 'Time')">Time</a></li>
            <li><a href="#" class="tablinks" onclick="openQuery(event, 'Uncertainty')">Uncertainty</a></li>
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
            <input type="button" value="Export" onclick="timeQuery(document.getElementById('tempquery').value, document.getElementById('from').value, document.getElementById('to').value);">
        </div>
        <div id="Uncertainty" class="tabcontent">
            <p>Uncertain Class</p>
            <input type="text" id="clsquery" list="classes-dl" size=50>
            <input type="button" value="Query" onclick="instQuery(document.getElementById('clsquery').value);">
        </div>
        <datalist id="classes-dl"></datalist>
        <datalist id="instances-dl"></datalist>
        <datalist id="properties-dl"></datalist>
        <datalist id="time-dl"></datalist>
    </div>
<script>
var DEF_SERVER = "http://dacura.scss.tcd.ie/dqs/dacura/def";
var SCHEMA = "http://dacura.scss.tcd.ie/seshat-test.ttl";
//var DEF_SERVER = "http://tcd:3020/dacura/def";
//var SCHEMA = "http://tcd:3020/data/uploaded/seshat-test.ttl";
var SESHAT_NS = "http://dacura.cs.tcd.ie/data/seshat#";
var class_array = new Array();
var instances_array = new Array();
var predicates_array = new Array();
var selected_class = "";
var selected_instance = "";
var selected_predicate = "";
    
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
    if(query === 'Instances') { resetInstances(); loadClasses(); }
    else if(query === 'Time') { resetTime(); loadTimeEntities(); }
    else if(query == 'Uncertainty') { resetUncertainty(); loadUncertainty(); } 
}
    
function instQuery(data) {
    selected_class = data;
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
                    instances_array.push(item);
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST",DEF_SERVER,true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=all "+data+" in "+SCHEMA);
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
    xhttp.open("POST",DEF_SERVER,true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=all "+data+" in "+SCHEMA+" from "+from+" to "+to);
}
    
function getProperties(data) {
    selected_instance = data;
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
                btn.setAttribute('value','Export');
                btn.setAttribute('onclick','export_to_tsv();');
                document.getElementById('Instances').innerHTML += '<p>Properties</p>';
                document.getElementById('Instances').appendChild(lst);
                document.getElementById('Instances').innerHTML += ' ';
                document.getElementById('Instances').appendChild(btn); 
                var dataList = document.getElementById('properties-dl'); 
                var jsonOptions = JSON.parse(xhttp.responseText);
                jsonOptions.forEach(function(item) {
                   var option = document.createElement('option');
                    option.value = item;
                    predicates_array.push(item);
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST",DEF_SERVER,true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=show properties for "+data+" in "+SCHEMA);
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
                    class_array.push(item);
                    dataList.appendChild(option);
                });
            }
        }
    };
    xhttp.open("POST",DEF_SERVER,true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=show classes in "+SCHEMA);
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
    xhttp.open("POST",DEF_SERVER,true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=subs "+SESHAT_NS+"TemporalEntity in "+SCHEMA);
}
    
function loadUncertainty() {
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
    xhttp.open("POST",DEF_SERVER,true);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=all "+SESHAT_NS+" in "+SCHEMA);
}
    
function resetInstances() {
    document.getElementById("Instances").innerHTML = "<p>Class</p><input type=\"text\" id=\"clsquery\" list=\"classes-dl\" size=50> <input type=\"button\" value=\"Query\" onclick=\"instQuery(document.getElementById('clsquery').value);\">";
}
function resetTime() {
    document.getElementById("Time").innerHTML = "<p>Temporal Entity</p><input type=\"text\" id=\"tempquery\" list=\"time-dl\" size=50>            <p>From:<br><input type=\"date\" id=\"from\"></p><p>To:<br><input type=\"date\" id=\"to\"></p><input type=\"button\" value=\"Query\" onclick=\"timeQuery(document.getElementById('tempquery').value, document.getElementById('from').value, document.getElementById('to').value);\">";
}
function resetUncertainty() {}
function export_to_tsv() { 
    var tsv_result = "";
    class_array.forEach(function(c){
        if(c == selected_class) {
            instances_array.forEach(function(i){
                if(i == selected_instance) {
                    predicates_array.forEach(function(p){
                       tsv_result += c+'\t'+i+'\t'+p+'\n';
                    });
                }
                else {
                    tsv_result += c+'\t'+i+'\n'; 
                }
            });
        }
        else {
            tsv_result += c+'\n';            
        }
    });
    var downloadLink = document.createElement("a");
    downloadLink.href = 'data:application/octet-stream,'+encodeURIComponent(tsv_result);
    downloadLink.download = "data.tsv";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>