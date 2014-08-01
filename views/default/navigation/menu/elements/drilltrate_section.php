<?php
/**
 * Drilltrate menu section
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 * @uses $vars['items']      Array of menu items
 * @uses $vars['section']    The section name
 * @uses $vars['item_class'] Additional CSS class for each menu item
 * @uses $vars['class']      Additional CSS class for the section
 * @uses $vars['parent_id']  Parent ID
 */

$item_class = elgg_extract('item_class', $vars, '');
$class = elgg_extract('class', $vars, '');
$child_parent_id = elgg_extract('parent_id', $vars, false);

if ($child_parent_id) {
	$child_parent_id = "data-parent-id='{$child_parent_id}'";
}

echo "<ul class='{$class}' id='{$parent_id}' {$child_parent_id}>";
foreach ($vars['items'] as $menu_item) {
	echo elgg_view('navigation/menu/elements/drilltrate_item', array(
		'item' => $menu_item,
		'item_class' => $item_class,
	));
}
echo '</ul>';
