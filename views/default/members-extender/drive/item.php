<?php
/**
 * Members-Extender google drive item view
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.com/
 *
 * @uses $vars['doc_title']
 * @uses $vars['doc_type'] 
 * @uses $vars['doc_event_type']
 * @uses $vars['doc_event_action']
 * @uses $vars['doc_event_string']
 * @uses $vars['doc_event_date']
 */

$title = elgg_extract('doc_title', $vars);
$type = elgg_extract('doc_type', $vars);
$event_type = elgg_extract('doc_event_type', $vars);
$event_action = elgg_extract('doc_event_action', $vars);
$event_string = elgg_extract('doc_event_string', $vars);
$event_date = elgg_extract('doc_event_date', $vars);

$item_image_url = members_extender_get_item_image_url($type);

$item_icon = elgg_view('output/img', array(
	'src' => $item_image_url,
	'alt' => $type,
	'title' => $type,
	'class' => '',
));

$item_content = elgg_view_image_block($item_icon, $event_string); 

echo "<li>{$item_content}<span class='date'>{$event_date}</span><div class='clearfix'></div></li>";