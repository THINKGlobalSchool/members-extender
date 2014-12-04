<?php
/**
 * Members-Extender AWS SDK loader
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

require_once(elgg_get_plugins_path() . 'members-extender/vendors/awssdk/aws-autoloader.php');

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Enum\ComparisonOperator;

global $dynamodbclient;

function get_dynamo_db_client($dblinktype) {
	global $dynamodbclient;
	if (!$dynamodbclient) {
		$dynamodbclient = DynamoDbClient::factory(array(
			'key'    => elgg_get_plugin_setting('awsaccesskey', 'members-extender'),
			'secret' => elgg_get_plugin_setting('awsaccesssecret', 'members-extender'),
			'region' => elgg_get_plugin_setting('awsaccessregion', 'members-extender')
		));
		return get_dynamo_db_client();
	} else {
		return $dynamodbclient;
	}
}
