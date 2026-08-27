<?php

/**
 * @package "Google Member Map" Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2011-2025 Spuds
 * @license This Source Code is subject to the terms of the Mozilla Public License
 * version 1.1 (the "License"). You can obtain a copy of the License at
 * https://mozilla.org/MPL/1.1/.
 *
 * @version 2.0.0
 *
 */

use ElkArte\Languages\Txt;
use ElkArte\Helper\HttpReq;
use ElkArte\Member;
use ElkArte\SettingsForm\SettingsForm;

/**
 * integrate_member_context hook
 *
 * - Called from Member.php
 * - Used to add items to the $memberContext array
 *
 * @param Member $member
 */
function imc_googlemap($member)
{
	// Prepare the data for use in javaScript
	$member->mergeWith([
		'googleMap' => [
			'latitude' => !isset($member['latitude']) ? 0 : (float) $member['latitude'],
			'longitude' => !isset($member['longitude']) ? 0 : (float) $member['longitude'],
			'pindate' => $member['pindate'] ?? '',
		]
	]);
}

/**
 * integrate_load_member_data
 *
 * - Called from MemberLoader.php
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
		$select_columns .= ', mem.latitude, mem.longitude, mem.pindate';
	}
}

/**
 * integrate_load_profile_fields
 *
 * - Called from ProfileFields.php
 * - Used to add additional fields to profile createlist
 *
 * @param array $profile_fields
 */
function ilpf_googlemap(&$profile_fields)
{
	// Our callback_func template is here
	theme()->getTemplates()->load('GoogleMap');
	loadCSSFile('GoogleMap.css', ['stale' => '?R200']);

	$profile_fields += [
		'latitude' => [
			'type' => 'callback',
			'callback_func' => 'googlemap_modify',
			'permission' => 'googleMap_place',
			'input_validate' => static function () {
				global $profile_vars, $cur_profile;

				$req = HttpReq::instance();

				// Changing / Updating
				if ($req->getPost('longitude') && $req->getPost('longitude','floatval') !== (float) $cur_profile['longitude'])
				{
					$profile_vars['pindate'] = time();
					$cur_profile['pindate'] = $profile_vars['pindate'];

					$profile_vars['longitude'] = $req->longitude;
					$cur_profile['longitude'] = $profile_vars['longitude'];
				}

				if ($req->getPost('latitude') && $req->getPost('latitude','floatval') !== (float) $cur_profile['latitude'])
				{
					$profile_vars['pindate'] = time();
					$cur_profile['pindate'] = $profile_vars['pindate'];

					$profile_vars['latitude'] = $req->latitude;
					$cur_profile['latitude'] = $profile_vars['latitude'];
				}

				return true;
			},
			'preload' => function () {
				global $context, $cur_profile;

				/* @var \ElkArte\member $context['member'] */
				$context['member']->googleMap = array_merge($context['member']->googleMap, [
					'latitude' => (float) $cur_profile->latitude,
					'longitude' => (float) $cur_profile->longitude,
					'pindate' => $cur_profile->pindate ?? 0,
				]);

				return true;
			},
		]
	];
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
	$fields = elk_array_insert($fields, 'website_title', ['latitude', 'hr'], 'before', false, false);
}

/**
 * integrate_menu_buttons
 *
 * - Menu Button hook, called from MenuContext.php
 * - used to add top menu buttons
 *
 * @param array $buttons
 * @param int $menu_count
 */
function imb_googlemap(&$buttons, &$menu_count)
{
	global $txt, $modSettings;

	Txt::load('MemberMap');

	// Where do we want to place our button
	$insert_after = 'memberlist';

	// Define the new menu item(s), this will call for MemberMap
	$new_menu = [
		'GoogleMap' => [
			'title' => $txt['googleMap'],
			'href' => getUrl('action', ['action' => 'MemberMap']),
			'show' => !empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'),
		]
	];

	$buttons['home']['sub_buttons'] = elk_array_insert($buttons['home']['sub_buttons'], $insert_after, $new_menu, 'after');
}

/**
 * ilp_googlemap()
 *
 * - Permissions hook, integrate_load_permissions, called from PermissionManager.php
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
	$permissionList['membergroup']['googleMap_view'] = [false, 'general', 'view_basic_info'];
	$permissionList['membergroup']['googleMap_place'] = [false, 'general', 'view_basic_info'];
}

/**
 * Help hook, integrate_quickhelp, called from Help.php
 * Used to add in additional help languages for use in the admin quickhelp
 */
function ilqh_googlemap()
{
	// Load the GoogleMap Help file.
	Txt::load('MemberMap');
}

/**
 * iaa_googlemap()
 *
 * - Admin Hook, integrate_admin_areas, called from Menu.php
 * - Used to add/modify admin menu areas. Here we add a new menu subsection to the addons addonsettings area
 *
 * @param array $admin_areas
 */
function iaa_googlemap($admin_areas)
{
	global $txt;

	Txt::load('MemberMap');

	/* @var \ElkArte\Menu\Menu $admin_areas */
	$admin_areas->insertSubsection('addons', 'addonsettings', 'googlemap', [$txt['googleMap']]);
}

/**
 * imm_googlemap()
 *
 * - Addons hook, integrate_sa_modify_modifications, called from AddonSettings.controller
 *
 * @param array $sub_actions
 */
function imm_googlemap(&$sub_actions)
{
	// The key is the subaction, the value is an array of the file and function to call.
	$sub_actions['googlemap'] = [
		'dir' => 'ADDONSDIR\MemberMap',
		'file' => 'MemberMapIntegrate.php',
		'function' => 'ModifyGoogleMapSettings'
	];
}

/**
 * integrate_profile_summary,
 *
 * - called from ProfileInfo.php
 */
function iprofs_googlemap()
{
	global $context, $modSettings;

	if (!empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'))
	{
		theme()->getTemplates()->load('GoogleMap');
		$context['summarytabs']['summary']['templates'] = elk_array_insert($context['summarytabs']['summary']['templates'], 1, ['gmm'], 'after');
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

	$_req = HttpReq::instance();
	Txt::load('MemberMap');

	if ($context[$context['admin_menu_name']]['current_subsection'] === 'googlemap')
	{
		$context[$context['admin_menu_name']]['object']->prepareTabData([
			'title' => $txt['googleMap'],
			'description' => $txt['googleMap_desc'],
			'class' => 'i-clip']
		);
	}

	// Instantiate the form
	$settingsForm = new SettingsForm(SettingsForm::DB_ADAPTER);

	$config_vars = [
		// Map - On or off?
		['check', 'googleMap_Enable', 'postinput' => $txt['googleMap_license']],
		['text', 'googleMap_Key', 'postinput' => $txt['googleMap_Key_desc']],
		// Default Location/Zoom/Map Controls/etc.
		['title', 'googleMap_MapSettings'],
		['float', 'googleMap_DefaultLat', 10, 'postinput' => $txt['googleMap_DefaultLat_info']],
		['float', 'googleMap_DefaultLong', 10, 'postinput' => $txt['googleMap_DefaultLong_info']],
		['int', 'googleMap_DefaultZoom', 'helptext' => $txt['googleMap_DefaultZoom_Info']],
		['select', 'googleMap_Type', [
			'ROADMAP' => $txt['googleMap_roadmap'],
			'SATELLITE' => $txt['googleMap_satellite'],
			'HYBRID' => $txt['googleMap_hybrid']]
		],
		['check', 'googleMap_EnableLegend'],
		['int', 'googleMap_PinNumber', 'subtext' => $txt['googleMap_PinNumber_info']],
		['select', 'googleMap_Sidebar', [
			'none' => $txt['googleMap_nosidebar'],
			'right' => $txt['googleMap_rightsidebar'],
			'left' => $txt['googleMap_leftsidebar']]
		],
		['check', 'googleMap_BoldMember'],
		// Member Pin Style
		['title', 'googleMap_MemeberpinSettings'],
		['text', 'googleMap_PinBackground', 6],
		['text', 'googleMap_PinForeground', 6],
		['int', 'googleMap_PinSize', 2],
		// Clustering Options
		['title', 'googleMap_ClusterpinSettings'],
		['check', 'googleMap_EnableClusterer', 'helptext' => $txt['googleMap_EnableClusterer_info']],
		['int', 'googleMap_MinMarkerPerCluster'],
		['int', 'googleMap_MinMarkertoCluster'],
		['int', 'googleMap_GridSize'],
		['check', 'googleMap_ScalableCluster', 'helptext' => $txt['googleMap_ScalableCluster_info']],
		// Clustering Style
		['title', 'googleMap_ClusterpinStyle'],
		['text', 'googleMap_ClusterBackground', 6],
		['text', 'googleMap_ClusterForeground', 6],
		['select', 'googleMap_ClusterStyle', [
			'googleMap_plainpin' => $txt['googleMap_plainpin'],
			'googleMap_zonepin' => $txt['googleMap_zonepin'],
			'googleMap_peepspin' => $txt['googleMap_peepspin'],
			'googleMap_talkpin' => $txt['googleMap_talkpin']]
		],
		['int', 'googleMap_ClusterSize', '2'],
	];

	// Load the settings to the form class
	$settingsForm->setConfigVars($config_vars);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();
		$settingsForm->setConfigValues((array) $_req->post);
		$settingsForm->save();

		redirectexit('action=admin;area=addonsettings;sa=googlemap');
	}

	// Continue on to the settings template
	$context['post_url'] = $scripturl . '?action=admin;area=addonsettings;save;sa=googlemap';
	$context['settings_title'] = $txt['googleMap'];
	loadJavascriptFile('/gmm/jscolor.min.js');
	theme()->addInlineJavascript(('
		document.getElementById(\'googleMap_PinBackground\').setAttribute("data-jscolor", "");
		document.getElementById(\'googleMap_PinForeground\').setAttribute("data-jscolor", "");
		document.getElementById(\'googleMap_ClusterBackground\').setAttribute("data-jscolor", "");
		document.getElementById(\'googleMap_ClusterForeground\').setAttribute("data-jscolor", "");'),
		true);

	$settingsForm->prepare();
}

/**
 * Who's online hook, integrate_whos_online, called from who.subs
 * translates custom actions to allow show what area a user is in
 *
 * @param array $actions
 * @return string
 */
function gmm_integrate_whos_online($actions)
{
	global $modSettings, $txt;

	if (isset($actions['action']) && $actions['action'] === 'MemberMap' && !empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'))
	{
		Txt::load('MemberMap');

		return (isset($actions['sa']) && $actions['sa'] === 'kml') ? $txt['whoall_kml'] : $txt['whoall_googlemap'];
	}

	return '';
}
