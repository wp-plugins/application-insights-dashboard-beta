jQuery(document).ready(function ($) {
    $("select#appinsights_subscription").change(function () {
        $("#appinsights-update-options").attr("disabled", "disabled");
        $("#appinsights_component_response").html('<td colspan="2"><div class="appinsights-spinner"><img src="images/wpspin_light.gif" alt="Loading..." /></div></td>');
        var data = {
            'action': 'display_appinsights_components',
            'subscription_id': $("#appinsights_subscription option:selected").val(),
            'next_nonce': AppInsights_Ajax.next_nonce
        };
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(AppInsights_Ajax.ajax_url, data, function (response) {
            $("#appinsights_component_response").html(response);
            $("#appinsights-update-options").removeAttr("disabled");
        });
    });
});


/*for getting the statsdata on ajax calls . */
function appinsights_refresh_statsdata(period, query, component_id) {

        var mydata = {
            action: "appinsights_refresh_statsdata",
            period: period, 
            query : query,
            component_id: component_id
        }; /* adding the Loading image until data comes back */
        jQuery("#appinsights_new_statsdata").html('<div class="appinsights-loader" ><img src="images/wpspin_light.gif" alt="Loading..." /></div>');
        jQuery.ajax({
            url: "admin-ajax.php",
            type: "POST",
            data: mydata,
            success: function (data) {
               jQuery("#appinsights_new_statsdata").html(data); /* Adding the Data back to the appinsights_new_statsdata*/
               jQuery("html, body").animate({ scrollTop: jQuery(document).height() }, 500); /*Setting the Screen the down the page. */
            }
        });
 }
