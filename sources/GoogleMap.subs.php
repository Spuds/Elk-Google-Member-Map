<?php

/**
 * @package "Google Member Map" Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2011-2022 Spuds
 * @license This Source Code is subject to the terms of the Mozilla Public License
 * version 1.1 (the "License"). You can obtain a copy of the License at
 * http://mozilla.org/MPL/1.1/.
 *
 * @version 1.0.6
 *
 */

/**
 * Finds the total number of pins that have been added to the map
 */
function gmm_pinCount()
{
	$db = database();

	// Lets find number of members that have placed their map pin for the template
	$request = $db->query('', '
		SELECT 
		    COUNT(*) as TOTAL
		FROM {db_prefix}members
		WHERE latitude <> false AND longitude <> false',
		array()
	);
	list($totalSet) = $db->fetch_row($request);
	$db->free_result($request);

	return $totalSet;
}

/**
 * Loads the member id's for a group of pins
 * Will load all pins or limit based off the max pins to show setting
 *
 * @param boolean $loadAll set to true to always load all member pins
 * @return array
 */
function gmm_loadPins($loadAll = false)
{
	global $modSettings;

	$db = database();

	$totalPins = gmm_pinCount();

	// Can we show all these pins or is a limit set?
	if (!$loadAll && !empty($modSettings['googleMap_PinNumber']) && $totalPins >= $modSettings['googleMap_PinNumber'])
	{
		// More pins than allowed, show so load the data up at random to the number set in the admin panel
		$query = '
		SELECT 
			id_member
		FROM {db_prefix}members
		WHERE latitude <> false 
			AND longitude <> false
		ORDER BY RAND()
		LIMIT 0, {int:max_pins_to_show}';
	}
	else
	{
		// Showing them all, load everyone ... with recently moved as first in the list
		$query = '
		SELECT 
		    id_member, real_name, IF(pindate > {int:last_week}, pindate, 0) AS pindate
		FROM {db_prefix}members
		WHERE latitude <> false 
		    AND longitude <> false
		ORDER BY pindate DESC, real_name ASC';
	}

	// Request defined, lets make the query
	$request = $db->query('',
		$query,
		array(
			'last_week' => time() - (7 * 24 * 60 * 60),
			'max_pins_to_show' => $modSettings['googleMap_PinNumber'] ?? 0,
		)
	);

	// Load the pins
	$temp = array();
	while ($row = $db->fetch_assoc($request))
	{
		$temp[] = $row['id_member'];
	}
	$db->free_result($request);

	return $temp;
}
