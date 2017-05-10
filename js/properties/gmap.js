//

dacura.gmap = {};

dacura.gmap.includeGoogleMapScript = function(key){
	var script = document.createElement("script");
	script.src = "https://maps.googleapis.com/maps/api/js?key=" + key;
	document.getElementsByTagName('head')[0].appendChild(script);	
}

dacura.gmap.translatePolygonToArray = function(polygon){
   var data = "[";
   var vertices = polygon.getPath();
   var seenValue = false;
   for (var j=0; j<vertices.getLength(); j++){
       if(seenValue){
           data = data + ",";
       }
       var xy = vertices.getAt(j);
       data = data + "[" + xy.lat() + "," + xy.lng() + "]"
       seenValue = true;
   }
   data = data + "]";
   return data;
};

dacura.gmap.translateArrayToPolygon = function(array){
   var jsonified = JSON.parse(array);
   var output = [];
   for(var i=0;i<jsonified.length;i++){
       x = {}
       x.lat = jsonified[i][0];
       x.lng = jsonified[i][1];
       output.push(x);
   }
   return output;
}

function MapViewer(config) {
	this.polygon = false;
	this.polyline = false;
	this.markers = [];
	if(config){
		this.init(config);
	}
}

MapViewer.prototype.init = function(config){
	this.type = ((config && config.type) ? config.type: 'Shape'); 
	this.width = ((config && config.width) ? config.width : '400px'); 
	this.height = ((config && config.height ) ? config.height : '400px'); 
	this.mapLat = ((config && config.mapLat) ? config.mapLat : '41.9010004'); 
	this.mapLong = ((config && config.mapLong ) ? config.mapLong : '12.500061500000015');
	this.zoom = ((config && config.zoom) ? config.zoom : 3);
	this.stroke = ((config && config.stroke) ? config.stroke : { color: '#FF0000', opacity: 0.3, weight: 3});
	this.fill = ((config && config.fill) ? config.fill : { color: '#FF0000', opacity: 0.1});
	this.line = ((config && config.line) ? config.line : { color: '#000000', opacity: 0.9, weight: 3});
}

MapViewer.prototype.destroy = function(){
    this.clear();
	jQuery(".googleMap").remove();
} 

MapViewer.prototype.hasDisplay = function(mode){
	return true;
}


MapViewer.prototype.display = function(elt, mode){
	var map = document.createElement("div");
	map.setAttribute('class', "googleMap");
	if(elt.rangeValue && elt.rangeValue.data){
		var val = elt.rangeValue.data;
	}
	var self = this;
	var doit = function(){
		self.initMap(map, mode, val);		
	}
	deferUntilGoogleLoaded(doit);
	if(this.type != "Point"){
		this.dataBind(elt, "contents", map);
	}
	else {
		this.bind(elt, "contents", map);
	}
    return map;
}

MapViewer.prototype.dataBind = function(obj, prop, elt){
    Object.defineProperty(obj, prop, {
        get: function(){return elt.dataset.value;}, 
        set: function(newValue){elt.dataset.value = newValue;},
        configurable: true
    });
}

MapViewer.prototype.bind = function(obj, prop, elt){
	var self = this;
    Object.defineProperty(obj, prop, {
        get: function(){
        	if(self.markers.length){
        		 return "[" + self.markers[0].position.lat() + "," + self.markers[0].position.lng() + "]";
        	}
        }, 
        set: function(newValue){elt.dataset.value = newValue;},
        configurable: true
    });
}

/*MapViewer.prototype.extract = function(frame, elt, m){
	var data = "";
	if(this.polygon != null){
		var vertices = this.polygon.getPath();
		for (var j =0; j < vertices.getLength(); j++) {
           var xy = vertices.getAt(j);
           if(data.length > 0){
        	   data = data + " ";
           }
           data += xy.lat() + "," + xy.lng();
        }
	}
	return data;
}*/


MapViewer.prototype.createPolygon = function(coords, map) {
	var polygon = new google.maps.Polygon({
        paths: coords,
        strokeColor: this.stroke.color,
        strokeOpacity: this.stroke.opacity,
        strokeWeight: this.stroke.weight,
        fillColor: this.fill.color,
        fillOpacity: this.fill.opacity
	});
    polygon.setMap(map);
	return polygon;
}

MapViewer.prototype.createPolyline = function(){
	 var poly = new google.maps.Polyline({
	      strokeColor: this.line.color,
	      strokeOpacity: this.line.opacity,
	      strokeWeight: this.line.weight
	  });
	 return poly;
}

MapViewer.prototype.clearMarkers = function() {
	for (var i = 0; i < this.markers.length; i++ ) {
		this.markers[i].setMap(null);
	}
	this.markers.length = 0;
}

MapViewer.prototype.addMarker = function(position, map, mapContainer, title) {
	var init = {
		position: position, 
		map: map
	}
	if(title){
		init.title = title;
	}
	var marker = new google.maps.Marker(init);
	var self = this;
	if(this.markers.length == 0){
     	marker.addListener('click', function() {
			coords = self.getCoordsFromMarkers();
    		self.polygon = self.createPolygon(coords, map);
            self.clearMarkers();
            self.polyline.setMap(null);
            mapContainer.dataset.value = dacura.gmap.translatePolygonToArray(self.polygon);
        });                	
    }
	this.markers.push(marker);
	return marker;
}

MapViewer.prototype.getCoordsFromMarkers = function(){
	return this.polyline.getPath();
}

MapViewer.prototype.initMap = function(mapContainer, mode, value){
	mapContainer.style.width = this.width;
	mapContainer.style.height = this.height;
	var map = new google.maps.Map(mapContainer, {
	    zoom: this.zoom,
		center: {lat: parseFloat(this.mapLat), lng: parseFloat(this.mapLong)}  
	});
	if(this.type == "Shape"){
	    this.polyline = this.createPolyline();
		this.polyline.setMap(map);
		var coords = (value ? dacura.gmap.translateArrayToPolygon(value) : []);
		if(mode == 'view' || mode == "edit"){
	    	this.polygon = this.createPolygon(coords, map);
	    }
	    if(mode == "create" || mode == "edit"){
	    	var self = this;
	        google.maps.event.addListener(map, 'click', function(event) {
	        	var path = self.polyline.getPath();
	        	path.push(event.latLng);
		  	    position = event.latLng;
		  	    self.addMarker(position, map, mapContainer, '#' + path.getLength());
		  	});
	    }
	}
	else if(this.type == "Point"){
    	var self = this;

		if(mode == 'view' || mode == "edit"){
			if(value.length){
				self.clearMarkers();
	            var lat = parseFloat(value.substring(1, value.indexOf(",")));
				var long = parseFloat(value.substring(value.indexOf(",")+1, value.length-1));
	            var init = {
	            	position: {lat: lat, lng: long},
	        		map: map
		        }
		        var marker = new google.maps.Marker(init);
		        self.markers.push(marker);
			}
	    }
	    if(mode == "create" || mode == "edit"){
	        google.maps.event.addListener(map, 'click', function(event) {
	        	var position = event.latLng;
	            self.clearMarkers();
	            var init = {
	        		position: position, 
	        		map: map
	        	}
	        	var marker = new google.maps.Marker(init);
	        	self.markers.push(marker);
		  	    //self.addMarker(position, map, mapContainer);
		  	});
	    }
		
	}
    google.maps.event.trigger(map, 'resize');
}

MapViewer.prototype.clear = function(){
	if(this.polygon) {
		this.polygon.setMap(null);
		this.polygon = false;
	}
	if(this.polyline) {
		this.clearMarkers();
		this.polyline.setMap(null);
	}

}

function deferUntilGoogleLoaded(method) {
	if (typeof google != "undefined"){
		method();
	}
	else {
    	setTimeout(function() { deferUntilGoogleLoaded(method) }, 50);
	}
}

//var initall = function(){
	dacura.gmap.includeGoogleMapScript("AIzaSyDD_KgqQgwVDiXFFVFDiwypsBN_k9TLJD8");
//}

//window.onload = initall;
