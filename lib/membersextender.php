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
	
	// Exclude parents (if enabled)
	$options['wheres'][] = members_extender_get_exclude_parent_sql();

	// This will exclude those in the hidden role
	$hidden_role = elgg_get_plugin_setting('hidden_role', 'members-extender');
	$role_relationship = ROLE_RELATIONSHIP;

	if ($hidden_role) {
		$options['wheres'][] = "NOT EXISTS (
				SELECT 1 FROM {$dbprefix}entity_relationships r_hidden 
				WHERE r_hidden.guid_one = e.guid
				AND r_hidden.relationship = '{$role_relationship}'
				AND r_hidden.guid_two = {$hidden_role})";
	}
			
	$options['order_by'] = 'ue.name ASC';

	switch ($page) {
		case 'popular':
			$options['relationship'] = 'friend';
			$options['inverse_relationship'] = FALSE;
			$content = elgg_list_entities_from_relationship_count($options);
			break;
		case 'online':
			set_input('members_custom', 1);
			$count = find_active_users(600, 10, $offset, true);
			$objects = find_active_users(600, 10, $offset);

			if ($objects) {
				$content = elgg_view_entity_list($objects, array(
					'count' => $count,
					'limit' => 10, 
					'offset' => get_input('offset', 0),
				)); 
			}
			break;
		case 'newest':
			$options['order_by'] = 'e.time_created DESC';
			$content = elgg_list_entities($options);
			break;
		default:
			// Show newest members if page is blank
			if (!$page) {
				forward('members/newest');
			}

			// Get role name, replace _ with ' '
			$role_name = str_replace('_', ' ', strtolower($page));
			$role = get_role_by_title($role_name);

			// Role options
			$options['relationship'] = ROLE_RELATIONSHIP;
			$options['relationship_guid'] = $role->guid;
			$options['inverse_relationship'] = TRUE;

			// Display role members
			$content = elgg_list_entities_from_relationship($options);
			break;
	}
	
	if (!$content) {
		$content = "<div style='width: 100%; text-align: center; margin: 10px;'><strong>No results</strong></div>";
	}

	$params = array(
		'content' => $content,
	//	'sidebar' => elgg_view('members/sidebar'),
		'title' => $title . " ($num_members)",
		'filter_override' => elgg_view('members/nav', array('selected' => $page)),
	);

	$body = elgg_view_layout('content_one_column', $params);

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

	// Relationship info for excluding hidden members
	$hidden_role = elgg_get_plugin_setting('hidden_role', 'members-extender');
	$role_relationship = ROLE_RELATIONSHIP;
	
	if (members_extender_get_exclude_parent_sql()) {
		$parent_sql = "AND " . members_extender_get_exclude_parent_sql();
	}

	$query = "SELECT count(*) as count from {$CONFIG->dbprefix}entities e
			  JOIN {$CONFIG->dbprefix}users_entity ue on ue.guid = e.guid
			  WHERE type='user' $access 
			  AND ue.banned = 'no'" . $parent_sql;
						
	if ($hidden_role) {
		$query .= "AND NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}entity_relationships r_hidden 
					WHERE r_hidden.guid_one = e.guid
					AND r_hidden.relationship = '{$role_relationship}'
					AND r_hidden.guid_two = {$hidden_role})";
	}

	$result = get_data_row($query);

	if ($result) {
		return $result->count;
	}

	return false;
}

/**
 * Get SQL to exclude parent (parentportal plugin) from members listings
 */
function members_extender_get_exclude_parent_sql() {
	if (elgg_is_active_plugin('parentportal')) {
		global $CONFIG;

		// MD info for excluding parents
		$is_parent = get_metastring_id('is_parent');
		$one_id = get_metastring_id(1);
		
		$parent_sql = "
		  NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}metadata md
					WHERE md.entity_guid = e.guid
					AND md.name_id = $is_parent
					AND md.value_id = $one_id)";
					
		return $parent_sql;
	} else {
		return '';
	}
}