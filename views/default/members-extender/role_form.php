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

echo "<div><label>$display_members_label</label>&nbsp;$display_members_input</div><br />";