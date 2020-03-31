<?php

    $configArray = parse_ini_file("config.ini");
    
    //NEUCORE CONFIGURATION
    $appid = $configArray["AppID"];
    $appsecret = $configArray["AppSecret"];
    $appURL = $configArray["AppURL"];
    $fcgroups = explode(",", str_replace(" ", "", $configArray["FCGroups"]));
    $hrgroups = explode(",", str_replace(" ", "", $configArray["HRGroups"]));

	//AUTHENTICATION CONFIGURATION
	$clientid = $configArray["ClientID"];
	$clientsecret = $configArray["ClientSecret"];
	$clientscopes = $configArray["ClientScopes"];
	$clientredirect = $configArray["ClientRedirect"];
	
	
	//DATABASE SERVER CONFIGURATION
	$databaseServer = $configArray["DatabaseServer"] . ":" . $configArray["DatabasePort"];
	$databaseUsername = $configArray["DatabaseUsername"];
	$databasePassword = $configArray["DatabasePassword"];
	
	
	//DATABASE NAME CONFIGURATION
	$databaseName = $configArray["DatabaseName"];
	
	//SITE CONFIGURATION
	$siteURL = $configArray["SiteURL"];
	$superadmins = explode(",", str_replace(" ", "", $configArray["SuperAdmins"]));
	$sessiontime = $configArray["SessionTime"];
	$maxTableRows = $configArray["MaxTableRows"];
	$storeVisitorIPs = $configArray["StoreVisitorIPs"];
    
    //MEMORY CONFIGURATION
    ini_set("memory_limit", "512M");
    /*
    Queries to the fleets database table can sometimes be extremely large, so this is here to give some more room.
    */

?>