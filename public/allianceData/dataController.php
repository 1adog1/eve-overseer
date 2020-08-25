<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Alliance PAP";
    
	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
    $checkData = ["Status" => "Unknown"];
    
    $rowCounter = 0;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "Affiliations") {
            
            $checkData["Affiliation Data"] = [];
            
            $toPull = $GLOBALS['MainDatabase']->query("SELECT * FROM alliances ORDER BY represented DESC");
            
            while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                
                $checkData["Affiliation Data"][$pulledData["allianceid"]] = ["Name" => $pulledData["alliancename"], "Represented" => $pulledData["represented"], "Short Stats" => json_decode($pulledData["shortstats"], true), "Corporations" => json_decode($pulledData["corporations"]), "Corporation Data" => []];
                
                $toQuery = $GLOBALS['MainDatabase']->query("SELECT * FROM corporations ORDER BY represented DESC");
                
                while ($queryData = $toQuery->fetch(PDO::FETCH_ASSOC)) {
                    
                    if (in_array($queryData["corporationid"], $checkData["Affiliation Data"][$pulledData["allianceid"]]["Corporations"])) {
                        
                        $checkData["Affiliation Data"][$pulledData["allianceid"]]["Corporation Data"][$queryData["corporationid"]] = ["Name" => $queryData["corporationname"], "Represented" => $queryData["represented"], "Members" => $queryData["members"], "Short Stats" => json_decode($queryData["shortstats"], true)];
                        
                    }
                    
                }
                                
            }
            
            $checkData["Status"] = "Data Found";
            
        }
        else {
            
            $checkData["Status"] = "Error";
            error_log("Bad Action");
            makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Bad Action");
            
        }
        
        echo json_encode($checkData);
        
    }
    else {
        
        error_log("Bad Method");
        makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Bad Method");
        
        $checkData["Status"] = "Error";
        echo json_encode($checkData);
        
    }
	
?>