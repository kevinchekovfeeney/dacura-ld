<?php 
/**
 * Access denied page
 * 
 * Presents the user with an access denied message
 * @package core/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<?php include_once(path_to_snippet("header"));?>
<div class='fullpage-message-container'>
	<div class='fullpage-message'>
		<div class='dacura-user-message-box dacura-error'>
			<div class='mtitle'><?=$params['title']?></div>
			<div class='mbody'><?=$params['message']?></div>
		</div>
	</div>
</div>
<?php include_once(path_to_snippet("footer"));?>
