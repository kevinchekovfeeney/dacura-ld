<div class='dacura-subscreen ld-export' id="ldo-export" title="<?=$params['ld_export_query_title']?>" name="query">
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
</script>