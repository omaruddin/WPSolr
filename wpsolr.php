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
	 * WP Solr Settings
	 */
	private $settings;

	
	/**
	 * Constructor
	 */
	function WPSolr() {
		// load our settings
		$this->settings = get_option( 'wpsolr_settings' );
		
		// add basic action
		add_action( 'wp_loaded', array( &$this, 'wp_loaded' ) );
	}
	// old-style constructor for backward PHP compatibility
	function __construct() { $this->WPSolr(); }
	
	function wp_loaded() {
		// add actions
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		
		// add filters
		add_filter( 'attachment_fields_to_edit', array( &$this, 'attachment_fields_to_edit' ), null, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'attachment_fields_to_save' ), null, 2 );
	}
	
	/**
	 * Add a settings menu for the plugin
	 */
    function admin_menu() {
        // WP Solr Settings Page
        add_options_page( 'WP Solr Settings', 'WP Solr Settings', 'manage_options', 'wpsolr-settings', array( &$this, 'wpsolr_settings_menu' ) );
    }

	/**
	 * Output the content for the WPSolr Settings Menu page
	 */
	function wpsolr_settings_menu() {
	    if ( 'true' == $_GET[ 'settings-updated' ] ) {
            $errors = get_settings_errors( 'wpsolr_settings_errors' );
            if ( is_array( $errors ) && count( $errors ) ) {
                foreach ( $errors as $error ) {
                    $messages[] = '<div id="' . $error->code . '" class="error fade"><p><strong>' . $error[ 'message' ] . '</strong></p></div>';
                }
            } else {
                $messages[] = '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved' ) . '.</p></div>';
            }
        }
		if ( is_array( $messages ) ) foreach ( $messages as $message ) echo $message;
        ?>
            <div class="wrap">
				<div id="icon-options-general" class="icon32"><br></div><h2>WP SOLR Settings</h2>
                <p>
					Please set up your WP Solr Settings here. 
				</p>
				<form method="post" action="options.php">
                    <?php settings_fields( 'wpsolr_settings' ); ?>
                    <?php do_settings_sections( 'wpsolr-settings' ); ?>
                    <p class="submit">
                        <input name="submit" type="submit" class="button-primary" value="<?php _e( 'Save Settings' ); ?>" />
                    </p>
                </form>
            </div>
        <?php
	}
	
	/**
	 * Register the settings for the WP SOLR app
	 */
	function admin_init() {
        // register settings
        register_setting( 'wpsolr_settings', 'wpsolr_settings', array( &$this, 'validate_wpsolr_settings' ) );
		
		// add a settings section
        add_settings_section( 'wpsolr_settings_section', 'Extra Metadata Fields', array( &$this, 'wpsolr_settings_section' ), 'wpsolr-settings' );
		
		// add settings fields
		add_settings_field( 'field_name',  __( 'Field Name'  ), array( &$this, 'field_name_field'  ), 'wpsolr-settings', 'wpsolr_settings_section' );
		add_settings_field( 'field_label', __( 'Field Label' ), array( &$this, 'field_label_field' ), 'wpsolr-settings', 'wpsolr_settings_section' );
		add_settings_field( 'field_helps', __( 'Field Help'  ), array( &$this, 'field_helps_field' ), 'wpsolr-settings', 'wpsolr_settings_section' );


	}
	
	/**
	 * Functions to output content of the settings page
	 */
	function wpsolr_settings_section() {
		echo '<p>Configure the settings for the added metadata field.</p>';
	}
	function field_name_field() {
		echo '<input type="text" id="wpsolr_results_per_page" name="wpsolr_settings[field_name]" value="' . $this->settings[ 'field_name' ] . '" /> (must have no spaces or non-word characters)';
	}
	function field_label_field() {
	
	}
	function field_helps_field() {
	
	}
	
	/**
	 * Modifies the list of fields available when editing uploaded media files
	 */
	function attachment_fields_to_edit( $fields, $post ) {
		//echo '<pre>' . print_r( $fields, true ) . '</pre>'; exit;
		$fields[ 'wpsolr' ][ 'label' ] = __( 'WPSolr Field' );
		$fields[ 'wpsolr' ][ 'helps' ] = __( 'Here is some helpful text.' );
		$fields[ 'wpsolr' ][ 'input' ] = 'html';
		$wpsolr_values = array( 'Red', 'White', 'Blue' );
		$wpsolr_options = '<option value="">Choose one...</option>';
		$wpsolr_value = get_post_meta( $post->ID, "wpsolr", true );
		foreach ( $wpsolr_values as $val ) {
			$selected = $val == $wpsolr_value ? ' selected' : '';
			$wpsolr_options .= '<option value="' . $val . '"' . $selected . '>' . $val . '</option>';
		}
		$fields[ 'wpsolr' ][ 'html'  ] = '<select name="attachments[' . $post->ID . '][wpsolr]">' .
											$wpsolr_options .
										 '</select>';
		/**/
		return $fields;
	}
	
	/**
	 * Saves the data from the fields added by attachment_fields_to_edit
	 */
	function attachment_fields_to_save( $post, $fields ) {
		// check to see that wpsolr data has been added
		if ( isset( $fields[ 'wpsolr' ] ) ) {
			update_post_meta( $post[ 'ID' ], 'wpsolr', $fields[ 'wpsolr' ] );
		}
		return $post;
	}
	
	/**
	 * Validate user input to the settings pages
	 */
	function validate_wpsolr_settings( $input ) {
		// TO DO: sanitize user input here
		return $input;
	}
	
}
// then create an instance of the class
if ( ! isset( $wpsolr ) ) $wpsolr = new WPSolr;

} else {
	// WPSolr class already existed
	die( 'WPSolr class already exists!' );
}
