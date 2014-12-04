<?php
/**
 * Members-Extender Helper Library
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */

/**
 * Get custom member listing content
 */
function members_extender_get_custom_member_listing($page) {
	$dashboard = elgg_view('drilltrate/dashboard', array(
		'menu_name' => 'members_custom',
		'infinite_scroll' => false,
		'main_class' => '',
		'default_params' => array(
			'type' => 'all',
		),
		'list_url' => elgg_get_site_url() . 'ajax/view/members-extender/list',
		'id' => 'members-custom-menu'
	));

	$params = array(
		'content' => $dashboard,
		'title' => $title,
		'filter' => ' '
	);

	$title = elgg_echo('members');
	$body = elgg_view_layout('content', $params);

	echo elgg_view_page($title, $body);
}

/**
 * Return the number of users registered in the system (except for parents, and banned)
 *
 * @param bool $show_deactivated Count not enabled users?
 *
 * @return int
 */
function members_extender_get_number_users($show_deactivated = FALSE) {
	global $CONFIG;

	$access = "";

	if (!$show_deactivated) {
		$access = "and " . _elgg_get_access_where_sql();
	}

	// Relationship info for excluding hidden members
	$hidden_role = elgg_get_plugin_setting('hidden_role', 'members-extender');
	$role_relationship = ROLE_RELATIONSHIP;
	
	if (members_extender_get_exclude_parent_sql()) {
		$parent_sql = "AND " . members_extender_get_exclude_parent_sql();
	}

	$query = "SELECT count(*) as count from {$CONFIG->dbprefix}entities e
			  JOIN {$CONFIG->dbprefix}users_entity ue on ue.guid = e.guid
			  WHERE type='user' $access 
			  AND ue.banned = 'no'" . $parent_sql;
						
	if ($hidden_role) {
		$query .= "AND NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}entity_relationships r_hidden 
					WHERE r_hidden.guid_one = e.guid
					AND r_hidden.relationship = '{$role_relationship}'
					AND r_hidden.guid_two = {$hidden_role})";
	}

	$result = get_data_row($query);

	if ($result) {
		return $result->count;
	}

	return FALSE;
}

/**
 * Get SQL to exclude parent (parentportal plugin) from members listings
 */
function members_extender_get_exclude_parent_sql() {
	if (elgg_is_active_plugin('parentportal')) {
		global $CONFIG;

		// MD info for excluding parents
		$is_parent = get_metastring_id('is_parent');
		$one_id = get_metastring_id(1);
		
		$parent_sql = "
		  NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}metadata md
					WHERE md.entity_guid = e.guid
					AND md.name_id = $is_parent
					AND md.value_id = $one_id)";
					
		return $parent_sql;
	} else {
		return '';
	}
}

/**
 * Get member post activity (objects created) between given time period
 *
 * @param ElggUser $user
 * @param int      $start Default: none
 * @param int      $end   Default: none
 * @return
 */
function members_extender_get_user_post_activity($user, $container = FALSE, $start = 0, $end = 0) {
	// Sanity
	if (!elgg_instanceof($user, 'user') || !is_int($start) || !is_int($end)) {
		return FALSE;
	}

	// Exclude these subtypes by default
	$exclude_subtypes = array(
		get_subtype_id('object', 'messages'),
		get_subtype_id('object', 'connected_blog_activity'),
		get_subtype_id('object', 'tidypics_batch')
	);

	// Trigger a hook to modify exclusions
	$hook_params = array(
		'user' => $user,
		'start' => $start,
		'end' => $end
	);

	$exclude_subtypes = elgg_trigger_plugin_hook('analytics:exclude:subtypes', 'userpost', $hook_params, $exclude_subtypes);

	if (count($exclude_subtypes)) {
		$exclude_subtypes = implode(',', $exclude_subtypes);
		$exclude_subtypes_sql = "AND e.subtype NOT IN ({$exclude_subtypes})";
	}

	$dbprefix = elgg_get_config('dbprefix');

	// Start date sql if supplied
	if ($start) {
		$start_sql = "AND e.time_created > {$start}";
	}

	// End date sql if supplied
	if ($end) {
		$end_sql = "AND e.time_created < {$end}";
	} else {
		$end = time();
	}

	// Handler container-container objects (photos, pages)
	$container_contained = array(
		get_subtype_id('object', 'image')
	);

	// Let plugins add to this list
	$container_contained = elgg_trigger_plugin_hook('analytics:container:contained', 'userpost', NULL, $container_contained);

	// See if we supplied a container guid (groups)
	$container = (int)$container;
	if ($container) {
		// Need to check container contained entities, so throw in a join
		$container_join = "JOIN {$dbprefix}entities ce on ce.guid = e.container_guid";
		$container_sql = "AND (e.container_guid = {$container} OR ce.container_guid = {$container})";
	}

	// Direct SQL
	$query = "SELECT count(e.guid) as post_count,
	          FROM_UNIXTIME(e.time_created, '%Y-%m-%d') as post_day
	          FROM {$dbprefix}entities e
			  $container_join
	          WHERE e.owner_guid = {$user->guid} 
	          AND e.type = 'object'
	          $start_sql 
	          $end_sql 
	          $exclude_subtypes_sql
	          $container_sql
	          GROUP BY post_day";

	// Get data
	$result = get_data($query, 'members_extender_activity_row_to_array');


	// Build results array with dates/posts
	$num_days = abs($start - $end)/60/60/24; // Determine number of days between timestat

	// Build date array
	$date_array = array();
	for ($i = 1; $i <= $num_days; $i++) {
		$dom = date('Y-m-d', strtotime("+{$i} day", $start)); // Day of month
		$post_count_by_dom = search_array_value_key($result, $dom);
		$date_array[$dom] = $post_count_by_dom ? $post_count_by_dom : 0;
	}

	return $date_array;
}

/**
 * Callback for get_data to convert activity rows to an array
 */
function members_extender_activity_row_to_array($row) {
	return array($row->post_day => $row->post_count);
}

/**
 * Handy multidimensional array key search function
 * From: http://snipplr.com/view/55684/
 *
 * @param array $array 
 * @param mixed $search
 * @return mixed
 */
function search_array_value_key(array $array, $search) {
	foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $key => $value) {
	    if ($search === $key)
		return $value;
	}
	return false;
}

/**
 * Add a user view to the spot views dynanmodb
 * 
 * 'Schema' (as far as nosql goes..)
 *
 * Hash key  : UserId
 * Range key : Time
 * 
 * Other fields (required)
 *
 * ObjectId            => ElggObject->guid
 * ObjectContainerId   => ElggObject->container_guid (or container of container guid)
 * ObjectOwnerId       => ElggObject->owner_guid
 * ObjectType          => ElggObject->getType()
 * ObjectSubtype       => ElggObject->getSubtype()
 * ObjectCreated       => ElggObject->time_created
 * ObjectUpdated       => ElggObject->time_updated
 */
function members_extender_add_user_view($user, $object) {
	// Check for valid entities
	if (!elgg_instanceof($user, 'user') || (!elgg_instanceof($object, 'group') && !elgg_instanceof($object, 'object'))) {
		return FALSE;
	}

	$client = get_dynamo_db_client();

	// Need to handle container contained subtypes
	$container_contained = array(
		'image'
	);

	// Trigger a hook
	$container_contained = elgg_trigger_plugin_hook('analytics:container:contained', 'userview', NULL, $container_contained);

	// Get container->contained_guid
	if (in_array($object->getSubtype(), $container_contained)) {
		$container_guid = $object->getContainerEntity()->container_guid;
	} else {
		$container_guid = $object->container_guid;
	}

	$time = time();

	// Create item
	$item = array(
		"UserId" => $client->formatValue((int)$user->guid),
		"ObjectId" => $client->formatValue((int)$object->guid),
		"ObjectContainerId" => $client->formatValue((int)$container_guid),
		"ObjectOwnerId" => $client->formatValue((int)$object->owner_guid),
		"ObjectType" => $client->formatValue($object->getType()),
		"ObjectSubtype" => $client->formatValue($object->getSubtype()),
		"ObjectCreated" => $client->formatValue((int)$object->time_created),
		"ObjectUpdated" => $client->formatValue((int)$object->time_updated),
		"Time" => $client->formatValue($time)
	);

	// Insert!
	$client->putItem(array(
		'TableName' => elgg_get_plugin_setting('awsaccessdbtable', 'members-extender'),
		'Item' => $item
	));
}

/**
 * Get a specific users views
 *
 * @param $options
 * 
 * Available options:
 *
 * 	'types'					=>	Object Types (ie: object, group)
 *	'subtypes'				=>	Object Subtypes (ie: blog, page)
 *	'guids'					=>	Object Guids
 *	'owner_guids'			=>	Object Owner guids
 *	'container_guids'		=>	Object Container guids (ie: group->guid)
 *	'modified_time_lower'	=>	Modified timestamp lower range
 *	'modified_time_upper'	=>	Modified timestamp upper ranges
 *	'created_time_lower'	=>	Created timestamp lower range
 *	'created_time_upper'	=>	Created timestamp upper range
 *	'view_time_lower'       =>  View timestamp lower range (part of dynamodb range key)
 *	'view_time_upper'       =>  View timestamp upper range (part of dynamodb range key)
 *
 * REQUIRED!!
 *  'view_user_guids'       =>  At least one user guid (HASH KEY!)
 * 
 * @return mixed
 */
function members_extender_get_user_views(array $options = array()) {
	// Default options
	$defaults = array(
		'types'					=>	ELGG_ENTITIES_ANY_VALUE,
		'subtypes'				=>	ELGG_ENTITIES_ANY_VALUE,
		'guids'					=>	ELGG_ENTITIES_ANY_VALUE,
		'owner_guids'			=>	ELGG_ENTITIES_ANY_VALUE,
		'container_guids'		=>	ELGG_ENTITIES_ANY_VALUE,
		'modified_time_lower'	=>	0,
		'modified_time_upper'	=>	ELGG_ENTITIES_ANY_VALUE,
		'created_time_lower'	=>	0,
		'created_time_upper'	=>	ELGG_ENTITIES_ANY_VALUE,
		'view_time_lower'       =>  0,
		'view_time_upper'       =>  ELGG_ENTITIES_ANY_VALUE,
	);

	// Elgg -> Dynamo Mapping
	$field_mapping = array(
		'types'					=>	'ObjectType',
		'subtypes'				=>	'ObjectSubtype',
		'guids'					=>	'ObjectId',
		'owner_guids'			=>	'ObjectOwnerId',
		'container_guids'		=>	'ObjectContainerId',
		'modified_time_lower'	=>	'ObjectUpdated',
		'modified_time_upper'	=>	'ObjectUpdated',
		'created_time_lower'	=>	'ObjectCreated',
		'created_time_upper'	=>	'ObjectCreated',
	);

	$options = array_merge($defaults, $options);
	$singulars = array('type', 'subtype', 'guid', 'owner_guid', 'container_guid', 'view_user_guid');
	$options = elgg_normalise_plural_options_array($options, $singulars);

	if (empty($options['view_user_guids'])) {
		return FALSE; // Need at least one user guid to query against
	}

	// Grab a client
	$client = get_dynamo_db_client();

	// Check view user guids
	$view_user_attrs = array();
	foreach ($options['view_user_guids'] as $idx => $user) {
		if (!elgg_instanceof(get_entity($user), 'user')) {
			unset($options['view_user_guids'][$idx]);
		} else {
			$view_user_attrs[] = $client->formatValue($user);
		}
	}

	// Start building key conditions
	$key_conditions = array(
		"UserId" => array(
			"ComparisonOperator" => 'EQ',
			"AttributeValueList" => $view_user_attrs
		)
	);

	// Add lower view time
	$time_attrs = array(
		$client->formatValue($options['view_time_lower'])
	);

	// If view upper is supplied change comparison and add attribute
	if ($options['view_time_upper']) {
		$time_comparison = "BETWEEN";
		$time_attrs[] = $client->formatValue($options['view_time_upper']);
	} else {
		$time_comparison = "GT";
	}

	// Put together view time key conditions
	$key_conditions["Time"] = array(
		"ComparisonOperator" => $time_comparison,
		"AttributeValueList" => $time_attrs
	);

	// Unset hash key options
	unset($options['view_user_guids']);
	unset($options['view_time_lower']);
	unset($options['view_time_upper']);

	// Begin building filter expression and attributes
	$expression_attrs = array();
	$filter_expressions = array();

	// Loop over options and process into query dynamodbexpressions/attributes
	foreach ($options as $key => $value) {
		// All array values will be treated as 'in' queries
		if (is_array($value)) {
			$expression_placeholders = array();
			foreach ($value as $idx => $sub_value) {
				if ($sub_value !== NULL && $field_mapping[$key]) {
					// Cast values to int where needed
					if (is_numeric($sub_value)) {
						$sub_value = (int)$sub_value;
					}

					$expression_attrs[":{$key}{$idx}"] = $client->formatValue($sub_value);
					$expression_placeholders[] = ":{$key}{$idx}";
				}
			}
			if ($field_mapping[$key]) {
				$expr_str = implode(',', $expression_placeholders);
				$filter_expressions[] = "({$field_mapping[$key]} in ({$expr_str}))";
			}
		} else {
			if ($value !== NULL && $field_mapping[$key]) {
				if ($key == 'created_time_lower' && $options['created_time_upper'] !== NULL) {
					$filter_expressions[] = "({$field_mapping[$key]} between :created_time_lower and :created_time_upper)";
				} else if ($key == 'created_time_lower' && $options['create_time_upper'] === NULL) {
					$filter_expressions[] = "({$field_mapping[$key]} > :created_time_lower)";
				} else if ($key == 'modified_time_lower' && $options['modified_time_upper'] !== NULL) {
					$filter_expressions[] = "({$field_mapping[$key]} between :modified_time_lower and :modified_time_upper)";
				} else if ($key == 'modified_time_lower' && $options['modified_time_upper'] === NULL) {
					$filter_expressions[] = "({$field_mapping[$key]} > :modified_time_lower)";
				} else if ($field_mapping[$key]) { // For future use.. just include: key => value
					$filter_expressions[] = "({$field_mapping[$key]} = :{$key})";
				}

				if (is_numeric($value)) {
					$expression_attrs[":{$key}"] = $client->formatValue((int)$value);
				} else {
					$expression_attrs[":{$key}"] = $client->formatValue($value);
				}
			}
		}
	}

	// Create expression string
	$filter_expressions_str = implode(' and ', $filter_expressions);

	// Expression attribute names (for reserved/other fields)
	$expression_attr_names = array('#time' => 'Time');

	$items = members_extender_run_query($client, array(
		"TableName" => elgg_get_plugin_setting('awsaccessdbtable', 'members-extender'),
		"KeyConditions" => $key_conditions,
		"FilterExpression" => $filter_expressions_str,
		"ExpressionAttributeNames" => $expression_attr_names,
		"ExpressionAttributeValues" => $expression_attrs,
		"ProjectionExpression" => "#time, ObjectId, ObjectSubtype, ObjectType"
	));

	return $items;
}

/**
 * Run a query on the dynamodb
 *
 * @param  $client  DynamoDBClient
 * @param  $options Query options
 * @return array|bool
 */
function members_extender_run_query($client, array $options = array()) {
	// Default options
	$defaults = array(
		'callback' =>  'members_extender_view_items_callback'
	);

	$options = array_merge($defaults, $options);

	// Check required fields
	$required = array(
		'TableName', 'KeyConditions'
	);

	foreach ($required as $r) {
		if (!array_key_exists($r, $options)) {
			return FALSE;
		}
	}

	// Run query
	$response = $client->query($options);

	$callback = $options['callback'];

	$is_callable = is_callable($callback);

	foreach ($response['Items'] as $item) {
		if ($is_callable) {
			$return[] = $callback($item);
		} else {
			$return[] = $item;
		}
	}

	if (!is_array($return)) {
		$return = array();
	}

	// Check if we've run into a result set limit
	if (is_array($response['LastEvaluatedKey'])) {
		// Got last evaluated key (not all matching items were returned), so include the last key as the 
		// start key
		$options['ExclusiveStartKey'] = $response['LastEvaluatedKey'];

		// Run'er again
		$return = array_merge_recursive(members_extender_run_query($client, $options), $return);
	}

	return $return;
}

/**
 * Get user user views formatted as date => view_count
 *
 * @param  $options Query options (see: members_extender_run_query)
 * @return array
 */
function members_extender_get_user_views_by_date(array $options = array()) {
	$views = members_extender_get_user_views($options);

	$start = $options['view_time_lower'];
	$end = $options['view_time_upper'];

	$num_days = abs($start - $end)/60/60/24; // Determine number of days between times

	// Build date => view count array
	$views_by_date = array();

	foreach ($views as $view) {
		$views_by_date[date('Y-m-d', $view['Time'])] += 1;
	}

	$date_array = array();
	for ($i = 1; $i <= $num_days; $i++) {
		$dom = date('Y-m-d', strtotime("+{$i} day", $start)); // Day of month
		$post_count_by_dom = search_array_value_key($views_by_date, $dom);
		$date_array[$dom] = $post_count_by_dom ? $post_count_by_dom : 0;
	}

	return $date_array;
}

/**
 * Dynamodb view items callback
 * 
 * Formats items as key => value (strips out the dynamodb formatting, ie: 'N' => xyz) 
 */
function members_extender_view_items_callback($item) {
	$return = array();
	foreach ($item as $key => $val) {
		$return[$key] = reset($val); // reset() is sweet (set array pointer to first item)
	}
	return $return;
}
