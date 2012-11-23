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
// Tack these tabs on the to the tab array if under members/schools context
if (elgg_get_context() == 'members' || elgg_get_context() == 'schools') {
	if (elgg_get_plugin_setting('student_role', 'members-extender')) {
		$vars['tabs']['students'] = array(
			'title' => elgg_echo('members-extender:label:students'),
			'url' => "members/students",
			'selected' => $custom_selected == 'students',
		);
	}
	
	if (elgg_get_plugin_setting('teacher_role', 'members-extender')) {
		$vars['tabs']['teachers'] = array(
			'title' => elgg_echo('members-extender:label:teachers'),
			'url' => "members/teachers",
			'selected' => $custom_selected == 'teachers',
		);
	}

	if (elgg_get_plugin_setting('staff_role', 'members-extender')) {
		$vars['tabs']['staff'] = array(
			'title' => elgg_echo('members-extender:label:staff'),
			'url' => "members/staff",
			'selected' => $custom_selected == 'staff',
		);
	}
}