<?php
/**
 * Members-Extender gallery view override
 *
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

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
	$login_h = elgg_echo('members-extender:stats:lastlogin');
	$post_h = elgg_echo('members-extender:stats:postactivity');
	$view_h = elgg_echo('members-extender:stats:viewactivity');

	// Placeholder
	$view_history = $post_history = $group_access = 'unavailable';

	// Check for and include group only content as necessary
	$page_owner = elgg_get_page_owner_entity();
	if (elgg_instanceof($page_owner, 'group')) {
		$group_header = "<th>$group_h</th>";
	}

	$html .= <<<HTML
		<table class='elgg-table member-engagement-table'>
			<thead>
				<tr>
					<th colspan='2'>$user_h</th>
					<th>$login_h</th>
					$group_header
					<th>$status_h</th>
					<th>$post_h</th>
					<th>$view_h</th>
				</tr>
			</thead>
			<tbody>
HTML;


	foreach ($items as $item) {
		$icon = elgg_view_entity_icon($item, 'tiny');
		$user_link = "<a href=\"" . $item->getUrl() . "\" $rel>" . $item->name . "</a>";

		// Deterime if user is 'online' (did something recently)
		$time = time() - 300; // Current time minus 5 minutes
		$status = $item->last_action >= $time ? 'online' : 'offline';
		$online_status = elgg_echo("members-extender:stats:{$status}");

		// Last login
		if ($item->last_login) {
			$last_login = date("d/m/y", $item->last_login);
			$login_class = '';
		} else {
			$last_login = elgg_echo('members-extender:stats:never');
			$login_class = 'empty-value';
		}

		// Time frames for view/post stats
		$today = time();
		$last_week = strtotime("-7 days", $today);

		if (elgg_instanceof($page_owner, 'group')) {
			$group_views = members_extender_get_user_views(array(
				'types' => array('group'),
				'view_user_guid' => $item->guid,
				'guid' => $page_owner->guid,
				'view_time_lower' => 0,
				'view_time_upper' => $today
			));

			usort($group_views, function($a, $b) {
				return $b['Time'] - $a['Time'];
			});

			if (!empty($group_views)) {
				$group_access = date("d/m/y", $group_views[0]['Time']);
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

			$post_history = "<canvas data-labels={$labels} data-values={$values} class='post-chart' id='post-chart-{$item->guid}' width='10px' height='50px'></canvas>";

			$post_class = '';
		} else {
			$post_class = 'empty-value';
		}

		$view_history_stats = members_extender_get_user_views_by_date(array(
			'types' => array('object'),
			'container_guid' => elgg_get_page_owner_guid(),
			'view_user_guid' => $item->guid,
			'view_time_lower' => $last_week,
			'view_time_upper' => $today
		));

		if (count($view_history_stats)) {
			$views = array();
			foreach ($view_history_stats as $date => $count) {
				$views[date('d',strtotime($date))] = $count;
			}

			$labels = json_encode(array_keys($views));
			$values = json_encode(array_values($views));

			$view_history = "<canvas data-labels={$labels} data-values={$values} class='post-chart' id='post-chart-{$item->guid}' width='10px' height='50px'></canvas>";

			$view_class = '';

		} else {
			$view_class = 'empty-value';
		}

		// Stats table row
		$html .= <<<HTML
			<tr>
				<td class='member-engagement-avatar'>$icon</td>
				<td class='member-engagement-link'>$user_link</td>
				<td class='member-engagement-login $login_class'>$last_login</td>
				$group_content
				<td class='member-engagement-status status-$status'>$online_status</td>
				<td class='member-engagement-post $post_class'>$post_history</td>
				<td class='member-engagement-view $view_class'>$view_history</td>
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
