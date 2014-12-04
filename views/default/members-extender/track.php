<?php
/**
 * Members-Extender generic tracking view
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

// Grab entity
$entity = $vars['entity'];

// Check entity
if ($entity) {
	// Make sure we only trigger the group view once per page
	if (!get_input('trigger-generic-view-stat')) {
		set_input('trigger-generic-view-stat', TRUE);
		members_extender_add_user_view(elgg_get_logged_in_user_entity(), $entity);
	}
}