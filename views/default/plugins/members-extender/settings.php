<?php
/**
 * Members-Extender navigation/tabs extension
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
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

// AWS Access
$aws_access_key_label = elgg_echo('members-extender:label:awsaccesskey');
$aws_access_key_input = elgg_view('input/text', array(
	'name' => 'params[awsaccesskey]',
	'value' => $vars['entity']->awsaccesskey
));

$aws_access_secret_label = elgg_echo('members-extender:label:awsaccesssecret');
$aws_access_secret_input = elgg_view('input/text', array(
	'name' => 'params[awsaccesssecret]',
	'value' => $vars['entity']->awsaccesssecret
));

$aws_access_region_label = elgg_echo('members-extender:label:awsaccessregion');
$aws_access_region_input = elgg_view('input/text', array(
	'name' => 'params[awsaccessregion]',
	'value' => $vars['entity']->awsaccessregion
));

$aws_access_dbtable_label = elgg_echo('members-extender:label:awsaccessdbtable');
$aws_access_dbtable_input = elgg_view('input/text', array(
	'name' => 'params[awsaccessdbtable]',
	'value' => $vars['entity']->awsaccessdbtable
));

// Hidden role
$global_engagement_role_label = elgg_echo('members-extender:label:engagementrole');
$global_engagement_role_select = elgg_view('input/roledropdown', array(
	'name' => 'params[engagement_role]',
	'value' => $vars['entity']->engagement_role,
	'show_none' => TRUE,
	'show_hidden' => TRUE,
));

// Time zone offset select
$utc = new DateTimeZone('UTC');
$dt = new DateTime('now', $utc);

$tz_option_values = array(0 => 'Disabled');

foreach(DateTimeZone::listIdentifiers() as $tz) {
	$current_tz = new DateTimeZone($tz);
	$offset =  $current_tz->getOffset($dt);
	$transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
	$abbr = $transition[0]['abbr'];
	$formatted_offset = todo_format_tz_offet($offset);
	$option = "{$tz} [{$abbr} $formatted_offset]";
	$tz_option_values[$tz] = $option;
}

$activity_tz_label = elgg_echo('members-extender:label:activity_tz');
$activity_tz_input = elgg_view('input/dropdown', array(
		'name' => 'params[activity_tz]',
		'options_values' => $tz_option_values,
		'value' => $vars['entity']->activity_tz,
));

$content = <<<HTML
	<div>
		<label>$hidden_role_label</label><br />
		$hidden_role_select
	</div>
	<div>
		<label>AWS SDK</label><br />
		This plugin now makes use of the AWS PHP SDK, see: <br /><br />
		<ul>
			<li>
				<a href='http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/SettingUpTestingSDKPHP.html'>http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/SettingUpTestingSDKPHP.html</a><br />
			</li>
			<li>
				<a href='http://docs.aws.amazon.com/aws-sdk-php/guide/latest/index.html'>http://docs.aws.amazon.com/aws-sdk-php/guide/latest/index.html</a>
			</li>
		</ul>
	</div>
	<div>
		<label>$aws_access_key_label</label><br />
		$aws_access_key_input
	</div>
	<div>
		<label>$aws_access_secret_label</label><br />
		$aws_access_secret_input
	</div>
	<div>
		<label>$aws_access_region_label</label><br />
		$aws_access_region_input
	</div>
	<div>
		<label>$aws_access_dbtable_label</label><br />
		$aws_access_dbtable_input
	</div>
	<div>
		<label>$global_engagement_role_label</label><br />
		$global_engagement_role_select
	</div>
	<div>
		<label>$activity_tz_label</label><br />
		$activity_tz_input
	</div>
HTML;


echo $content;
