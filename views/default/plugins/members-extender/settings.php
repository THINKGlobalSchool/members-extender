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

$student_role_label = elgg_echo('members-extender:label:studentrole');
$student_role_select = elgg_view('input/roledropdown', array(
	'name' => 'params[student_role]',
	'id' => 'student-role',
	'value' => $vars['entity']->student_role,
	'show_none' => TRUE,
	'show_hidden' => TRUE,
));

$teacher_role_label = elgg_echo('members-extender:label:teacherrole');
$teacher_role_select = elgg_view('input/roledropdown', array(
	'name' => 'params[teacher_role]',
	'id' => 'teacher-role',
	'value' => $vars['entity']->teacher_role,
	'show_none' => TRUE,
	'show_hidden' => TRUE,
));

$staff_role_label = elgg_echo('members-extender:label:staffrole');
$staff_role_select = elgg_view('input/roledropdown', array(
	'name' => 'params[staff_role]',
	'id' => 'staff-role',
	'value' => $vars['entity']->staff_role,
	'show_none' => TRUE,
	'show_hidden' => TRUE,
));

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
	<br />
	<div>
		<label>$student_role_label</label><br />
		$student_role_select
	</div>
	<div>
		<label>$teacher_role_label</label><br />
		$teacher_role_select
	</div>
	<div>
		<label>$staff_role_label</label><br />
		$staff_role_select
	</div>
	<div>
		<label>$hidden_role_label</label><br />
		$hidden_role_select
	</div>
HTML;

echo $content;