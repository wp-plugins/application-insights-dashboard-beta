 <?php 
        $to = ($period == "today") ? date(DATE_ISO8601, strtotime('tomorrow')) : date(DATE_ISO8601, strtotime('today'));
        $extra_statsdata = 0;
    	switch ($period) {
				
		case 'today' :
			$from = date(DATE_ISO8601, strtotime('today'));
			break;
				
		case 'yesterday' :
			$from = date(DATE_ISO8601, strtotime('yesterday'));
			break;
				
		case '7daysAgo' :
			$from = date(DATE_ISO8601, strtotime('7daysAgo'));
			break;
				
		case '14daysAgo' :
			$from = date(DATE_ISO8601, strtotime('14daysAgo'));
			break;
				
		case '30daysAgo' :
			$from = date(DATE_ISO8601, strtotime('30daysAgo'));
			break;
				
		default :
			$from = date(DATE_ISO8601, strtotime('30daysAgo'));
			break;
	}

	switch ($query) {
				
		case 'context.session.id.hash' :
			$title = __ ( "Sessions", 'appinsights' );
            $extra_statsdata = 2;
			break;
					
		case 'view.count' :
			$title = __ ( "Page Views", 'appinsights' );
			break;
					
		case 'context.user.anonId.hash' :
			$title = __ ( "Users", 'appinsights' );
            $extra_statsdata = 1; 
			break;

        case 'clientPerformance.total.value' :
			$title = __ ( "Browser Page Load Time (in sec)", 'appinsights' );
			break;
        
        case 'request.duration' :
			$title = __ ( "Server Response Time (in sec)", 'appinsights' );
			break;
				
		default :
			$title = __ ( "Sessions", 'appinsights' );
	}
		
	$appinsights_statsdata = $AppInsights_API->appinsights_main_charts( $component_id, $period, $from, $to, $query );

	if (! $appinsights_statsdata) {
		echo '<p>' . __ ( "No stats available.", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "Change settings", 'appinsights' ), 'secondary' ) . '</form>';
		return;
	}
		
	$appinsights_bottom_stats = $AppInsights_API->appinsights_bottom_stats( $component_id, $period, $from, $to );
		
	if (! $appinsights_bottom_stats) {
		echo '<p>' . __ ( "No Bottam stats available.", 'appinsights' ) . '</p><form action="' . menu_page_url ( 'appinsights', false ) . '" method="POST">' . get_submit_button ( __ ( "Change settings", 'appinsights' ), 'secondary' ) . '</form>';
		return;
	}
    ?>
<script type="text/javascript">
   var appinsights_data = [
       {
           key: "<?php echo $title; ?>",
           values: [ <?php echo $appinsights_statsdata; ?> ],
           color: "#2ca02c"
       }
    ];
    nv.addGraph(function () {
        var chart = nv.models.lineChart()
            .margin({left: 35, right: 30})
            .forceY([0, 10])
            .x(function(d) { return d[0] })
            .y(function(d) { return d[1] })
            .useInteractiveGuideline(true);
        chart.xAxis
            .tickValues(d3.time.weeks.utc(appinsights_data[0].values[0][0], appinsights_data[0].values[appinsights_data[0].values.length - 1][0], 1))
            .axisLabel("Date")
            .tickFormat(function (d) {
                return d3.time.format.utc("%b %d")(new Date(d))
            });
        chart.yAxis
            .axisLabel("<?php echo $title; ?>")
            .tickFormat(d3.format(","));
        
        d3.select("#appinsights_statsdata svg")
            .attr('width', 600)
            .attr('height', 375)
            .datum(appinsights_data)
            .call(chart);

        nv.utils.windowResize(function () {
            d3.select("#appinsights_statsdata svg").call(chart)
        });
        
        return chart;
    });
</script>

<div id="appinsights_statsdata" class='with-transitions'>
    <svg></svg>
</div>
<div id="details_div">
    <span>Usage Analatics</span>
    <table class="aitable" cellpadding="4">
		<tr>
			<td width="15%"><?php _e( "Users:", 'appinsights' );?></td>
			<td width="12%" class="aivalue">
                <a href="?query=context.user.anonId.hash&period=<?php echo $period; ?>" class="aitable">
                   <?php echo ( int ) $appinsights_bottom_stats ['users'];?>
                </a>
            </td>
			<td width="15%"><?php _e( "Sessions:", 'appinsights' );?></td>
			<td width="12%" class="aivalue">
                <a href="?query=context.session.id.hash&period=<?php echo $period; ?>" class="aitable">
                    <?php echo ( int ) $appinsights_bottom_stats ['sessions'];?>
                </a>
            </td>
			<td width="15%"><?php _e( "Page Views:", 'appinsights' );?></td>
            <td width="12%" class="aivalue">
                <a href="?query=view.count&period=<?php echo $period; ?>" class="aitable">
                    <?php echo ( int ) $appinsights_bottom_stats ['views']; ?>
                </a>
            </td>
		</tr>
		<tr>
            <td width="15%"><?php _e( "Requests:", 'appinsights' );?></td>
			<td width="12%" class="aivalue">
                <a class="aitable"><?php echo ( int ) $appinsights_bottom_stats ['requests']; ?>
                </a>
            </td>
			<td width="15%"><?php _e( "Events:", 'appinsights' );?></td>
			<td width="12%" class="aivalue">
                <a class="aitable"><?php echo ( int ) $appinsights_bottom_stats ['event']; ?>
                </a>
            </td>
            <td colspan="2"></td>			
		</tr>
	</table>
</div>
<?php 
if ($extra_statsdata) {

    if( $extra_statsdata == 1 ) { /* for users */
        $metric         = "context.user.anonId.hash"; 
        $extra_title    = "Users";         
    } elseif( $extra_statsdata == 2) {    /* for Sessions */     
        $metric         = "context.session.id.hash";
        $extra_title    = "Sessions";
    }

    $name           = array( 'Continent', "Country or Region",'State or Province', "City", "Browser", "Operting Systems", "Entry Page" , "Exit Page" , 'Page Bounce');
    $data           = array(
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "context.location.continent", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F' or Key eq null", "metrics" => "context.location.country", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "context.location.province", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "context.location.city", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "context.device.browser", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "context.device.osVersion", "key" => "context.data.isSynthetic" ),                                
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "sessionMetric.exitUrl", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "sessionMetric.entryUrl", "key" => "context.data.isSynthetic" ),
                                array( "filter_experession" => "Key eq 'F'", "metrics" => "sessionMetric.pageBounceActivity", "key" => "context.data.isSynthetic" ) 
                                
                          );
    
    $metrics[] = array(
    	'Metric' => $metric,
    	'ApplyFunction' => 'Cardinality'
    );
   
    /* this section display the data which is statitics */
     
    echo '<br /><div id="geolocation_data"><span>*Unique count of '.$extra_title.' by </span><br clear="all" />'; 

    for ( $i = 0, $j = 0; $i < count( $data ); $i++ ) {
        
        $appinsights_extra_statsdata = $AppInsights_API->appinsights_extra_stats( $component_id, $period, $from, $to,  $metrics, $data[$i]);
        
        if(count($appinsights_extra_statsdata)) {
            echo "<div class='extrastatsdata'><table class='statsdata' cellpadding='3'><thead><th width='60%'>".$name[$i]."</th><th>Unique Count</th></thead>";

            foreach($appinsights_extra_statsdata as $key) {
                echo "<tr><td width='60%'>".$key['key']."</td><td class='extra_cen'>".$key['value']."</td></tr>";
            }
            echo '</table></div>';
            $j++; 
            if($j % 2 == 0) echo '<br clear="all" />';
        }
        
    }
    echo '</div><br clear="all" />';
}
?>


