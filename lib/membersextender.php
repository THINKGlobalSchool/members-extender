<?php
/**
 * Members-Extender Helper Library
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
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
 * @param ElggUser   $user
 * @param ElggObject $container Object container
 * @param int        $start     Default: none
 * @param int        $end       Default: none
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
		$a_start_sql = "AND n_table.time_created > {$start}";
	}

	// End date sql if supplied
	if ($end) {
		$end_sql = "AND e.time_created < {$end}";
		$a_end_sql = "AND n_table.time_created < {$end}";
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
	$object_query = "SELECT count(e.guid) as post_count,
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


	$annotation_query = "SELECT count(DISTINCT n_table.id) as post_count,
						 FROM_UNIXTIME(n_table.time_created, '%Y-%m-%d') as post_day
						 FROM {$dbprefix}annotations n_table
						 JOIN {$dbprefix}entities e ON n_table.entity_guid = e.guid
						 JOIN {$dbprefix}metastrings msn on n_table.name_id = msn.id
						 $container_join
						 WHERE n_table.owner_guid = {$user->guid} 
						 $a_start_sql 
	         			 $a_end_sql
	         			 $exclude_subtypes_sql
	         			 $container_sql
	         			 AND (msn.string IN ('generic_comment', 'likes'))
	         			 GROUP BY post_day";

	// Get data
	$object_result = get_data($object_query, 'members_extender_activity_row_to_array');
	$annotation_result = get_data($annotation_query, 'members_extender_activity_row_to_array');

	// Build results array with dates/posts
	$num_days = abs($start - $end)/60/60/24; // Determine number of days between timestat

	// Build date array
	$date_array = array();
	for ($i = 1; $i <= $num_days; $i++) {
		$dom = date('Y-m-d', strtotime("+{$i} day", $start)); // Day of month
		$object_count_by_dom = search_array_value_key($object_result, $dom);
		$annotation_count_by_dom = search_array_value_key($annotation_result, $dom);
		$total = $object_count_by_dom + $annotation_count_by_dom;
		$date_array[$dom] = $total ? $total : 0;
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
 * Get a user's Google Drive activity
 *
 * @param ElggUser $user
 * @param int      $start       Default: none
 * @param int      $end         Default: none
 * @param int      $max_results Maximum amount of results to return
 * @return
 */
function members_extender_get_user_drive_activity($user, $start, $end, $page_token = FALSE, $max_results = 1000) {
	if (!$user->email || $user->email === "" || !(substr($user->email, -strlen(MEMBERS_GAPPS_DOMAIN)) === MEMBERS_GAPPS_DOMAIN)) {
		return FALSE;
	}

	$client = googleapps_get_service_client(array(
		"https://www.googleapis.com/auth/admin.reports.audit.readonly",
		"https://www.googleapis.com/auth/admin.reports.usage.readonly"
	));
	
	elgg_load_library('gapc:Reports');

	$service = new Google_Service_Reports($client);

	$params = array(
		'maxResults' => $max_results
	);

	if ($page_token) {
		$params['pageToken'] = $page_token;
	}

	// Attempt to create DateTime objects with given date strings
	$start_date = $start ? new DateTime(date("c", $start)) : FALSE;
	$end_date =  $end ? new DateTime(date("c", $end)) : FALSE;

	// If we've got dates, format then ISO-8602 (2014-12-07T00:00:00Z)
	if ($start_date) {
		$params['startTime'] = $start_date->format('c');
	}

	if ($end_date) {
		$params['endTime'] = $end_date->format('c');
	}

	// Wrap this in a try/catch to handle invalid requests
	try {
		$activity = $service->activities->listActivities($user->email, 'drive', $params);
		$items = $activity->getItems();
		
		if ($activity->getNextPageToken() && count($items) < $max_results) {
			$item_count = $max_results - count($items);
			$new_activity_items = members_extender_get_user_drive_activity($user, $start, $end, $activity->getNextPageToken(), $item_count);
			if ($new_activity_items) {
				$items = array_merge($items, $new_activity_items);
			}
		}

		return $items;
	} catch (Google_Service_Exception $e) {
		$errors = $e->getErrors();
		// Check for a bad request error, this is likely non-existant domain email
		if (isset($errors['reason']) && $errors['reason'] == 'badRequest') {
			return FALSE;			
		}
	}	
}

/**
 * Get user drive activity stats
 */
function members_extender_get_user_drive_activity_stats($user, $start, $end) {
	$activity_objects = members_extender_get_user_drive_activity($user, $start, $end);

	$num_days = abs($start - $end)/60/60/24; // Determine number of days start and end time


	$drive_activity_by_date = array();
	foreach ($activity_objects as $activity) {
			$date = date('Y-m-d', strtotime($activity->getId()->getTime()));
			$drive_activity_by_date[$date] += 1;
	}

	$date_array = array();
	for ($i = 1; $i <= $num_days; $i++) {
		$dom = date('Y-m-d', strtotime("+{$i} day", $start)); // Day of month
		$drive_activity_by_dom = search_array_value_key($drive_activity_by_date, $dom);
		$date_array[$dom] = $drive_activity_by_dom ? $drive_activity_by_dom : 0;
	}

	return $date_array;
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

	$time = round(microtime(true)*1000);

	// Create item
	$item = array(
		"UserId" => $client->formatValue((int)$user->guid),
		"ObjectId" => $client->formatValue((int)$object->guid),
		"ObjectContainerId" => $client->formatValue((int)$container_guid),
		"ObjectOwnerId" => $client->formatValue((int)$object->owner_guid),
		"ObjectType" => $client->formatValue($object->getType()),
		"ObjectSubtype" => $client->formatValue($object->getSubtype() ? $object->getSubtype() : 0), // subtype can be empty (ie: groups)
		"ObjectCreated" => $client->formatValue((int)$object->time_created),
		"ObjectUpdated" => $client->formatValue((int)$object->time_updated),
		"Time" => $client->formatValue((int)$time)
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
	$options = _elgg_normalize_plural_options_array($options, $singulars);

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

	// Check for a valid container guids
	if ($options['container_guids']) {
		foreach ($options['container_guids'] as $idx => $container) {
			if (!$container) {
				unset($options['container_guids'][$idx]);
			}
		}
	}

	if (empty($options['container_guids']) || !$options['container_guids']) {
		unset($options['container_guids']);
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

	usort($views, function($a, $b) {
		return $a['Time'] - $b['Time'];
	});

	$start = round($options['view_time_lower'] / 1000);
	$end = round($options['view_time_upper'] / 1000);

	$num_days = abs($start - $end)/60/60/24; // Determine number of days between times

	// Build date => view count array
	$views_by_date = array();

	$last_view = 0;

	foreach ($views as $view) {
		$time = round($view['Time']/1000);

		if ($time > $last_view) {
			$last_view = $time;
		}

		$views_by_date[date('Y-m-d', $time)] += 1;
	}

	$date_array = array();
	for ($i = 1; $i <= $num_days; $i++) {
		$dom = date('Y-m-d', strtotime("+{$i} day", $start)); // Day of month
		$post_count_by_dom = search_array_value_key($views_by_date, $dom);
		$date_array[$dom] = $post_count_by_dom ? $post_count_by_dom : 0;
	}

	$return = array(
		'last_view' => $last_view,
		'dates' => $date_array
	);

	return $return;
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

/**
 * Gatekeeper for global member engagement
 */
function members_extender_engagement_gatekeeper() {
	$engagement_role = elgg_get_plugin_setting('engagement_role', 'members-extender');
	return (roles_is_member($engagement_role, elgg_get_logged_in_user_guid()) || elgg_is_admin_logged_in()) && elgg_is_logged_in();
}

/**
 * Helper function to get the timezone offset for activity views
 *
 * @return int
 */
function members_extender_get_timezone_offset() {
	// Get timezone
	$utc = new DateTimeZone('UTC');

	// Get current date/time
	$current_dt = new DateTime('now', $utc);

	$activity_tz = elgg_get_plugin_setting('activity_tz', 'members-extender');

	// Might be unset/disabled, so return 0
	if (!$activity_tz) {
		return 0;
	}

	// Get configured time zone object
	$time_zone = new DateTimeZone($activity_tz);

	// Calulate offset
	$offset =  $time_zone->getOffset($current_dt);

	return $offset;
}

/**
 * Generate url for given google drive type icon
 */
function members_extender_get_item_image_url($type) {
	$known_types = array(
		'document',
		'presentation',
		'spreadsheet',
		'video',
		'folder'
	);

	$base = elgg_normalize_url('mod/members-extender/graphics/');

	if (in_array($type, $known_types)) {
		return "{$base}google_{$type}.png";
	} else {
		return "{$base}google_drive.png";
	}
}

/**
 * Generate event info
 */
function members_extender_get_events_info($events) {
	// Get date from events
	$doc_event_date = $events->getId()->getTime();
	$doc_event_date = strtotime($doc_event_date) + members_extender_get_timezone_offset();
	$doc_event_date = date('d/m/y g:i:s A', $doc_event_date);

	$multi_events = array(
		'add_to_folder' => array(
			'primary' => 'doc_title',
			'secondary' => 'destination_folder_title'
		),
		'remove_from_folder' => array(
			'primary' => 'doc_title',
			'secondary' => 'source_folder_title'
		),
		'rename' =>  array(
			'primary' => 'old_value', 
			'secondary' => 'new_value'
		),
		'move' => array(
			'primary' => 'doc_title',
			'secondary' => 'destination_folder_title'
		)
	);

	foreach ($events as $event) {
		$primary_field = $secondary_field = NULL;
		$simple_event = $event->toSimpleObject();
		$doc_event_type = $event->getType();
		$doc_event_action = $event->getName();

		if (array_key_exists($doc_event_action, $multi_events)) {
			$secondary_field = $multi_events[$doc_event_action]['secondary'];
			$primary_field = $multi_events[$doc_event_action]['primary'];
		}

		foreach ($simple_event->parameters as $param) {
			if ($param['name'] == 'primary_event' && $param['boolValue'] == TRUE) {
				break 2;
			}
		}
	}
	
	$event_info = array();
	foreach ($simple_event->parameters as $param) {
		if (isset($param['value'])) {
			$event_info[$param['name']] = $param['value'];
		} else if (isset($param['boolValue'])) {
			$event_info[$param['name']] = $param['boolValue'];
		} else if (isset($param['multiValue'])) {
			$event_info[$param['name']] = $param['multiValue'];
		}
	}

	$doc_title = $event_info['doc_title'];
	$doc_type = $event_info['doc_type'];

	if ($doc_event_type == 'access') {
		

		if ($secondary_field) {
			$doc_primary = is_array($event_info[$primary_field]) ? $event_info[$primary_field][0] : $event_info[$primary_field];
			$doc_secondary = is_array($event_info[$secondary_field]) ? $event_info[$secondary_field][0] : $event_info[$secondary_field];
			
			$string_vals = array(
				$doc_primary,
				$doc_secondary
			);
		} else {
			$string_vals = array($doc_title);
		}

		$doc_event_string = elgg_echo("members-extender:drive:{$doc_event_action}", $string_vals);
	} else {
		$doc_event_string = elgg_echo('members-extender:drive:permissions', array($doc_title));
	}

	return array(
		'doc_type' => $doc_type,
		'doc_title' => $doc_title,
		'doc_event_type' => $doc_event_type,
		'doc_event_action' => $doc_event_action,
		'doc_event_string' => $doc_event_string,
		'doc_event_date' => $doc_event_date
	);
}