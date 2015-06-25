<?php 
class AppInsights_API {
	const OAUTH2_TOKEN_URI = 'https://login.windows.net/%s/oauth2/token';
	const OAUTH2_AUTH_URL = 'https://login.windows.net/%s/oauth2/authorize';
	const AZURE_HOST_URL = 'https://management.azure.com';
	const AZURE_SUBS_URL = 'https://management.azure.com/subscriptions';
	const AZURE_RESOURCES_URL = 'https://management.azure.com/subscriptions/%s/resources';
	const ANTIFORGERY_ID_KEY = 'ANTIFORGERY_ID';
	
	private $tenant_id;
	private $client_id;
	private $resource = 'https://management.core.windows.net/';
	private $redirect_uri = '';
	private $token = array();
	private $api_version_default = '2014-04-01-preview';
	private $api_version_insights = '2014-08-01';
	private $request_timeout = 60;
	private $timeshift;
	
	public function __construct() {
		global $AppInsights_Config;
		
	    $this->tenant_id = $AppInsights_Config->options['appinsights_tenantid'];
	    $this->client_id = $AppInsights_Config->options['appinsights_clientid'];
	    $this->redirect_uri = admin_url('options-general.php');
	    
	    if ($AppInsights_Config->options['appinsights_token']) {
	    	$token = $AppInsights_Config->options['appinsights_token'];
	    	$token = $this->appinsights_refresh_token();
	    	if ($token) {
	    		$this->set_access_token($token);
	    	}
	    }
	}
	
	public function authenticate($code) {
		if (strlen($code) == 0) {
			return new WP_Error( 'appinsights_api_auth', __( "Invalid authorization code.", "appinsights" ) );
		}
		
		$params = array(
		    'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->redirect_uri,
			'resource' => $this->resource,
			'client_id' => $this->client_id
		);
		
		$response = wp_remote_post( sprintf(self::OAUTH2_TOKEN_URI, $this->tenant_id), array(
			'timeout' => $this->request_timeout, 
			'body' => http_build_query($params, '', '&')
		) );
		
		if (wp_remote_retrieve_response_code( $response ) == 200) {
			$this->set_access_token(wp_remote_retrieve_body($response));
			$this->token['created'] = time();
			return $this->get_access_token();
		} else {
			return new WP_Error( wp_remote_retrieve_response_code( $response ), trim( wp_remote_retrieve_response_message( $response ) ) );
		}
	}
	
	public function token_request() {
		$auth_url = $this->_create_auth_url();
		?>
<form name="input"
	action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
	<table class="options">
		<tr>
			<td colspan="2" class="info">
			    <?php echo __( "Use this link to authenticate using Microsoft Azure credentials:", 'appinsights' ) . ' <a href="' . $auth_url . '">' . __ ( "Login", 'appinsights' ) . '</a>'; ?>
			</td>
		</tr>
	</table>
</form>
		<?php
	}
	
	public function refresh_subscriptions() {
		$response = wp_remote_get( 
		    self::AZURE_SUBS_URL. "?" . 
			http_build_query( array( 'api-version' => $this->api_version_default ), null, '&' ), 
			array(
				'timeout' => $this->request_timeout, 
				'headers' => $this->get_authorization_header()
		    )
		);
		
		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) )
			return false;
		
		$body = trim( wp_remote_retrieve_body( $response ) );
		
		$azure_subs = json_decode($body);
		
		if (count($azure_subs->value) != 0) {
			$appinsights_subs_list = array();
			foreach ($azure_subs->value as $azure_sub) {
				$appinsights_subs_list[] = array(
						$azure_sub->subscriptionId,
						$azure_sub->displayName
				);
			}
			
			return $appinsights_subs_list;
		}
	}
	
	public function appinsights_refresh_token() {
        global $AppInsights_Config;
        
        try {
	        $transient = get_site_transient("appinsights_refresh_token");
	        
	        if (empty($transient)) {
	            if (! $AppInsights_Config->options['appinsights_refresh_token']) {
	                $microsoft_token = json_decode($AppInsights_Config->options['appinsights_token']);
	                $AppInsights_Config->options['appinsights_refresh_token'] = $microsoft_token->refresh_token;
	                $this->_refresh_token($microsoft_token->refresh_token);
	            } else {
	                $this->_refresh_token($AppInsights_Config->options['appinsights_refresh_token']);
	            }
	            
	            $token = $this->get_access_token();
	            $microsoft_token = json_decode($token);
	            $AppInsights_Config->options['appinsights_token'] = $token;
	            
	            set_site_transient ( "appinsights_refresh_token", $token, $microsoft_token->expires_in );
	            $AppInsights_Config->set_plugin_options();
	            
	            return $token;
	        } else {
	        	return $transient;
	        }
        } catch (Exception $e) {
        	$this->appinsights_reset_token(false);
        	return false;
        }
    }
    
    public function appinsights_reset_token($all = true) {
    	global $AppInsights_Config;
    	
    	delete_transient('appinsights_refresh_token');
    	$AppInsights_Config->options['appinsights_token'] = "";
    	$AppInsights_Config->options['appinsights_refresh_token'] = "";
    	
    	if ($all) {
    		$AppInsights_Config->options['appinsights_subscription'] = "";
    		$AppInsights_Config->options['appinsights_subscription_list'] = "";
    		$AppInsights_Config->options['appinsights_component'] = "";
    		$AppInsights_Config->options['appinsights_component_list'] = "";
    	}
    	
    	$AppInsights_Config->set_plugin_options();
    }
	
	public function set_access_token($token) {
        $token = json_decode($token, true);
        if ($token == null) {
            return new WP_Error( 'appinsights_api_set_token', __( "Could not json decode the token.", "appinsights" ) );
        }
        
        if (! isset($token['access_token'])) {
            return new WP_Error( 'appinsights_api_set_token', __( "Invalid token format.", "appinsights" ) );
        }
        
        $this->token = $token;
    }
    
    public function get_access_token() {
    	$token = json_encode($this->token);
    	return (null == $token || 'null' == $token || '[]' == $token) ? null : $token;
    }
    
    public function appinsights_components($subscription_id) {
    	$response = wp_remote_get( 
		    sprintf(self::AZURE_RESOURCES_URL, $subscription_id). "?" . 
    		urldecode(
				http_build_query( 
				    array( 
				        'api-version' => $this->api_version_default, 
				        '$filter' =>  'resourceType%20eq%20\'microsoft.insights/components\''
				    ), 
				    null, 
				    '&' 
				)
    		), 
			array(
				'timeout' => $this->request_timeout, 
				'headers' => $this->get_authorization_header()
		    )
		);
    	
    	if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) )
    		return false;
    	
    	$body = trim( wp_remote_retrieve_body( $response ) );
    	
    	$appinsights_components = json_decode($body);
    	
    	if (count($appinsights_components->value) != 0) {
    		$appinsights_components_list = array();
    		foreach ($appinsights_components->value as $appinsights_component) {
    			$appinsights_components_list[] = array(
    					$appinsights_component->id,
    					$appinsights_component->name
    			);
    		}
    			
    		return $appinsights_components_list;
    	}
    	
    }
    
    public function appinsights_main_charts($component_id, $period, $from, $to, $query) {
    	global $AppInsights_Config;

    	$metrics = array();
        $round = 0;
    	if ($query == "context.session.id.hash" || $query == "context.user.anonId.hash" ) {
    		$metrics_apply_function = "Cardinality";
    	} else if ($query == "view.count" || $query == "request.count" || $query == "event.count" ) {
    		$metrics_apply_function = "Sum";
    	} else if ( $query == "clientPerformance.total.value" ||  $query == "request.duration" ){
    	    $metrics_apply_function = "Avg";
            $round = 1 ; 
    	}
    	
    	$metrics[] = array(
    		'Metric' => $query,
    		'ApplyFunction' => $metrics_apply_function
    	);    		
    	
        $grain    = ($period == "today" || $period == "yesterday") ? "1h": "1d"; 
        $timeouts = ($period == "today") ? 0 : 1;
    	
    	try {
    		$serial = 'insights_qr2' . md5( str_replace ( array ('.', ',', '-'), "", $component_id . $period . $query ) );

            $transient = get_site_transient($serial);    		
    		if (empty ( $transient )) {
    			$data = $this->_get_appinsights_data(
    					    $component_id, 
    					    $from, 
    					    $to, 
    					    $metrics, 
    					    array('grain' => $grain)
    		    );    			
    			set_site_transient ( $serial, $data, $this->get_timeouts ( $timeouts ) );
    		} else {
    			$data = $transient;
    		}
    	} catch ( Exception $e ) {
    		echo 'Message: ' . $e->getMessage();
    		return 0;
    	}
    	
    	$appinsights_statsdata = "";
    	// Here requesting only one metrics, so no need to loop through $metrics array.
    	$statsdata = array();
    	if (is_array($data)) {
    		if ($period == "today" or $period == "yesterday") {
                foreach ($data as $bucket) { /* Apply round Function till 2 digits if $round is 1 to show data in seconds. */
    				$statsdata[date("H:i", (string) ($bucket['key']/1000))] = ($round == 1) ? round(($bucket[$metrics[0]['Metric'] . "." . $metrics_apply_function]['value'])/10000000, 2) : ($bucket[$metrics[0]['Metric'] . "." . $metrics_apply_function]['value']);
    			}
            } else {
                foreach ($data as $bucket) { /* Apply round Function till 2 digits if Apply Function is Avg to show data in seconds. */
    				$statsdata[date("Y-m-d", (string) ($bucket['key']/1000))] = ($round == 1) ? round($bucket[$metrics[0]['Metric'] . "." . $metrics_apply_function]['value']/10000000, 2) : $bucket[$metrics[0]['Metric'] . "." . $metrics_apply_function]['value'];
    			}
            }
    	} 

        if(count($statsdata)){
    	    $appinsights_statsdata = $this->_prepare_tick_values($statsdata, $from, $to, $grain);
        }
    	return $appinsights_statsdata;
    }
    
    public function appinsights_bottom_stats($component_id, $period, $from, $to) {
    	global $AppInsights_Config;
    	
    	$timeouts = ($period == "today") ? 0 : 1;
    	
    	$metrics = array(
    		array(
    		    'Metric' => 'context.session.id.hash',
    			'ApplyFunction' => 'Cardinality'
    	    ),
    		array(
    			'Metric' => 'context.user.anonId.hash',
    			'ApplyFunction' => 'Cardinality'
    		),
    		array(
    			'Metric' => 'view.count',
    			'ApplyFunction' => 'Sum'
    		),
            array(
    			'Metric' => 'event.count',
    			'ApplyFunction' => 'Sum'
    		),
    		array(
    			'Metric' => 'request.count',
    			'ApplyFunction' => 'Sum'
    		)
    	);
    	
    	$grain = '1y';
    	
    	try {
    		$serial = 'insights_qr3' . md5( str_replace ( array ('.', ',', '-'), "", $component_id . $period ) );
    		$transient = get_site_transient($serial);

    		if (empty ( $transient )) {
    			$data = $this->_get_appinsights_data(
    					    $component_id, 
    					    $from, 
    					    $to, 
    					    $metrics, 
    					    array('grain' => $grain)
    		    );
    			
    			set_site_transient ( $serial, $data, $this->get_timeouts ( $timeouts ) );
    		} else {
				$data = $transient;
			}
    		
    	} catch ( Exception $e ) {
    		return 0;
    	}
    	
    	$bottom_stats = array();
    	
    	if (isset ( $data [0] )) {
    		$bottom_stats['users'] = $data [0] ['context.user.anonId.hash.Cardinality'] ['value'];
    		$bottom_stats['sessions'] = $data [0] ['context.session.id.hash.Cardinality'] ['value'];
    		$bottom_stats['requests'] = $data [0] ['request.count.Sum'] ['value'];
    		$bottom_stats['views'] = $data [0] ['view.count.Sum'] ['value'];
            $bottom_stats['event'] = $data [0] ['event.count.Sum'] ['value'];
    	}
    	
    	return $bottom_stats;
    }
    
     public function appinsights_extra_stats($component_id, $period, $from, $to, $metrics, $opt_array) {
    	global $AppInsights_Config;
    	
    	$timeouts = ($period == "today") ? 0 : 1;
            
    	$grain = '1d';
    	
    	try {
    			$data = $this->_get_appinsights_data(
    					    $component_id, 
    					    $from, 
    					    $to, 
    					    $metrics, 
    					    array('grain' => $grain, "filter_expression" => $opt_array["filter_expression"], "key" =>$opt_array['key'], 'metrics' => $opt_array["metrics"] )
    		    );
    	} catch ( Exception $e ) {
    		return 0;
    	}
    	$extra_stats = array();

        if (isset ( $data)) {
            foreach ( $data  as $key ) {
                if($key['key'] != 2) {
                    $extra_stats[] = array("key"=> $key['key'] , "value" => $key[$metrics[0]['Metric'] . "." . $metrics[0]['ApplyFunction']]['value']);  
                } else {
                    $extra_stats[] = array("key"=> "Multiple Pages" , "value" => $key[$metrics[0]['Metric'] . "." . $metrics[0]['ApplyFunction']]['value']);  
                }
            }
    	}
    	return $extra_stats;
    }
    

    private function _get_appinsights_data($id, $start_date, $end_date, $metrics, $opt_params = array()) {
    	global $AppInsights_Config;
    	
    	include_once ($AppInsights_Config->plugin_path . '/appinsights-payload.php');
    	$payload = new AppInsights_Pay_Load();
    	
    	if (isset($opt_params['grain'])) {
    		$payload->AggregateByDimension[0]->Dimension->Grain = $opt_params['grain'];
    	}

        if(isset($opt_params['metrics'])){
            $payload->AggregateByDimension[0]->Dimension->Key = $opt_params['metrics'];
            $payload->AggregateByDimension[0]->Dimension->Top = 11;
        }
    	
    	foreach ($metrics as $key => $metrics_to_calculate) {
    		$payload->MetricsToCalculate[$key] = new MetricsToCalculate();
    	    $payload->MetricsToCalculate[$key]->Metric->Key = $metrics_to_calculate['Metric'];
    	    $payload->MetricsToCalculate[$key]->ApplyFunction = $metrics_to_calculate['ApplyFunction'];
    	}
    	
    	$payload->DimensionFilters[0]->Start = $start_date;
    	$payload->DimensionFilters[0]->End = $end_date;
    	if (isset($opt_params['filter_expression'])) {
    		$payload->DimensionFilters[1]->FilterExpression = $opt_params['filter_expression'];
            $payload->DimensionFilters[1]->Dimension->Key = $opt_params['key'];

    	}
    	
    	$response = wp_remote_get(
    		self::AZURE_HOST_URL . $id . "/Aggregate?" .
    		    http_build_query(
    			    array(
    				    'api-version' => $this->api_version_insights,
    					'payload' => json_encode($payload)
    				),
    				null,
    				'&'
    			),
    			array(
    				'timeout' => $this->request_timeout, 
    			    'headers' => $this->get_authorization_header()
    			)
    	);
    	
    	$response_code = wp_remote_retrieve_response_code( $response );
    	
        if ( is_wp_error($response) ) {
    		$errno    = $response->get_error_code();
    		$errorstr = $response->get_error_message();
    		if ( ! empty( $errorstr ) ) {
    			throw new Exception( $errorstr );
    		} else {
    			throw new Exception('Unknown error occurred.');
    		}
    	}
    	
    	if ( 200 != $response_code ) {
    		$response_msg = wp_remote_retrieve_response_message( $response );
    	    if ( ! empty( $response_msg ) ) {
    			throw new Exception( $response_msg );
    		} else {
    			throw new Exception('Unknown error occurred.');
    		}
    	}
    	
    	$body = trim( wp_remote_retrieve_body( $response ) );
    	
    	$results = json_decode($body); 
    	$results_values = json_decode($results->d->results->value, true);
    	
    	$data = $results_values['aggregations'][$payload->AggregateByDimension[0]->Dimension->Key]['buckets'];
    	
    	return $data;
    }
    
    private function _prepare_tick_values($statsdata, $from, $to, $grain) {
    	$from = new DateTime( $from );
    	$to = new DateTime( $to );
    	
    	if ($grain == "1h") {
    		$interval_str = "1 hour";
    		$ticks_format = "H:i";
    	} else {
    		$interval_str = "1 day";
    		$ticks_format = "Y-m-d";
    	}
    	
    	$interval = DateInterval::createFromDateString($interval_str);
    	$period = new DatePeriod($from, $interval, $to);
    	
    	$ticks = array();
    	foreach ( $period as $dt )
    		$ticks[$dt->format( $ticks_format )] = 0;
    	
    	$tick_values = array_merge($ticks, $statsdata);
    	
    	$appinsights_statsdata = "";
    	if (!empty($tick_values)) {
    	    foreach ($tick_values as $tick => $value) {
    	    	$date = new DateTime( $tick );
	    		$appinsights_statsdata .= "[" . ($date->getTimestamp()*1000) . ", " . $value . "],";
	    	}
    	}
    	
    	return rtrim ( $appinsights_statsdata, ',' );
    }
	
	private function _create_auth_url() {
		$params = array(
		    'response_type' => 'code',
			'redirect_uri' => $this->redirect_uri,
			'client_id' => $this->client_id,
			'resource' => $this->resource,
			'state' => $this->_get_state()
		);
		
		return sprintf(self::OAUTH2_AUTH_URL, $this->tenant_id) . "?" . http_build_query($params, '', '&');
	}
	
	private function _get_state() {
		$antiforgery_id = $this->_appinsights_com_create_guid();
		$_SESSION[ self::ANTIFORGERY_ID_KEY ] = $antiforgery_id;
		
		$state = 'page=appinsights&security_token='. $antiforgery_id;
		
		return $state;
	}
	
	private function _appinsights_com_create_guid(){
		mt_srand( (double) microtime() * 10000 ); //optional for php 4.2.0 and up.
		$charid = strtoupper( md5( uniqid( rand(), true ) ) 	);
		$hyphen = chr( 45 ); // "-"
		$uuid = chr( 123 ) . // "{"
		substr( $charid, 0, 8 ) . $hyphen .
		substr( $charid, 8, 4 ) . $hyphen .
		substr( $charid, 12, 4 ) . $hyphen .
		substr( $charid, 16, 4 ) . $hyphen .
		substr( $charid, 20, 12 ) .
		chr( 125 ); // "}"
	
		return $uuid;
	}
	
	private function _refresh_token($refresh_token) {
        $params = array(
          'client_id' => $this->client_id,
          'grant_type' => 'refresh_token',
          'refresh_token' => $refresh_token,
          'resource' => $this->resource
        );
        
        $response = wp_remote_post( sprintf(self::OAUTH2_TOKEN_URI, $this->tenant_id), array(
            'timeout' => $this->request_timeout,
		    'body' => http_build_query($params, '', '&')
        ) );
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code == 200) {
            $token = json_decode($body, true);
            
            if ($token == null) {
                throw new Exception('Could not json decode the access token.');
            }
            
            if (! isset($token['access_token']) || ! isset($token['expires_in'])) {
                throw new Exception('Invalid token format.');
            }
            
            if (isset($token['id_token'])) {
            	$this->token['id_token'] = $token['id_token'];
            }
            $this->token['access_token'] = $token['access_token'];
            $this->token['expires_in'] = $token['expires_in'];
            $this->token['created'] = time();
        } else {
            $error_response = json_decode($body);
            throw new Exception(
                "Error refreshing the OAuth2 token, message: '$error_response->error_description'", 
                $code
            );
        }
    }
    
    public function get_authorization_header() {
        if (null == $this->token) {
            throw new Exception('Cannot sign the request without an OAuth access token.');
        }
        
        // Check if the token is set to expire in the next 30 seconds
        // (or has already expired).
        if ($this->is_access_token_expired()) {
            if (! array_key_exists('refresh_token', $this->token)) {
                throw new Exception(
		            "The OAuth 2.0 access token has expired,"
		            ." and a refresh token is not available."
                );
            }
            $this->_refresh_token($this->token['refresh_token']);
        }
        
        $auth_header = array('Authorization' => 'Bearer ' . $this->token['access_token']);
        
        return $auth_header;
    }
    
    public function is_access_token_expired() {
        if (!$this->token || !isset($this->token['created'])) {
	        return true;
        }
        
        // If the token is set to expire in the next 30 seconds.
        $expired = ($this->token['created']
            + ($this->token['expires_in'] - 30)) < time();
        
        return $expired;
    }
    
    function get_timeouts($daily) {
    	$local_time = time () + $this->timeshift;
    	if ($daily) {
    		$nextday = explode ( '-', date ( 'n-j-Y', strtotime ( ' +1 day', $local_time ) ) );
    		$midnight = mktime ( 0, 0, 0, $nextday [0], $nextday [1], $nextday [2] );
    		return $midnight - $local_time;
    	} else {
    		$nexthour = explode ( '-', date ( 'H-n-j-Y', strtotime ( ' +1 hour', $local_time ) ) );
    		$newhour = mktime ( $nexthour [0], 0, 0, $nexthour [1], $nexthour [2], $nexthour [3] );
    		return $newhour - $local_time;
    	}
    }
}

if (!isset($GLOBALS['AppInsights_API'])) {
	$GLOBALS['AppInsights_API'] = new AppInsights_API();
}
?>