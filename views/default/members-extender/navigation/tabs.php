<?php
/**
 * Members-Extender navigation/tabs extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 *
 */

$custom_selected = get_input('members_custom_tab_selected');
// Tack these tabs on the to the tab array if under members context
if (elgg_get_context() == 'members') {

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
			$vars['tabs'][$role_lower] = array(
				'title' => $role->title,
				'url' => "members/$role_lower",
				'selected' => $custom_selected == $role_lower,
			);
		}
	}
}