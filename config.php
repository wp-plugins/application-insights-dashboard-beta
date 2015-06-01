<?php 
class AppInsights_Config {
	public $options;
	public $plugin_path;
	public $plugin_url;
	
	public function __construct() {
		$this->get_plugin_path();
		$this->get_plugin_options();
	}
	
	public function set_plugin_options() {
		$options = $this->options;
		
		if (current_user_can('manage_options')) {
			update_option('appinsights_options', json_encode($this->validate_data($options)));
		}
	}
	
	private function get_plugin_options() {
		if (!get_option( 'appinsights_options')) {
			AppInsights_Install::install ();
		}
		$this->options = (array) json_decode(get_option('appinsights_options'));
	}
	
	private static function validate_data($options) {
		if (isset($options['appinsights_tenantid'])) {
			$options['appinsights_tenantid'] = sanitize_text_field($options['appinsights_tenantid']);
		}
		if (isset($options['appinsights_clientid'])) {
			$options['appinsights_clientid'] = sanitize_text_field($options['appinsights_clientid']);
		}
		
		return $options;
	}
	
	public function get_plugin_path() {
		$this->plugin_path = dirname ( __FILE__ );
		$this->plugin_url = plugins_url ( "", __FILE__ );
	}
}

if (!isset($GLOBALS['AppInsights_Config'])) {
	$GLOBALS['AppInsights_Config'] = new AppInsights_Config();
}
?>