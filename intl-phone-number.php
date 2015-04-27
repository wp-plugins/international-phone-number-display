<?php 
    /*
    Plugin Name: International Phone Number Display
    Plugin URI: http://www.paulfp.net/wordpress-plugins/international-phone-number-display/
    Description: Automatically display telephone numbers in either standard national format or full international format (including dialling prefix) depending on where your web page is being viewed from.
    Author: Paul Freeman-Powell
    Version: 1.0.2
    Author URI: https://twitter.com/paulfp
    */

/*  Copyright 2015  Paul Freeman-Powell

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function intPnd_admin_actions() {
	add_options_page("International Phone Number Display", "International Phone Number Display", "manage_options", "InternationalPhoneNumberDisplay", "intPnd_admin");
}
 
add_action('admin_menu', 'intPnd_admin_actions');

function intPnd_tag_func( $atts ) {
    $a = shortcode_atts( array(
        'servicenumber' => 'something',
		'location' => 'GB'
    ), $atts );
	
	$url = 'http://ipinfo.io/' . $_SERVER['REMOTE_ADDR'] . '/json';
	$data = wp_remote_get($url);
	$countryCode = json_decode($data['body'])->country;
	
	if($data['response']['code'] === 200) {
		return formatNumberForInternational("{$a['servicenumber']}", "{$a['location']}", $countryCode);
	} else {
		return "{$a['servicenumber']}"; // fallback if no IP address to country code
	}
}
add_shortcode( 'intPnd', 'intPnd_tag_func' );

function formatNumberForInternational($serviceNumber, $location, $dialedFrom) {

$url = 'https://api.telecomscloud.com/v1/format/number/international/'.$serviceNumber.'/'.$location.'/'.$dialedFrom.'?pretty=true';

$headers = array( 'Authorization' => 'Basic ' . base64_encode(get_option('intPnd_sid').":".get_option('intPnd_token') ) );
$result = wp_remote_get( $url, array( 'headers' => $headers ) );
$responseBody = json_decode($result['body']);

	if($result['response']['code'] === 200) {
		return $responseBody->number;
	} else {
		return $serviceNumber; // fallback if unable to get response from API
	}

}

function intPnd_admin() {
    ?>
	<style type="text/css">
		label {
				display: block;
				width: 400px;
				text-align: right;
			}
	</style>
	<div class="wrap">
    <h2>International Phone Number Display Options</h2>
	<?php
	if($_POST['intPnd_hidden'] == 'Y') {
        //Form data sent
        $intPnd_sid = $_POST['intPnd_sid'];
		update_option('intPnd_sid', $intPnd_sid);
         
        $intPnd_token = $_POST['intPnd_token'];
		update_option('intPnd_token', $intPnd_token);  
        ?>
        <div class="updated"><p><strong>Options saved.</strong></p></div>
        <?php
    } else {
		$intPnd_sid = get_option('intPnd_sid');
		$intPnd_token = get_option('intPnd_token');
}       
	   ?>
	
	<p>The plugin will automatically display telephone numbers in the correct format for international users from any country in the world. It does this by looking up their IP address (using <a href="http://ipinfo.io" target="_blank">ipinfo.io</a>) and determining the country, then passing this information along with the phone number to the <a href="https://www.telecomscloud.com/api" target="_blank">Telecoms Cloud API</a> which formats the number correctly and adds the appropriate International Direct Dialing Number (IDD).</p>
	
	<p><strong>Example:</strong> You may have a London phone number - 02079460981 - which would be displayed like so for visitors from the following countries:</p>
	<ul>
		<li>UK: 020 7946 0981 (no prefix added - just spaces added to make number readable)</li>
		<li>US: 011 44 20 7946 0981</li>
		<li>Spain: 00 44 20 7946 0981</li>
	</ul>
	
	<p><strong>Usage:</strong> wherever you want a telephone number to be formatted automatically within your website, use the shortcode like so:</p>
	
	<pre>[intPnd servicenumber="02079460981" location="GB"]</pre>
	
	<p>Note: you must pass the 2-digit country code in which the telephone number is located. For a list, see <a href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2" target="_blank">http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2</a></p>
     
    <form name="intPnd_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="intPnd_hidden" value="Y">
        <h4>Your Telecoms Cloud API Settings</h4>
		<p>In order for the conversion to take place, you must enter your API access keys below.</p>
        <p><label>SID: <input type="text" name="intPnd_sid" value="<?php echo $intPnd_sid; ?>" size="30"></label></p>
        <p><label>Token: <input type="text" name="intPnd_token" value="<?php echo $intPnd_token; ?>" size="30"></label></p>
		
		<p>Get your API access keys for free at <a href="https://www.telecomscloud.com/sign-up.html?api">https://www.telecomscloud.com/sign-up.html?api</a></p>	
     
        <p class="submit">
        <input type="submit" name="Submit" value="Save Credentials" />
        </p>
    </form>
	
</div>
	<?php
}
?>
