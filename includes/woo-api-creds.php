<?php
global $ep_WooCommerce;

$wc_api_php = ea_PLUGINPATH .'/assets/wc-api-php/src';

require_once($wc_api_php.'/WooCommerce/Client.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/BasicAuth.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/HttpClient.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/HttpClientException.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/OAuth.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/Options.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/Request.php');
require_once($wc_api_php.'/WooCommerce/HttpClient/Response.php');

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

$ep_WooCommerce = new Client(
	'https://epimapi.making.me.uk',
	'ck_8b79606fc571108b8ecd0312304ae61aef77ca23',
	'cs_24ed3b548fb39c4b71cbe2b8e00fe76ad6826924',
	[
		//'wp_api' => true,
		'version' => 'wc/v3',
		'query_string_auth' => true
	]
);