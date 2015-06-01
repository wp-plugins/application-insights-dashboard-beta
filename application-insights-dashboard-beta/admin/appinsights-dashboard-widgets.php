<?php
class AppInsights_Widgets {
	function appinsights_dashboard_widgets() {
		global $AppInsights_Config;
		
		if ($AppInsights_Config->options['appinsights_token']) {
			include_once ($AppInsights_Config->plugin_path . '/appinsights-api.php');
			global $AppInsights_API;
		} else {
			echo '<p>' . __ ( "This plugin needs an authorization:", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "Authorize Plugin", 'appinsights' ), 'secondary' ) . '</form>';
			return;
		}
		
		include_once ($AppInsights_Config->plugin_path . '/appinsights-tools.php');
	    $tools = new AppInsights_Tools();
		
	    $tools->appinsights_cleanup_timeouts();
	    
	    if (! $AppInsights_API->get_access_token()) {
	    	echo '<p>' . __ ( "Something went wrong.", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "Application insights settings", 'appinsights' ), 'secondary' ) . '</form>';
	    	return;
	    }
	    
	    if (current_user_can ( 'manage_options' )) {
	    	if (isset( $_REQUEST['appinsights_component_select'] )) {
	    		$AppInsights_Config->options['appinsights_component'] = $_REQUEST['appinsights_component_select'];
	    	}
	    	
	    	$components = $AppInsights_Config->options['appinsights_component_list'];
	    	
	    	$component_switch = '';
	    	
	    	if (is_array ( $components )) {
	    		if (! $AppInsights_Config->options ['appinsights_component']) {
	    			echo '<p>' . __ ( "Please select an application insights resource:", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "Select component", 'appinsights' ), 'secondary' ) . '</form>';
	    			return;
	    		}
	    		
	    		$component_switch .= '<select id="appinsights_component_select" name="appinsights_component_select" onchange="appinsights_refresh_statsdata(document.getElementById(\'appinsights_period\').value, document.getElementById(\'appinsights_query\').value , this.value);">';
	    		foreach ( $components as $component ) {
	    			if (! $AppInsights_Config->options ['appinsights_component']) {
	    				$AppInsights_Config->options ['appinsights_component'] = $component [0];
	    			}
	    			
	    			if (isset ( $component [1] )) {
	    			    $component_switch .= '<option value="' . esc_attr ( $component [0] ) .
	    			        '" ' . selected($component [0], $AppInsights_Config->options ['appinsights_component'], false) .
	    			        ' >' . esc_attr($component [1]) . '</option>';
	    			}
	    		}
	    		$component_switch .= "</select>";
	    	} else {
	    		echo '<p>' . __ ( "Something went wrong while retrieving components list.", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "More details", 'appinsights' ), 'secondary' ) . '</form>';
	    		return;
	    	}
	    }
	    
	    $AppInsights_Config->set_plugin_options ();
	    ?>
<form id="appinsights" method="POST">
        <?php 
        if (current_user_can ( 'manage_options' )) {
            echo $component_switch;
            $component_id = $AppInsights_Config->options ['appinsights_component'];
        }
        
        if (isset ( $_REQUEST ['query'] )) {
            $query = $_REQUEST ['query'];
            $AppInsights_Config->options ['appinsights_default_metric'] = $query;
            $AppInsights_Config->set_plugin_options ();
        } else {
			$query = isset ( $AppInsights_Config->options ['appinsights_default_metric'] ) ? $AppInsights_Config->options ['appinsights_default_metric'] : 'context.session.id.hash';
		}
		
		if (isset ( $_REQUEST ['period'] )) {
			$period = $_REQUEST ['period'];
			$AppInsights_Config->options ['appinsights_default_dimension'] = $period;
			$AppInsights_Config->set_plugin_options ();
		} else {
			$period = isset ( $AppInsights_Config->options ['appinsights_default_dimension'] ) ? $AppInsights_Config->options ['appinsights_default_dimension'] : '30daysAgo';
		}
        ?>
<select id="appinsights_period" name="period" onchange="appinsights_refresh_statsdata(this.value, document.getElementById('appinsights_query').value , <?php echo isset($component_id) ? "document.getElementById('appinsights_component_select').value" : ""; ?>);">
	<option value="today" <?php selected ( "today", $period, true ); ?>><?php _e("Today",'appinsights'); ?></option>
	<option value="yesterday" <?php selected ( "yesterday", $period, true ); ?>><?php _e("Yesterday",'appinsights'); ?></option>
	<option value="7daysAgo" <?php selected ( "7daysAgo", $period, true ); ?>><?php _e("Last 7 Days",'appinsights'); ?></option>
	<option value="14daysAgo" <?php selected ( "14daysAgo", $period, true ); ?>><?php _e("Last 14 Days",'appinsights'); ?></option>
	<option value="30daysAgo" <?php selected ( "30daysAgo", $period, true ); ?>><?php _e("Last 30 Days",'appinsights'); ?></option>
</select>
<select id="appinsights_query" name="query" onchange="appinsights_refresh_statsdata(document.getElementById('appinsights_period').value, this.value,<?php echo isset($component_id) ? "document.getElementById('appinsights_component_select').value" : ""; ?>);">
	<option value="context.session.id.hash" <?php selected ( "context.session.id.hash", $query, true ); ?>><?php _e("Sessions",'appinsights'); ?></option>
	<option value="context.user.anonId.hash" <?php selected ( "context.user.anonId.hash", $query, true ); ?>><?php _e("Users",'appinsights'); ?></option>
	<option value="view.count" <?php selected ( "view.count", $query, true ); ?>><?php _e("Page Views",'appinsights'); ?></option>   
    <option value="request.duration" <?php selected ( "request.duration", $query, true ); ?>><?php _e("Server Response Time",'appinsights'); ?></option>
    <option value="clientPerformance.total.value" <?php selected ( "clientPerformance.total.value", $query, true ); ?>><?php _e("Browser Page Load Time",'appinsights'); ?></option>
   
    
    
</select>
</form> 
  
<div id="appinsights_new_statsdata">
  <?php include_once ('appinsights-ajax.php');?>
    </div>
	<?php } 
}
?>