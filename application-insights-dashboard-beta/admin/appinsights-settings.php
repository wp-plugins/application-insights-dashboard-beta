<?php 
class AppInsights_Settings {
	private static function set_get_options($reset = false) {
		global $AppInsights_Config;
		
		$options = $AppInsights_Config->options;
		
		if (isset($_POST['options']['appinsights_hidden']) 
		    && isset($_POST['options']) 
		        && (isset($_POST['appinsights_security']) 
		            && wp_verify_nonce($_POST['appinsights_security'], 'appinsights_form' )
                        && !$reset)) {
			if ($_POST ['options']['appinsights_component_list'] && is_string($_POST ['options']['appinsights_component_list'])) {
				$_POST ['options']['appinsights_component_list'] = unserialize(stripslashes($_POST ['options']['appinsights_component_list']));
			}
			
			$new_options = $_POST ['options'];
			$options = array_merge($options, $new_options);
			$AppInsights_Config->options = $options;
			$AppInsights_Config->set_plugin_options();
		}
		
		return $options;
	}
	public static function appinsights_plugin_options_page() {
		global $AppInsights_Config;
		
		include_once ($AppInsights_Config->plugin_path . '/appinsights-tools.php');
		$tools = new AppInsights_Tools();
		
		if (!current_user_can ( 'manage_options')) {
			return;
		}
		
		$options = self::set_get_options();
		
		include_once ($AppInsights_Config->plugin_path . '/appinsights-api.php');
		global $AppInsights_API;
		
		if ($_GET['code'] && !$options['appinsights_token']) {
			if (isset($_GET['state'])) {
				$state = urldecode($_GET['state']);
				parse_str($state, $state_vars);
		
				if (isset($state_vars['security_token']) && $state_vars['security_token'] == $_SESSION[ AppInsights_API::ANTIFORGERY_ID_KEY ]) {
					$authentication = $AppInsights_API->authenticate($_GET['code']);
					if (!is_wp_error($authentication)) {
						$AppInsights_Config->options['appinsights_token'] = $AppInsights_API->get_access_token();
						$microsoft_token = json_decode($AppInsights_API->get_access_token());
						$AppInsights_Config->options['appinsights_refresh_token'] = $microsoft_token->refresh_token;
						$AppInsights_Config->set_plugin_options();
						$message = "<div class='updated'><p><strong>" . __ ( "Plugin authorization succeeded.", 'appinsights' ) . "</strong></p></div>";
						$options = self::set_get_options();
					} else {
						$message = "<div class='error'><p><strong>" . $authentication->get_error_message() . "</strong></p></div>";
					}
				} else {
					$message = "<div class='error'><p><strong>" . __ ( "A required anti-forgery token was not supplied or was invalid.", 'appinsights' ) . "</strong></p></div>";
				}
			} else {
				$message = "<div class='error'><p><strong>" . __ ( "A required anti-forgery token was not supplied or was invalid.", 'appinsights' ) . "</strong></p></div>";
			}
		}
		
		if ($AppInsights_API->get_access_token()) {
			if ($AppInsights_Config->options['appinsights_subscription_list']) {
				$subscriptions = $AppInsights_Config->options['appinsights_subscription_list'];
			} else {
				$subscriptions = $AppInsights_API->refresh_subscriptions();
			}
			
			if ($subscriptions) {
				$AppInsights_Config->options['appinsights_subscription_list'] = $subscriptions;
				
				if (!$AppInsights_Config->options['appinsights_subscription']) {
					$AppInsights_Config->options['appinsights_subscription'] = $subscriptions[0][0];
				}
				
				if ($AppInsights_Config->options['appinsights_component_list']) {
					$components = $AppInsights_Config->options['appinsights_component_list'];
				} else if ($AppInsights_Config->options['appinsights_subscription']) {
					$components = $AppInsights_API->appinsights_components($AppInsights_Config->options['appinsights_subscription']);
				} else {
					$components = $AppInsights_API->appinsights_components($subscriptions[0][0]);
				}
				
				
				$AppInsights_Config->set_plugin_options();
				$options = self::set_get_options();
			}
		}
		
		if (isset($_POST['reset'])) {
			if (isset($_POST['appinsights_security']) && wp_verify_nonce($_POST['appinsights_security'], 'appinsights_form')) {
				$AppInsights_API->appinsights_reset_token(true);
				$tools->appinsights_clear_cache();
				$message = "<div class='updated'><p><strong>" . __ ( "Token Reseted.", 'appinsights' ) . "</strong></p></div>";
				$options = self::set_get_options(true);
			} else {
				$message = "<div class='error'><p><strong>" . __ ( "Nonce is invalid", 'appinsights' ) . "</strong></p></div>";
			}
		}
		
		if (isset($_POST ['options']['appinsights_hidden']) && !isset($_POST['reset'])) {
			$message = "<div class='updated'><p><strong>" . __ ( "Options saved.", 'appinsights' ) . "</strong></p></div>";
			if (!(isset($_POST['appinsights_security']) && wp_verify_nonce($_POST['appinsights_security'], 'appinsights_form' ))) {
				$message = "<div class='error'><p><strong>" . __ ( "Nonce is invalid", 'appinsights' ) . "</strong></p></div>";
			}
		}
		?>
<div class="wrap">
    <h2><?php _e('Microsoft Azure Application Insights Settings'); ?></h2>
    <hr>
</div>		
	    <?php 
	    if (isset($_POST['authorize'])) {
	    	// make token request.
	    	$AppInsights_API->token_request();
	    } else {
            if (isset($message)) {
	            echo $message;
	        }
	    	?>
	    	<form name="appinsights_form" method="post"
			    action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
			    <input type="hidden" name="options[appinsights_hidden]" value="Y">
				<?php wp_nonce_field('appinsights_form','appinsights_security'); ?>
				<table class="options">
				    <tr>
					    <td colspan="2">
					        <h2><?php _e('Plugin Authorization'); ?></h2>
					    </td>
					</tr>
					<tr>
					    <td class="title">
					        <label for="options[appinsights_tenantid]"><?php _e('Tenant ID:'); ?></label>
						</td>
						<td>
						    <input type="text" name="options[appinsights_tenantid]" 
						        value="<?php echo esc_attr($options['appinsights_tenantid']); ?>" 
						        size="40">
						</td>
					</tr>
					<tr>
					    <td class="title">
					        <label for="options[appinsights_clientid]"><?php _e('Client ID:'); ?></label>
						</td>
						<td>
						    <input type="text" name="options[appinsights_clientid]" 
						        value="<?php echo esc_attr($options['appinsights_clientid']); ?>" 
						        size="40">
						</td>
					</tr>
					<?php 
					if ($options['appinsights_token']) {
                        ?>
                        <tr>
						    <td colspan="2"><input type="submit" name="reset"
									class="button button-secondary"
									value="<?php _e( "Clear Authorization", 'appinsights' ); ?>" />
							</td>
						</tr>
						<tr>
					        <td colspan="2"><hr></td>
					    </tr>
					    <?php 
					    if ($options['appinsights_subscription_list']) { 
                        ?>
					    <tr>
							<td colspan="2">
							    <?php echo "<h2>" . __( "General Settings", 'appinsights' ) . "</h2>"; ?>
							</td>
						</tr>
						<tr>
						    <td class="title">
						        <label for="appinsights_subscription"><?php _e("Select Subscription: ", 'appinsights' ); ?></label>
						    </td>
						    <td>
						        <select id="appinsights_subscription" name="options[appinsights_subscription]">
							        <?php 
							        foreach ( $options['appinsights_subscription_list'] as $items ) {
	                                    echo '<option value="' . esc_attr ( $items [0] ) . 
	                                        '" ' . selected($items [0], $options['appinsights_subscription']) . 
	                                        ' >' . esc_html($items[1]) . '</option>';
	                                }
							        ?>
						        </select>
						    </td>
						</tr>
						<tr id="appinsights_component_response">
						<?php 
						    if ($components) { 
                        ?>
						    <td class="title">
						        <label for="appinsights_component"><?php _e("Select Component: ", 'appinsights' ); ?></label>
						    </td>
						    <td>
						        <input type="hidden" name="options[appinsights_component_list]" 
						            value="<?php echo esc_attr(serialize($components)); ?>">
						        <select id="appinsights_component" name="options[appinsights_component]">
						         <option value=""> -- Select -- </option>
							        <?php 
							        foreach ( $components as $items ) {
	                                    echo '<option value="' . esc_attr ( $items [0] ) . 
	                                        '" ' . selected($items [0], $options['appinsights_component']) . 
	                                        ' >' . esc_html($items[1]) . '</option>';
	                                }
							        ?>
						        </select>
						    </td>
						<?php 
                            } else { 
                        ?>
							<td colspan="2">
							    <div class='error'><p><strong><?php _e( "No application insights resources found under the selected subscription. ", 'appinsights' ); ?></strong></p></div>
							</td>
					    <?php 
                            } 
                        ?>
                        </tr>
                        <tr>
						    <td colspan="2"><hr></td>
						</tr>
						<tr>
							<td colspan="2" class="submit">
							    <input type="submit"
							        id="appinsights-update-options" 
								    name="Submit" class="button button-primary"
								    value="<?php _e('Update Options', 'appinsights' ) ?>" />
							</td>
						</tr>
                        <?php 
                        } else { 
					    	$AppInsights_API->appinsights_reset_token(true);
					    ?>
					    <tr>
							<td colspan="2">
							    <div class='error'><p><strong><?php _e( "No Microsoft Azure subscriptions found. ", 'appinsights' ); ?><a href="https://account.windowsazure.com/SignUp"><?php _e( "Sign up for Microsoft Azure.", 'appinsights' ); ?></a></strong></p></div>
							</td>
						</tr>
                        <?php 
							  }
                    } else {
                    ?>
                    <tr>
					    <td colspan="2"><hr></td>
					</tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="authorize" 
                                class="button button-secondary" id="authorize" value="Authorize Plugin" />
                        </td>
                    </tr>
                    <?php 
                    }
					?>
			    </table>
			</form>
	    	<?php 
	    }
		
	}
	
	public function display_appinsights_components() {
        global $AppInsights_Config;
        
        include_once ($AppInsights_Config->plugin_path . '/appinsights-api.php');
        global $AppInsights_API;
        
        $nonce = $_POST['next_nonce'];
        if ( ! wp_verify_nonce( $nonce, 'appinsights-next-nonce' ) )
        	die ( 'Nonce is invalid!');
        $subscription_id = $_POST['subscription_id'];
        $components = $AppInsights_API->appinsights_components($subscription_id);
        $html = '';
        if ($components) {
            $html .= '<td class="title"><label for="appinsights_component">' . 
                __ ("Select Component: ", 'appinsights' ) . '</label></td><td>';
            $html .= '<input type="hidden" name="options[appinsights_component_list]" value="' . 
                esc_attr(serialize($components)) . '">';
            $html .= '<select id="appinsights_component" name="options[appinsights_component]">';
            $html .= '<option value=""> -- Select -- </option>';
            foreach ( $components as $items ) { 
                $html .= '<option value="' . esc_attr ( $items [0] ) .
                    '" ' . selected($items [0], $options['appinsights_component']) .
                    ' >' . esc_html($items[1]) . '</option>';
            }
            $html .= '</select></td>';
        } else {
            $html .= '<input type="hidden" name="options[appinsights_component_list]" value="">';
            $html .= '<input type="hidden" name="options[appinsights_component]" value="">';
            $html .= "<td colspan='2'><div class='error'><p><strong>" . __ ( "No application insights resource found under this account.", 'appinsights' ) . "</strong></p></div></td>";
        }
        echo $html;
        die();
    }
}
?>