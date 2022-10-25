<?php

/**
 * @package "Google Member Map" Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2011-2022 Spuds
 * @license This Source Code is subject to the terms of the Mozilla Public License
 * version 1.1 (the "License"). You can obtain a copy of the License at
 * http://mozilla.org/MPL/1.1/.
 *
 * @version 1.0.7
 *
 */

/**
 * integrate_member_context hook
 *
 * - Called from load.php
 * - Used to add items to the $memberContext array
 *
 * @param int $user
 * @param mixed $display_custom_fields
 */
function imc_googlemap($user, $display_custom_fields)
{
	global $memberContext, $user_profile;

	$memberContext[$user] += array(
		'googleMap' => array(
			'latitude' => !isset($user_profile[$user]['latitude']) ? 0 : (float) $user_profile[$user]['latitude'],
			'longitude' => !isset($user_profile[$user]['longitude']) ? 0 : (float) $user_profile[$user]['longitude'],
			'pindate' => !isset($user_profile[$user]['pindate']) ? '' : $user_profile[$user]['pindate'],
		)
	);
}

/**
 * integrate load member data
 *
 * - Called from load.php
 * - Used to add columns / tables to the query so additional data can be loaded for a set
 *
 * @param string $select_columns
 * @param array $select_tables
 * @param string $set
 */
function ilmd_googlemap(&$select_columns, &$select_tables, $set)
{
	if ($set === 'profile' || $set === 'normal')
	{
		$select_columns .= ',mem.latitude, mem.longitude, mem.pindate';
	}
}

/**
 * integrate_load_profile_fields
 *
 * - Called from profile.subs
 * - Used to add additional fields to the profile createlist
 *
 * @param array $profile_fields
 */
function ilpf_googlemap(&$profile_fields)
{
	// Our callback_func template is here
	LoadTemplate('GoogleMap');
	loadCSSFile('GoogleMap.css', array('stale' => '?R107'));

	$profile_fields += array(
		'latitude' => array(
			'type' => 'callback',
			'callback_func' => 'googlemap_modify',
			'permission' => 'googleMap_place',
			'input_validate' => function (&$value) {
				global $profile_vars, $cur_profile;

				// Set latitude to a float value
				$value = (float) $value;

				// Fix up longitude as well
				$profile_vars['longitude'] = !empty($_POST['longitude']) ? (float) $_POST['longitude'] : 0;
				$cur_profile['longitude'] = !empty($_POST['longitude']) ? (float) $_POST['longitude'] : 0;

				// Right now is a good time for the pin date ;)
				$pintime = time();
				$profile_vars['pindate'] = $pintime;
				$cur_profile['pindate'] = $pintime;

				return true;
			},
			'preload' => function () {
				global $context, $cur_profile;

				$context['member']['googleMap']['latitude'] = (float) $cur_profile['latitude'];
				$context['member']['googleMap']['longitude'] = (float) $cur_profile['longitude'];
				$context['member']['googleMap']['pindate'] = $cur_profile['pindate'];

				return true;
			},
		)
	);
}

/**
 * Profile fields hook, integrate_' . $hook . '_profile_fields
 *
 * - Called from Profile.subs.php / setupProfileContext
 * - Used to add additional sections to the profile context for a page load, here we
 * add latitude to be displayed, its defined by integrate_load_profile_fields above
 *
 * @param array $fields
 */
function ifpf_googlemap(&$fields)
{
	$fields = elk_array_insert($fields, 'website_title', array('latitude', 'hr'), 'before', false, false);
}

/**
 * integrate_menu_buttons
 *
 * - Menu Button hook, called from subs.php
 * - used to add top menu buttons
 *
 * @param array $buttons
 * @param int $menu_count
 */
function imb_googlemap(&$buttons, &$menu_count)
{
	global $txt, $scripturl, $modSettings;

	loadlanguage('GoogleMap');

	// Where do we want to place our button (new menu layout, this needs to be redone)
	// $insert_after = empty($modSettings['googleMap_ButtonLocation']) ? 'memberlist' : $modSettings['googleMap_ButtonLocation'];
	$insert_after = 'memberlist';

	// Define the new menu item(s), this will call for GoogleMap.controller
	$new_menu = array(
		'GoogleMap' => array(
			'title' => $txt['googleMap'],
			'href' => $scripturl . '?action=GoogleMap',
			'show' => !empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'),
		)
	);

	$buttons['home']['sub_buttons'] = elk_array_insert($buttons['home']['sub_buttons'], $insert_after, $new_menu, 'after');
}

/**
 * integrate_profile_save
 *
 * - Profile save fields hook, called from Profile.controller.php
 * - used to prep and check variables before a profile update is saved
 *
 * @param array $profile_vars
 * @param array $post_errors
 * @param int $memID
 */
function ips_googlemap(&$profile_vars, &$post_errors, $memID)
{
	if (isset($_POST['latitude']))
	{
		$profile_vars['latitude'] = $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : 0;
	}

	if (isset($_POST['longitude']))
	{
		$profile_vars['longitude'] = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : 0;
	}
}

/**
 * ilp_googlemap()
 *
 * - Permissions hook, integrate_load_permissions, called from ManagePermissions.php
 * - used to add new permissions
 *
 * @param array $permissionGroups
 * @param array $permissionList
 * @param array $leftPermissionGroups
 * @param array $hiddenPermissions
 * @param array $relabelPermissions
 */
function ilp_googlemap(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	$permissionList['membergroup']['googleMap_view'] = array(false, 'general', 'view_basic_info');
	$permissionList['membergroup']['googleMap_place'] = array(false, 'general', 'view_basic_info');
}

/**
 * Help hook, integrate_quickhelp, called from help.controller.php
 * Used to add in additional help languages for use in the admin quickhelp
 */
function ilqh_googlemap()
{
	// Load the GoogleMap Help file.
	loadLanguage('GoogleMap');
}

/**
 * iaa_googlemap()
 *
 * - Admin Hook, integrate_admin_areas, called from Admin.php
 * - used to add/modify admin menu areas
 *
 * @param array $admin_areas
 */
function iaa_googlemap(&$admin_areas)
{
	global $txt;

	loadlanguage('GoogleMap');
	$admin_areas['config']['areas']['addonsettings']['subsections']['googlemap'] = array($txt['googleMap']);
}

/**
 * imm_googlemap()
 *
 * - Addons hook, integrate_sa_modify_modifications, called from AddonSettings.controller
 * - used to add new menu screens areas.
 *
 * @param array $sub_actions
 */
function imm_googlemap(&$sub_actions)
{
	$sub_actions['googlemap'] = array(
		'dir' => SOURCEDIR,
		'file' => 'GoogleMapIntegration.php',
		'function' => 'ModifyGoogleMapSettings'
	);
}

/**
 * integrate_profile_summary,
 *
 * - called from ProfileInfo.controller.php
 */
function iprofs_googlemap()
{
	global $context, $modSettings;

	if (!empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'))
	{
		loadTemplate('GoogleMap');
		$context['summarytabs']['summary']['templates'] = elk_array_insert($context['summarytabs']['summary']['templates'], 1, array('gmm'), 'after');
	}
}

/**
 * ModifyGoogleMapSettings()
 *
 * - Defines our settings array and uses our settings class to manage the data
 */
function ModifyGoogleMapSettings()
{
	global $txt, $scripturl, $context;

	loadlanguage('GoogleMap');
	$context[$context['admin_menu_name']]['tab_data']['tabs']['googlemap']['description'] = $txt['googleMap_desc'];

	// Lets build a settings form
	require_once(SUBSDIR . '/SettingsForm.class.php');

	// Instantiate the form
	$gmmSettings = new Settings_Form();

	$config_vars = array(
		// Map - On or off?
		array('check', 'googleMap_Enable', 'postinput' => $txt['googleMap_license']),
		array('text', 'googleMap_Key', 'postinput' => $txt['googleMap_Key_desc']),
		// Default Location/Zoom/Map Controls/etc.
		array('title', 'googleMap_MapSettings'),
		/* New menu structure, need to rethink what makes sense here,
		   for now it will be under members in community
		array('select', 'googleMap_ButtonLocation', array(
				'home' => $txt['home'],
				'help' => $txt['help'],
				'search' => $txt['search'],
				'login' => $txt['login'],
				'register' => $txt['register'],
				'calendar' => $txt['calendar'],
				'profile' => $txt['profile'],
				'pm' => $txt['pm_short'])
		),
		*/
		array('float', 'googleMap_DefaultLat', 10, 'postinput' => $txt['googleMap_DefaultLat_info']),
		array('float', 'googleMap_DefaultLong', 10, 'postinput' => $txt['googleMap_DefaultLong_info']),
		array('int', 'googleMap_DefaultZoom', 'helptext' => $txt['googleMap_DefaultZoom_Info']),
		array('select', 'googleMap_Type', array(
			'ROADMAP' => $txt['googleMap_roadmap'],
			'SATELLITE' => $txt['googleMap_satellite'],
			'HYBRID' => $txt['googleMap_hybrid'])
		),
		array('check', 'googleMap_EnableLegend'),
		array('check', 'googleMap_KMLoutput_enable', 'helptext' => $txt['googleMap_KMLoutput_enable_info']),
		array('int', 'googleMap_PinNumber', 'subtext' => $txt['googleMap_PinNumber_info']),
		array('select', 'googleMap_Sidebar', array(
			'none' => $txt['googleMap_nosidebar'],
			'right' => $txt['googleMap_rightsidebar'],
			'left' => $txt['googleMap_leftsidebar'])
		),
		array('check', 'googleMap_BoldMember'),
		// Member Pin Style
		array('title', 'googleMap_MemeberpinSettings'),
		array('text', 'googleMap_PinBackground', 6),
		array('text', 'googleMap_PinForeground', 6),
		array('int', 'googleMap_PinSize', 2),
		// Clustering Options
		array('title', 'googleMap_ClusterpinSettings'),
		array('check', 'googleMap_EnableClusterer', 'helptext' => $txt['googleMap_EnableClusterer_info']),
		array('int', 'googleMap_MinMarkerPerCluster'),
		array('int', 'googleMap_MinMarkertoCluster'),
		array('int', 'googleMap_GridSize'),
		array('check', 'googleMap_ScalableCluster', 'helptext' => $txt['googleMap_ScalableCluster_info']),
		// Clustering Style
		array('title', 'googleMap_ClusterpinStyle'),
		array('text', 'googleMap_ClusterBackground', 6),
		array('text', 'googleMap_ClusterForeground', 6),
		array('select', 'googleMap_ClusterStyle', array(
			'googleMap_plainpin' => $txt['googleMap_plainpin'],
			'googleMap_zonepin' => $txt['googleMap_zonepin'],
			'googleMap_peepspin' => $txt['googleMap_peepspin'],
			'googleMap_talkpin' => $txt['googleMap_talkpin'])
		),
		array('int', 'googleMap_ClusterSize', '2'),
	);

	// Load the settings to the form class
	$gmmSettings->settings($config_vars);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();
		Settings_Form::save_db($config_vars);
		redirectexit('action=admin;area=addonsettings;sa=googlemap');
	}

	// Continue on to the settings template
	$context['post_url'] = $scripturl . '?action=admin;area=addonsettings;save;sa=googlemap';
	$context['settings_title'] = $txt['googleMap'];
	loadJavascriptFile('jscolor.min.js');
	addInlineJavascript('
		document.getElementById(\'googleMap_PinBackground\').setAttribute("data-jscolor", "");
		document.getElementById(\'googleMap_PinForeground\').setAttribute("data-jscolor", "");
		document.getElementById(\'googleMap_ClusterBackground\').setAttribute("data-jscolor", "");
		document.getElementById(\'googleMap_ClusterForeground\').setAttribute("data-jscolor", "");',
		true);

	Settings_Form::prepare_db($config_vars);
}

/**
 * Whos online hook, integrate_whos_online, called from who.subs
 * translates custom actions to allow show what area a user is in
 *
 * @param string $actions
 * @return string
 */
function gmm_integrate_whos_online($actions)
{
	global $modSettings, $txt;

	if (isset($actions['action']) && $actions['action'] === 'GoogleMap' && !empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'))
	{
		loadlanguage('GoogleMap');

		return (isset($actions['sa']) && $actions['sa'] === 'kml') ? $txt['whoall_kml'] : $txt['whoall_googlemap'];
	}

	return '';
}
