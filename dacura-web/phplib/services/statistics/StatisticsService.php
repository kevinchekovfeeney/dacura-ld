<?php
include_once("StatisticsDacuraServer.php");

class StatisticsService extends DacuraService {
	
	//cid/did/users/userid
	
	function handlePageLoad(){
		$this->renderScreen("stats", array());
	}
	
	function getBreadCrumbsHTML($id){
		$paths = $this->get_service_breadcrumbs();
		$html = "<ul class='service-breadcrumbs'>";
		foreach($paths as $i => $path){
			$n = (count($path) - $i) + 1;
			if($i == 0){
				$html .= "<li class='first'><a href='".$path['url']."' style='z-index:$n;'><span></span>".$path['title']."</a></li>";
			}
			elseif(!$id && $i+1 == count($path)){
				$html .= "<li><a href='#' style='z-index:$n;'>".$path['title']."</a></li>";
			}
			else {
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
		}
		if($id){
			$html .= "<li><a href='#' style='z-index:0;'>User ".$id."</a></li>";
		}
		$html .= "</ul>";
		return $html;
	}
	
}