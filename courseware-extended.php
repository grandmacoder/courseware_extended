<?php
/**
 * Plugin Name: Courseware Extended
 * Version: 1.0.0
 * Plugin URI: http://grandmacoder.com
 * Description: Extends wp courseware
 * Author: Amy Carlson
 * Author URI: http://grandmacoder.com
 */
/** The current version of the database. */
define('Courseware_extended_PLUGIN_VERSION', 			'1.0.0');		// Used for plugin updates
/** The ID used for menus */
define('COURSEWARE_EXTENDED_PLUGIN_ID', 				'CE_courseware');
/** The ID of the plugin for update purposes, must be the file path and file name. */
define('COURSEWARE_EXTENDED_UPDATE_ID', 		'courseware_extended/courseware-extended.php');
// Admin Only
if (is_admin()) {
	// Plugin-specific
include 'lib/ce-course-extras.php';
// AJAX
include 'lib/ajax_admin.inc.php';
}
function CE_plugin_init()
{
    $startPageCatID = 539;
	$introPageCatID = 538;
	add_option( 'intro_page_categories',  $introPageCatID, '', 'yes' );
	add_option( 'start_page_categories', $startPageCatID, '', 'yes' );
	$aCourseTypes =array('learning module','mini-module','LERN','short-course','inactive');
	add_option( 'course_types', $aCourseTypes, '', 'yes' );
	add_action('plugins_loaded','');

	// ### Admin
	if (is_admin())
	{
		//add media for file media library uploader
		wp_enqueue_media();
		// Menus
		add_action('admin_menu','CE_menu_MainMenu');
        // add the bootstrap for form elements
		wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
		//add the jquery
        wp_enqueue_script( 'ce_ajax', plugin_dir_url( __FILE__ ) . 'js/ce-admin.js', array( 'jquery' ) );
		wp_enqueue_script( 'bootstrap_jquery', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array( 'jquery' ) );
	    //since the php included for ajax calls this should localize the callbacks
						//name of ajax callback     name of object referenced in js        path to wordpress ajax handler
	     wp_localize_script( 'ce_ajax', 'CEAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		
	}
    // ### Frontend
	else
	{
		// Scripts and styles
		CE_addCustomScripts_FrontEnd();
        // Shortcodes
    }
}
add_action('init', 'CE_plugin_init');
function CE_menu_MainMenu()
{
//add a menu item for course extras
add_menu_page( 'Course Extras', 'Course Extras',  'manage_options', 'courseware-extras', 'add_courseware_extras' );	
}
/**
 * Add the scripts we want loaded in the header.
 */
function CE_addCustomScripts_FrontEnd()
{
	if (is_admin()) {
		return;
	}
//add scripts here 
}
?>
