<?php
/**
 * Members-Extender user icon extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 */

if (elgg_in_context('members') && elgg_in_context('gallery')) {
	$user_link = $user->getURL();
	$content = <<<HTML
		<div class='members-gallery-hover'>
			<span class='members-gallery-hover-name'><a href="$user_link">$user->name</a></span>
		</div>
HTML;

	echo $content;
}