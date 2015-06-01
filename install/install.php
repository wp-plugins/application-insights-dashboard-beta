<?php
class AppInsights_Install {
	static function install() {
		if (! get_option ( 'appinsights_token' )) {
			$options = array ();
			$options ['appinsights_tenantid'] = '';
			$options ['appinsights_clientid'] = '';
			$options ['appinsights_clientsecret'] = '';
			$options ['appinsights_access_front'] [] = 'administrator';
			$options ['appinsights_access_back'] [] = 'administrator';
			$options ['appinsights_cachetime'] = 3600;
			$options ['appinsights_token'] = '';
			$options ['appinsights_refresh_token'] = '';
			$options ['appinsights_profile_list'] = '';
			$options ['appinsights_subscription_list'] = '';
			$options ['appinsights_component_list'] = '';
			$options ['appinsights_subscription'] = '';
			$options ['appinsights_component'] = '';
			$options ['appinsights_tracking_code'] = '';
			$options ['appinsights_default_metric'] = 'context.session.id.hash';
			$options ['appinsights_default_dimension'] = '30daysAgo';
			$options ['appinsights_network'] = 0;
		} else {
			$options = array ();
			$options ['appinsights_tenantid'] = get_option ( 'appinsights_tenantid' );
			$options ['appinsights_clientid'] = get_option ( 'appinsights_clientid' );
			$options ['appinsights_clientsecret'] = get_option ( 'appinsights_clientsecret' );
			$options ['appinsights_access'] = get_option ( 'appinsights_access' );
			$options ['appinsights_access_front'] [] = 'administrator';
			$options ['appinsights_access_back'] [] = 'administrator';
			$options ['appinsights_cachetime'] = get_option ( 'appinsights_cachetime' );
			$options ['appinsights_token'] = get_option ( 'appinsights_token' );
			$options ['appinsights_refresh_token'] = get_option ( 'appinsights_refresh_token' );
			$options ['appinsights_profile_list'] = get_option ( 'appinsights_profile_list' );
			$options ['appinsights_subscription_list'] = get_option ( 'appinsights_subscription_list' );
			$options ['appinsights_component_list'] = get_option ( 'appinsights_component_list' );
			$options ['appinsights_subscription'] = get_option ( 'appinsights_subscription' );
			$options ['appinsights_component'] = get_option ( 'appinsights_component' );
			$options ['appinsights_default_metric'] = 'context.session.id.hash';
			$options ['appinsights_default_dimension'] = '30daysAgo';
			$options ['appinsights_network'] = 0;
			
			delete_option ( 'appinsights_tenantid' );
			delete_option ( 'appinsights_clientid' );
			delete_option ( 'appinsights_clientsecret' );
			delete_option ( 'appinsights_access' );
			delete_option ( 'appinsights_access_front' );
			delete_option ( 'appinsights_access_back' );
			delete_option ( 'appinsights_frontend' );
			delete_option ( 'appinsights_cachetime' );
			delete_option ( 'appinsights_token' );
			delete_option ( 'appinsights_refresh_token' );
			delete_option ( 'appinsights_profile_list' );
			delete_option ( 'appinsights_subscription_list' );
			delete_option ( 'appinsights_component_list' );
			delete_option ( 'appinsights_subscription' );
			delete_option ( 'appinsights_component' );
		}
		
		add_option ( 'appinsights_options', json_encode ( $options ) );
	}
}
