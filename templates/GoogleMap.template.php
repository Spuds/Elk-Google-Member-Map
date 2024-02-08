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

function template_map()
{
	global $context, $modSettings, $scripturl, $txt, $settings;

	if (!empty($modSettings['googleMap_Enable']))
	{
		echo '
				<div id="gmm">';

		// Show a left sidebar?
		if ((!empty($modSettings['googleMap_Sidebar'])) && $modSettings['googleMap_Sidebar'] === 'left')
		{
			echo '
					<div class="sidebarleft">
						<h2 class="category_header">
							', $txt['googleMap_Pinned'], '
						</h2>
						<div id="googleSidebar"></div>
						<div class="centertext googleMap_Legend">' . (!empty($modSettings['googleMap_BoldMember']) ? '
							<strong>' . $txt['googleMap_bold'] . '</strong>&nbsp;' . $txt['googleMap_OnMove'] : '&nbsp;') . '
						</div>
					</div>';
		}

		// Our map
		echo '
					<div class="mappanel">
						<h2 class="category_header">
							<span class="align_left">', $txt['googleMap'], '</span>
						</h2>
						<div id="mapWindow">
							<div id="map"></div>
							<div id="googleMapReset" onclick="resetMap(); return false;" title="' . $txt['googleMap_Reset'] . '"></div>
						</div>';

		// Set the text for the number of pins we are, or can, show
		if ($context['total_pins'] >= $modSettings['googleMap_PinNumber'] && $modSettings['googleMap_PinNumber'] != 0)
		{
			echo
			sprintf($txt['googleMap_Thereare'], '<strong>(' . $modSettings['googleMap_PinNumber'] . '+)</strong>');
		}
		else
		{
			echo
			sprintf($txt['googleMap_Thereare'], '<strong>(' . $context['total_pins'] . ')</strong>');
		}

		echo '
					</div>';

		// Show a right sidebar?
		if (!empty($modSettings['googleMap_Sidebar']) && $modSettings['googleMap_Sidebar'] === 'right')
		{
			echo '
					<div class="sidebarright">
						<h2 class="category_header">
							', $txt['googleMap_Pinned'], '
						</h2>
						<div id="googleSidebar"></div>
						<div class="centertext googleMap_Legend">' . (!empty($modSettings['googleMap_BoldMember']) ? '
							<strong>' . $txt['googleMap_bold'] . '</strong>&nbsp;' . $txt['googleMap_OnMove'] : '&nbsp;') . '
						</div>
					</div>';
		}

		echo '
				</div>';

		// Show a legend?
		if (!empty($modSettings['googleMap_EnableLegend']))
		{
			echo '
				<svg style="position: absolute; overflow: hidden" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
					<defs>
						<symbol id="icon-cluster" viewBox="0 0 36 36">
							<path d="M18 0c-6.213 0-11.25 5.037-11.25 11.25 0 11.25 11.25 24.75 11.25 24.75s11.25-13.5 11.25-24.75c0-6.213-5.037-11.25-11.25-11.25zM18 18c-3.728 0-6.75-3.022-6.75-6.75s3.022-6.75 6.75-6.75 6.75 3.022 6.75 6.75-3.022 6.75-6.75 6.75z"></path>
						</symbol>
						<symbol id="icon-member" viewBox="0 0 848 1280">
							<path d="M393.5 1c-86 6.4-163.8 34.6-227.9 82.7-33.4 25.1-70.9 62.1-94.3 93.1C30.7 230.7 8.9 288.6 1.8 361.5c-1.9 19.9-1.6 70 .5 86.3 7.5 56.7 27.2 105.7 74 183.7C99.8 670.6 114.4 692.9 194 812c49.8 74.4 58.1 87.2 73.8 114.1 42 71.9 75.6 142.2 99.2 207.4 11.4 31.7 17.1 50.4 32.5 107.5 3.6 13.5 7.5 26.4 8.6 28.7 4.9 10.1 15.3 13.4 23.7 7.3 7.8-5.7 11.5-14.6 24.7-59.7 25-85.4 39.5-124.4 68.9-184.8 35.2-72.3 75-138.2 159.1-263.5 62.4-93 97.3-150.6 125.9-207.8 16.8-33.5 25.5-56.5 30-79.4 9.2-46.1 9.9-115.2 1.6-157.9-11.2-57.9-37.9-114-78.8-165.6-11.7-14.8-44.8-47.6-61.1-60.6C635.2 44.3 556.3 12 470 2.4 453.5.6 409.9-.2 393.5 1z"></path>
						</symbol>
					</defs>
				</svg>';

			echo '
				<div class="clear">
					<h2 class="category_header">
						<span class="align_left">', $txt['googleMap_Legend'], '</span>
					</h2>
					<table id="googleMap_Legend" class="centertext">
						<tr>';

			echo '
							<td>
								<svg class="svgicon" style="color: #' . $modSettings['googleMap_PinBackground'] . '"><use href="#icon-member"></use></svg>', $txt['googleMap_MemberPin'], '
							</td>';


			if (!empty($modSettings['googleMap_EnableClusterer']) && ($context['total_pins'] > (!empty($modSettings['googleMap_MinMarkertoCluster']) ? $modSettings['googleMap_MinMarkertoCluster'] : 0)))
			{
				// Shortcut
				$codebase = '//github.com/googlemaps/js-markerclustererplus/raw/main';

				switch ($modSettings['cpin'])
				{
					case 1:
						$pinsrc = $codebase . '/images/m1.png';
						break;
					case 2:
						$pinsrc = $codebase . '/images/people35.png';
						break;
					case 3:
						$pinsrc = $codebase . '/images/conv30.png';
						break;
					default:
						$pinsrc = '';
				}

				echo '
								<td>', empty($pinsrc) ? '
									<svg class="svgicon" style="color: #' . $modSettings['googleMap_ClusterBackground'] . '"><use href="#icon-cluster"></use></svg>' : '
									<img src="' . htmlspecialchars($pinsrc) . '" height=37 alt="" />', $txt['googleMap_GroupOfPins'], '
								</td>';
			}

			echo '
							</tr>
						</table>';
		}

		echo '
						<table class="gmm_centertext">';

		// If they can place a pin, give them a hint
		if ($context['place_pin'])
		{
			echo '
							<tr>
								<td>
									<a href="', $scripturl, '?action=profile;area=forumprofile#GMAP">', $txt['googleMap_AddPinNote'], '</a>
								</td>
							</tr>';
		}

		// Google earth klm output enabled?
		if (!empty($modSettings['googleMap_KMLoutput_enable']))
		{
			echo '
							<tr>
								<td style=text-align: center;">
									<a href="', $scripturl, '?action=GoogleMap;sa=kml">
										<img src="', $settings['default_theme_url'], '/images/google_earth_feed.gif" border="0" alt="" />
									</a>
								</td>
							</tr>';
		}

		// Done with the bottom table
		echo '
						</table>';

		// Close it up jim
		echo '
					</div>';
	}
}

/**
 * Call back for Google Map Member Map area of ProfileInfo
 */
function template_profile_googlemap_modify()
{
	global $txt, $modSettings, $context;

	if (!empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'))
	{
		echo '
		<dt id="GMAP">
			<strong>', $txt['googleMap'], '</strong>
			<br /><span class="smalltext">' . $txt['googleMap_PleaseClick'] . '<br />' . $txt['googleMap_Disclaimer'] . '</span>
		</dt>
		<dd>
			<script src="//maps.google.com/maps/api/js?libraries=places&key=' . $modSettings['googleMap_Key'] . '"></script>
			<input id="searchTextField" type="text" size="50">
        	<div id="map_canvas"></div>
       		<input type="hidden" name="latitude" id="latitude" size="50" value="', $context['member']['googleMap']['latitude'], '" />
  		    <input type="hidden" name="longitude" id="longitude" size="50" value="', $context['member']['googleMap']['longitude'], '" />
     		<input type="hidden" name="pindate" id="pindate" size="50" value="', $context['member']['googleMap']['pindate'], '" />
      		<script>
				let markersArray = [],
					i;
		
				// Used to clear any previous pin placed on the map
				function clearOverlays() {
					if (markersArray)
					{
						for (i in markersArray)
						{
							markersArray[i].setMap(null);
						}
					}
				}

				// Show the map
				function initialize() {
					let latlng = new google.maps.LatLng(', (!empty($context['member']['googleMap']['latitude']) ? $context['member']['googleMap']['latitude'] : (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0)) . ', ' . (!empty($context['member']['googleMap']['longitude']) ? $context['member']['googleMap']['longitude'] : (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0)), ');
					let options = {
						zoom: ', !empty($context['member']['googleMap']['latitude']) ? 15 : (!empty($modSettings['googleMap_DefaultZoom']) ? $modSettings['googleMap_DefaultZoom'] : 4), ',
						center: latlng,
						scrollwheel: false,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						mapTypeControlOptions: {
							style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
						},
						zoomControl: true,
						zoomControlOptions: {
							style: google.maps.ZoomControlStyle.DEFAULT
						},
					};
					map = new google.maps.Map(document.getElementById("map_canvas"), options);';

		// show the member pin, and provide a way to remove it,  if one has been set
		if (!empty($context['member']['googleMap']['latitude']) && !empty($context['member']['googleMap']['longitude']))
		{
			echo '
					var marker = new google.maps.Marker({
						position: latlng,
						map: map
					});
					markersArray.push(marker);
		
					// Listen and act on a marker click, used to remove this pin
					marker.addListener("click", function() {
						clearOverlays();
						document.getElementById("latitude").value = 0;
						document.getElementById("longitude").value = 0;
					});';
		}

		echo '
					// Listen and act on a map click, used to add pins
					map.addListener("click", function(event) {
						let marker = new google.maps.Marker({position:event.latLng, map:map});
						
						clearOverlays();
						markersArray.push(marker);
						map.panTo(event.latLng);
						document.getElementById("latitude").value = event.latLng.lat();
						document.getElementById("longitude").value = event.latLng.lng();
		
						// Listen and act on a marker click, used to remove pins
						marker.addListener("click", function() {
							clearOverlays();
							document.getElementById("latitude").value = 0;
							document.getElementById("longitude").value = 0;
						});
					});
		
					// Set up the searchbox to be part of the map
					let input = document.getElementById("searchTextField"),
						autocomplete = new google.maps.places.Autocomplete(input);
		
					autocomplete.bindTo("bounds", map);
					autocomplete.setTypes(["geocode"]);
		
					// Watch for a search box selection, when found move and zoom to that place
					autocomplete.addListener("place_changed", function() {
						let place = autocomplete.getPlace();
		
						if (place.geometry.viewport)
						{
							map.fitBounds(place.geometry.viewport);
						}
						else
						{
							map.setCenter(place.geometry.location);
							map.setZoom(13);
						}
					});
				}
				
				window.addEventListener("load", initialize);
			</script>
		</dd>';
	}
}

/**
 * Profile Summary template for showing the users map location, if they have one set
 */
function template_profile_block_gmm()
{
	global $scripturl, $context, $txt, $modSettings;

	// If they have a pin set then we show the block
	if (!empty($context['member']['googleMap']['longitude']) && !empty($context['member']['googleMap']['latitude']))
	{
		// Try to get the shout out right where is vs where are
		$title = $txt['googleMap_Where'] . ' ' . (preg_match('~\s(and|&|&amp;)\s~i', $context['member']['name']) ? $txt['googleMap_Whereare'] : $txt['googleMap_Whereis']) . ' ' . $context['member']['name'];

		echo '
	<div class="profileblock">
		<h3 class="category_header hdicon cat_img_eye">
			', ($context['user']['is_owner']) ? '<a href="' . $scripturl . '?action=profile;area=forumprofile;u=' . $context['member']['id'] . '">' . $title . '</a>' : $title, '
		</h3>
		<div class="profileblock">
			<script src="//maps.google.com/maps/api/js?key=' . $modSettings['googleMap_Key'] . '"></script>
			<div id="map_canvas" style="width: 100%; height: 300px; color: #000000;"></div>
				<input type="hidden" name="latitude" size="50" value="', $context['member']['googleMap']['latitude'], '" />
				<input type="hidden" name="longitude" size="50" value="', $context['member']['googleMap']['longitude'], '" />
				<input type="hidden" name="pindate" size="50" value="', $context['member']['googleMap']['pindate'], '" />
				<script>
					let latlng = new google.maps.LatLng(', $context['member']['googleMap']['latitude'], ', ', $context['member']['googleMap']['longitude'], ');
					let options = {
						zoom: 14,
						center: latlng,
						scrollwheel: false,
						mapTypeId: google.maps.MapTypeId.HYBRID,
						mapTypeControlOptions: {
							style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
						},
						zoomControl: true,
						zoomControlOptions: {
							style: google.maps.ZoomControlStyle.DEFAULT
						},
					};

					map = new google.maps.Map(document.getElementById("map_canvas"), options);
					new google.maps.Marker({
						position: latlng,
						map: map
					});
				</script>
			</div>
		</div>';
	}
}
