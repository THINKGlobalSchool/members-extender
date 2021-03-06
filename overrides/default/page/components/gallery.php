<?php
/**
 * Members-Extender gallery view override
 *
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.com/
 * 
 */

$page_owner = elgg_get_page_owner_entity();

if (elgg_instanceof($page_owner, 'group')) {
	if (!$page_owner->canEdit()) {
		return;
	}
} else if (!members_extender_engagement_gatekeeper()) {
	return;
}

$items = $vars['items'];
$offset = elgg_extract('offset', $vars);
$limit = elgg_extract('limit', $vars);
$count = elgg_extract('count', $vars);
$base_url = elgg_extract('base_url', $vars, '');
$pagination = elgg_extract('pagination', $vars, true);
$offset_key = elgg_extract('offset_key', $vars, 'offset');
$position = elgg_extract('position', $vars, 'after');

$html = "";
$nav = "";

if ($pagination && $count) {
	$nav .= elgg_view('navigation/pagination', array(
		'base_url' => $base_url,
		'offset' => $offset,
		'count' => $count,
		'limit' => $limit,
		'offset_key' => $offset_key,
	));
}

if (is_array($items) && count($items) > 0) {
	$user_h = elgg_echo('members-extender:stats:user');
	$status_h = elgg_echo('members-extender:stats:status');
	$group_h = elgg_echo('members-extender:stats:groupaccess');
	$activity_h = elgg_echo('members-extender:stats:lastactivity');
	$post_h = elgg_echo('members-extender:stats:postactivity');
	$view_h = elgg_echo('members-extender:stats:viewactivity');

	// Placeholder
	$view_history = $post_history = $group_access = 'unavailable';

	// Check for and include group only content as necessary
	if (elgg_instanceof($page_owner, 'group')) {
		$group_header = "<th>$group_h</th>";

		$post_h = elgg_echo('members-extender:label:group_prefix', array($post_h));
		$view_h = elgg_echo('members-extender:label:group_prefix', array($view_h));
		$group_class = 'group-engagement';
	} else {
		$drive_h = "<th>" . elgg_echo('members-extender:stats:driveactivity') . "</th>";
	}

	$html .= <<<HTML
	<div id="chartjs-tooltip" style='opacity: 0;'></div>
		<table class='elgg-table member-engagement-table $group_class'>
			<thead>
				<tr>
					<th colspan='2'>$user_h</th>
					<th>$activity_h</th>
					$group_header
					<th>$status_h</th>
					<th>$post_h</th>
					<th>$view_h</th>
					$drive_h
				</tr>
			</thead>
			<tbody>
HTML;


	// Load google drive activity system cache
	$drive_activity_cache = unserialize(elgg_load_system_cache('google_user_drive_activity_cache'));

	$canvas_height = "75px";

	foreach ($items as $item) {
		$icon = elgg_view_entity_icon($item, 'tiny');
		$user_link = "<a href=\"" . $item->getUrl() . "\" $rel>" . $item->name . "</a>";

		// Deterime if user is 'online' (did something recently)
		$time = time() - 300; // Current time minus 5 minutes
		$status = $item->last_action >= $time ? 'online' : 'offline';
		$online_status = elgg_echo("members-extender:stats:{$status}");

		// Time frames for view/post stats
		$today = time();
		$last_week = strtotime("-7 days", $today);
		$last_week_ms = $last_week * 1000;
		$today_ms = $today * 1000;

		if (elgg_instanceof($page_owner, 'group')) {
			$group_views = members_extender_get_user_views(array(
				'types' => array('group'),
				'view_user_guid' => $item->guid,
				'guid' => $page_owner->guid,
				'view_time_lower' => 0,
				'view_time_upper' => $today_ms
			));

			usort($group_views, function($a, $b) {
				return $b['Time'] - $a['Time'];
			});

			if (!empty($group_views)) {
				$group_access = date("d/m/y", round($group_views[0]['Time'] / 1000));
				$group_class = '';
			} else {
				$group_access = elgg_echo('members-extender:stats:never');
				$group_class = "empty-value";
			}

			$group_content = "<td class='member-engagement-group $group_class'>$group_access</td>";
		}

		// Get post stats
		$post_history_stats = members_extender_get_user_post_activity($item, elgg_get_page_owner_guid(), $last_week, $today);

		if (count($post_history_stats)) {
			$posts = array();
			foreach ($post_history_stats as $date => $count) {
				$posts[date('d',strtotime($date))] = $count;
			}

			$labels = json_encode(array_keys($posts));
			$values = json_encode(array_values($posts));

			$post_history = "<canvas data-labels={$labels} data-values={$values} class='post-chart engagement-chart' id='post-chart-{$item->guid}' width='10px' height='{$canvas_height}'></canvas>";

			$post_class = '';
		} else {
			$post_class = 'empty-value';
		}

		$view_history_stats = members_extender_get_user_views_by_date(array(
			'types' => array('object'),
			'container_guid' => elgg_get_page_owner_guid(),
			'view_user_guid' => $item->guid,
			'view_time_lower' => $last_week_ms,
			'view_time_upper' => $today_ms
		));

		if (count($view_history_stats['dates'])) {
			$views = array();

			foreach ($view_history_stats['dates'] as $date => $count) {
				$views[date('d',strtotime($date))] = $count;
			}

			$labels = json_encode(array_keys($views));
			$values = json_encode(array_values($views));

			$view_history = "<canvas data-labels={$labels} data-values={$values} class='view-chart engagement-chart' id='post-chart-{$item->guid}' width='10px' height='{$canvas_height}'></canvas>";

			$view_class = '';

		} else {
			$view_class = 'empty-value';
		}

		$last_view = $last_activity_date = FALSE;

		if ($view_history_stats['last_view']) {
			$last_view = $view_history_stats['last_view'] + members_extender_get_timezone_offset();
			$last_activity_date = date('d/m/y g:i:s A', $last_view);
		}

		$activity_class = '';
		if (!$last_activity_date) {
			// Use last login date if no views are available
			if ($item->last_login) {
				$last_activity_date = date('d/m/y g:i:s A', $item->last_login + members_extender_get_timezone_offset());
			} else {
				$last_activity_date = elgg_echo('members-extender:stats:never');
				$activity_class = 'empty-value';
			}
		}

		// Drive history (if not in group view)
		if ($drive_h) {
			if (array_key_exists($item->guid, $drive_activity_cache)) {
				$drive_history_stats = $drive_activity_cache[$item->guid];
			} else {
				$drive_history_stats = members_extender_get_user_drive_activity_stats($item, $last_week, $today);
			}

			if (!$item->email || $item->email === "" || !(substr($item->email, -strlen(MEMBERS_GAPPS_DOMAIN)) === MEMBERS_GAPPS_DOMAIN)) {
				$drive_history = "<td class='empty-value member-engagement-drive'>unavailable</td>";
			} else {
				$drive_activity = array();

				foreach ($drive_history_stats as $date => $count) {
					$drive_activity[date('d',strtotime($date))] = $count;
				}

				$labels = json_encode(array_keys($drive_activity));
				$values = json_encode(array_values($drive_activity));

				$endpoint = elgg_normalize_url("ajax/view/members-extender/engagement_detail/drive?user_guid={$item->guid}");

				$drive_history = "<td class='member-engagement-drive'><canvas data-endpoint='{$endpoint}' data-labels={$labels} data-values={$values} class='drive-chart engagement-chart' id='drive-chart-{$item->guid}' width='10px' height='{$canvas_height}'></canvas></td>"; 
			}
		}

		// Stats table row
		$html .= <<<HTML
			<tr>
				<td class='member-engagement-avatar'>$icon</td>
				<td class='member-engagement-link'>$user_link</td>
				<td class='member-engagement-activity $activity_class'>$last_activity_date</td>
				$group_content
				<td class='member-engagement-status status-$status'>$online_status</td>
				<td class='member-engagement-post $post_class'>$post_history</td>
				<td class='member-engagement-view $view_class'>$view_history</td>
				$drive_history
			</tr>
HTML;

	}
	$html .= '</tbody></table>';
	$script = <<<JAVASCRIPT
		<script type='text/javascript'>
			elgg.register_hook_handler('init', 'system', function() {
				elgg.membersextender.initCharts();
			});
		</script>
JAVASCRIPT;
	echo $script;
}

if (!$items) {
	return;
}

if ($position == 'before' || $position == 'both') {
	$html = $nav . $html;
}

if ($position == 'after' || $position == 'both') {
	$html .= $nav;
}

echo $html;
