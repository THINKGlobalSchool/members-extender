<?php
/**
 * Drilltrate menu
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

// Get vars
$disable_advanced = elgg_extract('disable_advanced', $vars);
$disable_extras = elgg_extract('disable_extras', $vars);
$item_class = elgg_extract('item_class', $vars, '');
$additional_main_class = elgg_extract('main_class', $vars, FALSE);

$main_class = 'drilltrate-menu-main elgg-menu elgg-menu-filter elgg-menu-filter-default';

if ($additional_main_class) {
	$main_class .= " {$additional_main_class}";
}

$content = "<div class='drilltrate-menu-container'>";

// Main section
$content .= elgg_view('navigation/menu/elements/drilltrate_section', array(
	'items' => $vars['menu']['main'],
	'class' => $main_class,
	'section' => 'main',
	'name' => 'dashboard',
	'item_class' => $item_class
));

// Extras section
if (count($vars['menu']['extras']) && !$disable_extras) {
	$content .= elgg_view('navigation/menu/elements/drilltrate_section', array(
		'items' => $vars['menu']['extras'],
		'class' => "drilltrate-menu-extras",
		'section' => 'extras',
		'name' => 'dashboard',
		'item_class' => $item_class
	));	
}

echo $content . "</div>";