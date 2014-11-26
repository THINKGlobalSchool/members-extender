<?php
/**
 * Members-Extender Helper Library
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

/**
 * Get custom member listing content
 */
function members_extender_get_custom_member_listing($page) {
	$dashboard = elgg_view('drilltrate/dashboard', array(
		'menu_name' => 'members_custom',
		'infinite_scroll' => false,
		'main_class' => '',
		'default_params' => array(
			'type' => 'all',
		),
		'list_url' => elgg_get_site_url() . 'ajax/view/members-extender/list',
		'id' => 'members-custom-menu'
	));

	$params = array(
		'content' => $dashboard,
		'title' => $title,
		'filter' => ' '
	);

	$title = elgg_echo('members');
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
function members_extender_get_number_users($show_deactivated = FALSE) {
	global $CONFIG;

	$access = "";

	if (!$show_deactivated) {
		$access = "and " . _elgg_get_access_where_sql();
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

	return FALSE;
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