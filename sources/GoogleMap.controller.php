<?php

/**
 * @package "Google Member Map" Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2011-2022 Spuds
 * @license This Source Code is subject to the terms of the Mozilla Public License
 * version 1.1 (the "License"). You can obtain a copy of the License at
 * http://mozilla.org/MPL/1.1/.
 *
 * @version 1.0.8
 *
 */

class GoogleMap_Controller extends Action_Controller
{
	/** @var string Cluster pin style */
	protected $_cpin;

	/**
	 * Entry point function for GMM, permission checks, makes sure its on
	 */
	public function pre_dispatch()
	{
		global $modSettings;

		// If GMM is disabled, we don't go any further
		if (empty($modSettings['googleMap_Enable']))
		{
			fatal_lang_error('feature_disabled', true);
		}

		// Some things we will need
		loadLanguage('GoogleMap');
		require_once(SUBSDIR . '/GoogleMap.subs.php');

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
		loadTemplate('GoogleMap');
		loadCSSFile('GoogleMap.css', array('stale' => '?R107'));

		// Load number of member pins
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
		loadJavascriptFile('//maps.google.com/maps/api/js?key=' . $modSettings['googleMap_Key'] . '&loading=async&callback=initialize', array('async' => true), 'sensor.js');

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
		if (!empty($modSettings['googleMap_ScalableCluster']))
		{
			$clusterSize = [$c_iconsize * 1.0, $c_iconsize * 1.3, $c_iconsize * 1.6, $c_iconsize * 1.9, $c_iconsize * 2.2];
		}

		// Move ACP settings into JS vars for use in gmm.js
		addJavascriptVar([
			'npic_fillColor' => '"#' . $modSettings['googleMap_PinBackground'] . '"',
			'npic_strokeColor' => '"#' . $modSettings['googleMap_PinForeground'] . '"',
			'npic_scale' => round($m_iconsize / 24, 2),
			'cpic_fillColor' => '"#' . $modSettings['googleMap_ClusterBackground'] . '"',
			'cpic_strokeColor' => '"#' . $modSettings['googleMap_ClusterForeground'] . '"',
			'googleMap_ScalableCluster' => !empty($modSettings['googleMap_ScalableCluster']),
			'clusterSize' => json_encode($clusterSize),
			'googleMap_GridSize' => !empty($modSettings['googleMap_GridSize']) ? $modSettings['googleMap_GridSize'] : 2,
			'googleMap_MinMarkerPerCluster' => !empty($modSettings['googleMap_MinMarkerPerCluster']) ? $modSettings['googleMap_MinMarkerPerCluster'] : 20,
			'latlng' => '{lat: ' . (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0) . ', lng: ' . (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0) . '}',
			'googleMap_DefaultLat' => !empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0,
			'googleMap_DefaultLong'	=>  !empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0,
			'googleMap_DefaultZoom' => $modSettings['googleMap_DefaultZoom'],
			'googleMap_Type' => '"' . $modSettings['googleMap_Type'] . '"',
			'googleMap_EnableClusterer' => !empty($modSettings['googleMap_EnableClusterer']) && ($context['total_pins'] > (!empty($modSettings['googleMap_MinMarkertoCluster']) ? $modSettings['googleMap_MinMarkertoCluster'] : 50)) ? 'true' : 'false',
			'googleMap_MaxLinesCluster' => $modSettings['googleMap_MaxLinesCluster'] ?? 10,
			'googleMap_Sidebar' => '"' . $modSettings['googleMap_Sidebar'] . '"',
		]);

		// Clean the txt vars
		addJavascriptVar([
			'txt_googleMap_xmlerror' => $txt['googleMap_xmlerror'],
			'txt_googleMap_error' => $txt['googleMap_error'],
			'txt_googleMap_Plus' => $txt['googleMap_Plus'],
			'txt_googleMap_Otherpins' => $txt['googleMap_Otherpins'],
		], true);
	}

	/**
	 * Creates xml data for use on a map
	 *
	 * - Builds the pin info window content
	 * - Builds the map sidebar layout
	 * - Called from the googlemap JS initialize function via ajax (?action=GoogleMap;sa=xml)
	 */
	public function action_xml()
	{
		global $context, $settings, $options, $scripturl, $txt, $modSettings, $user_info, $memberContext;

		// Make sure the buffer is empty
		ob_clean();

		// XML Header
		header('Content-Type: application/xml; charset=UTF-8');

		// Lets load in some pin data
		$temp = gmm_loadPins();

		// Load all the data for these 'pined' members
		loadMemberData($temp);
		foreach ($temp as $mem)
		{
			loadMemberContext($mem);
		}
		unset($temp);

		// Begin the XML output
		$last_week = time() - (7 * 24 * 60 * 60);
		echo '<?xml version="1.0" encoding="UTF-8"?', '>
		<markers>';

		if (isset($memberContext))
		{
			// To prevent the avatar being outside the popup info window we set a max div height
			$div_height = max($modSettings['avatar_max_height_external'] ?? 0, $modSettings['avatar_max_height_upload'] ?? 100);

			// For every member with a pin, build the info bubble ...
			foreach ($memberContext as $marker)
			{
				$dataBlurb = '';

				// Guests don't get to see this ....
				if (!$user_info['is_guest'])
				{
					$dataBlurb = '
			<div class="googleMap">
				<h4>
					<a  href="' . $marker['online']['href'] . '" title="' . $marker['online']['text'] . '">';
						$dataBlurb .= '
						<i class="' . ($marker['online']['is_online'] ? 'iconline' : 'icoffline') . '" title="' . $marker['online']['text'] . '"></i>
					<a href="' . $marker['href'] . '">' . $marker['name'] . '</a>
				</h4>';

					// avatar?
					if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($marker['avatar']['image']))
					{
						$dataBlurb .= '
				<div class="gmm_avatar" style="max-height:' . $div_height . 'px">' . $marker['avatar']['image'] . '</div>';
					}

					// user info section
					$dataBlurb .= '
				<div class="gmm_poster">
					<ul class="reset">';

					// Show the member's primary group (like 'Administrator') if they have one.
					if (!empty($marker['group']))
					{
						$dataBlurb .= '
						<li class="membergroup">' . $marker['group'] . '</li>';
					}

					// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
					if ((empty($settings['hide_post_group']) || $marker['group'] === '') && $marker['post_group'] !== '')
					{
						$dataBlurb .= '
						<li class="postgroup">' . $marker['post_group'] . '</li>';
					}

					// groups icons
					$dataBlurb .= '
						<li class="icons">' . $marker['group_icons'] . '</li>';

					// show the title, if they have one
					if (!empty($marker['title']) && !$user_info['is_guest'])
					{
						$dataBlurb .= '
						<li class="title">' . $marker['title'] . '</li>';
					}

					// Show the profile, website, email address, and personal message buttons.
					if ($settings['show_profile_buttons'])
					{
						$dataBlurb .= '
						<li>
							<ul>';

						// Don't show an icon if they haven't specified a website.
						if ($marker['website']['url'] !== '' && !isset($context['disabled_fields']['website']))
						{
							$dataBlurb .= '
								<li>
									<a href="' . $marker['website']['url'] . '" title="' . $marker['website']['title'] . '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/www_sm.png" alt="' . $marker['website']['title'] . '" />' : $txt['www']) . '
								</li>';
						}

						// Don't show the email address if they want it hidden.
						if (in_array($marker['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
						{
							$dataBlurb .= '
								<li>
									<a href="' . $scripturl . '?action=emailuser;sa=email;uid=' . $marker['id'] . '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '
								</li>';
						}

						// Show the PM tag
						$dataBlurb .= '
								<li>
									<a href="' . $scripturl . '?action=pm;sa=send;u=' . $marker['id'] . '">';
						$dataBlurb .= $settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/im_' . ($marker['online']['is_online'] ? 'on' : 'off') . '.png" alt="' . $txt['send_message'] . '" title="' . $txt['send_message'] . '" />' : ($marker['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']);
						$dataBlurb .= '
								</li>';

						$dataBlurb .= '
							</ul>
						</li>';
					}

					$dataBlurb .= '
					</ul>
				</div>';

					// Show their personal text?
					if (!empty($settings['show_blurb']) && !empty($marker['cust_blurb']))
					{
						$dataBlurb .= '
				<br class="clear" />' . $marker['cust_blurb'];
					}

					$dataBlurb .= '
			</div>';
				}

				// Let's bring it all together...
				$markers = '<marker lat="' . round($marker['googleMap']['latitude'], 8) . '" lng="' . round($marker['googleMap']['longitude'], 8) . '" ';
				$markers .= 'gender="0"';

				if (!empty($modSettings['googleMap_BoldMember']) && $marker['googleMap']['pindate'] >= $last_week)
				{
					$markers .= ' label="[b]' . $marker['name'] . '[/b]"><![CDATA[' . $dataBlurb . ']]></marker>';
				}
				else
				{
					$markers .= ' label="' . $marker['name'] . '"><![CDATA[' . $dataBlurb . ']]></marker>';
				}

				echo $markers;
			}
		}
		echo '
		</markers>';

		// Ok we should be done with output, dump it to the template
		obExit(false);
	}

	/**
	 * Creates Google Earth kml data
	 *
	 * - Generates a file for saving that can then be imported in to Google Earth
	 */
	public function action_kml()
	{
		global $settings, $options, $context, $scripturl, $txt, $modSettings, $user_info, $mbname, $memberContext;

		// Are we allowed to view the map?
		isAllowedTo('googleMap_view');

		// If it's not enabled, die.
		if (empty($modSettings['googleMap_KMLoutput_enable']))
		{
			obExit(false);
		}

		// Language
		loadLanguage('GoogleMap');

		// Start off empty, we want a clean stream
		ob_clean();

		// It will be a file called ourforumname.kml
		header('Content-type: application/keyhole;');
		header('Content-Disposition: attachment; filename="' . $mbname . '.kml"');

		// Load all the data up, no need to limit an output file to the 'world'
		$temp = gmm_loadPins(true);

		loadMemberData($temp);
		foreach ($temp as $v)
		{
			loadMemberContext($v);
		}

		// Start building the output
		echo '<?xml version="1.0" encoding="', $context['character_set'], '"?' . '>
		<kml xmlns="https://www.opengis.net/kml/2.2"
		 xmlns:gx="https://www.google.com/kml/ext/2.2">
		<Folder>
			<name>' . $mbname . '</name>
			<open>1</open>';

		// create the pushpin styles ... just color really, all with a 80% transparency
		echo '
		<Style id="member">
			<IconStyle>
				<color>CF', $this->gmm_validate_color('googleMap_PinBackground', '66FF66'), '</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>
		<Style id="cluster">
			<IconStyle>
				<color>CF', $this->gmm_validate_color('googleMap_ClusterBackground', '66FF66'), '</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>
		<Style id="female">
			<IconStyle>
				<color>CFFF0099</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>
		<Style id="male">
			<IconStyle>
				<color>CF0066FF</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>';

		if (isset($memberContext))
		{
			// Assuming we have data to work with...
			foreach ($memberContext as $marker)
			{
				// to prevent the avatar being outside the popup window we need to set a max div height
				$div_height = max($modSettings['avatar_max_height_external'] ?? 0, $modSettings['avatar_max_height_upload'] ?? 0);

				echo '
		<Placemark id="' . $marker['name'] . '">
			<description>
				<![CDATA[
					<div style="width:240px">
						<h4>
							<a href="' . $marker['online']['href'] . '">
								<img src="' . $marker['online']['image_href'] . '" alt="' . $marker['online']['text'] . '" /></a>
							<a href="' . $marker['href'] . '">' . $marker['name'] . '</a>
						</h4>';

				// avatar?
				if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($marker['avatar']['image']))
				{
					echo '
							<div style="float:right;height:' . $div_height . 'px">'
						. $marker['avatar']['image'] . '<br />
							</div>';
				}

				// user info section
				echo '
						<div style="float:left;">
							<ul style="padding:0;margin:0;list-style:none;">';

				// Show the member's primary group (like 'Administrator') if they have one.
				if (!empty($marker['group']))
				{
					echo '
								<li>' . $marker['group'] . '</li>';
				}

				// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
				if ((empty($settings['hide_post_group']) || $marker['group'] === '') && $marker['post_group'] !== '')
				{
					echo '
								<li>' . $marker['post_group'] . '</li>';
				}

				// groups icons
				echo '
								<li>' . $marker['group_icons'] . '</li>';

				// show the title, if they have one
				if (!empty($marker['title']) && !$user_info['is_guest'])
				{
					echo '
								<li>' . $marker['title'] . '</li>';
				}

				// Show the profile, website, email address, etc
				if ($settings['show_profile_buttons'])
				{
					echo '
								<li>
									<ul style="padding:0;margin:0;list-style:none;">';

					// Don't show an icon if they haven't specified a website.
					if ($marker['website']['url'] !== '' && !isset($context['disabled_fields']['website']))
					{
						echo '
										<li>
											<a href="', $marker['website']['url'], '" title="', $marker['website']['title'], '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/www_sm.png" alt="' . $marker['website']['title'] . '" />' : $txt['www']) . '
										</li>';
					}

					// Don't show the email address if they want it hidden.
					if (in_array($marker['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
					{
						echo '
										<li>
											<a href="', $scripturl, '?action=emailuser;sa=email;uid=', $marker['id'], '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '
										</li>';
					}

					// Show the PM tag
					echo '
										<li>
											<a href="', $scripturl, '?action=pm;sa=send;u=', $marker['id'], '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/im_' . ($marker['online']['is_online'] ? 'on' : 'off') . '.png" />' : ($marker['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline'])) . '
										</li>
									</ul>
								</li>';
				}

				echo '
							</ul>
						</div>
					</div>
				]]>
			</description>
			<name>', $marker['name'], '</name>
			<LookAt>
				<longitude>', round($marker['googleMap']['longitude'], 8), '</longitude>
				<latitude>', round($marker['googleMap']['latitude'], 8), '</latitude>
				<range>15000</range>
			</LookAt>';

				// pin color
				echo '
			<styleUrl>#member</styleUrl>';

				echo '
			<Point>
				<extrude>1</extrude>
				<altitudeMode>clampToGround</altitudeMode>
				<coordinates>' . round($marker['googleMap']['longitude'], 8) . ',' . round($marker['googleMap']['latitude'], 8) . ',0</coordinates>
			</Point>
		</Placemark>';
			}
		}

		echo '
		</Folder>
	</kml>';

		// Ok done, should send everything now..
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
		if (substr($modSettings[$color], 0, 1) === '#')
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
