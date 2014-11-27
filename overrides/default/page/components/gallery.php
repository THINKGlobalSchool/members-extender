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
		$group_content = "<td class='member-engagement-group empty-value'>$group_access</td>";
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
			$last_login = date("F j, Y", $item->last_login);
			$login_class = '';
		} else {
			$last_login = elgg_echo('members-extender:stats:never');
			$login_class = 'empty-value';
		}
		$today = time();
		$last_week = strtotime("-7 days", $today);

		$post_history_stats = members_extender_get_user_post_activity($item, FALSE, $last_week, $today);

		elgg_dump($post_history_stats);

		if (count($post_history_stats)) {
			$posts = array();
			foreach ($post_history_stats as $day => $count) {
				$posts[] = $count;
			}

			$post_history = "<span class='bar hidden'>" . implode(',', $posts) . "</span>";
			$post_class = '';
		} else {
			$post_class = 'empty-value';
		}

		$html .= <<<HTML
			<tr>
				<td class='member-engagement-avatar'>$icon</td>
				<td class='member-engagement-link'>$user_link</td>
				<td class='member-engagement-login $login_class'>$last_login</td>
				$group_content
				<td class='member-engagement-status status-$status'>$online_status</td>
				<td class='member-engagement-post $post_class'>$post_history</td>
				<td class='member-engagement-view empty-value'>$view_history</td>
			</tr>
HTML;

	}
	$html .= '</tbody></table>';
	$script = <<<JAVASCRIPT
		<script type='text/javascript'>
			$(document).ready(function() {
				$.fn.peity.defaults.bar = {
					delimiter: ",",
					fill: ["#85161d"],
					height: 16,
					max: null,
					min: 0,
					padding: 0.1,
					width: '100%'
				}

				$(".bar").peity("bar");
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
