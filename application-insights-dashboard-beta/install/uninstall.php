<?php 
class AppInsights_Uninstall {
	static function uninstall() {
		global $wpdb;
		
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_appinsights%%'" );
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_appinsights%%'" );
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_insights%%'" );
		$sqlquery = $wpdb->query ( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_insights%%'" );
		delete_option ( 'appinsights_options' );
		delete_transient ( 'appinsights_refresh_token' );
	}
}
?>