dacura_widget.loadCandidate = function(data){
	this.load(data.contents);
	this.candidate_id = data.id;
	this.chunk_id = data.chunkid;
	$('#dc-actors-section-header').removeClass('dc-section-hidden');
	$('#dc-actors-section-header').addClass('dc-section-displayed');
	$('#dc-actors-section-header').next().show();	
	$('#dc-description-section-header').removeClass('dc-section-hidden');
	$('#dc-description-section-header').addClass('dc-section-displayed');
	$('#dc-description-section-header').next().show();	
};

dacura_widget.mode = "internal";
