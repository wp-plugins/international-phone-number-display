<?php 
    /*
    Plugin Name: International Phone Number Display
    Plugin URI: http://www.paulfp.net/wordpress-plugins/international-phone-number-display/
    Description: Automatically display telephone numbers in either standard national format or full international format (including dialling prefix) depending on where your web page is being viewed from.
    Author: Paul Freeman-Powell
    Version: 2.0.0
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

define("TC_API_BASE_URL", "https://api.telecomscloud.com");

function intPnd_admin_actions() {
	add_options_page("International Phone Number Display", "International Phone Number Display", "manage_options", "InternationalPhoneNumberDisplay", "intPnd_admin");
}
 
add_action('admin_menu', 'intPnd_admin_actions');

function intPnd_tag_func( $atts ) {
    $a = shortcode_atts( array(
        'servicenumber' => 'something',
		'location' => 'GB'
    ), $atts );
	
	$args = array(
    'timeout'     => 30,
    'redirection' => 5,
    'httpversion' => '1.0',
    'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
    'blocking'    => true,
    'headers'     => array(),
    'cookies'     => array(),
    'body'        => null,
    'compress'    => false,
    'decompress'  => true,
    'sslverify'   => true,
    'stream'      => false,
    'filename'    => null
);
	
	$url = 'http://ipinfo.io/' . $_SERVER['REMOTE_ADDR'] . '/json';
	$data = wp_remote_get($url, $args);
	$countryCode = json_decode($data['body'])->country;
	
	if($data['response']['code'] === 200) {
		return formatNumberForInternational("{$a['servicenumber']}", "{$a['location']}", $countryCode);
	} else {
		return "{$a['servicenumber']}"; // fallback if no IP address to country code
	}
}
add_shortcode( 'intPnd', 'intPnd_tag_func' );

function formatNumberForInternational($serviceNumber, $location, $dialedFrom) {

// check for cached value
$cachedValue = get_option('intPnd_'.$serviceNumber.'_'.$location.'_'.$dialedFrom);
if($cachedValue !== FALSE) {
	return $cachedValue;
}

$endPoint = '/v1/format/number/international/'.$serviceNumber.'/'.$location.'/'.$dialedFrom.'?pretty=true';
$url = TC_API_BASE_URL . $endPoint;

$apiToken = intPnd_token();
if(!$apiToken) {
	return $serviceNumber;
}

$url .= '&access_token=' . $apiToken;

 $args = array(
    'timeout'     => 30,
    'redirection' => 5,
    'httpversion' => '1.0',
    'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
    'blocking'    => true,
    'headers'     => array(),
    'cookies'     => array(),
    'body'        => null,
    'compress'    => false,
    'decompress'  => true,
    'sslverify'   => true,
    'stream'      => false,
    'filename'    => null
);

$result = wp_remote_get( $url, $args );

if(count($result->errors) > 0) {
	return $serviceNumber;
}
$responseBody = json_decode($result['body']);

	if($result['response']['code'] === 200) {
		// cache for future identical requests
		update_option('intPnd_'.$serviceNumber.'_'.$location.'_'.$dialedFrom, $responseBody->number);
		
		// return for showing now
		return $responseBody->number;
	} else {
		return $serviceNumber; // fallback if unable to get response from API
	}

}

function intPnd_token() {

	// Get last saved Token & Expiry from WP Database
	$TelecomsCloudAPI_accessToken = get_option('TelecomsCloudAPI_accessToken');
	$TelecomsCloudAPI_accessToken_expiry = get_option('TelecomsCloudAPI_accessToken_expiry');
	
	$currentDate = new DateTime('now');
	$now = $currentDate->format('Y-m-d H:i:s');
	
	if( (!$TelecomsCloudAPI_accessToken) OR (!$TelecomsCloudAPI_accessToken_expiry) OR ($TelecomsCloudAPI_accessToken_expiry < $now) ) {
		// Token expired, get a new one and save it etc.
		$client_id = get_option('intPnd_clientID');
		$client_secret = get_option('intPnd_clientSecret');
		
		if( (!$client_id) OR (!$client_secret) ) {
			return false;
		}
		
		return intPnd_getNewToken($client_id, $client_secret);
	} else {
		// Current token still valid for use
		return $TelecomsCloudAPI_accessToken;
	}

}

function intPnd_getNewToken($client_id, $client_secret) {
	// Validate API Credentials provided
	$endPoint = "/v1/authorization/oauth2/grant-client";
	$url = TC_API_BASE_URL . $endPoint;

	//open connection
	$ch = curl_init();

	$fields = array(
		'client_id' => urlencode($client_id),
		'client_secret' => urlencode($client_secret)
		);
	$fields_string = json_encode($fields, true);

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($fields_string))
	);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);

	$result = json_decode($result, true);
	
	if(array_key_exists('error', $result)) {
		return false;
	} else {
		
		$expiresDate = new DateTime('now');
		$expiresDate->add(new DateInterval('PT' . $result['expires_in'] . 'S'));
		$expiresDate = $expiresDate->format('Y-m-d H:i:s');
		update_option('TelecomsCloudAPI_accessToken', $result['access_token']);
		update_option('TelecomsCloudAPI_accessToken_expiry', $expiresDate);
			
		return $result['access_token'];
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
        
		$intPnd_clientID = trim($_POST['intPnd_clientID']);
		$intPnd_clientSecret = trim($_POST['intPnd_clientSecret']);
		
		$result = intPnd_getNewToken($intPnd_clientID, $intPnd_clientSecret);
		
		if(!$result) {
			?>
			<div class="error"><p><strong>The Credentials you entered for the Telecoms Cloud API were not valid. Please check them and try again.</strong></p></div>
			<?php
		} else {
			update_option('intPnd_clientID', $intPnd_clientID);
			update_option('intPnd_clientSecret', $intPnd_clientSecret);
			?>
			<div class="updated"><p><strong>Options saved.</strong></p></div>
			<?php
		}
    } else {
		$intPnd_clientID = get_option('intPnd_clientID');
		$intPnd_clientSecret = get_option('intPnd_clientSecret');
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
        <p><label>Client ID: <input type="text" name="intPnd_clientID" value="<?php echo $intPnd_clientID; ?>" size="30" required="required" /></label></p>
        <p><label>Client Secret: <input type="text" name="intPnd_clientSecret" value="<?php echo $intPnd_clientSecret; ?>" size="30" required="required" /></label></p>
		
		<?php
			$TelecomsCloudAPI_accessToken = get_option('TelecomsCloudAPI_accessToken');
			$TelecomsCloudAPI_accessToken_expiry = get_option('TelecomsCloudAPI_accessToken_expiry');
			if( ($TelecomsCloudAPI_accessToken !== FALSE) AND ($TelecomsCloudAPI_accessToken_expiry !== FALSE) ) {
		?>
		<p>Your current access token is <strong><?=$TelecomsCloudAPI_accessToken;?></strong> which expires on <strong><?=$TelecomsCloudAPI_accessToken_expiry;?></strong>. The plugin will automatically request a new access token when this expires.</p>
		<?php } ?>
		
		<p>Get your API access keys for free at <a href="https://www.telecomscloud.com/sign-up.html?api">https://www.telecomscloud.com/sign-up.html?api</a></p>	
     
        <p class="submit">
        <input type="submit" name="Submit" value="Save Credentials" />
        </p>
    </form>
	
</div>
	<?php
}
?>
