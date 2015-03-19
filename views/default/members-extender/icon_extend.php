<?php
/**
 * Members-Extender user icon extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 *
 * @uses $vars['entity']     The user entity. If none specified, the current user is assumed.
 */

if ((elgg_in_context('members') || elgg_in_context('members_custom_avatar')) && elgg_in_context('gallery')) {
	// Get user
	$user = elgg_extract('entity', $vars, elgg_get_logged_in_user_entity());

	// Truncate long names
	$user_name_segments = explode(" ", $user->name);
	foreach ($user_name_segments as $s) {
		if (strlen($s) > 11) {
			$user_name .= " " . elgg_get_excerpt($s, 9);
		} else {
			$user_name .= " " . $s;
		}
	}

	trim($user_name);

	$user_link = $user->getURL();
	$location = $user->location;

	$content = <<<HTML
		<div onclick="javascript:window.location.href='$user_link'" class='members-gallery-hover'>
				<span class='members-gallery-hover-name'>$user_name</span>
				<span class='members-gallery-hover-location'>$location</span>
		</div>
HTML;
	echo $content;
}