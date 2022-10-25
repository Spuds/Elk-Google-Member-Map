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

		// Create the pins for template use
		if (!empty($modSettings['googleMap_EnableLegend']))
		{
			$this->gmm_buildpins();
		}

		// Load in our javascript
		loadJavascriptFile('https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js');
		loadJavascriptFile('//maps.google.com/maps/api/js?key=' . $modSettings['googleMap_Key'] . '"', array(), 'sensor.js');

		// Show the map
		$context['place_pin'] = allowedTo('googleMap_place');
		$context['total_pins'] = $totalSet;
		$context['sub_template'] = 'map';
		$context['page_title'] = $txt['googleMap'];
	}

	/**
	 * Creates the maps javascript file based on the admin settings
	 * Called from the map template file via map action=GoogleMap;sa=js
	 */
	public function action_js()
	{
		global $context, $txt, $modSettings;

		// Clean and restart the buffer, such that we only return JS back to the template
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
		{
			ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// Let them know what they are about to get
		header('Content-Type: application/javascript');

		// Our push pins as defined from gmm_buildpins
		$this->gmm_buildpins();

		// Validate the specified pin size is not to small
		$m_iconsize = (isset($modSettings['googleMap_PinSize']) && $modSettings['googleMap_PinSize'] > 14) ? $modSettings['googleMap_PinSize'] : 24;
		$c_iconsize = (isset($modSettings['googleMap_ClusterSize']) && $modSettings['googleMap_ClusterSize'] > 14) ? $modSettings['googleMap_ClusterSize'] : 24;

		// Pin count
		$context['total_pins'] = isset($_REQUEST['count']) ? (int) $_REQUEST['count'] : 0;

		// Lets start making some javascript
		echo '	// Globals
	let xhr = false;

	// Arrays to hold copies of the markers and html used by the sidebar
	let gmarkers = [],
		htmls = [],
		sidebar_html = "";

	// Map, cluster and info bubble
	let map,
		mc;
			
	// Create an infoWindow for on click use	
	const infowindow = new google.maps.InfoWindow();

	// Icon base location for cluster icons
	let codebase = "//github.com/googlemaps/js-markerclustererplus/raw/main";

	// Mmember pin details
	let npic = {
		path: "M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z",
		fillColor: "#' . $modSettings['googleMap_PinBackground'] . '",
		fillOpacity: .8,
		strokeColor: "#' . $modSettings['googleMap_PinForeground'] . '",
		strokeWeight: 1,
		scale: ' . round($m_iconsize / 24, 2) . ',
	};
	
	// Cluster pin details
	let cpic = {
		path: "M385.5 1.1c-55.5 4.4-104.3 17.6-153 41.4C86.8 113.7-4.5 264.8.3 426.5 2.2 487 15.5 542 40.9 594.5 51.8 617 59.2 629.8 74 652c6.5 9.6 85.6 136.9 176 282.7 90.3 145.9 164.6 265.3 165 265.3.4 0 74.7-119.4 165-265.2C670.4 788.9 749.5 661.6 756 652c14.8-22.2 22.2-35 33.1-57.5 42.1-86.9 52-186.9 27.9-282.1-24.4-95.8-82.2-179.5-164-237.1C583.4 26.2 497.9-.6 412.6.1c-9.4.1-21.6.5-27.1 1zM449 177.5c44 8 81.3 27.1 112 57.5 76.9 76.1 82.5 198 13 281.2-33.5 40.2-81.7 66.3-134.4 72.8-11.4 1.4-37.8 1.4-49.2 0-85.3-10.5-155-71.5-176.4-154.4-14.1-54.5-5.2-113.6 24.3-161.3 33-53.1 86.2-87.6 149.7-96.8 13-1.9 48.1-1.3 61 1z",
		view: "0,0,1280,1280",
		fillColor: "#' . $modSettings['googleMap_ClusterBackground'] . '",
		fillOpacity: .8,
		strokeColor: "#' . $modSettings['googleMap_ClusterForeground'] . '",
		strokeWeight: 20,
	};';

		// Cluster Pin Styles
		if (!empty($modSettings['googleMap_EnableClusterer']))
		{
			$clusterSize = array_fill(0, 5, $c_iconsize);
			if (!empty($modSettings['googleMap_ScalableCluster']))
			{
				$clusterSize = [$c_iconsize, $c_iconsize * 1.3, $c_iconsize * 1.6, $c_iconsize * 1.9, $c_iconsize * 2.2];
			}

			echo '
	// Create a dataURL for use in style url, here a standard pin:
	const clusterPin = "data:image/svg+xml;base64," + window.btoa(\'<svg xmlns="http://www.w3.org/2000/svg" viewBox="\' + cpic.view + \'"><g><path stroke="\' + cpic.strokeColor + \'" stroke-width="\' + cpic.strokeWeight + \'" fill="\' + cpic.fillColor + \'" fill-opacity="\' + cpic.fillOpacity + \'" d="\' + cpic.path + \'" /></g></svg>\');

	// Various cluster pin styles
	const styles = [[
		MarkerClusterer.withDefaultStyle({url: clusterPin, textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[0] . ', height: ' . $clusterSize[0] . ', anchorIcon: [' . $clusterSize[0] . ', ' . $clusterSize[0] / 2 . '], anchorText: [-6, -6], textSize: 10}),
		MarkerClusterer.withDefaultStyle({url: clusterPin, textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[1] . ', height: ' . $clusterSize[1] . ', anchorIcon: [' . $clusterSize[1] . ', ' . $clusterSize[1] / 2 . '], anchorText: [-8, -8], textSize: 11}),
		MarkerClusterer.withDefaultStyle({url: clusterPin, textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[2] . ', height: ' . $clusterSize[2] . ', anchorIcon: [' . $clusterSize[2] . ', ' . $clusterSize[2] / 2 . '], anchorText: [-10, -10], textSize: 12}),
		MarkerClusterer.withDefaultStyle({url: clusterPin, textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[3] . ', height: ' . $clusterSize[3] . ', anchorIcon: [' . $clusterSize[3] . ', ' . $clusterSize[3] / 2 . '], anchorText: [-12, -12], textSize: 13}),
		MarkerClusterer.withDefaultStyle({url: clusterPin, textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[4] . ', height: ' . $clusterSize[4] . ', anchorIcon: [' . $clusterSize[4] . ', ' . $clusterSize[4] / 2 . '], anchorText: [-14, -14], textSize: 14}),
	],[
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/m1.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[0] . ', height: ' . $clusterSize[0] . ', anchorIcon: [' . $clusterSize[0] . ', ' . $clusterSize[0] / 2 . ']}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/m2.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[1] . ', height: ' . $clusterSize[1] . ', anchorIcon: [' . $clusterSize[1] . ', ' . $clusterSize[1] / 2 . ']}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/m3.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[2] . ', height: ' . $clusterSize[2] . ', anchorIcon: [' . $clusterSize[2] . ', ' . $clusterSize[2] / 2 . ']}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/m4.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[3] . ', height: ' . $clusterSize[3] . ', anchorIcon: [' . $clusterSize[3] . ', ' . $clusterSize[3] / 2 . ']}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/m5.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[4] . ', height: ' . $clusterSize[4] . ', anchorIcon: [' . $clusterSize[4] . ', ' . $clusterSize[4] / 2 . ']}),
	],[
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/people35.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[0] . ', height: ' . $clusterSize[0] . ', anchorIcon: [' . $clusterSize[0] . ', ' . $clusterSize[0] / 2 . '], anchorText: [8, 0]}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/people45.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[1] . ', height: ' . $clusterSize[1] . ', anchorIcon: [' . $clusterSize[1] . ', ' . $clusterSize[1] / 2 . '], anchorText: [10, 0]}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/people55.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[2] . ', height: ' . $clusterSize[2] . ', anchorIcon: [' . $clusterSize[2] . ', ' . $clusterSize[2] / 2 . '], anchorText: [10, 0]}),
	],[
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/conv30.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[0] . ', height: ' . $clusterSize[0] . ', anchorIcon: [' . $clusterSize[0] . ', ' . $clusterSize[0] / 2 . '], anchorText: [-5, 0]}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/conv40.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[1] . ', height: ' . $clusterSize[1] . ', anchorIcon: [' . $clusterSize[1] . ', ' . $clusterSize[1] / 2 . '], anchorText: [-6, 0]}),
		MarkerClusterer.withDefaultStyle({url: codebase + "/images/conv50.png", textColor: "#'. $modSettings['googleMap_ClusterForeground'] .'", width: ' . $clusterSize[2] . ', height: ' . $clusterSize[2] . ', anchorIcon: [' . $clusterSize[2] . ', ' . $clusterSize[2] / 2 . '], anchorText: [-7, 0]}),
	]];

	// Who does not like a good old fashioned cluster, cause that is what we have here
	let style = ' . (is_int($this->_cpin) ? $this->_cpin : 0) . ';
	let mcOptions = {
		gridSize: ' . (!empty($modSettings['googleMap_GridSize']) ? $modSettings['googleMap_GridSize'] : 2) . ',
		maxZoom: 6,
		averageCenter: true,
		zoomOnClick: false,
		minimumClusterSize: ' . (!empty($modSettings['googleMap_MinMarkerPerCluster']) ? $modSettings['googleMap_MinMarkerPerCluster'] : 20) . ',
		title: "' . $txt['googleMap_GroupOfPins'] . '",
		styles: styles[style],
	};';
		}

		echo '
	// Functions to read xml data
	function makeRequest(url) {
		if (window.XMLHttpRequest)
		{
			xhr = new XMLHttpRequest();
		}
		else
		{
			if (window.ActiveXObject)
			{
				try {
					xhr = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) { }
			}
		}

		if (xhr)
		{
			xhr.onreadystatechange = showContents;
			xhr.open("GET", url, true);
			xhr.send(null);
		}
		else
		{
			document.write("' . $txt['googleMap_xmlerror'] . '");
		}
	}

	function showContents() {
		let xmldoc = \'\';

		if (xhr.readyState === 4)
		{
			// Run on server (200) or local machine (0)
			if (xhr.status === 200 || xhr.status === 0)
			{
				xmldoc = xhr.responseXML;
				makeMarkers(xmldoc);
			}
			else
			{
				document.write("' . $txt['googleMap_error'] . ' - " + xhr.status);
			}
		}
	}

	// Create the map and load our data
	function initialize() {
		// Create the map
		let latlng = {lat: '. (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0) . ', lng: ' . (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0) . '};
		let myStyle = [{
			featureType: "road",
			elementType: "geometry",
			stylers: [
				{ lightness: -50 },
				{ hue: "#0099ff" }
			]
		}];
		let options = {
			zoom: ' . $modSettings['googleMap_DefaultZoom'] . ',
			controlSize: 25,
			center: latlng,
			styles: myStyle,
			gestureHandling: "cooperative",
			mapTypeId: google.maps.MapTypeId.' . $modSettings['googleMap_Type'] . ',
			mapTypeControlOptions: {
         		mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.TERRAIN, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID],
         		style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
	       	},
	       	zoomControl: true,
			mapTypeControl: true,
			scaleControl: true,
			streetViewControl: true,
			rotateControl: false,
			fullscreenControl: false,
		};
		map = new google.maps.Map(document.getElementById("map"), options);

		// Load the members data
		makeRequest(elk_scripturl + "?action=GoogleMap;sa=xml");

		// Our own initial state button since its gone walkies in the v3 api
		let reset = document.getElementById("googleMapReset");
		reset.style.opacity = ".2";
	}

	// Read the output of the marker xml
	function makeMarkers(xmldoc) {
		let markers = xmldoc.documentElement.getElementsByTagName("marker"),
			point,
			html,
			label;

		// Create the pins/markers
		for (let i = 0; i < markers.length; ++i) {
			point = {lat: parseFloat(markers[i].getAttribute("lat")), lng: parseFloat(markers[i].getAttribute("lng"))};
			html = markers[i].childNodes[0].nodeValue;
			label = markers[i].getAttribute("label");
			createMarker(point, npic, label, html, i);
		}';

		// Clustering enabled and we have enough pins?
		if (!empty($modSettings['googleMap_EnableClusterer']) && ($context['total_pins'] > (!empty($modSettings['googleMap_MinMarkertoCluster']) ? $modSettings['googleMap_MinMarkertoCluster'] : 50)))
		{
			echo '
		// Send the markers array to the cluster script
		mc = new MarkerClusterer(map, gmarkers, mcOptions);
		mc.addListener("clusterclick", function(cluster) {
			let clusterMarkers = cluster.getMarkers();
			
			map.setCenter(cluster.getCenter());

			// Build the info window content
			let content = "<div style=\"text-align:left\">",
				numtoshow = Math.min(cluster.getSize(), ', $modSettings['googleMap_MaxLinesCluster'] ?? 10, '),
				myLatlng;
				
			for (let i = 0; i < numtoshow; ++i)
				content = content + "<img src=\"" + clusterPin + "\" width=\"12\" height=\"12\" />   " + clusterMarkers[i].title + "<br />";

			if (cluster.getSize() > numtoshow)
				content = content + "<br />', $txt['googleMap_Plus'], ' [" + (cluster.getSize() - numtoshow) + "] ', $txt['googleMap_Otherpins'], '";

			content = content + "</div>";
			
			myLatlng = new google.maps.LatLng(cluster.getCenter().lat(), cluster.getCenter().lng());
			infowindow.close();
			infowindow.setOptions({pixelOffset: new google.maps.Size(0, -28)});
			infowindow.setContent(content);
			infowindow.setPosition(myLatlng);
			infowindow.open(map);
			map.panTo(infowindow.getPosition());
		});';
		}

		echo '
		// Place the assembled sidebar_html contents into the sidebar div
		document.getElementById("googleSidebar").innerHTML = sidebar_html;
	}

	// Create a marker and set up the event window
	function createMarker(point, pic, name, html, i) {
		// Map marker
		let marker = new google.maps.Marker({
			position: point,
			map: map,
			icon: pic,
			optimized: true,
			title: name.replace(/\[b\](.*)\[\/b\]/gi, "$1")
		});

		// Listen for a marker click
		marker.addListener("click", () => {
			infowindow.close();
			infowindow.setContent(html);
			infowindow.open(map, marker);
			map.panTo(infowindow.getPosition());
		});

		// Save the info used to populate the sidebar
		gmarkers.push(marker);
		htmls.push(html);
		name = name.replace(/\[b\](.*)\[\/b\]/gi, "<strong>$1</strong>");

		// Add a line to the sidebar html';
		if ($modSettings['googleMap_Sidebar'] !== 'none')
		{
			echo '
		sidebar_html += \'<a href="javascript:finduser(\' + i + \')">\' + name + \'</a><br /> \';';
		}

		echo '
	}

	// Picks up the sidebar click and opens the corresponding info window
	function finduser(i) {
		let marker = gmarkers[i]["position"];
		
		infowindow.close();
		infowindow.setOptions({pixelOffset: new google.maps.Size(0, -20)});
		infowindow.setContent(htmls[i]);
		infowindow.setPosition(marker);
		infowindow.open(map);
		map.panTo(infowindow.getPosition());
	}

	// Resets the map to the initial zoom/center values
	function resetMap() {
		// Close any info windows we may have opened
		infowindow.close();

		map.setCenter(new google.maps.LatLng(' . (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0) . ', ' . (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0) . '));
		map.setZoom(' . $modSettings['googleMap_DefaultZoom'] . ');
	}

	window.addEventListener("load", initialize);';

		obExit(false);
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

		// Make sure the buffer is empty so we return clean XML to the template
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
		{
			@ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// XML Header
		header('Content-Type: application/xml; charset=UTF-8');

		// Lets load in some pin data
		$temp = gmm_loadPins();

		// Load all of the data for these 'pined' members
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
				$datablurb = '';

				// Guests don't get to see this ....
				if (!$user_info['is_guest'])
				{
					$datablurb = '
			<div class="googleMap">
				<h4>
					<a  href="' . $marker['online']['href'] . '" title="' . $marker['online']['text'] . '">';

					// 1.0
					if (!empty($marker['online']['image_href']))
					{
						$datablurb .= '
						<img class="centericon" src="' . $marker['online']['image_href'] . '" alt="' . $marker['online']['text'] . '" /></a>';
					}
					// 1.1
					else
					{
						$datablurb .= '
						<i class="' . ($marker['online']['is_online'] ? 'iconline' : 'icoffline') . '" title="' . $marker['online']['text'] . '"></i>';
					}

					$datablurb .= '	
					<a href="' . $marker['href'] . '">' . $marker['name'] . '</a>
				</h4>';

					// avatar?
					if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($marker['avatar']['image']))
					{
						$datablurb .= '
				<div class="gmm_avatar" style="max-height:' . $div_height . 'px">' . $marker['avatar']['image'] . '</div>';
					}

					// user info section
					$datablurb .= '
				<div class="gmm_poster">
					<ul class="reset">';

					// Show the member's primary group (like 'Administrator') if they have one.
					if (!empty($marker['group']))
					{
						$datablurb .= '
						<li class="membergroup">' . $marker['group'] . '</li>';
					}

					// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
					if ((empty($settings['hide_post_group']) || $marker['group'] === '') && $marker['post_group'] !== '')
					{
						$datablurb .= '
						<li class="postgroup">' . $marker['post_group'] . '</li>';
					}

					// groups icons
					$datablurb .= '
						<li class="icons">' . $marker['group_icons'] . '</li>';

					// show the title, if they have one
					if (!empty($marker['title']) && !$user_info['is_guest'])
					{
						$datablurb .= '
						<li class="title">' . $marker['title'] . '</li>';
					}

					// Show the profile, website, email address, and personal message buttons.
					if ($settings['show_profile_buttons'])
					{
						$datablurb .= '
						<li>
							<ul>';

						// Don't show an icon if they haven't specified a website.
						if ($marker['website']['url'] !== '' && !isset($context['disabled_fields']['website']))
						{
							$datablurb .= '
								<li>
									<a href="' . $marker['website']['url'] . '" title="' . $marker['website']['title'] . '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/www_sm.png" alt="' . $marker['website']['title'] . '" />' : $txt['www']) . '
								</li>';
						}

						// Don't show the email address if they want it hidden.
						if (in_array($marker['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
						{
							$datablurb .= '
								<li>
									<a href="' . $scripturl . '?action=emailuser;sa=email;uid=' . $marker['id'] . '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '
								</li>';
						}

						// Show the PM tag
						$datablurb .= '
								<li>
									<a href="' . $scripturl . '?action=pm;sa=send;u=' . $marker['id'] . '">';
						$datablurb .= $settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/im_' . ($marker['online']['is_online'] ? 'on' : 'off') . '.png" alt="' . $txt['send_message'] . '" title="' . $txt['send_message'] . '" />' : ($marker['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']);
						$datablurb .= '
								</li>';

						$datablurb .= '
							</ul>
						</li>';
					}

					$datablurb .= '
					</ul>
				</div>';

					// Show their personal text?
					if (!empty($settings['show_blurb']))
					{
						// 1.0
						if (!empty($marker['blurb']))
						{
							$datablurb .= '
				<br class="clear" />' . $marker['blurb'];
						}
						// 1.1
						elseif (!empty($marker['cust_blurb']))
						{
							$datablurb .= '
				<br class="clear" />' . $marker['cust_blurb'];
						}
					}

					$datablurb .= '
			</div>';
				}

				// Let's bring it all together...
				$markers = '<marker lat="' . round($marker['googleMap']['latitude'], 8) . '" lng="' . round($marker['googleMap']['longitude'], 8) . '" ';
				$markers .= 'gender="0"';

				if (!empty($modSettings['googleMap_BoldMember']) && $marker['googleMap']['pindate'] >= $last_week)
				{
					$markers .= ' label="[b]' . $marker['name'] . '[/b]"><![CDATA[' . $datablurb . ']]></marker>';
				}
				else
				{
					$markers .= ' label="' . $marker['name'] . '"><![CDATA[' . $datablurb . ']]></marker>';
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
	 * Creates google earth kml data
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
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
		{
			@ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

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
		<kml xmlns="http://www.opengis.net/kml/2.2"
		 xmlns:gx="http://www.google.com/kml/ext/2.2">
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

		// no leading #'s please
		if (substr($modSettings[$color], 0, 1) === '#')
		{
			$modSettings[$color] = substr($modSettings[$color], 1);
		}

		// is it a hex
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
