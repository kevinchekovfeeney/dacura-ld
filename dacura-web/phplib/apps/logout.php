<div class='dacura-widget' id='dacura-widget-logout'>
	<div class="dacura-widget-intro">You are currently logged into Dacura as <strong><?=$context?></strong>.</div>
	<div id="logoutbox-status" class="dacura-status"></div>
	<div class="dacura-widget-buttons">
		<a class="button logout-button" id='dacura-logout-button' href="javascript:dacura.system.logout()">Logout</a>
	</div>
	<style>
    #dacura-widget-logout{
		width: 360px;
		margin: 60px auto 10px auto;
		color: white;
	}
	.dacura-widget-intro {
		margin-bottom: 20px;
	}
	
	div#logoutbox-status {
		border-radius: 2px; 
		display: none;
		padding-bottom: 12px;
	}
	
	</style>
<script>
dacura.system.logout = function(){
	var ajs = dacura.system.getAjaxSettings('logout');
	this.disablelogout();
	var self=this;
	ajs.beforeSend = function(){
		self.writeBusyMessage('#logoutbox-status', "Signing out...");
	};
	ajs.complete = function(){
		self.enablelogout();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
	     	if(self.mode == 'local'){
	     		//window.location.replace("<?=$dacura_settings['install_url']?>");
			}    
		})
		.fail(function (jqXHR, textStatus){
			self.writeErrorMessage('#logoutbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};


dacura.system.disablelogout = function(){
	$('#dacura-logout-button').unbind("click");
	$('#dacura-logout-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.system.enablelogout = function(){
	$('#dacura-logout-button').unbind("click");		
	$('#dacura-logout-button').click( function(e){
		 e.preventDefault();
		 dacura.system.logout();			
	});
}


</script>	
