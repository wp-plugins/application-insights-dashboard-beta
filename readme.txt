=== Application Insights Dashboard Plugin ===
Contributors: vsacja 
Tags: Application Insights, Microsoft Azure, Appinsights,Windows Azure,  
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 2.2a
License: GPL2
Version: 2.2a

Application Insights Dashboard gives you the ability to view your Application Insights data in your WordPress dashboard.

== Description ==
Application Insights Dashboard gives you the ability to view your Application Insights data in your WordPress dashboard.

== Installation ==

= Install =

1. Extract the Application Insights Dashboard plugin appinsights.zip to the plugins directory of the WordPress installation. 
e.g. if WordPress is installed in "C:\inetpub\wwwroot\wordpress" directory, extract the appinsights.zip file into directory "C:\inetpub\wwwroot\wordpress\wp-content\plugins".

2. To activate the plugin, log in into the WordPress as administrator and navigate to list of plugins. Then check the associated checkbox for the plugin and click on "Activate" link.

= Register an Azure Active Directory application =

For these steps, you must have an Azure subscription with access to the Azure Active Directory tenant.

1. Sign in to the Azure portal, and navigate to the ACTIVE DIRECTORY section. Choose the directory (tenant) that you would like to use. This should be the active directory which is linked to your Azure subscription.

2. Under the APPLICATIONS tab, click ADD to register a new application. Choose 'Add an application my organization is developing', and a recognizable name. Select the application type as "Native client application".

3. Enter a value for Redirect URI with the format http://<your blog url>/wp-admin/options-general.php
e.g. http://localhost/wordpress/wp-admin/options-general.php

== Appinsights merging with Website

1. Download another plugin "https://wordpress.org/plugins/application-insights/" 
 
2.Install This Plugin =

a. Extract the Application Insights plugin to the plugins directory of the WordPress installation. 
e.g. if WordPress is installed in "C:\inetpub\wwwroot\wordpress" directory, extract the zip file into directory "C:\inetpub\wwwroot\wordpress\wp-content\plugins".

b. To activate the plugin, log in into the WordPress as administrator and navigate to list of plugins. Then check the associated checkbox for the plugin and click on "Activate" link.and Configure install it and then activate the plugin 

c. Goto Setting and fine application insights and add the instrumentation key of the components for which you want statistics.  


= Configure the plugin =

1. The plugin can be configured in Settings > Application Insights Dashboard.

2. Enter the Tenant ID and Client ID and click "Authorize Plugin" button. You can find these values under the CONFIGURE tab of your Azure Active Directory application in the Microsoft Azure portal.

3. Login in using your Microsoft Azure credentials.

== Changelog ==
= 2.0a = 
	* Beta release of Application Insights Dashboard.
= 2.2a =
	* Beta release of with Some more Application Insights Dashboard stats.