var graphurl = "http://tcd:3020/data/seshattiny.ttl";
var instgraphurl ="http://tcd:3020/data/annotation.ttl";
var defurl = "http://tcd:3020/dacura/def";
var annourl = "http://tcd:3020/dacura/element_annotation";
var seshat_ns = "http://dacura.scss.tcd.ie/ontology/seshat#";

function getInstancesOfClass(cls){
    var result = postDQSRequest(defurl, "query=all "+cls+" in "+graphurl);
    var i = 0;
    result.forEach(function(entry){
        result[i] = entry.replace("http://dacura.scss.tcd.ie/ontology/seshat#","");
        i++;
    });
    return result;
}

function getAnnotationFrame(inst,prop){
    inst = inst.replace("seshattiny:",seshat_ns);
    var result = postDQSRequest(annourl, "schema="+graphurl"&instance="+instgraphurl+"&property="+prop+"&element="+inst);
    var i = 0;
    result.forEach(function(entry){
        result[i] = entry.replace("http://dacura.scss.tcd.ie/ontology/seshat#","");
        i++;
    });
}

function postDQSRequest(url,send){
    var result;
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                result = JSON.parse(xhttp.responseText);
            }
        }
    }
    xhttp.open("POST",url,false);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send(send);
    return result;
}