<?php
/**
 * Members-Extender google drive activity detail
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.com/
 * 
 */

$user_guid = get_input('user_guid');

$user = get_entity($user_guid);

if (!elgg_instanceof($user, 'user')) {
	return FALSE;
}

$today = time();
$last_week = strtotime("-7 days", $today);

$activity = members_extender_get_user_drive_activity($user, $last_week, $today, FALSE, 15);

if (count($activity)) {
	$activity_content .= '<ul>';

	foreach ($activity as $a) {
		$event_info = members_extender_get_events_info($a);
		$activity_content .= elgg_view('members-extender/drive/item', $event_info);
	}

	$activity_content .= '</ul>';

} else {
	$activity_content = elgg_echo('members-extender:label:noresults');
}

$module = elgg_view_module('aside', elgg_echo('members-extender:label:userdriveactivity', array($user->name)), $activity_content);

echo "<div class='members-extender-engagement-drive-detail-container'>{$module}</div>";