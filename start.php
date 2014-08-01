<?php
/**
 * Members-Extender start.php
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 * OVERRIDES:
 * - default/user/default (Changes to the user entity view)
 * - members/nav          (Replace the members/nav menu)
 */

// Register init
elgg_register_event_handler('init', 'system', 'members_extender_init');

// Init
function members_extender_init() {

	define('MEMBERS_SUB_CATEGORY_RELATIONSHIP', 'is_member_subcategory_for');

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

	// Register drilltrate js library
	$f_js = elgg_get_simplecache_url('js', 'drilltrate/drilltrate');
	elgg_register_simplecache_view('js/drilltrate/drilltrate');
	elgg_register_js('elgg.drilltrate', $f_js);

	// Register drilltrate js library
	$f_css = elgg_get_simplecache_url('css', 'drilltrate/drilltrate');
	elgg_register_simplecache_view('css/drilltrate/drilltrate');
	elgg_register_css('elgg.drilltrate', $f_css);
	elgg_load_css('elgg.drilltrate');

	// Register drilltrate utilities js library
	$f_js = elgg_get_simplecache_url('js', 'drilltrate/utilities');
	elgg_register_simplecache_view('js/drilltrate/utilities');
	elgg_register_js('elgg.drilltrate.utilities', $f_js);

	// Extend navigation/tabs view
	//elgg_extend_view('navigation/tabs', 'members-extender/navigation/tabs', 0);

	// Prepend gallery view to allow injecting gallery class via set_input
	elgg_extend_view('page/components/gallery', 'members-extender/gallery_prepend', 0);

	// Extend user icon view
	elgg_extend_view('icon/user/default', 'members-extender/icon_extend');

	// Prepend user icon view
	elgg_extend_view('icon/user/default', 'members-extender/icon_prepend', 0);

	// Extend roles form
	elgg_extend_view('forms/roles/edit/extend', 'members-extender/role_form');

	// Hook into find_active_users hook to ignore banned and optionally parent users
	elgg_register_plugin_hook_handler('find_active_users', 'system', 'members_extender_active_members_handler');

	// Extend groups page handler
	elgg_register_plugin_hook_handler('route', 'groups', 'members_extender_route_groups_handler', 50);

	// Hook into create/update events to save roles for an entity
	elgg_register_event_handler('update', 'object', 'members_extender_roles_save_members_tab');
	elgg_register_event_handler('create', 'object', 'members_extender_roles_save_members_tab');

	// Re-register our own page handler
	elgg_unregister_page_handler('members');
	elgg_register_page_handler('members', 'members_extender_page_handler');
	
	// If not logged in, don't allow user searches
	if (!elgg_is_logged_in()) {
		elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
	}

	// Register plugin hook to prevent friend river entries
	elgg_register_plugin_hook_handler('creating', 'river', 'friend_river_interrupt_handler');

	// Set up members menu
	elgg_register_plugin_hook_handler('register', 'menu:members_custom', 'members_custom_menu_setup');

	// Whitelist ajax views
	elgg_register_ajax_view('members-extender/list');
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
	set_input('user_gallery_size', get_input('user_gallery_size', 'medium'));
	set_input('limit', 28);

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
 * Hook into group routing to provide extra content
 */
function members_extender_route_groups_handler($hook, $type, $return, $params) {
	if (is_array($return['segments']) && $return['segments'][0] == 'members') {
		// Use gallery view 
		set_input('list_type', 'gallery');
		set_input('user_gallery_size', get_input('user_gallery_size', 'medium'));
		set_input('limit', 28);
		elgg_push_context('members_custom_avatar');
	}
	return $return;
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

/**
 * Set up members custom menu
 */
function members_custom_menu_setup($hook, $type, $return, $params) {

	$options = array(
		'name' => 'members-all',
		'href' => '#',
		'text' => elgg_echo('members-extender:label:all'),
		'encode_text' => FALSE,
		'data-param' => 'type',
		'data-value' => 'all',
		'section' => 'main',
		'priority' => 1
	);

	$return[] = ElggMenuItem::factory($options);

	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'limit' => ELGG_ENTITIES_NO_VALUE,
		'metadata_name' => 'display_members_tab',
		'metadata_value' => 1,
	);

	$members_roles = elgg_get_entities_from_metadata($options);

	if (count($members_roles)) {
		$priority = 100;
		foreach ($members_roles as $role) {
			$role_lower = str_replace(' ', '_', strtolower($role->title));

			$options = array(
				'name' => "members-{$role_lower}",
				'href' => '#',
				'text' => $role->title,
				'encode_text' => FALSE,
				'data-param' => 'type',
				'data-value' => $role->guid,
				'section' => 'main',
				'priority' => $priority
			);

			$role_item = ElggMenuItem::factory($options);

			// Get sub category roles (if any)
			$members_sub_categories = elgg_get_entities_from_relationship(array(
				'relationship' => MEMBERS_SUB_CATEGORY_RELATIONSHIP,
				'relationship_guid' => $role->guid,
				'inverse_relationship' => TRUE,
				'limit' => 0
			));

			$sub_priority = 100;
			foreach ($members_sub_categories as $sub_role) {
				$sub_role_lower = str_replace(' ', '_', strtolower($sub_role->title));
				$options = array(
					'name' => "members-{$sub_role_lower}",
					'href' => '#',
					'text' => $sub_role->title,
					'encode_text' => FALSE,
					'data-param' => 'sub',
					'data-value' => $sub_role->guid,
					'section' => 'children',
					'priority' => $sub_priority
				);

				$sub_role_item = ElggMenuItem::factory($options);

				$sub_role_item->setParent($role_item);
				$role_item->addChild($sub_role_item);

				$sub_priority += 100;
			}

			$return[] = $role_item;
			$priority += 100;
		}
	}

	return $return;
}

/**
 * Add display as members tab metadata to roles
 */
function members_extender_roles_save_members_tab($event, $object_type, $object) {
	if (elgg_instanceof($object, 'object', 'role')) {
		$display_members_tab = get_input('display_members_tab', 0);
		$object->display_members_tab = $display_members_tab;

		if ($categories = get_input('member_subcategories')) {
			// Remove all existing relationships first
			remove_entity_relationships($object->guid, MEMBERS_SUB_CATEGORY_RELATIONSHIP, TRUE);

			foreach ($categories as $category) {
				add_entity_relationship($category, MEMBERS_SUB_CATEGORY_RELATIONSHIP, $object->guid);
			}
		}
	}
	return TRUE;
}