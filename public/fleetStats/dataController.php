<?php

	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR", "Fleet Commander"];

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel, false);
    
    $checkData = ["Status" => "Unknown"];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "Get" and (isset($_POST["FleetID"]))) {
            
            $checkData = ["Status" => "Unknown", "Header Data" => [] , "Affiliation Data" => [], "Member Data" => [], "Ship Data" => ["Ship List" => [], "Snapshot History" => []]];
            
            $fleetID = htmlspecialchars($_POST["FleetID"]);

            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM fleets WHERE fleetid=:fleetid LIMIT 1");
            $toPull->bindParam(":fleetid", $fleetID);
            $toPull->execute();
            $pulledArrayData = $toPull->fetchAll();

            if (!empty($pulledArrayData)) {
                                
                foreach ($pulledArrayData as $throwaway => $pulledData) {
                    
                    if (in_array("HR", $_SESSION["AccessRoles"]) or in_array("Super Admin", $_SESSION["AccessRoles"])) {
                        
                        $checkData["Header Data"]["FleetID"] = $pulledData["fleetid"];
                        
                    }
                    
                    $checkData["Header Data"]["Name"] = $pulledData["fleetname"];
                    $checkData["Header Data"]["SRP Level"] = $pulledData["srplevel"];
                    $checkData["Header Data"]["Boss"] = $pulledData["commandername"];
                    $checkData["Header Data"]["Boss ID"] = $pulledData["commanderid"];
                    $checkData["Header Data"]["Member Count"] = $pulledData["peakmembers"];
                    $checkData["Header Data"]["Start Timestamp"] = $pulledData["starttime"];
                    $checkData["Header Data"]["Start Time"] = date("F jS, Y - H:i:s", $pulledData["starttime"]);
                    $checkData["Header Data"]["End Time"] = date("F jS, Y - H:i:s", $pulledData["endtime"]);
                    $checkData["Header Data"]["Run Time"] = ($pulledData["endtime"] - $pulledData["starttime"]);
                    
                    $checkData["Member Data"] = json_decode($pulledData["memberstats"], true);
                    
                    foreach ($checkData["Member Data"] as $memberID => $memberData) {
                        
                        $checkData["Member Data"][$memberID]["Join Time"] = date("H:i:s", $memberData["join_time"]);
                        
                        if (!isset($checkData["Affiliation Data"][$memberData["alliance_id"]])) {
                            
                            $checkData["Affiliation Data"][$memberData["alliance_id"]] = ["Name" => $memberData["alliance_name"], "Count" => 0, "Corporations" => []];
                            
                        }
                        
                        if (!isset($checkData["Affiliation Data"][$memberData["alliance_id"]]["Corporations"][$memberData["corp_id"]])) {
                            
                            $checkData["Affiliation Data"][$memberData["alliance_id"]]["Corporations"][$memberData["corp_id"]] = ["Name" => $memberData["corp_name"], "Count" => 0];
                            
                        }
                        
                        $checkData["Affiliation Data"][$memberData["alliance_id"]]["Count"] += 1;
                        
                        $checkData["Affiliation Data"][$memberData["alliance_id"]]["Corporations"][$memberData["corp_id"]]["Count"] += 1;
                        
                    }
                    
                    usort($checkData["Member Data"], function($a, $b) {
                        $initial = strtolower($a["name"]);
                        $secondary = strtolower($b["name"]);
                        
                        return strcmp($initial, $secondary); 
                    });
                    
                    $checkData["Ship Data"]["Ship List"] = json_decode($pulledData["shiplist"]);
                    rsort($checkData["Ship Data"]["Ship List"]);
                    $checkData["Ship Data"]["Snapshot History"] = json_decode($pulledData["shipstats"], true);
                    
                    $checkData["Status"] = "Data Found";
                    
                }
                
            }
            else {
                
                $checkData["Status"] = "Error";
                
                error_log("No Fleet Found");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "List") {
            
            $checkData = ["Status" => "Unknown", "Fleet Data" => []];
            
            $rowLimit = (int)$maxTableRows;
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT fleetid, fleetname, srplevel, commandername, starttime, endtime, peakmembers FROM fleets ORDER BY starttime DESC LIMIT :limit");
            $toPull->bindParam(":limit", $rowLimit, PDO::PARAM_INT);
            
            if ($toPull->execute()) {
                
                $checkData["Status"] = "Data Found";
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    $checkData["Fleet Data"][$pulledData["fleetid"]] = ["Name" => $pulledData["fleetname"], "SRP Level" => $pulledData["srplevel"], "Boss" => $pulledData["commandername"], "Start Date" => date("F jS, Y", $pulledData["starttime"]), "Start Time" => date("H:i:s", $pulledData["starttime"]), "Run Time" => ($pulledData["endtime"] - $pulledData["starttime"]) / 3600, "Total Members" => $pulledData["peakmembers"], "timestamp" => $pulledData["starttime"]];
                    
                }
                
            }
            else {
                
                $checkData["Status"] = "Error";
                error_log("Database Query Failed");
                
            }
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Delete" and isset($_POST["FleetID"])) {
            
            if (in_array("HR", $_SESSION["AccessRoles"]) or in_array("Super Admin", $_SESSION["AccessRoles"])) {
            
                $fleetID = htmlspecialchars($_POST["FleetID"]);
                
                $toPull = $GLOBALS['MainDatabase']->prepare("DELETE FROM fleets WHERE fleetid=:fleetid LIMIT 1");
                $toPull->bindParam(":fleetid", $fleetID);
                $toPull->execute();
                
                $checkData["Status"] = "Success";
                
                makeLogEntry("Fleet Deleted", "Fleet Stats", $_SESSION["Character Name"], "Data for the fleet " . $fleetID . " has been deleted.");
                            
            }
            else {
                
                $checkData["Status"] = "Error";
                
                error_log("Insufficient Permissions");
                
            }
            
        }
        else {
            
            $checkData["Status"] = "Error";
            
            error_log("Bad Action");
            
        }
        
        echo json_encode($checkData);
        
    }
    else {
        
        error_log("Bad Method");
        
        $checkData["Status"] = "Error";
        echo json_encode($checkData);
        
    }
    
?>