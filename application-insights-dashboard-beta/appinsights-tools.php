<?php
class AppInsights_Tools {
	function appinsights_clear_cache() {
		global $wpdb;
		
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_appinsights%%'" );
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_appinsights%%'" );
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_insights%%'" );
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_insights%%'" );
	}
	
	function appinsights_cleanup_timeouts() {
		global $wpdb;
		
		$transient = get_transient ( "appinsights_cleanup_timeouts" );
		
		if (empty ( $transient )) {
			$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_appinsights%%'" );
			$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_insights%%'" );
			set_transient ( "appinsights_cleanup_timeouts", '1', 60 * 60 * 24 * 3 );
		}
	}
	
	function check_roles($access_level, $tracking = false) {
		if (is_user_logged_in () && isset ( $access_level )) {
			global $current_user;
			$roles = $current_user->roles;
			if ((current_user_can ( 'manage_options' )) and ! $tracking) {
				return true;
			}
			if (isset($roles[0]) && in_array ( $roles[0], $access_level )) {
				return true;
			} else {
				return false;
			}
		}
	}
}