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
		add_action( 'admin_menu',        array( &$this, 'admin_menu' ) );
		add_action( 'admin_init',        array( &$this, 'admin_init' ) );
		add_action( 'wp_ajax_add_field', array( &$this, 'add_field'  ) );
		
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
		add_action( 'admin_print_styles-'  . $wpsolr_options_page, array( &$this, 'admin_print_styles'  ) );
	}
	
	/**
	 * Output necessary javascripts
	 */
	function admin_print_scripts() {
		wp_enqueue_script( 'wpsolr-js' );
	}

	/**
	 * Output necessary css
	 */
	function admin_print_styles() {
		wp_enqueue_style( 'wpsolr-css' );
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
		/*
		add_settings_field( 'field_name',  __( 'Field Name'  ), array( &$this, 'field_name_field'  ), 'wpsolr-settings', 'wpsolr_settings_section' );
		add_settings_field( 'field_label', __( 'Field Label' ), array( &$this, 'field_label_field' ), 'wpsolr-settings', 'wpsolr_settings_section' );
		add_settings_field( 'field_helps', __( 'Field Help'  ), array( &$this, 'field_helps_field' ), 'wpsolr-settings', 'wpsolr_settings_section' );
		add_settings_field( 'field_type',  __( 'Field Type'  ), array( &$this, 'field_type_field'  ), 'wpsolr-settings', 'wpsolr_settings_section' );
		*/
		// register styles and scripts
		wp_register_script( 'wpsolr-js',  plugins_url( 'js/wpsolr.js',   __FILE__ ), 'jquery' );
		wp_register_style(  'wpsolr-css', plugins_url( 'css/wpsolr.css', __FILE__ ) );
	}
	
	/**
	 * Functions to output content of the settings page
	 */
	function wpsolr_settings_section() {
		$fields = $this->settings[ 'fields' ];
		echo '<p>Configure the settings for the added metadata field.</p>';
		?>
		<table class="form-table" id="wpsolr-fields-table">
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Label</th>
					<th scope="col">Help</th>
					<th scope="col">Type</th>
					<th scope="col">Extra</th>
					<th scope="col">Remove</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				if ( is_array( $fields ) ) {
					foreach ( $fields as $i => $f ) {
					?>
					<tr>
						<th><input type="text" name="wpsolr_settings[fields][<?php echo $i; ?>][field_name]"  value="<?php echo $f[ 'field_name' ]; ?>"  /></th>
						<td><input type="text" name="wpsolr_settings[fields][<?php echo $i; ?>][field_label]" value="<?php echo $f[ 'field_label' ]; ?>" /></td>
						<td><input type="text" name="wpsolr_settings[fields][<?php echo $i; ?>][field_helps]" value="<?php echo $f[ 'field_helps' ]; ?>" /></td>
						<td><?php $this->field_type_select( $i, $f[ 'field_type' ] ); ?></td>
						<td><?php $this->field_type_extras( $i, $f[ 'field_type' ], $f[ 'choice_type_options' ] ); ?></td>
						<td></td>
					</tr>
					<?php
					}
				} else {
				
				}
				?>
			</tbody>
		</table>
		<div id="wpsolr-fields-table-buttons">
			<button type="button" class="button" id="wpsolr_add_field_button">Add a Field</button>
		</div>
		<?php
	}
	function field_type_select( $i, $ft = 'text' ) {
		?>
		<select name="wpsolr_settings[fields][<?php echo $i; ?>][field_type]" class="wpsolr_settings_field_type_selector">
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
		<?php
	}
	function field_type_extras( $i, $ft = 'text', $opts = '' ) {
		$display = 'text';
		if ( in_array( $ft, array( 'checkbox', 'radio', 'select' ) ) ) $display = 'choice';
		if ( in_array( $ft, array( 'number', 'range', 'date', 'time', 'datetime' ) ) ) $display = 'range';
		?>
		<div class="choice_type_options type_options" style="display:<?php echo 'choice' == $display ? 'block' : 'none'; ?>;">
			<textarea name="wpsolr_settings[fields][<?php echo $i; ?>][choice_type_options]" style="float:left;"><?php 
				echo $opts; 
			?></textarea><!--enter choices, one per line, or<br>enter key/value pairs, one per line-->
		</div>
		<div class="range_type_options type_options" style="display:<?php echo 'range' == $display ? 'block' : 'none'; ?>;">
			<label>Max: <input type="number" name="wpsolr_settings[fields][<?php echo $i; ?>][field_max]"></label><br>
			<label>Min: <input type="number" name="wpsolr_settings[fields][<?php echo $i; ?>][field_min]"></label><br>
			<label>Step: <input type="number" name="wpsolr_settings[fields][<?php echo $i; ?>][field_step]"></label> 
		</div>
		<?php
	}

	/**
	 * Modifies the list of fields available when editing uploaded media files
	 */
	function attachment_fields_to_edit( $fields, $post ) {
		echo '<pre>' . print_r( $this->settings, true ) . '</pre>';
		$flds = $this->settings[ 'fields' ];
		if ( is_array( $flds ) ) {
			foreach ( $flds as $i => $f ) {
				$ft   = $f[ 'field_type' ];
				$fn   = $f[ 'field_name' ];
				$val  = get_post_meta( $post->ID, $fn, true );
				$name = 'attachments[' . $post->ID . '][' . $fn . ']';
				$fields[ $fn ][ 'label' ] = $f[ 'field_label' ];
				$fields[ $fn ][ 'helps' ] = $f[ 'field_helps' ];
				switch ( $ft ) {
					case 'text':
						$fields[ $fn ][ 'value' ] = $val;
						break;
					case 'email':
					case 'tel':
					case 'url':
						// output a text box of the appropriate type
						$fields[ $fn ][ 'input' ] = 'html';
						$fields[ $fn ][ 'html'  ] = '<input type="' . $ft . '" name="'. $name . '" value="' . $val . '">';
						break;
					case 'textarea':
						$fields[ $fn ][ 'input' ] = 'html';
						$fields[ $fn ][ 'html'  ] = '<textarea name="' . $name . '">' . $val . '</textarea>';
						break;
					case 'radio':
						$fields[ $fn ][ 'input' ] = 'html';
						$opts = $f[ 'choice_type_options' ];
						$opts = explode( "\n", $opts );
						$radios = '';
						foreach ( $opts as $opt ) {
							$v = strtok( trim( $opt ), '/' );
							$n = strtok( '/' );
							$checked = $v == $val ? ' checked' : '';
							$radios .= '<label><input type="radio" name="' . $name . '" value="' . $v . '"' . $checked . '> ' . $n . '</label><br>';
						}
						$fields[ $fn ][ 'html'  ] = $radios;
						break;
					case 'checkbox':
						$fields[ $fn ][ 'input' ] = 'html';
						$opts = $f[ 'choice_type_options' ];
						$opts = explode( "\n", $opts );
						$boxes = '';
						foreach ( $opts as $opt ) {
							$v = strtok( trim( $opt ), '/' );
							$n = strtok( '/' );
							$checked = in_array( $v, $val ) ? ' checked' : '';
							$boxes .= '<label><input type="checkbox" name="' . $name . '[]" value="' . $v . '"' . $checked . '> ' . $n . '</label><br>';
						}
						$fields[ $fn ][ 'html'  ] = $boxes;
						break;
					case 'number':
					case 'range':
					case 'date':
					case 'time':
					case 'datetime':
						$val  =  '' !== $val ? ' value="'  . $val . '"': '';
						$max  = isset( $f[ 'field_max'  ] ) ? ' max="'  . $f[ 'field_max'  ] . '"' : '';
						$min  = isset( $f[ 'field_min'  ] ) ? ' min="'  . $f[ 'field_min'  ] . '"' : '';
						$step = isset( $f[ 'field_step' ] ) ? ' step="' . $f[ 'field_step' ] . '"' : '';
						$fields[ $fn ][ 'input' ] = 'html';
						$fields[ $fn ][ 'html'  ] = '<input type="' .$ft . '"' . $max . $min . $step . $val . '>';
						break;
				}
			}
		}
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
	 * Add a field to the table
	 */
	function add_field() {
		$i = $_GET[ 'next' ];
		?>
		<tr>
			<th><input type="text" name="wpsolr_settings[fields][<?php echo $i; ?>][field_name]"   /></th>
			<td><input type="text" name="wpsolr_settings[fields][<?php echo $i; ?>][field_label]"  /></td>
			<td><input type="text" name="wpsolr_settings[fields][<?php echo $i; ?>][field_helps]"  /></td>
			<td>
				<select name="wpsolr_settings[fields][<?php echo $i; ?>][field_type]" id="wpsolr_settings_field_type">
					<optgroup class="text_types" label="Text Types">
						<option value="text">Text (single line)</option>
						<option value="textarea">Text Area</option>
						<option value="email">Email</option>
						<option value="tel">Telephone Number</option>
						<option value="url">URL</option>
					</optgroup>
					<optgroup class="choice_types" label="Choice Types">
						<option value="checkbox">Check Boxes</option>
						<option value="radio">Radio Buttons</option>
						<option value="select">Dropdown Menu</option>
					</optgroup>
					<optgroup class="range_types" label="Range Types">
						<option value="number">Numeric</option>
						<option value="range">Numeric Range</option>
						<option value="date">Date</option>
						<option value="time">Time</option>
						<option value="datetime">Date and Time</option>
					</optgroup>
				</select>
			</td>
			<td><?php echo $this->field_type_select( $i ); ?></td>
			<td></td>
		</tr>
		<?php
		exit;
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
