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
        $wpsolr_options_page = add_options_page( 'WP Solr Settings', 'WP Solr Settings', 'manage_options', 'wpsolr-settings', array( &$this, 'wpsolr_settings_menu' ) );
    
		// enqueue scripts for this page
		add_action( 'admin_print_scripts-' . $wpsolr_options_page, array( &$this, 'admin_print_scripts' ) );
	}
	
	/**
	 * Output necessary javascripts
	 */
	function admin_print_scripts() {
		wp_enqueue_script( 'wpsolr-js' );
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
		add_settings_field( 'field_type',  __( 'Field Type'  ), array( &$this, 'field_type_field'  ), 'wpsolr-settings', 'wpsolr_settings_section' );

		// register styles and scripts
		wp_register_script( 'wpsolr-js', plugins_url( 'js/wpsolr.js', __FILE__ ), 'jquery' );
	}
	
	/**
	 * Functions to output content of the settings page
	 */
	function wpsolr_settings_section() {
		echo '<p>Configure the settings for the added metadata field.</p>';
	}
	function field_name_field() {
		echo '<input type="text" id="wpsolr_settings_field_name" name="wpsolr_settings[field_name]" value="' . $this->settings[ 'field_name' ] . '" /> (must have no spaces or non-word characters)';
	}
	function field_label_field() {
		echo '<input type="text" id="wpsolr_settings_field_label" name="wpsolr_settings[field_label]" value="' . $this->settings[ 'field_label' ] . '" /> ';
	}
	function field_helps_field() {
		echo '<input type="text" id="wpsolr_settings_field_helps" name="wpsolr_settings[field_helps]" value="' . $this->settings[ 'field_helps' ] . '" /> ';
	}
	function field_type_field() {
		$ft = $this->settings[ 'field_type' ];
		//echo $ft; exit;
		?>
		<select name="wpsolr_settings[field_type]" id="wpsolr_settings_field_type">
			<optgroup class="text_types" label="Text Types">
				<option value="text"<?php echo 'text' == $ft ? ' selected' : ''; ?>>Text (single line)</option>
				<option value="textarea"<?php echo 'textarea' == $ft ? ' selected' : ''; ?>>Text Area</option>
				<option value="email"<?php echo 'email' == $ft ? ' selected' : ''; ?>>Email</option>
				<option value="tel"<?php echo 'tel' == $ft ? ' selected' : ''; ?>>Telephone Number</option>
				<option value="url"<?php echo 'url' == $ft ? ' selected' : ''; ?>>URL</option>
			</optgroup>
			<optgroup class="choice_types" label="Choice Types">
				<option value="checkbox"<?php echo 'checkbox' == $ft ? ' selected' : ''; ?>>Check Boxes</option>
				<option value="radio"<?php echo 'radio' == $ft ? ' selected' : ''; ?>>Radio Buttons</option>
				<option value="select"<?php echo 'select' == $ft ? ' selected' : ''; ?>>Dropdown Menu</option>
			</optgroup>
			<optgroup class="range_types" label="Range Types">
				<option value="number"<?php echo 'number' == $ft ? ' selected' : ''; ?>>Numeric</option>
				<option value="range"<?php echo 'range' == $ft ? ' selected' : ''; ?>>Numeric Range</option>
				<option value="date"<?php echo 'date' == $ft ? ' selected' : ''; ?>>Date</option>
				<option value="time"<?php echo 'time' == $ft ? ' selected' : ''; ?>>Time</option>
				<option value="datetime"<?php echo 'datetime' == $ft ? ' selected' : ''; ?>>Date and Time</option>
			</optgroup>
		</select>
		<div class="choice_type_options type_options" style="display:none;">
			<p>Choice type options go here.</p>
		</div>
		<div class="range_type_options type_options" style="display:none;">
			<p>Range type options go here.</p>
		</div>
		<?php
	}
	
	/**
	 * Modifies the list of fields available when editing uploaded media files
	 */
	function attachment_fields_to_edit( $fields, $post ) {
		$fields[ $this->settings[ 'field_name' ] ][ 'label' ] = $this->settings[ 'field_label' ];
		$fields[ $this->settings[ 'field_name' ] ][ 'helps' ] = $this->settings[ 'field_helps' ];
		$fields[ $this->settings[ 'field_name' ] ][ 'value' ] = get_post_meta( $post->ID, $this->settings[ 'field_name' ], true );
		return $fields;
	}
	
	/**
	 * Saves the data from the fields added by attachment_fields_to_edit
	 */
	function attachment_fields_to_save( $post, $fields ) {
		// check to see that wpsolr data has been added
		if ( isset( $fields[ $this->settings[ 'field_name' ] ] ) ) {
			update_post_meta( $post[ 'ID' ], $this->settings[ 'field_name' ], $fields[ $this->settings[ 'field_name' ] ] );
		}
		return $post;
	}
	
	/**
	 * Validate user input to the settings pages
	 */
	function validate_wpsolr_settings( $input ) {
		// TO DO: sanitize user input here
		if ( isset( $input[ 'field_name' ] ) && $input[ 'field_name' ] != $this->settings[ 'field_name' ] ) {
			echo '<pre>' . print_r( $input, true ) . '</pre>'; exit;
		}
		return $input;
	}
	
}
// then create an instance of the class
if ( ! isset( $wpsolr ) ) $wpsolr = new WPSolr;

} else {
	// WPSolr class already existed
	die( 'WPSolr class already exists!' );
}
