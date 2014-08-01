<?php
/**
 * Members-Extender role form extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2013
 * @link http://www.thinkglobalschool.com/
 */

$role_guid = elgg_extract('guid', $vars);

$role = get_entity($role_guid);

$display_members_tab = $role->display_members_tab;

if (!$display_members_tab) {
	$display_members_tab = 0;
} else {
	$class = 'members-show-subcategories';
}

$display_members_label = elgg_echo('members-extender:label:displayonmembers');
$display_members_input = elgg_view('input/dropdown', array(
	'name' => 'display_members_tab', 
	'value' => $display_members_tab,
	'options_values' => array(
		1 => elgg_echo('roles:label:yes'),
		0 => elgg_echo('roles:label:no'),
	)
));

// Get sub category roles (if any)
$sub_categories = elgg_get_entities_from_relationship(array(
	'relationship' => MEMBERS_SUB_CATEGORY_RELATIONSHIP,
	'relationship_guid' => $role_guid,
	'inverse_relationship' => TRUE,
	'limit' => 0
));

if (!empty($sub_categories)) {
	$sub_categories_value = array();
	foreach ($sub_categories as $sub_category) {
		$sub_categories_value[] = $sub_category->guid;
	}
}

$sub_categories_label = elgg_echo('members-extender:label:subcategories');
$sub_categories_input = elgg_view('input/roles', array(
	'label' => $sub_categories_label,
	'name' => 'member_subcategories',
	'value' => $sub_categories_value
));

$content = <<<HTML
	<div>
		<label>$display_members_label</label>&nbsp;$display_members_input
	</div><br />
	<div class='members-sub-categories-container $class'>
		$sub_categories_input
		<br /><br />
	</div>
HTML;

echo $content;