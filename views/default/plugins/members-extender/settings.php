<?php
/**
 * Members-Extender navigation/tabs extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 *
 */

// Hidden role
$hidden_role_label = elgg_echo('members-extender:label:hiddenrole');
$hidden_role_select = elgg_view('input/roledropdown', array(
	'name' => 'params[hidden_role]',
	'id' => 'staff-role',
	'value' => $vars['entity']->hidden_role,
	'show_none' => TRUE,
	'show_hidden' => TRUE,
));


$content = <<<HTML
	<div>
		<label>$hidden_role_label</label><br />
		$hidden_role_select
	</div>
HTML;

echo $content;