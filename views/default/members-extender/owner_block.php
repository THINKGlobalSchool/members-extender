<?php
/**
 * Members-Extender Owner Block view extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

// Grab owner
$owner = elgg_get_page_owner_entity();

// Check for a group
if (elgg_instanceof($owner, 'group')) {
	// Make sure we only trigger the group view once per page
	if (!get_input('trigger-group-view-stat')) {
		set_input('trigger-group-view-stat', TRUE);
		members_extender_add_user_view(elgg_get_logged_in_user_entity(), $owner);
	}
}