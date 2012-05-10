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
 *
 */

// Register init
elgg_register_event_handler('init', 'system', 'members_extender_init');

// Init
function members_extender_init() {
	// Register library
	elgg_register_library('elgg:membersextender', elgg_get_plugins_path() . 'members-extender/lib/membersextender.php');

	// Extend navigation/tabs view
	elgg_extend_view('navigation/tabs', 'members-extender/navigation/tabs', 0);

	// Hook into find_active_users hook to ignore banned and optionally parents users
	elgg_register_plugin_hook_handler('find_active_users', 'system', 'members_extender_active_members_handler');

	// Re-register our own page handler
	elgg_unregister_page_handler('members');
	elgg_register_page_handler('members', 'members_extender_page_handler');
}

/**
 * Members extender page handler (replaces regular page handler)
 *
 * @param array $page url segments
 * @return bool
 */
function members_extender_page_handler($page) {
	$base = elgg_get_plugins_path() . 'members/pages/members';

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
	$limit = $params['limit'];
	$offset = $params['offset'];
	$count = $params['count'];

	$time = time() - $seconds;

	$options = array(
		'type' => 'user', 
		'limit' => $limit,
		'offset' => $offset,
		'count' => $count,
		'joins' => array("join {$CONFIG->dbprefix}users_entity u on e.guid = u.guid"),
		'wheres' => array(
			"u.last_action >= {$time}",
			"u.banned = 'no'", // Banned users shouldn't be online, but just in case
		),
		'order_by' => "u.last_action desc"
	);
	
	// Check for input to ignore parents
	if (get_input('members_no_parents')) {
		// MD info for excluding parents
		$is_parent = get_metastring_id('is_parent');
		$one_id = get_metastring_id(1);

		$options['wheres'][] = "NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}metadata md
					WHERE md.entity_guid = e.guid
					AND md.name_id = $is_parent
					AND md.value_id = $one_id)";
	}

	$result = elgg_get_entities($options);
	return $result;
}