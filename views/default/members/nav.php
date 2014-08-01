<?php
/**
 * Members-Extender members/nav override
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 *
 */

$list = get_input('list', 'all');

$tabs = array(
	'all' => array(
		'title' => elgg_echo('members-extender:label:all'),
		'url' => "members",
		'selected' => $list == 'all',
		'class' => 'members-extender-main-nav',
		'id' => 'members-extender-main-nav-all'
	)
);

$options = array(
	'type' => 'object',
	'subtype' => 'role',
	'limit' => ELGG_ENTITIES_NO_VALUE,
	'metadata_name' => 'display_members_tab',
	'metadata_value' => 1,
);

$members_roles = elgg_get_entities_from_metadata($options);

if (count($members_roles)) {
	foreach ($members_roles as $role) {
		$role_lower = str_replace(' ', '_', strtolower($role->title));
		$tabs[$role_lower] = array(
			'title' => $role->title,
			'url' => "members/$role_lower",
			'selected' => $custom_selected == $role_lower,
			'class' => 'members-extender-main-nav',
			'id' => "members-extender-main-nav-{$role_lower}"
		);
	}
}

echo elgg_view('navigation/tabs', array('tabs' => $tabs));