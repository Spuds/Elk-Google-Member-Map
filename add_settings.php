<?php

/**
 * @name      Google Member Map
 * @copyright (c) 2011-2021 Spuds
 * @license   MPL 1.1 http://mozilla.org/MPL/1.1/
 *
 * @version 1.0.8
 *
 */

// If we have found SSI.php and we are outside of ELK, then we are running standalone.
if (file_exists(__DIR__ . '/SSI.php') && !defined('ELK'))
{
	require_once(__DIR__ . '/SSI.php');
}
elseif (!defined('ELK')) // If we are outside ELK and can't find SSI.php, then throw an error
{
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as Elkarte\'s SSI.php.');
}

$db = database();

global $modSettings;

// List settings here in the format: setting_key => default_value.  Escape any "s. (" => \")
$mod_settings = array(
	'googleMap_Enable' => 0,
	'googleMap_Key' => '',
	'googleMap_EnableLegend' => 1,
	'googleMap_PinGender' => 0,
	'googleMap_KMLoutput_enable' => 0,
	'googleMap_PinNumber' => 250,
	'googleMap_Type' => 'G_HYBRID_MAP',
	'googleMap_Sidebar' => 'right',
	'googleMap_PinBackground' => '66FF66',
	'googleMap_PinForeground' => '202020',
	'googleMap_PinStyle' => 'plainpin',
	'googleMap_PinSize' => 25,
	'googleMap_DefaultLat' => 39.0,
	'googleMap_DefaultLong' => -95.0,
	'googleMap_DefaultZoom' => 4,
	'googleMap_EnableClusterer' => 1,
	'googleMap_MinMarkerPerCluster' => 5,
	'googleMap_MinMarkertoCluster' => 90,
	'googleMap_GridSize' => 30,
	'googleMap_MaxNumClusters' => 10,
	'googleMap_MaxLinesCluster' => 10,
	'googleMap_ClusterBackground' => 'FF66FF',
	'googleMap_ClusterForeground' => '202020',
	'googleMap_ClusterSize' => 35,
	'googleMap_ClusterStyle' => 'plainpin',
	'googleMap_ClusterShadow' => 1,
	'googleMap_BoldMember' => 1,
	'googleMap_ButtonLocation' => 'calendar',
);

// Update mod settings if applicable
foreach ($mod_settings as $new_setting => $new_value)
{
	if (!isset($modSettings[$new_setting]))
	{
		updateSettings(array($new_setting => $new_value));
	}
}

// Settings to create the new tables...
$tables = array();

// Add a row to an existing table
$rows = array();

// Add a column to an existing table
$columns = array();
$dbtbl = db_table();
$columns[] = array(
	'table_name' => '{db_prefix}members',
	'if_exists' => 'ignore',
	'error' => 'fatal',
	'parameters' => array(),
	'column_info' => array(
		'name' => 'longitude',
		'auto' => false,
		'default' => 0,
		'type' => 'decimal(18,15)',
		'null' => true,
	)
);
$columns[] = array(
	'table_name' => '{db_prefix}members',
	'if_exists' => 'ignore',
	'error' => 'fatal',
	'parameters' => array(),
	'column_info' => array(
		'name' => 'latitude',
		'auto' => false,
		'default' => 0,
		'type' => 'decimal(18,15)',
		'null' => true,
	)
);
$columns[] = array(
	'table_name' => '{db_prefix}members',
	'if_exists' => 'ignore',
	'error' => 'fatal',
	'parameters' => array(),
	'column_info' => array(
		'name' => 'pindate',
		'auto' => false,
		'default' => 0,
		'type' => 'int',
		'size' => 10,
		'null' => false,
	)
);

foreach ($tables as $table)
{
	$dbtbl->db_create_table($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);
}

foreach ($rows as $row)
{
	$db->insert($row['method'], $row['table_name'], $row['columns'], $row['data'], $row['keys']);
}

foreach ($columns as $column)
{
	$dbtbl->db_add_column($column['table_name'], $column['column_info'], $column['parameters'], $column['if_exists'], $column['error']);
}

// Initialize the groups array with 'ungrouped members' (ID: 0).
$groups = array(0);

// Get all the non-postcount based groups.
$request = $db->query('', '
	SELECT 
	    id_group
	FROM {db_prefix}membergroups
	WHERE min_posts = -1');

while ($row = $db->fetch_assoc($request))
{
	$groups[] = $row['id_group'];
}

// Give them all their new map permissions to make it easy for the admin.
$db->query('', '
	INSERT IGNORE INTO {db_prefix}permissions
		(permission, id_group, add_deny)
	VALUES
		("googleMap_view", ' . implode(', 1),("googleMap_view", ', $groups) . ', 1)');

$db->query('', '
	INSERT IGNORE INTO {db_prefix}permissions
		(permission, id_group, add_deny)
	VALUES
		("googleMap_place", ' . implode(', 1),("googleMap_place", ', $groups) . ', 1)');

if (ELK === 'SSI')
{
	echo 'Congratulations! You have successfully installed this Addon!';
}
