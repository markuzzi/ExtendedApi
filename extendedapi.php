<?php

/**
 * Description of extendedapi
 *
 * @author Markus Luckey <luckey at kernblick.de>
 */
require_once (INCLUDE_DIR . 'class.plugin.php');
require_once (INCLUDE_DIR . 'class.signal.php');
require_once (INCLUDE_DIR . 'class.dispatcher.php');
require_once (INCLUDE_DIR . 'class.osticket.php');

require_once ('config.php');

define ( 'EXTENDEDAPI_PLUGIN_VERSION', '0.1' );

define ( 'EXTENDEDAPI_TABLE', TABLE_PREFIX . 'extendedapi' );

define ( 'OST_WEB_ROOT', osTicket::get_root_path ( __DIR__ ) );

define ( 'EXTENDEDAPI_WEB_ROOT', OST_WEB_ROOT . 'scp/dispatcher.php/extendedapi/' );

define ( 'OST_ROOT', INCLUDE_DIR . '../' );

define ( 'PLUGINS_ROOT', INCLUDE_DIR . 'plugins/' );

define ( 'EXTENDEDAPI_PLUGIN_ROOT', __DIR__ . '/' );
define ( 'EXTENDEDAPI_INCLUDE_DIR', EXTENDEDAPI_PLUGIN_ROOT . 'include/' );
define ( 'EQUIPMENT_VENDOR_DIR', EXTENDEDAPI_PLUGIN_ROOT . 'vendor/' );

require_once (EXTENDEDAPI_INCLUDE_DIR . 'eapi.tickets.php');
require_once (EXTENDEDAPI_INCLUDE_DIR . 'eapi.users.php');

// require_once (EQUIPMENT_VENDOR_DIR . 'autoload.php');

class ExtendedApiPlugin extends Plugin {
	var $config_class = 'ExtendedApiConfig';

	function bootstrap() {
		$config = $this->getConfig ();

		// Fetch the config from the parent Plugin class
		$config = $this->getConfig();

		// TODO: check if global cfg is the same?
		TicketExtendedApiController::$config = $config;

		if ($config->get ( 'extendedapi_enable' )) {
			Signal::connect ( 'api', array (
					'ExtendedApiPlugin',
					'callbackDispatch'
			) );
		}
	}

	static public function callbackDispatch($object, $data) {

		$tickets_urls = array(
			url_get(  "^/tickets$",  array('eapi.tickets.php:TicketExtendedApiController', 'listTickets')),
			url_post( "^/tickets?$", array('eapi.tickets.php:TicketExtendedApiController', 'create')),
			url('^/tickets?/', patterns('',
				url_post(   "^(?P<id>\d+)$",                      array('eapi.tickets.php:TicketExtendedApiController', 'update')),
				url_post(   "^(?P<id>\d+)/notes$",                array('eapi.tickets.php:TicketExtendedApiController', 'postNote')),
				url_post(   "^(?P<id>\d+)/internal-notes$",       array('eapi.tickets.php:TicketExtendedApiController', 'postInternalNote')),
				url_get(    "^(?P<id>\d+)$",                      array('eapi.tickets.php:TicketExtendedApiController', 'get')),
				url_delete( "^(?P<id>\d+)$",                      array('eapi.tickets.php:TicketExtendedApiController', 'delete')),
				url_get(    "^(?P<id>\d+)/notes$",                array('eapi.tickets.php:TicketExtendedApiController', 'thread')),
				url_get(    "^(?P<id>\d+)/fields$",               array('eapi.tickets.php:TicketExtendedApiController', 'fields')),
			)),
			url_get(  "^/users$",  array('eapi.users.php:UserApiController', 'list')),
			url_post( "^/users?$", array('eapi.users.php:UserApiController', 'create')),
			url('^/users?/', patterns('',
				url_post(   "^(?P<id>\d*)/account$",              array('eapi.users.php:UserApiController', 'createAccount')),
				url_post(   "^(?P<id>\d*)$",                      array('eapi.users.php:UserApiController', 'update')),
				url_get(    "^(?P<id>\d+)$",                      array('eapi.users.php:UserApiController', 'get')),
				url_delete( "^(?P<id>\d+)$",                      array('eapi.users.php:UserApiController', 'delete')),
			)),
		);
		
		foreach ($tickets_urls as $url) { $object->append($url); }
	}
}
