<?php
/*
Plugin Name: WP Solr
Plugin URI: http://www.wpsolr.net/
Description: A WordPress plugin that uses Apache Solr to turn the native WordPress search into a full text search engine capable of indexing all types of documents that are uploaded to the site.
Version: 0.1.0
Author: Morgan Benton and Rachel Jacobson
Author URI: http://www.wpsolr.net/
License: GPLv3
*/

// check to see that the WPSolr class does not already exist
if ( ! class_exists( 'WPSolr' ) ) :

// if not, then create it
class WPSolr {
	
	/**
	 * Constructor
	 */
	function WPSolr() {
	}
	// old-style constructor for backward PHP compatibility
	function __construct() { $this->WPSolr(); }
}
// then create an instance of the class
if ( ! isset( $wpsolr ) ) $wpsolr = new WPSolr;

else:
	// WPSolr class already existed
	die( 'WPSolr class already exists!' );
endif;
