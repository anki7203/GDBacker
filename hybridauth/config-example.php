<?php
/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */
// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

$config = array(
			"base_url" => "-----",
			"providers" => array(
				"Google" => array(
					"enabled" => true,
					"keys" => array("id" => "-----", "secret" => "-----"),
				),
				"Facebook" => array(
					"enabled" => true,
					"keys" => array("id" => "-----", "secret" => "-----"),
					"trustForwarded" => false
				),
				"Twitter" => array(
					"enabled" => true,
					"keys" => array("key" => "-----", "secret" => "-----"),
					"includeEmail" => false
				),
			),
			"debug_mode" => true,
			"debug_file" => "hybridauth-log.txt"
);