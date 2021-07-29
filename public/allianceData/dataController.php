<?php
    session_start();

    require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
    
    configureErrorChecking();

    require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
    
    checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR", "CEO"];
    checkLastPage();
    $_SESSION["CurrentPage"] = "Alliance PAP";
    
    checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
    
    $checkData = ["Status" => "Unknown"];
    
    $rowCounter = 0;
    
    function checkCEORestrictions() {
        
        return (in_array("CEO", $_SESSION["AccessRoles"]) and !in_array("HR", $_SESSION["AccessRoles"]) and !in_array("Super Admin", $_SESSION["AccessRoles"]));
        
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "Affiliations") {
            
            $checkData["Affiliation Data"] = [];
            
            $ceoAllianceRestriction = (checkCEORestrictions()) ? " WHERE allianceid=:allianceid " : " ";
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM alliances" . $ceoAllianceRestriction . "ORDER BY represented DESC");
            
            if (checkCEORestrictions()) {
                
                $toPull->bindParam(":allianceid", $_SESSION["AllianceID"]);
                
            }
            
            $toPull->execute();
            
            while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                
                $checkData["Affiliation Data"][$pulledData["allianceid"]] = ["Name" => $pulledData["alliancename"], "Represented" => $pulledData["represented"], "Short Stats" => json_decode($pulledData["shortstats"], true), "Corporations" => json_decode($pulledData["corporations"]), "Corporation Data" => []];
                
                $ceoCorporationRestriction = (checkCEORestrictions()) ? " WHERE corporationid=:corporationid " : " ";
                
                $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT * FROM corporations" . $ceoCorporationRestriction . "ORDER BY represented DESC");
                
                if (checkCEORestrictions()) {
                    
                    $toQuery->bindParam(":corporationid", $_SESSION["CorporationID"]);
                    
                }
                
                $toQuery->execute();
                
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