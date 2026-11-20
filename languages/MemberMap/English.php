<?php

global $scripturl;

// Template strings
$txt['googleMap'] = 'Member Map';
$txt['googleMap_Reset'] = 'Reset Map';
$txt['googleMap_GroupOfPins'] = "Group of Pins";
$txt['googleMap_Where'] = 'Where';
$txt['googleMap_Whereis'] = 'is';
$txt['googleMap_Whereare'] = 'are';
$txt['googleMap_Thereare'] = 'There are %s member pins on the map';
$txt['googleMap_Plus'] = 'Plus';
$txt['googleMap_Otherpins'] = 'other Pins';
$txt['googleMap_Legend'] = 'Legend';
$txt['googleMap_Pinned'] = 'Member Pins';
$txt['googleMap_MemberPin'] = 'Someones Pin';
$txt['googleMap_AndrogynyPin'] = 'No Gender';
$txt['googleMap_MalePin'] = 'Male';
$txt['googleMap_FemalePin'] = 'Female';
$txt['googleMap_OnMove'] = 'Recently Added/Moved';
$txt['googleMap_bold'] = 'BOLD';
$txt['googleMap_AddPinNote'] = '<b>Click here to add or edit your pin location on the map.</b>';
$txt['googleMap_PleaseClick'] = 'Click to place your pin on the map or click on your pin to remove it.';
$txt['googleMap_Disclaimer'] = '<br />Place your pin as close to your location as you feel comfortable.<br />Use the search function on the map to quickly move to a location (city, zip code, etc) then click on the map to set your pin.<br /><br />When you are done click on "Change Profile" to save your location.';

// Permissions / errors
$txt['cannot_googleMap_view'] = 'Sorry, you are not allowed to view the Member Map.';
$txt['permissionname_googleMap_view'] = 'View Member Map';
$txt['permissionhelp_googleMap_view'] = 'Allow the people to view the Member Map.  If not set, the people will not see the map.';
$txt['cannot_googleMap_place'] = 'Sorry, you are not allowed to place a pin on the Member Map.';
$txt['permissionname_googleMap_place'] = 'Place Pin on the Member Map';
$txt['permissionhelp_googleMap_place'] = 'Allow the people place their pin the Member Map.  If not set, the people will not be able to place their pins.';
$txt['googleMap_xmlerror'] = 'Error making the ajax request';
$txt['googleMap_error'] = 'Unable to read the map pin data, result was';

// Map addon settings
$txt['googleMap_license'] = 'The Google JavaScript Maps API V3 is a free service, available for any web site that is free to consumers. By enabling and using this ElkArte modification you are acknowledging and agreeing to the <a href="https://developers.google.com/maps/terms?csw=1" target="_blank"> [Google terms of use]</a>';
$txt['googleMap_Key'] = 'Google Map API key';
$txt['googleMap_Key_desc'] = '<a class="linkbutton" href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Get an API key</a>';
$txt['googleMap_MapSettings'] = 'General Map Settings';
$txt['googleMap_desc'] = 'From here you can control the settings for how the Member Map appears and functions on your forum';
$txt['googleMap_Enable'] = 'Enable the Member Map Modification';
$txt['googleMap_ButtonLocation'] = 'Select the top menu location for the Member Map button';
$txt['googleMap_DefaultLat'] = 'The default Latitude';
$txt['googleMap_DefaultLat_info'] = 'Latitude for map center';
$txt['googleMap_DefaultLong'] = 'The default Longitude';
$txt['googleMap_DefaultLong_info'] = 'Longitude for map center';
$txt['googleMap_DefaultZoom'] = 'The default Zoom Level';
$txt['googleMap_DefaultZoom_Info'] = 'Defines the default map location by defining the map center and zoom level. Examples: Europe: Lat:48, Lng:15, Zm:4 / USA: Lat:39, Lng:-95, Zm:4';
$txt['googleMap_Type'] = 'The type of map to display by default';
$txt['googleMap_roadmap'] = 'RoadMap';
$txt['googleMap_satellite'] = 'Satellite';
$txt['googleMap_hybrid'] = 'Hybrid';
$txt['googleMap_EnableLegend'] = 'Display a Pin Legend below the map';
$txt['googleMap_PinNumber'] = 'Maximum number of pins to show on map';
$txt['googleMap_PinNumber_info'] = 'Enter 0 for no limit';
$txt['googleMap_Sidebar'] = 'Where to show the map sidebar';
$txt['googleMap_nosidebar'] = 'No Sidebar';
$txt['googleMap_rightsidebar'] = 'Right Sidebar';
$txt['googleMap_leftsidebar'] = 'Left Sidebar';
$txt['googleMap_BoldMember'] = 'Show recently added / moved pins as <strong>bold</strong> in the sidebar';

$txt['googleMap_MemeberpinSettings'] = 'Member Pin Style';
$txt['googleMap_PinStyle'] = 'What style of member pin to use';
$txt['googleMap_PinSize'] = 'Enter the size of the member icon pin';
$txt['googleMap_plainpin'] = 'Plain Pin';

$txt['googleMap_ClusterpinSettings'] = 'Cluster Pin Settings';
$txt['googleMap_EnableClusterer'] = 'Enable Pin Clustering';
$txt['googleMap_EnableClusterer_info'] = 'Groups nearby pins into a single cluster pin to prevent overloading a map.  Zooming in on a cluster will expand it out to the individual pins.';
$txt['googleMap_MinMarkerPerCluster'] = 'Minimum number of pins per grid to generate a cluster';
$txt['googleMap_MinMarkertoCluster'] = 'Minimum number of pins on the map before clustering starts (if enabled)';
$txt['googleMap_GridSize'] = 'The size of the cluster searching grid';
$txt['googleMap_MaxLinesCluster'] = 'Maximum number of info lines to display in a Cluster Info Box';
$txt['googleMap_ClusterpinStyle'] = 'Cluster Pin Style';
$txt['googleMap_PinBackground'] = 'Background color of the member pin as a six-digit HTML hexadecimal color';
$txt['googleMap_ClusterBackground'] = 'Background color of the cluster pin as a six-digit HTML hexadecimal color';
$txt['googleMap_ClusterForeground'] = 'Text color of the cluster pin as a six-digit HTML hexadecimal color';
$txt['googleMap_ClusterStyle'] = 'What style of clustering pin to use';
$txt['googleMap_ClusterSize'] = 'Enter the size of the cluster pin';
$txt['googleMap_zonepin'] = 'Zone icon';
$txt['googleMap_peepspin'] = 'People icon';
$txt['googleMap_talkpin'] = 'Talk icon';
$txt['googleMap_ScalableCluster'] = 'Allow Clusters with more pins to grow in size';
$txt['googleMap_ScalableCluster_info'] = 'Allows cluster pins to grow dynamically in size depending on how many pins they contain';

// Who strings
$txt['whoall_googlemap'] = 'Viewing the <a href="' . $scripturl . '?action=GoogleMap">Member Map</a>.';
$txt['whoall_kml'] = 'Viewing the Google Earth Feed.';
