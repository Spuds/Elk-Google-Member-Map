<?php

/**
 * @package "Google Member Map" Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2011-2026 Spuds
 * @license This Source Code is subject to the terms of the Mozilla Public License
 * version 1.1 (the "License"). You can obtain a copy of the License at
 * http://mozilla.org/MPL/1.1/.
 *
 * @version 2.0.0
 *
 */

namespace Addons\MemberMap;

use ElkArte\AbstractController;
use ElkArte\Errors\Errors;
use ElkArte\Helper\Util;
use ElkArte\Http\Headers;
use ElkArte\Languages\Txt;
use ElkArte\MembersList;
use ElkArte\User;

class MemberMap extends AbstractController
{
	/** @var string Cluster pin style */
	protected $_cpin;

	/**
	 * Entry point function for MemberMap, permission checks, makes sure its on
	 */
	public function pre_dispatch()
	{
		global $modSettings;

		// If GMM is disabled, we don't go any further
		if (empty($modSettings['googleMap_Enable']))
		{
			Errors::instance()->fatal_lang_error('feature_disabled', true);
		}

		// Some things we will need
		Txt::load('MemberMap');
		require_once(ADDONSDIR . '/MemberMap/MemberMap.subs.php');

		// Are we allowed to view the map?
		isAllowedTo('googleMap_view');
	}

	/**
	 * Default action method, if a specific method wasn't
	 * directly called already. Simply forwards to main.
	 */
	public function action_index()
	{
		$this->action_gmm_main();
	}

	/**
	 * gmm_main()
	 *
	 * Calls the googlemap template which in turn makes the
	 * xml or js request for data
	 */
	public function action_gmm_main()
	{
		global $context, $txt, $modSettings;

		// Load up our template and style sheet
		theme()->getTemplates()->load('GoogleMap');
		loadCSSFile('GoogleMap.css', ['stale' => '?R200']);

		// Load the number of member pins
		$totalSet = gmm_pinCount();
		$context['total_pins'] = $totalSet;

		// Create the pins for template use
		if (!empty($modSettings['googleMap_EnableLegend']))
		{
			$this->gmm_buildpins();
		}

		// Set up our JS Vars and base scripts
		$this->gmm_buildVars();
		loadJavascriptFile(['https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js', '/gmm/gmm.js']);

		// The main Google Maps script, it will call our initialize function (in gmm.js)
		loadJavascriptFile('//maps.google.com/maps/api/js?key=' . $modSettings['googleMap_Key'] . '&loading=async&callback=initialize', ['async' => true], 'sensor.js');

		// Show the template
		$context['place_pin'] = allowedTo('googleMap_place');
		$context['sub_template'] = 'map';
		$context['page_title'] = $txt['googleMap'];
	}

	public function gmm_buildVars()
	{
		global $modSettings, $txt, $context;

		// Our push pins are defined from gmm_buildpins
		$this->gmm_buildpins();

		// Validate the specified pin size is not to small
		$m_iconsize = (isset($modSettings['googleMap_PinSize']) && $modSettings['googleMap_PinSize'] > 14) ? $modSettings['googleMap_PinSize'] : 24;
		$c_iconsize = (isset($modSettings['googleMap_ClusterSize']) && $modSettings['googleMap_ClusterSize'] > 14) ? $modSettings['googleMap_ClusterSize'] : 24;

		// Cluster sizing when enabled
		$clusterSize = array_fill(0, 5, $c_iconsize);
		$clusterStyles = [];
		if (!empty($modSettings['googleMap_ScalableCluster']))
		{
			$clusterStyles = [0 => 'googleMap_plainpin', 1 => 'googleMap_zonepin', 2 => 'googleMap_peepspin', 3 => 'googleMap_talkpin'];
			$clusterSize = [$c_iconsize * 1.0, $c_iconsize * 1.3, $c_iconsize * 1.6, $c_iconsize * 1.9, $c_iconsize * 2.2];
		}

		// Move ACP settings into JS vars for use in gmm.js
		theme()->addJavascriptVar([
			'npic_fillColor' => '"#' . $modSettings['googleMap_PinBackground'] . '"',
			'npic_strokeColor' => '"#' . $modSettings['googleMap_PinForeground'] . '"',
			'npic_scale' => round($m_iconsize / 24, 2),
			'cpic_fillColor' => '"#' . $modSettings['googleMap_ClusterBackground'] . '"',
			'cpic_strokeColor' => '"#' . $modSettings['googleMap_ClusterForeground'] . '"',
			'cpic_style' => (int) array_search($modSettings['googleMap_ClusterStyle'], $clusterStyles, true),
			'googleMap_ScalableCluster' => !empty($modSettings['googleMap_ScalableCluster']) ? 'true' : 'false',
			'clusterSize' => json_encode($clusterSize),
			'googleMap_GridSize' => !empty($modSettings['googleMap_GridSize']) ? $modSettings['googleMap_GridSize'] : 2,
			'googleMap_MinMarkerPerCluster' => !empty($modSettings['googleMap_MinMarkerPerCluster']) ? $modSettings['googleMap_MinMarkerPerCluster'] : 20,
			'latlng' => '{lat: ' . (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0) . ', lng: ' . (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0) . '}',
			'googleMap_DefaultLat' => !empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0,
			'googleMap_DefaultLong' => !empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0,
			'googleMap_DefaultZoom' => $modSettings['googleMap_DefaultZoom'],
			'googleMap_Type' => '"' . $modSettings['googleMap_Type'] . '"',
			'googleMap_EnableClusterer' => !empty($modSettings['googleMap_EnableClusterer']) && ($context['total_pins'] > (!empty($modSettings['googleMap_MinMarkertoCluster']) ? $modSettings['googleMap_MinMarkertoCluster'] : 50)) ? 'true' : 'false',
			'googleMap_MaxLinesCluster' => $modSettings['googleMap_MaxLinesCluster'] ?? 10,
			'googleMap_Sidebar' => '"' . $modSettings['googleMap_Sidebar'] . '"',
		]);

		// Clean the txt vars
		theme()->addJavascriptVar([
			'txt_googleMap_xmlerror' => $txt['googleMap_xmlerror'],
			'txt_googleMap_error' => $txt['googleMap_error'],
			'txt_googleMap_Plus' => $txt['googleMap_Plus'],
			'txt_googleMap_Otherpins' => $txt['googleMap_Otherpins'],
			'txt_googleMap_GroupOfPins' => $txt['googleMap_GroupOfPins'],
		], true);
	}

	/**
	 * Creates xml data for use on a map
	 *
	 * - Builds the pin info window content
	 * - Builds the map sidebar layout
	 * - Called from the googlemap JS initialize function via ajax (?action=MemberMap;sa=xml)
	 */
	public function action_xml()
	{
		global $context, $settings, $options, $scripturl, $txt, $modSettings, $user_info;

		// Make sure the buffer is empty
		ob_clean();

		// XML Header
		$headers = Headers::instance();
		$headers
			->removeHeader('all')
			->contentType('application/xml', 'UTF-8')
			->sendHeaders();

		// Lets load in some pin data
		$temp = gmm_loadPins();

		if (empty($temp))
		{
			obExit(false);
		}

		// Begin the XML output
		$last_week = time() - (7 * 24 * 60 * 60);
		echo '<?xml version="1.0" encoding="UTF-8"?', '>
		<markers>';

		// To prevent the avatar being outside the popup info window we set a max div height
		$div_height = max($modSettings['avatar_max_height_external'] ?? 0, $modSettings['avatar_max_height_upload'] ?? 100);

		// Load the data for these 'pined' members
		MembersList::load($temp, false);
		foreach ($temp as $mem)
		{
			$member = MembersList::get($mem);
			$member->loadContext(true);

			if ($member->isEmpty())
			{
				continue;
			}

			// For every member with a pin, build an info bubble ...
			$dataBlurb = '';

			// Guests never get to see this data.
			if (!User::$info->is_guest)
			{
				$dataBlurb = '
			<div class="googleMap">';

				// avatar?
				if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($member['avatar']['image']))
				{
					$dataBlurb .= '
				<div class="gmm_avatar" style="max-height:' . $div_height . 'px">' . $member['avatar']['image'] . '</div>';
				}

				// user info section
				$dataBlurb .= '
				<div class="gmm_poster">
					<ul class="reset">';

				// Show the member's primary group (like 'Administrator') if they have one.
				if (!empty($member['group']))
				{
					$dataBlurb .= '
						<li class="membergroup">' . $member['group'] . '</li>';
				}

				// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
				if ((empty($settings['hide_post_group']) || $member['group'] === '') && $member['post_group'] !== '')
				{
					$dataBlurb .= '
						<li class="postgroup">' . $member['post_group'] . '</li>';
				}

				// groups icons
				$dataBlurb .= '
						<li class="icons">' . $member['group_icons'] . '</li>';

				// show the title, if they have one
				if (!empty($member['title']) && !User::$info->is_guest)
				{
					$dataBlurb .= '
						<li class="title">' . $member['title'] . '</li>';
				}

				// Show the profile, website, email address, and personal message buttons.
				if ($settings['show_profile_buttons'])
				{
					$dataBlurb .= '
						<li>
							<ul>';

					// Don't show an icon if they haven't specified a website.
					if ($member['website']['url'] !== '' && !isset($context['disabled_fields']['website']))
					{
						$dataBlurb .= '
								<li>
									<a href="' . $member['website']['url'] . '" title="' . $member['website']['title'] . '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<i class="icon i-website" title="' . $member['website']['title'] . '"></i>' : $txt['www']) . '
								</li>';
					}

					// Don't show the email address if they want it hidden.
					if (in_array($member['show_email'], ['yes', 'yes_permission_override', 'no_through_forum']))
					{
						$dataBlurb .= '
								<li>
									<a href="mailto:' . $member['email'] . '" rel="nofollow">' . ($settings['use_image_buttons'] ? '<i class="icon i-envelope-o" title="' . $txt['email'] . '" title="' . $txt['email'] . '"></i>' : $txt['email']) . '
								</li>';
					}

					// Allowed to send PMs and the message is not their own and not from a guest.
					if (allowedTo('pm_send'))
					{
						$dataBlurb .= '
								<li>
									<a href="' . $scripturl . '?action=pm;sa=send;u=' . $member['id'] . '" title="' . $member['online']['member_online_text'] . '"><i class="icon ' . ($member['online']['is_online'] ? 'i-dot i-dot-green' : 'i-dot') . '" title="' . $member['online']['text'] . '"></i></a>
								</li>';
					}

					$dataBlurb .= '
							</ul>
						</li>';
				}

				$dataBlurb .= '
					</ul>
				</div>';

				// Show their personal text?
				if (!empty($settings['show_blurb']) && !empty($member['cust_blurb']))
				{
					$dataBlurb .= '
				<br class="clear" />' . $member['cust_blurb'];
				}

				$dataBlurb .= '
			</div>';
			}

			// Build the header for the info bubble
			$header = '
				<h1>
					<a href="' . $member['online']['href'] . '" title="' . $member['online']['text'] . '">
						<i class="' . ($member['online']['is_online'] ? 'iconline' : 'icoffline') . '" title="' . $member['online']['text'] . '"></i>
					<a href="' . $member['href'] . '">' . $member['name'] . '</a>
				</h1>';

			// Let's bring it all together lat/lng/gender/header/label are attributes of the marker
			$members = '<marker lat="' . round($member['googleMap']['latitude'], 8) . '" lng="' . round($member['googleMap']['longitude'], 8) . '"';
			$members .= ' gender="0"';
			$members .= ' header="' . Util::htmlspecialchars($header) . '" ';

			if (!empty($modSettings['googleMap_BoldMember']) && $member['googleMap']['pindate'] >= $last_week)
			{
				$members .= ' label="[b]' . $member['name'] . '[/b]"><![CDATA[' . $dataBlurb . ']]></marker>';
			}
			else
			{
				$members .= ' label="' . $member['name'] . '"><![CDATA[' . $dataBlurb . ']]></marker>';
			}

			echo $members;
		}

		echo '
		</markers>';

		// Ok we should be done with output, jump to the template
		obExit(false);
	}

	/**
	 * Does the majority of work in determining how the map pin should look based on admin settings
	 */
	private function gmm_buildpins()
	{
		global $modSettings;

		// Lets work out all those options so this works
		$modSettings['googleMap_ClusterBackground'] = $this->gmm_validate_color('googleMap_ClusterBackground', 'FF66FF');
		$modSettings['googleMap_ClusterForeground'] = $this->gmm_validate_color('googleMap_ClusterForeground', '202020');
		$modSettings['googleMap_PinBackground'] = $this->gmm_validate_color('googleMap_PinBackground', '66FF66');
		$modSettings['googleMap_PinForeground'] = $this->gmm_validate_color('googleMap_PinForeground', '202020');

		// What style cluster pins have been chosen
		$this->_cpin = $this->gmm_validate_pin('googleMap_ClusterStyle', 'd_map_pin');
		$modSettings['cpin'] = $this->_cpin;
	}

	/**
	 * Makes sure we have a 6digit hex for the color definitions or sets a default value
	 *
	 * @param string $color
	 * @param string $default
	 * @return string
	 */
	private function gmm_validate_color($color, $default)
	{
		global $modSettings;

		// No leading #'s please
		if (strpos($modSettings[$color], '#') === 0)
		{
			$modSettings[$color] = substr($modSettings[$color], 1);
		}

		// Is it a hex, it needs to be!
		if (!preg_match('~^[a-f0-9]{6}$~i', $modSettings[$color]))
		{
			$modSettings[$color] = $default;
		}

		return strtoupper($modSettings[$color]);
	}

	/**
	 * Outputs the correct pin type based on selection
	 *
	 * @param string $area
	 * @param string $default
	 * @return string
	 */
	private function gmm_validate_pin($area, $default)
	{
		global $modSettings;

		$pin = $default;

		// Return the type of pin requested
		if (isset($modSettings[$area]))
		{
			switch ($modSettings[$area])
			{
				case 'googleMap_plainpin':
					$pin = 'd_map_pin';
					break;
				case 'googleMap_zonepin':
					$pin = 1;
					break;
				case 'googleMap_peepspin':
					$pin = 2;
					break;
				case 'googleMap_talkpin':
					$pin = 3;
					break;
				default:
					$pin = 'd_map_pin';
			}
		}

		return $pin;
	}
}
