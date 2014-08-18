<?php
global $dcuser;
if($dcuser){
	echo "<P>aaa";
   $apman->getApplicationHTML("login", $path, "full");
}
else {
	echo "<P>hello world</p>";	
}