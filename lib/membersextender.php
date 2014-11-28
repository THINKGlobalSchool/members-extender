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

/**
 * Get member post activity (objects created) between given time period
 *
 * @param ElggUser $user
 * @param int      $start Default: none
 * @param int      $end   Default: none
 * @return
 */
function members_extender_get_user_post_activity($user, $container = FALSE, $start = 0, $end = 0) {
	// Sanity
	if (!elgg_instanceof($user, 'user') || !is_int($start) || !is_int($end)) {
		return FALSE;
	}

	// Exclude these subtypes by default
	$exclude_subtypes = array(
		get_subtype_id('object', 'messages'),
		get_subtype_id('object', 'connected_blog_activity'),
		get_subtype_id('object', 'tidypics_batch')
	);

	// Trigger a hook to modify exclusions
	$hook_params = array(
		'user' => $user,
		'start' => $start,
		'end' => $end
	);

	elgg_trigger_plugin_hook('analytics:exclude:subtypes', 'user', $hook_params, $exclude_subtypes);

	if (count($exclude_subtypes)) {
		$exclude_subtypes = implode(',', $exclude_subtypes);
		$exclude_subtypes_sql = "AND e.subtype NOT IN ({$exclude_subtypes})";
	}

	$dbprefix = elgg_get_config('dbprefix');

	// Start date sql if supplied
	if ($start) {
		$start_sql = "AND e.time_created > {$start}";
	}

	// End date sql if supplied
	if ($end) {
		$end_sql = "AND e.time_created < {$end}";
	} else {
		$end = time();
	}

	// Handler container-container objects (photos, pages)
	$container_contained = array(
		get_subtype_id('object', 'image'),
		get_subtype_id('object', 'page')
	);

	// Let plugins add to this list
	elgg_trigger_plugin_hook('analytics:container:contained', 'user', NULL, $container_contained);

	// See if we supplied a container guid (groups)
	$container = (int)$container;
	if ($container) {
		// Need to check container contained entities, so throw in a join
		$container_join = "JOIN {$dbprefix}entities ce on ce.guid = e.container_guid";
		$container_sql = "AND (e.container_guid = {$container} OR ce.container_guid = {$container})";
	}

	// Direct SQL
	$query = "SELECT count(e.guid) as post_count,
	          FROM_UNIXTIME(e.time_created, '%Y-%m-%d') as post_day
	          FROM {$dbprefix}entities e
			  $container_join
	          WHERE e.owner_guid = {$user->guid} 
	          AND e.type = 'object'
	          $start_sql 
	          $end_sql 
	          $exclude_subtypes_sql
	          $container_sql
	          GROUP BY post_day";

	// Get data
	$result = get_data($query, 'members_extender_activity_row_to_array');


	// Build results array with dates/posts
	$num_days = abs($start - $end)/60/60/24; // Determine number of days between timestat

	// Build date array
	$date_array = array();
	for ($i = 1; $i <= $num_days; $i++) {
		$dom = date('Y-m-d', strtotime("+{$i} day", $start)); // Day of month

		// $dom_post_count = 
		$post_count_by_dom = search_array_value_key($result, $dom);
		$date_array[$dom] = $post_count_by_dom ? $post_count_by_dom : 0;
	}

	return $date_array;
}

/**
 * Callback for get_data to convert activity rows to an array
 */
function members_extender_activity_row_to_array($row) {
	return array($row->post_day => $row->post_count);
}

/**
 * Handy multidimensional array key search function
 * From: http://snipplr.com/view/55684/
 *
 * @param array $array 
 * @param mixed $search
 * @return mixed
 */
function search_array_value_key(array $array, $search) {
	foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $key => $value) {
	    if ($search === $key)
		return $value;
	}
	return false;
}