<?php
/**
 * Members-Extender user icon prepend
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 */

if ((elgg_in_context('members') || elgg_in_context('members_custom_avatar')) && elgg_in_context('gallery')) {
	$vars['class'] = 'members-gallery-avatar';
}