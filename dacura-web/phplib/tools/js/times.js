jQuery.fn.exists = function(){return this.length>0;};


dacura_widget.parsePage = function(){
	var canonical_url = "http://find.galegroup.com/ttda/infomark.do?tabID=T003&docPage=article&type=multipage&contentSet=LTO&version=1.0&docId=";
	var url_base = "http://find.galegroup.com/ttda/";
	var d = new Date($('#quickSearchForm input[name="workId"]').attr("value")); 
	var mthNames = ["Jan.","Feb.","Mar.","Apr.","May","Jun.","July","Aug.","Sep.","Oct.","Nov.","Dec."];
	var m = mthNames[d.getMonth()];
	var sp = d.getDate() +  " " + m + " "+d.getFullYear();
	x = $('div.ct_LtoNewspaper div').text().split(sp);
	var txt = x[0].substring(1, x[0].length-26);
	if(x.length > 1){
		var pg = x[1].substring(2, x[1].indexOf("."));
	}
	
	var sect = $('b:contains("Category: ")').parent().html().substring(18);
	var ix = $('b:contains("Category: ")').parent().prev().html();
	var ibits = ix.split("Issue ") ;
	var iid = ibits[1].substring(0, ibits[1].indexOf("."));
	bits = {};
	//bits['title'] = "you what";
	//bits['section'] = "section";
	bits['date'] = {from: {}};
	var previousDay = new Date(d.getTime() - (1000*60*60*24));
	bits['date']['from']['year'] = previousDay.getFullYear();
	bits['date']['from']['month'] = previousDay.getMonth() + 1;
	bits['date']['from']['day'] = previousDay.getDate();
	
	bits['citation'] = {
		publicationtitle : "The Times",
		publicationurl: "http://www.thetimes.co.uk/tto/archive/",
		issuetitle : iid,
		issueurl : url_base + $('#BrowseIssueId').attr("href"),
		issuedate: {'year' : d.getFullYear(), 'month' : d.getMonth() + 1, "day": d.getDate()},
		sectiontitle: sect,
		sectionurl: url_base + $('#FlexViewBrowseIssueId').attr("href"),
		articletitle: txt,
		articleurl: canonical_url + $('#resultsForm input[name="docId"]').attr("value"),
		articleid: $('#resultsForm input[name="docId"]').attr("value"),
		articleimage : $('img#fascimileImg').attr("src"), 
		articlepagefrom : pg,
		articlepageto : ""
	};
	//bits['description'] =txt;
	return bits;
};


dacura_widget.parseChunk = function(data){
	var canonical_url = "http://find.galegroup.com/ttda/infomark.do?tabID=T003&docPage=article&type=multipage&contentSet=LTO&version=1.0&docId=";
	var url_base = "http://find.galegroup.com/ttda/";
	var d = new Date($('#quickSearchForm input[name="workId"]', data).attr("value")); 
	var mthNames = ["Jan.","Feb.","Mar.","Apr.","May","Jun.","July","Aug.","Sep.","Oct.","Nov.","Dec."];
	var m = mthNames[d.getMonth()];
	var sp = d.getDate() +  " " + m + " "+d.getFullYear();
	x = $('div.ct_LtoNewspaper div', data).text().split(sp);
	var txt = x[0].substring(1, x[0].length-26);
	if(x.length > 1){
		var pg = x[1].substring(2, x[1].indexOf("."));
	}
	var pcat = $('b:contains("Category: ")', data).parent();
	if(pcat.exists()){
		var sect = $('b:contains("Category: ")', data).parent().html().substring(18);
		var pix = $('b:contains("Category: ")', data).parent().prev();
		if(pix.exists()){
			var ix = $('b:contains("Category: ")', data).parent().prev().html();
		}
		else {
			var ix = "";
		}
	}
	else {
		var sect = "";
		var ix = "";
		return false;
	}
	var ibits = ix.split("Issue ") ;
	var iid = ibits[1].substring(0, ibits[1].indexOf("."));
	bits = {};
	//bits['title'] = "you what";
	//bits['section'] = "section";
	bits['date'] = {from: {}};
	var previousDay = new Date(d.getTime() - (1000*60*60*24));
	bits['date']['from']['year'] = previousDay.getFullYear();
	bits['date']['from']['month'] = previousDay.getMonth() + 1;
	bits['date']['from']['day'] = previousDay.getDate();
	
	bits['citation'] = {
		publicationtitle : "The Times",
		publicationurl: "http://www.thetimes.co.uk/tto/archive/",
		issuetitle : iid,
		issueurl : url_base + $('#BrowseIssueId', data).attr("href"),
		issuedate: {'year' : d.getFullYear(), 'month' : d.getMonth() + 1, "day": d.getDate()},
		sectiontitle: sect,
		sectionurl: url_base + $('#FlexViewBrowseIssueId', data).attr("href"),
		articletitle: txt,
		articleurl: canonical_url + $('#resultsForm input[name="docId"]', data).attr("value"),
		articleid: $('#resultsForm input[name="docId"]', data).attr("value"),
		articleimage : $('img#fascimileImg', data).attr("src"), 
		articlepagefrom : pg,
		articlepageto : ""
	};
	//bits['description'] =txt;
	return bits;
};



//this should be moved to a place of its own
dacura_widget.loadToolFromContext = function(){
	data = this.parsePage();
	data.actors = {};
	data.description = "";
	this.load(data);
	//this.debugDump(this.parsePage());
};


dacura_widget.mode = 'capture';
