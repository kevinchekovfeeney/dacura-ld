<?php 
$cls = isset($params['css_class']) ? $params['css_class'] : "";
$clk = isset($params['close-link']) ? $params['close-link'] : "";
$tit = isset($params['title']) ? $params['title'] : "";
$clm = isset($params['close-msg']) ? $params['close-msg'] : "";
$sub = isset($params['subtitle']) ? $params['subtitle'] : "";
$des = isset($params['description']) ? $params['description'] : "";
$inm = isset($params['init-msg']) ? $params['init-msg'] : "";
?>


<div class="tool-header <?=$cls?>">
	<div class='tool-close dch'>
		<a href='<?=$clk?>' title='Close <?=$tit?> - <?=$clk?>'>
		<?=$clm?></a>
	</div>
	<span class='tool-image'>
		<?php if(isset($params['image']) && $params['image']){?>
		<img class='tool-header-image' src="<?=$params['image']?>" title="<?=$tit?>" />
		<?php }?>
	</span>
	<span class='tool-title'><?=$tit?></span>
	<span class='tool-subtitle'><?=$sub?></span>
	<span class='tool-description'><?=$des?></span>
</div>
<?php if(isset($params['breadcrumbs'])){?>
	<div class="pcbreadcrumbs"><?=$params['breadcrumbs']?></div>
<?php }?>
<div class='tool-contents'>
	<div class='tool-info'><?=$params['init-msg']?></div>
		<div class='tool-body'>
