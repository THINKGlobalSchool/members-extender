<?php
/**
 * Members-Extender start.php
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 * 
 * OVERRIDES:
 * - default/user/default (Changes to the user entity view)
 */

// Register init
elgg_register_event_handler('init', 'system', 'members_extender_init');

// Init
function members_extender_init() {

	// Register library
	elgg_register_library('elgg:membersextender', elgg_get_plugins_path() . 'members-extender/lib/membersextender.php');

	// Register members CSS
	$m_css = elgg_get_simplecache_url('css', 'membersextender/css');
	elgg_register_simplecache_view('css/membersextender/css');
	elgg_register_css('elgg.membersextender', $m_css);
	elgg_load_css('elgg.membersextender');

	// Register members JS
	$m_js = elgg_get_simplecache_url('js', 'membersextender/membersextender');
	elgg_register_simplecache_view('js/membersextender/membersextender');
	elgg_register_js('elgg.membersextender', $m_js);
	elgg_load_js('elgg.membersextender');

	// Extend navigation/tabs view
	elgg_extend_view('navigation/tabs', 'members-extender/navigation/tabs', 0);

	// Prepend gallery view to allow injecting gallery class via set_input
	elgg_extend_view('page/components/gallery', 'members-extender/gallery_prepend', 0);

	// Extend user icon view
	elgg_extend_view('icon/user/default', 'members-extender/icon_extend');

	// Prepend user icon view
	elgg_extend_view('icon/user/default', 'members-extender/icon_prepend', 0);

	// Hook into find_active_users hook to ignore banned and optionally parents users
	elgg_register_plugin_hook_handler('find_active_users', 'system', 'members_extender_active_members_handler');

	// Re-register our own page handler
	elgg_unregister_page_handler('members');
	elgg_register_page_handler('members', 'members_extender_page_handler');
	
	// If not logged in, don't allow user searches
	if (!elgg_is_logged_in()) {
		elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
	}

	// Register plugin hook to prevent friend river entries
	elgg_register_plugin_hook_handler('creating', 'river', 'friend_river_interrupt_handler');
}

/**
 * Members extender page handler (replaces regular page handler)
 *
 * @param array $page url segments
 * @return bool
 */
function members_extender_page_handler($page) {
	$base = elgg_get_plugins_path() . 'members/pages/members';

	// Use gallery view 
	set_input('list_type', 'gallery');
	set_input('user_gallery_size', 'medium');
	set_input('limit', 14);

	if (!isset($page[0])) {
		$page[0] = 'newest';
	}

	$vars = array();
	$vars['page'] = $page[0];

	switch ($page[0]) {
		case 'search':
			$vars['search_type'] = $page[1];
			require_once "$base/search.php";
			break;
		default:
			elgg_load_library('elgg:membersextender'); 
			members_extender_get_custom_member_listing($page[0]);
			break;
	}

	return true;
}

/**
 * Hook into find_active_users and ignore banned and optionally, parent users
 */
function members_extender_active_members_handler($hook, $type, $result, $params) {
	global $CONFIG;

	// Get params
	$seconds = $params['seconds'];
	$limit = get_input('limit', $params['limit']);
	$offset = get_input('offset', $params['offset']);
	$count = $params['count'];

	$time = time() - $seconds;

	$options = array(
		'type' => 'user', 
		'limit' => $limit,
		'offset' => $offset,
		'count' => $count,
		'joins' => array("join {$CONFIG->dbprefix}users_entity u on e.guid = u.guid"),
		'order_by' => "u.last_action desc"
	);
	
	// Check for input to customize results
	if (get_input('members_custom')) {
		// Relationship info for excluding hidden members
		$hidden_role = elgg_get_plugin_setting('hidden_role', 'members-extender');
		$role_relationship = ROLE_RELATIONSHIP;
		
		if ($hidden_role) {
			$options['wheres'][] = members_extender_get_exclude_parent_sql();

			$options['wheres'][] = "NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}entity_relationships r_hidden 
					WHERE r_hidden.guid_one = e.guid
					AND r_hidden.relationship = '{$role_relationship}'
					AND r_hidden.guid_two = {$hidden_role})";
		}
	}
	
	$options['wheres'][] = "(u.last_action >= {$time})";

	$result = elgg_get_entities($options);
	
	if (!$result) {
		$result = 1;
	}

	return $result;
}

/**
 * Hook handler to prevent friend river entries from being created
 */
function friend_river_interrupt_handler($hook, $type, $result, $params) {	
	if ($result['view'] == 'river/relationship/friend/create') {
		return FALSE; // Nope, sorry.
	} else {
		return $result;
	}
}