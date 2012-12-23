<?php
/**
 * This partial expects two array variables:
 * 
 * @var array $bars Zero-indexed array containing percentages to show on chart
 * @var array $labels Zero-indexed array of associative arrays
 * 
 * Each label item is in this array format:
 * 
 * array(
 *		'name'			=> String name of element
 *		'value'			=> Numeric count for this element
 *		'show_blob'		=> Optional, legend blob is hidden if this is false
 * )
 */
?>

<div class="bar-chart-block">
	<?php // Here's the chart bars ?>
	<div class="bar-chart-container">
		<?php foreach ($bars as $i => $barValue): ?>
			<div class="bar-chart-bar-item bar-<?php echo $i + 1 ?>"
				 style="width: <?php echo $barValue . '%' ?>;"
			>
				<span class="bar-chart-bar-item-text"></span>
			</div>
		<?php endforeach ?>
		<div class="clear"></div>
	</div>

	<?php // Here's the chart legend ?>
	<div class="bar-chart-legend-container">
		<?php foreach ($labels as $i => $label): ?>
			<span class="bar-chart-legend-item">
				<?php if (!isset($label['show_blob']) || $label['show_blob']): ?>
					<div class="bar-chart-legend-blob bar-<?php echo $i + 1 ?>"></div>
				<?php endif ?>
				<?php echo $label['name'] ?>:
				<?php echo $label['value'] ?>
			</span>
		<?php endforeach ?>
		<div class="clear"></div>
	</div>
</div>