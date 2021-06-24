<?php
/*
Plugin Name:	Oxygen & Ultimate Member Email Integration
Description:    Fixes Oxygen and Ultimate Member integration issue for email. Since Oxygen doesn't allow for a theme location which causes issues with editing and saving UM email templates.
Version:        1.0.0
Text Domain:    oxygen-um-email-integration
License:		GPL-2.0+
License URI:	http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path:    /languages
*/

if ( ! defined( 'WPINC' ) ) {

	die;

}



// Override Ultimate Member's email save
function oumei_change_settings_before_save( $settings ) {

	// If Oxygen Isn't Defined, Return
	if ( ! defined('CT_VERSION' ) ) {

		return $settings;

	}

	// If not email template move on
	if ( empty( $settings['um_email_template'] ) ) {

		return $settings;

	}

	$template = $settings['um_email_template'];

	$content = stripslashes( $settings[ $template ] );

	// Get the template path "where it should be" if a theme existed
	$theme_template_path = UM()->mail()->get_template_file( 'theme', $template );

	// Remove oxygen's added 'fake.'
	$theme_template_path = str_replace('fake/', '', $theme_template_path);

	// Build the local plugin path
	$local_plugin_template_path =  plugin_dir_path( __FILE__ ) . $theme_template_path;

	// If file doesn't exist copy over the template
	if ( ! file_exists( $local_plugin_template_path ) ) {

		$plugin_template_path = UM()->mail()->get_template_file( 'plugin', $template );

		$temp_path = str_replace( trailingslashit( plugin_dir_path( __FILE__ )  ), '', $local_plugin_template_path );
		$temp_path = str_replace( '/', DIRECTORY_SEPARATOR, $temp_path );
		$folders = explode( DIRECTORY_SEPARATOR, $temp_path );
		$folders = array_splice( $folders, 0, count( $folders ) - 1 );
		$cur_folder = '';
		$plugin_dir = trailingslashit( plugin_dir_path( __FILE__ )  );

		foreach ( $folders as $folder ) {

			$prev_dir = $cur_folder;
			$cur_folder .= $folder . DIRECTORY_SEPARATOR;

			if ( ! is_dir( $plugin_dir . $cur_folder ) && wp_is_writable( $plugin_dir . $prev_dir ) ) {

				mkdir( $plugin_dir . $cur_folder, 0755 );

			}

		}
		

	}

	// Open and write local file
	$fp = fopen( $local_plugin_template_path, "w" );
	$result = fputs( $fp, $content );
	fclose( $fp );

	if ( $result !== false ) {
			unset( $settings['um_email_template'] );
			unset( $settings[ $template ] );
	}

	return $settings;

}



// Override Ultimate Member's email load
function oumei_locate_email_template( $template, $template_name ) {

	// If Oxygen Isn't Defined, Return
	if ( ! defined('CT_VERSION')) {

		return;

	}


	$theme_template_path = UM()->mail()->get_template_file( 'theme', $template_name );

	$theme_template_path = str_replace('fake/', '', $theme_template_path);

	$local_plugin_template_path =  plugin_dir_path( __FILE__ ) . $theme_template_path;


	if ( file_exists( $local_plugin_template_path ) ) {
		
		return $local_plugin_template_path;

	}

	return $template;
				  
}



add_filter( 'um_change_settings_before_save', 'oumei_change_settings_before_save', 10, 1 );
add_filter( 'um_locate_email_template', 'oumei_locate_email_template', 10, 2 );