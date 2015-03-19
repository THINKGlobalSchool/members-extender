<?php
/**
 * Members list drillpoint endpoint
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */
elgg_load_library('elgg:membersextender');

// Set some view inputs
set_input('list_type', 'gallery');
set_input('user_gallery_size', get_input('user_gallery_size', 'medium'));

// Get role filter inputs
$type = get_input('type', FALSE);
$sub = get_input('sub', FALSE);

// Start building options
$options = array(
	'type' => 'user', 
	'full_view' => FALSE,
	'limit' => 36,
	'gallery_class' => 'elgg-gallery-users',
	'class' => 'members-gallery-avatar'
);

$dbprefix = elgg_get_config('dbprefix');

// If we have categories (main and sub categories)
if ($type || $sub) {
	// Check for type role
	if ($type && elgg_instanceof(get_entity($type), 'object', 'role')) {
		// Role options
		$options['relationship'] = ROLE_RELATIONSHIP;
		$options['relationship_guid'] = $type;
		$options['inverse_relationship'] = TRUE;
	}

	// Check for sub role
	if ($sub && elgg_instanceof(get_entity($sub), 'object', 'role')) {
		// Need to add additional relationship manually :(
		$options['wheres'][] = "(r2.relationship = 'member_of_role' AND r2.guid_two = {$sub})";
		$options['joins'][] = "JOIN {$dbprefix}entity_relationships r2 on r2.guid_one = e.guid";		
	}
}

// This will remove banned users
$options['wheres'][] = "ue.banned = 'no'";
$options['joins'][] = "JOIN {$dbprefix}users_entity ue on ue.guid = e.guid";

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

elgg_push_context('members');
elgg_push_context('members_custom_avatar');

$content = elgg_list_entities_from_relationship($options);

if (!$content) {
	$content = "<div style='width: 100%; text-align: center; margin: 10px;'><strong>No results</strong></div>";
}

echo $content;