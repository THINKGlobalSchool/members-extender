<?php
/**
 * Members-Extender Helper Library
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 * 
 */

/** Get page content for custom role listings **/
function members_extender_get_custom_member_listing($page) {
	$num_members = members_extender_get_number_users();

	$title = elgg_echo('members');

	$options = array('type' => 'user', 'full_view' => false);
	
	// Set seperate 'selected' data for custom tabs due to a chicken and egg issue
	set_input('members_custom_tab_selected', $page);

	$dbprefix = elgg_get_config('dbprefix');

	// This will remove banned users
	$options['wheres'][] = "ue.banned = 'no'";
	$options['joins'] = "JOIN {$dbprefix}users_entity ue on ue.guid = e.guid";
	
	// This will exclude parents
	$is_parent = get_metastring_id('is_parent');
	$one_id = get_metastring_id(1);
	$options['wheres'][] = "NOT EXISTS (
								SELECT 1 FROM {$dbprefix}metadata md
								WHERE md.entity_guid = e.guid
								AND md.name_id = $is_parent
								AND md.value_id = $one_id)";

	switch ($page) {
		case 'students':
			$role = elgg_get_plugin_setting('student_role', 'members-extender');
			$options['relationship'] = ROLE_RELATIONSHIP;
			$options['relationship_guid'] = $role;
			$options['inverse_relationship'] = TRUE;
			$content = elgg_list_entities_from_relationship($options);
			break;
		case 'teachers':
			$role = elgg_get_plugin_setting('teacher_role', 'members-extender');
			$options['relationship'] = ROLE_RELATIONSHIP;
			$options['relationship_guid'] = $role;
			$options['inverse_relationship'] = TRUE;
			$content = elgg_list_entities_from_relationship($options);
			break;
		case 'staff':
			$role = elgg_get_plugin_setting('staff_role', 'members-extender');
			$options['relationship'] = ROLE_RELATIONSHIP;
			$options['relationship_guid'] = $role;
			$options['inverse_relationship'] = TRUE;
			$content = elgg_list_entities_from_relationship($options);
			break;
		case 'popular':
			$options['relationship'] = 'friend';
			$options['inverse_relationship'] = FALSE;
			$content = elgg_list_entities_from_relationship_count($options);
			break;
		case 'online':
			set_input('members_no_parents', 1);
			$count = find_active_users(600, 10, $offset, true);
			$objects = find_active_users(600, 10, $offset);

			if ($objects) {
				$content = elgg_view_entity_list($objects, array(
					'count' => $count,
					'limit' => 10
				));
			}
			break;
		case 'newest':
		default:
			$content = elgg_list_entities($options);
			break;
	}
	
	if (!$content) {
		$content = "<div style='width: 100%; text-align: center; margin: 10px;'><strong>No results</strong></div>";
	}

	$params = array(
		'content' => $content,
		'sidebar' => elgg_view('members/sidebar'),
		'title' => $title . " ($num_members)",
		'filter_override' => elgg_view('members/nav', array('selected' => $page)),
	);

	$body = elgg_view_layout('content', $params);

	echo elgg_view_page($title, $body);
}

/**
 * Return the number of users registered in the system (except for parents, and banned)
 *
 * @param bool $show_deactivated Count not enabled users?
 *
 * @return int
 */
function members_extender_get_number_users($show_deactivated = false) {
	global $CONFIG;

	$access = "";

	if (!$show_deactivated) {
		$access = "and " . get_access_sql_suffix();
	}

	// MD info for excluding parents
	$is_parent = get_metastring_id('is_parent');
	$one_id = get_metastring_id(1);

	$query = "SELECT count(*) as count from {$CONFIG->dbprefix}entities e
			  JOIN {$CONFIG->dbprefix}users_entity ue on ue.guid = e.guid
			  WHERE type='user' $access 
			  AND ue.banned = 'no'
			  AND NOT EXISTS (
						SELECT 1 FROM {$CONFIG->dbprefix}metadata md
						WHERE md.entity_guid = e.guid
						AND md.name_id = $is_parent
						AND md.value_id = $one_id)";

	$result = get_data_row($query);

	if ($result) {
		return $result->count;
	}

	return false;
}