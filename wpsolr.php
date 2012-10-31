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
if ( ! class_exists( 'WPSolr' ) ) {

// if not, then create it
class WPSolr {
	
	/**
	 * Constructor
	 */
	function WPSolr() {
		add_action( 'wp_loaded', array( &$this, 'wp_loaded' ) );
	}
	// old-style constructor for backward PHP compatibility
	function __construct() { $this->WPSolr(); }
	
	function wp_loaded() {
	
		// add filters
		add_filter( 'attachment_fields_to_edit', array( &$this, 'attachment_fields_to_edit' ), null, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'attachment_fields_to_save' ), null, 2 );
	}
	
	/**
	 * Modifies the list of fields available when editing uploaded media files
	 */
	function attachment_fields_to_edit( $fields, $post ) {
		$fields[ 'wpsolr' ][ 'label' ] = __( 'WPSolr Field' );
		$fields[ 'wpsolr' ][ 'value' ] = get_post_meta( $post->ID, "wpsolr", true );
		$fields[ 'wpsolr' ][ 'helps' ] = __( 'Here is some helpful text.' );
		return $fields;
	}
	
	/**
	 * Saves the data from the fields added by attachment_fields_to_edit
	 */
	function attachment_fields_to_save() {
	
	}
	
}
// then create an instance of the class
if ( ! isset( $wpsolr ) ) $wpsolr = new WPSolr;

} else {
	// WPSolr class already existed
	die( 'WPSolr class already exists!' );
}
