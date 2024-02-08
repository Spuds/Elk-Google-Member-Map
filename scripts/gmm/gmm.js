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

// Arrays to hold copies of the markers and html used by the sidebar
let gmarkers = [],
	htmls = [],
	sidebar_html = '';

// Map, cluster and info bubble
let map,
	mc,
	infoWindow;

// Pins
let npic,
	cpic;

/**
 * Defines the pin styles for markers and clusters on the Google Map.
 *
 * @return {void}
 */
function definePins()
{
	// Member SVG pin details
	npic = {
		path: "M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z",
		fillColor: npic_fillColor,
		fillOpacity: .8,
		strokeColor: npic_strokeColor,
		strokeWeight: 1,
		scale: npic_scale,
	};

	// Cluster SVG pin details
	cpic = {
		path: "M385.5 1.1c-55.5 4.4-104.3 17.6-153 41.4C86.8 113.7-4.5 264.8.3 426.5 2.2 487 15.5 542 40.9 594.5 51.8 617 59.2 629.8 74 652c6.5 9.6 85.6 136.9 176 282.7 90.3 145.9 164.6 265.3 165 265.3.4 0 74.7-119.4 165-265.2C670.4 788.9 749.5 661.6 756 652c14.8-22.2 22.2-35 33.1-57.5 42.1-86.9 52-186.9 27.9-282.1-24.4-95.8-82.2-179.5-164-237.1C583.4 26.2 497.9-.6 412.6.1c-9.4.1-21.6.5-27.1 1zM449 177.5c44 8 81.3 27.1 112 57.5 76.9 76.1 82.5 198 13 281.2-33.5 40.2-81.7 66.3-134.4 72.8-11.4 1.4-37.8 1.4-49.2 0-85.3-10.5-155-71.5-176.4-154.4-14.1-54.5-5.2-113.6 24.3-161.3 33-53.1 86.2-87.6 149.7-96.8 13-1.9 48.1-1.3 61 1z",
		view: "0,0,1280,1280",
		fillColor: cpic_fillColor,
		fillOpacity: .8,
		strokeColor: cpic_strokeColor,
		strokeWeight: 20,
	};

	// Cluster Pin Styles
	if (googleMap_EnableClusterer)
	{
		// Create a dataURL for use in style url, here a standard pin:
		const clusterPin = "data:image/svg+xml;base64," + window.btoa('<svg xmlns="http://www.w3.org/2000/svg" viewBox="' + cpic.view + '"><g><path stroke="' + cpic.strokeColor + '" stroke-width="' + cpic.strokeWeight + '" fill="' + cpic.fillColor + '" fill-opacity="' + cpic.fillOpacity + '" d="' + cpic.path + '" /></g></svg>');
		let codebase = "//github.com/googlemaps/js-markerclustererplus/raw/main";

		// Various cluster pin styles
		const styles = [[
			MarkerClusterer.withDefaultStyle({
				url: clusterPin,
				textColor: cpic_strokeColor,
				width: clusterSize[0],
				height: clusterSize[0],
				anchorIcon: [clusterSize[0], ' . clusterSize[0] / 2 . '],
				anchorText: [-6, -6],
				textSize: 10
			}),
			MarkerClusterer.withDefaultStyle({
				url: clusterPin,
				textColor: cpic_strokeColor,
				width: clusterSize[1],
				height: clusterSize[1],
				anchorIcon: [clusterSize[1], ' . clusterSize[1] / 2 . '],
				anchorText: [-8, -8],
				textSize: 11
			}),
			MarkerClusterer.withDefaultStyle({
				url: clusterPin,
				textColor: cpic_strokeColor,
				width: clusterSize[2],
				height: clusterSize[2],
				anchorIcon: [clusterSize[2], ' . clusterSize[2] / 2 . '],
				anchorText: [-10, -10],
				textSize: 12
			}),
			MarkerClusterer.withDefaultStyle({
				url: clusterPin,
				textColor: cpic_strokeColor,
				width: clusterSize[3],
				height: clusterSize[3],
				anchorIcon: [clusterSize[3], ' . clusterSize[3] / 2 . '],
				anchorText: [-12, -12],
				textSize: 13
			}),
			MarkerClusterer.withDefaultStyle({
				url: clusterPin,
				textColor: cpic_strokeColor,
				width: clusterSize[4],
				height: clusterSize[4],
				anchorIcon: [clusterSize[4], ' . clusterSize[4] / 2 . '],
				anchorText: [-14, -14],
				textSize: 14
			}),
		], [
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/m1.png",
				textColor: cpic_strokeColor,
				width: clusterSize[0],
				height: clusterSize[0],
				anchorIcon: [clusterSize[0], ' . clusterSize[0] / 2 . ']
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/m2.png",
				textColor: cpic_strokeColor,
				width: clusterSize[1],
				height: clusterSize[1],
				anchorIcon: [clusterSize[1], ' . clusterSize[1] / 2 . ']
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/m3.png",
				textColor: cpic_strokeColor,
				width: clusterSize[2],
				height: clusterSize[2],
				anchorIcon: [clusterSize[2], ' . clusterSize[2] / 2 . ']
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/m4.png",
				textColor: cpic_strokeColor,
				width: clusterSize[3],
				height: clusterSize[3],
				anchorIcon: [clusterSize[3], ' . clusterSize[3] / 2 . ']
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/m5.png",
				textColor: cpic_strokeColor,
				width: clusterSize[4],
				height: clusterSize[4],
				anchorIcon: [clusterSize[4], ' . clusterSize[4] / 2 . ']
			}),
		], [
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/people35.png",
				textColor: cpic_strokeColor,
				width: clusterSize[0],
				height: clusterSize[0],
				anchorIcon: [clusterSize[0], ' . clusterSize[0] / 2 . '],
				anchorText: [8, 0]
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/people45.png",
				textColor: cpic_strokeColor,
				width: clusterSize[1],
				height: clusterSize[1],
				anchorIcon: [clusterSize[1], ' . clusterSize[1] / 2 . '],
				anchorText: [10, 0]
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/people55.png",
				textColor: cpic_strokeColor,
				width: clusterSize[2],
				height: clusterSize[2],
				anchorIcon: [clusterSize[2], ' . clusterSize[2] / 2 . '],
				anchorText: [10, 0]
			}),
		], [
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/conv30.png",
				textColor: cpic_strokeColor,
				width: clusterSize[0],
				height: clusterSize[0],
				anchorIcon: [clusterSize[0], ' . clusterSize[0] / 2 . '],
				anchorText: [-5, 0]
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/conv40.png",
				textColor: cpic_strokeColor,
				width: clusterSize[1],
				height: clusterSize[1],
				anchorIcon: [clusterSize[1], ' . clusterSize[1] / 2 . '],
				anchorText: [-6, 0]
			}),
			MarkerClusterer.withDefaultStyle({
				url: codebase + "/images/conv50.png",
				textColor: cpic_strokeColor,
				width: clusterSize[2],
				height: clusterSize[2],
				anchorIcon: [clusterSize[2], ' . clusterSize[2] / 2 . '],
				anchorText: [-7, 0]
			}),
		]];

		// Who does not like a good old-fashioned cluster, cause that is what we have here
		let style = 0;
		let mcOptions = {
			gridSize: googleMap_GridSize,
			maxZoom: 6,
			averageCenter: true,
			zoomOnClick: false,
			minimumClusterSize: googleMap_MinMarkerPerCluster,
			title: txt_googleMap_GroupOfPins,
			styles: styles[style],
		};
	}
}

/**
 * Makes a request to the specified URL using XMLHttpRequest.
 *
 * @param {string} url - The URL to send the request to.
 */
function makeRequest(url)
{
	getXMLDocument(url, showContents);

	return false;
}

/**
 * Callback for getXMLDocument, creates pins based on the returned content.
 */
function showContents(oXMLDoc)
{
	makeMarkers(oXMLDoc);
}

/**
 * Initializes the Google Map and sets its options and controls.
 * Also defines the necessary pins and loads the members data.
 *
 * @return {void}
 */
function initialize()
{
	definePins();

	// Create the map
	let myStyle = [{
		featureType: "road",
		elementType: "geometry",
		stylers: [
			{lightness: -50},
			{hue: "#0099ff"}
		]
	}];
	let options = {
		zoom: googleMap_DefaultZoom,
		controlSize: 25,
		center: latlng,
		styles: myStyle,
		gestureHandling: "cooperative",
		mapTypeId: google.maps.MapTypeId[googleMap_Type],
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
	infoWindow = new google.maps.InfoWindow();

	// Load the members data
	makeRequest(elk_scripturl + "?action=GoogleMap;sa=xml");

	// Our own reset to initial state button since its gone walkies in the v3 api
	let reset = document.getElementById("googleMapReset");
	reset.style.opacity = ".4";
}

/**
 * Creates markers based on data from an XML document.
 *
 * @param {Document} xmldoc - The XML document containing marker data.
 */
function makeMarkers(xmldoc)
{
	let markers = xmldoc.documentElement.getElementsByTagName("marker"),
		point,
		html,
		label;

	// Create the pins/markers
	for (let i = 0; i < markers.length; ++i)
	{
		point = {lat: parseFloat(markers[i].getAttribute("lat")), lng: parseFloat(markers[i].getAttribute("lng"))};
		html = markers[i].childNodes[0].nodeValue;
		label = markers[i].getAttribute("label");
		createMarker(point, npic, label, html, i);
	}

	// Clustering enabled and we have enough pins?
	if (googleMap_EnableClusterer)
	{
		// Send the markers array to the cluster script
		mc = new MarkerClusterer(map, gmarkers, mcOptions);
		mc.addListener("clusterclick", function (cluster) {
			let clusterMarkers = cluster.getMarkers();

			map.setCenter(cluster.getCenter());

			// Build the info window content
			let content = '<div style="text-align:left">',
				numtoshow = Math.min(cluster.getSize(), googleMap_MaxLinesCluster),
				myLatlng;

			for (let i = 0; i < numtoshow; ++i)
			{
				content = content + '<img src="' + clusterPin + '" width="12" height="12" /> ' + clusterMarkers[i].title + '<br />';
			}

			if (cluster.getSize() > numtoshow)
			{
				content = content + '<br />' + txt_googleMap_Plus + ' [' + (cluster.getSize() - numtoshow) + '] ' + txt_googleMap_Otherpins;
			}

			content = content + '</div>';

			myLatlng = new google.maps.LatLng(cluster.getCenter().lat(), cluster.getCenter().lng());
			infoWindow.close();
			infoWindow.setOptions({pixelOffset: new google.maps.Size(0, -28)});
			infoWindow.setContent(content);
			infoWindow.setPosition(myLatlng);
			infoWindow.open(map);
			map.panTo(infoWindow.getPosition());
		});
	}

	// Place the assembled sidebar_html contents into the sidebar div
	document.getElementById("googleSidebar").innerHTML = sidebar_html;
}

/**
 * Creates a marker on a Google Map.
 *
 * @param {google.maps.LatLng} point - The position of the marker.
 * @param {string} pic - The icon image for the marker.
 * @param {string} name - The title of the marker.
 * @param {string} html - The content of the marker's info window.
 * @param {number} i - The index used to identify the marker.
 * @return {void}
 */
function createMarker(point, pic, name, html, i)
{
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
		infoWindow.close();
		infoWindow.setContent(html);
		infoWindow.open(map, marker);
		map.panTo(infoWindow.getPosition());
	});

	// Save the info used to populate the sidebar
	gmarkers.push(marker);
	htmls.push(html);
	name = name.replace(/\[b\](.*)\[\/b\]/gi, "<strong>$1</strong>");

	// Add a line to the sidebar html
	if (googleMap_Sidebar !== 'none')
	{
		sidebar_html += '<a href="javascript:finduser(' + i + ')">' + name + '</a><br />';
	}
}

/**
 * Finds a user and displays their information in an info window.
 *
 * @param {number} i - The index of the user in the gmarkers array.
 */
function finduser(i)
{
	let marker = gmarkers[i]["position"];

	infoWindow.close();
	infoWindow.setOptions({pixelOffset: new google.maps.Size(0, -20)});
	infoWindow.setContent(htmls[i]);
	infoWindow.setPosition(marker);
	infoWindow.open(map);

	map.panTo(infoWindow.getPosition());
}

/**
 * Resets the map by closing any open info windows and setting the center and zoom level to default values.
 *
 * @returns {void}
 */
function resetMap()
{
	// Close any info windows we may have opened
	infoWindow.close();

	map.setCenter(new google.maps.LatLng(googleMap_DefaultLat || 0, googleMap_DefaultLong || 0));
	map.setZoom(googleMap_DefaultZoom);
}
