<?php

/**
 * @name      Google Member Map
 * @copyright Spuds
 * @license   MPL 1.1 http://mozilla.org/MPL/1.1/
 *
 * @version 1.0.7
 *
 */

// If we have found SSI.php and we are outside of Elkarte, then we are running standalone.
if (file_exists(__DIR__ . '/SSI.php') && !defined('ELK'))
{
	require_once(__DIR__ . '/SSI.php');
}
elseif (!defined('ELK')) // If we are outside Elkarte and can't find SSI.php, then throw an error
{
	die('<b>Error:</b> Cannot uninstall - please verify you put this file in the same place as Elkarte\'s SSI.php.');
}

global $modSettings;

// Only do database changes on uninstall if requested.
if (!empty($_POST['do_db_changes']))
{
	$db = database();

	// List all mod settings here to REMOVE
	$settings_to_remove = array(
		'googleMap_Enable',
		'googleMap_Key',
		'googleMap_EnableLegend',
		'googleMap_Key',
		'googleMap_PinGender',
		'googleMap_KMLoutput_enable',
		'googleMap_PinNumber',
		'googleMap_Type',
		'googleMap_NavType',
		'googleMap_Sidebar',
		'googleMap_PinBackground',
		'googleMap_PinForeground',
		'googleMap_PinStyle',
		'googleMap_PinShadow',
		'googleMap_PinText',
		'googleMap_PinIcon',
		'googleMap_PinSize',
		'googleMap_DefaultLat',
		'googleMap_DefaultLong',
		'googleMap_DefaultZoom',
		'googleMap_EnableClusterer',
		'googleMap_MinMarkerCluster',
		'googleMap_MaxVisMarker',
		'googleMap_MaxNumClusters',
		'googleMap_MaxLinesCluster',
		'googleMap_ClusterBackground',
		'googleMap_ClusterForeground',
		'googleMap_ClusterSize',
		'googleMap_ClusterStyle',
		'googleMap_ClusterShadow',
		'googleMap_ClusterText',
		'googleMap_ClusterIcon',
		'googleMap_BoldMember',
	);

	// Remove the modSettings from the settings table
	if (count($settings_to_remove) > 0)
	{
		// Remove the mod_settings if applicable, first the session
		foreach ($settings_to_remove as $setting)
		{
			if (isset($modSettings[$setting]))
			{
				unset($modSettings[$setting]);
			}
		}

		// And now the database values
		$db->query('', '
			DELETE FROM {db_prefix}settings
			WHERE variable IN ({array_string:settings})',
			array(
				'settings' => $settings_to_remove,
			)
		);

		// Make sure the cache is reset as well
		updateSettings(array(
			'settings_updated' => time(),
		));
	}

	if (ELK === 'SSI')
	{
		echo 'Congratulations! You have successfully removed this Addon!';
	}
}
