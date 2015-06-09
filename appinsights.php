<?php
/**
 * @package AppInsights
 */
/**
 * Plugin Name: Application Insights Dashboard Beta
 * Plugin URI: https://wordpress.org/plugins/application-insights-dashboard-beta/
 * Description: Application Insights Dashboard gives you the ability to view your Application Insights data in your WordPress dashboard.
 * Version: 2.0a
 * Author: Sachin Jain
 * Author URI: 
 * License: GPL2
 */
/*  Copyright 2015  Sachin Jain

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die("No script kiddies please!");

include_once 'install/install.php';
include_once 'install/uninstall.php';
include_once 'config.php';
include_once 'admin/appinsights-settings.php';

register_activation_hook ( __FILE__, array(
    'AppInsights_Install',
    'install'
));

register_uninstall_hook ( __FILE__, array(
    'AppInsights_Uninstall',
    'uninstall'
));

add_action('init', 'appinsights_register_session');
add_action('admin_init', 'appinsights_redirect_to_settings_page');
add_action('wp_dashboard_setup', 'appinsights_setup');
add_action('admin_enqueue_scripts', 'appinsights_admin_enqueue_styles');
add_action('admin_menu', 'appinsights_plugin_menu');
add_action( 'wp_ajax_display_appinsights_components', 
    array(
        'AppInsights_Settings', 
        'display_appinsights_components'
    ) 
);
add_action('wp_ajax_appinsights_refresh_statsdata', 'appinsights_refresh_statsdata');

function appinsights_register_session() {
	if ( !session_id() ) {
		session_start();
	}
}

function appinsights_redirect_to_settings_page() {
	$basename = basename( $_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING'] );

	if($basename == 'options-general.php' && isset($_GET['state']) && !isset($_GET['page'])) {
		$state = urldecode($_GET['state']);
		parse_str($state, $state_vars);
		if (isset($state_vars['page']) && $state_vars['page'] == 'appinsights') {
			wp_redirect(add_query_arg(array('page' => 'appinsights')));
			exit;
		}
	}
}

function appinsights_setup() {
	global $AppInsights_Config;
	
	include_once ($AppInsights_Config->plugin_path . '/appinsights-tools.php');
	$tools = new AppInsights_Tools();
	
	if ($tools->check_roles($AppInsights_Config->options['appinsights_access_back'])) {
		include_once (dirname ( __FILE__ ) . '/admin/appinsights-dashboard-widgets.php');
		
		wp_add_dashboard_widget( 
		    'appinsights-widget', 
		    __ ( "Microsoft Azure Application Insights Dashboard", 'appinsights' ), 
		    array (
		        'AppInsights_Widgets',
		        'appinsights_dashboard_widgets'
			), 
			$control_callback = null 
		);
	}
}

function appinsights_admin_enqueue_styles($hook) {
    if ('settings_page_appinsights' != $hook && 'index.php' != $hook) {
        return;
    }
    
    wp_register_style('appinsights', plugin_dir_url( __FILE__ ) . 'admin/css/appinsights.css');
    wp_register_style('nvd3', plugin_dir_url( __FILE__ ) . 'admin/css/nv.d3.css');
    
    wp_enqueue_style('appinsights');
    wp_enqueue_style('nvd3');
    
    wp_enqueue_script( 'd3', plugin_dir_url( __FILE__ ) . 'admin/js/d3.v3.js', array() );
    wp_enqueue_script( 'nvd3', plugin_dir_url( __FILE__ ) . 'admin/js/nv.d3.js', array() );
    wp_enqueue_script( 'appinsights-tooltip', plugin_dir_url( __FILE__ ) . 'admin/js/tooltip.js', array() );
    wp_enqueue_script( 'appinsights-utils', plugin_dir_url( __FILE__ ) . 'admin/js/utils.js', array() );
    wp_enqueue_script( 'appinsights-legend', plugin_dir_url( __FILE__ ) . 'admin/js/legend.js', array() );
    wp_enqueue_script( 'appinsights-axis', plugin_dir_url( __FILE__ ) . 'admin/js/axis.js', array() );
    wp_enqueue_script( 'appinsights-scatter', plugin_dir_url( __FILE__ ) . 'admin/js/scatter.js', array() );
    wp_enqueue_script( 'appinsights-line', plugin_dir_url( __FILE__ ) . 'admin/js/line.js', array() );
    wp_enqueue_script( 'appinsights-linechart', plugin_dir_url( __FILE__ ) . 'admin/js/lineChart.js', array() );
    wp_enqueue_script( 'appinsights-ajax-script', plugin_dir_url( __FILE__ ) . 'admin/js/appinsights.js', array('jquery') );
    
    wp_localize_script( 
        'appinsights-ajax-script', 
        'AppInsights_Ajax',
        array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ), 
            'next_nonce' => wp_create_nonce( 'appinsights-next-nonce' ) 
        ) 
    );
}

function appinsights_plugin_menu() {
	add_options_page(
	    'Microsoft Azure Application Insights Settings',
	    'Application Insights Dashboard',
	    'manage_options',
	    'appinsights',
	    array('AppInsights_Settings', 'appinsights_plugin_options_page')
	);
}
function appinsights_refresh_statsdata() {

        $query  = trim($_POST['query']);
        $period = trim($_POST['period']);
        if( $query != "" && $period != "") {
            global $AppInsights_Config;
               
	        $AppInsights_Config->options ['appinsights_default_metric'] = $query;
		    $AppInsights_Config->options ['appinsights_default_dimension'] = $period;

            if (current_user_can ( 'manage_options' )) {
	    	    if (isset( $_POST['component_id'] )) {
                    $component_id = $_POST['component_id'];
	    		    $AppInsights_Config->options['appinsights_component'] = $component_id;
	            }
            }           
            
            if ($AppInsights_Config->options['appinsights_token']) {
			    include_once ($AppInsights_Config->plugin_path . '/appinsights-api.php');
			    global $AppInsights_API;
		    } 
            $AppInsights_Config->set_plugin_options ();
            include (dirname ( __FILE__ ) . '/admin/appinsights-ajax.php');
            die();
        }
        echo '<p>' . __ ( "No stats available.", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "Change settings", 'appinsights' ), 'secondary' ) . '</form>';
        die();
    } 
?>