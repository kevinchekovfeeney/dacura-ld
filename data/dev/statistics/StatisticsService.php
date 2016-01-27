<?php
include_once("StatisticsDacuraServer.php");

class StatisticsService extends DacuraService {
	
	function handlePageLoad(){
		if(count($this->servicecall->args) > 0) {
			$firstarg = array_shift($this->servicecall->args);
			$secondarg = array_shift($this->servicecall->args);
			$thirdarg = array_shift($this->servicecall->args);
			if ($secondarg == null && $thirdarg == null) { // perform user call
				$this->renderScreen("stats", array("userid" => $firstarg));
			}
			else if ($thirdarg == null) { // perform general dated
				$this->renderScreen("stats", array("startdate" => gmdate("d.m.Y H:i", $firstarg), "enddate" => gmdate("d.m.Y H:i", $secondarg)));
			}
			else if ($secondarg == 'session')	{ // perform session log
				$this->renderScreen("stats", array("userid" => $firstarg, "sessionid" => $thirdarg));
			}
			else { // perform user dated
				$this->renderScreen("stats", array("userid" => $firstarg, "startdate" => gmdate("d.m.Y H:i", $secondarg), "enddate" => gmdate("d.m.Y H:i", $thirdarg)));
			}
		}
		else { // perform general undated
			$this->renderScreen("stats", array());
		}
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