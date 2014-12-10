<?php
/**
 * Members-Extender gallery view prepend
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

if ((elgg_in_context('members') || elgg_in_context('members_custom_avatar'))) {
	$vars['gallery_class'] = 'elgg-gallery-users';

	// Determine if we're including the engagment link to toggle engagement view
	if (get_input('include_engagement') && elgg_is_admin_logged_in()) {
		$url = current_page_url();
		$q = parse_url($url, PHP_URL_QUERY);

		if (get_input('engagement')) {
			$text = elgg_echo('members-extender:label:viewgallery');
			$add = FALSE;
		} else {
			$text = elgg_echo('members-extender:label:viewengagement');	
			$add = TRUE;
		}
		if ($add) {
			if ($q) {
			$str = "&engagement=1";
			} else {
				$str = "?engagement=1";
			}
			$url .= $str;
		} else {
			$url = str_replace(array("&engagement=1", "?engagement=1"), '', $url);
		}

		echo elgg_view('output/url', array(
			'value' => $url,
			'text' => $text,
			'class' => 'members-view-engagment'
		));
	}

	// Handle engagment view
	if (get_input('engagement') && elgg_is_admin_logged_in()) {
		elgg_set_view_location('page/components/gallery', elgg_get_plugins_path() . "members-extender/overrides/");
	}
}