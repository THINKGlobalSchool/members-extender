<?php
/**
 * Drilltrate menu section
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 * 
 * @uses $vars['item']       ElggMenuItem
 * @uses $vars['item_class'] Additional CSS class for the menu item
 */

$item = $vars['item'];

$item_class = $item->getItemClass();
if ($item->getSelected()) {
	$item_class = "$item_class elgg-state-selected";
}
if (isset($vars['item_class']) && $vars['item_class']) {
	$item_class .= ' ' . $vars['item_class'];
}

$children = $item->getChildren();

if ($children) {
	$item->addLinkClass($link_class);
	$item->addLinkClass('elgg-menu-parent');
	$parent_id = "drilltrate-parent-" . uniqid();

	$children = elgg_view('navigation/menu/elements/drilltrate_section', array(
		'items' => $children,
		'class' => 'elgg-menu elgg-child-menu',
		'parent_id' => $parent_id
	));
}

echo "<li class=\"$item_class\" id='{$parent_id}' >";
echo $item->getContent();
if ($children) {
	echo $children;
}
echo '</li>';
