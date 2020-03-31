<?php

	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR", "Fleet Commander", "All"];

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel, false);
    
    $checkData = ["Status" => "Unknown", "Header Data" => [], "Dates" => [], "Ships" => [], "Timezones" => ["EUTZ" => 0, "USTZ" => 0, "AUTZ" => 0], "Roles" => ["Fleet" => 0, "Wing" => 0, "Squad" => 0], "Fleets" => []];
    
    function checkCharacterExists($characterID) {
        
        $characterExists = false;
        
        $CharacterJson = @file_get_contents("http://esi.evetech.net/latest/characters/" . $characterID . "/?datasource=tranquility");
        $CharacterData = json_decode($CharacterJson, TRUE);
        
        if (isset($CharacterData["name"])) {
            
            $characterExists = true;
            
        }
        
        return $characterExists;
        
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "Get") {
            
            if (htmlspecialchars($_POST["ID"]) != null and (in_array("HR", $_SESSION["AccessRoles"]) or in_array("Super Admin", $_SESSION["AccessRoles"])) and is_numeric(htmlspecialchars($_POST["ID"])) and checkCharacterExists(htmlspecialchars($_POST["ID"])) === true) {

                $checkData["Header Data"]["ID"] = htmlspecialchars($_POST["ID"]);

                $checkData["Header Data"]["Name"] = checkCache("Character", $checkData["Header Data"]["ID"]);
                    
                $CharacterJson = file_get_contents("http://esi.evetech.net/latest/characters/" . $checkData["Header Data"]["ID"] . "/?datasource=tranquility");
                $CharacterData = json_decode($CharacterJson, TRUE);
                
                $checkData["Header Data"]["Corporation ID"] = $CharacterData["corporation_id"];
                
                $checkData["Header Data"]["Corporation"] = checkCache("Corporation", $checkData["Header Data"]["Corporation ID"]);
                
                if (isset($CharacterData["alliance_id"])) {
                    
                    $checkData["Header Data"]["Alliance ID"] = $CharacterData["alliance_id"];
                    $checkData["Header Data"]["Alliance"] = checkCache("Alliance", $checkData["Header Data"]["Alliance ID"]);
                    
                }
                
                else {
                    $checkData["Header Data"]["Alliance ID"] = 0;
                    $checkData["Header Data"]["Alliance"] = "[No Alliance]";
                }
            
            }
            else {

                $checkData["Header Data"]["Name"] = $_SESSION["Character Name"];
                $checkData["Header Data"]["ID"] = $_SESSION["CharacterID"];
                
                $checkData["Header Data"]["Corporation"] = $_SESSION["Corporation Name"];
                $checkData["Header Data"]["Corporation ID"] = $_SESSION["CorporationID"];
                
                $checkData["Header Data"]["Alliance"] = $_SESSION["Alliance Name"];
                $checkData["Header Data"]["Alliance ID"] = $_SESSION["AllianceID"];
                
            }
            
            $checkData["Header Data"]["Total Fleets"] = 0;
            $checkData["Header Data"]["Total Time"] = 0;
            $checkData["Header Data"]["Last Fleet"] = "Never";
            
            $rowLimit = (int)$maxTableRows;
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM fleets ORDER BY starttime DESC LIMIT :limit");
            $toPull->bindParam(":limit", $rowLimit, PDO::PARAM_INT);
            
            if ($toPull->execute()) {
                
                $checkData["Status"] = "No Data";
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                                        
                    $memberData = json_decode($pulledData["memberstats"], true);
                    
                    $stringID = (string)$checkData["Header Data"]["ID"];
                    
                    if (isset($memberData[$stringID])) {
                        
                        $checkData["Status"] = "Data Found";
                        
                        $characterData = $memberData[$stringID];
                        
                        if ($checkData["Header Data"]["Last Fleet"] == "Never") {
                            
                            $checkData["Header Data"]["Last Fleet"] = date("F jS, Y - H:i:s", $characterData["join_time"]);
                            
                        }
                        
                        $checkData["Header Data"]["Total Fleets"] += 1;
                        $checkData["Header Data"]["Total Time"] += $characterData["time_in_fleet"];
                        
                        $dateStamp = strtotime('tomorrow', $pulledData["starttime"]);
                        
                        if (!isset($checkData["Dates"][$dateStamp])) {
                            
                            $checkData["Dates"][$dateStamp] = 0;
                            
                        }
                        
                        $checkData["Dates"][$dateStamp] += $characterData["time_in_fleet"];
                        
                        $joinHour = $characterData["join_time"] / 3600 % 24;
                        
                        if ($joinHour >= 8 and $joinHour < 16) {
                            $checkData["Timezones"]["AUTZ"] += $characterData["time_in_fleet"];
                        }
                        elseif ($joinHour >= 16 and $joinHour < 24) {
                            $checkData["Timezones"]["EUTZ"] += $characterData["time_in_fleet"];
                        }
                        elseif ($joinHour >= 0 and $joinHour < 8) {
                            $checkData["Timezones"]["USTZ"] += $characterData["time_in_fleet"];
                        }
                        
                        foreach ($characterData["time_in_ships"] as $shipID => $shipData) {

                            if (!isset($checkData["Ships"][$shipData["Name"]])) {
                                
                                $checkData["Ships"][$shipData["Name"]] = 0;
                                
                            }
                            
                            $checkData["Ships"][$shipData["Name"]] += $shipData["Time"];
                            
                        }
                        
                        $checkData["Roles"]["Fleet"] += $characterData["time_in_roles"]["Fleet Commander"];
                        $checkData["Roles"]["Wing"] += $characterData["time_in_roles"]["Wing Commander"];
                        $checkData["Roles"]["Squad"] += $characterData["time_in_roles"]["Squad Commander"];
                        
                        $eachFleetData = $characterData;
                        
                        $eachFleetData["Fleet Name"] = $pulledData["fleetname"];
                        $eachFleetData["SRP Level"] = $pulledData["srplevel"];
                        $eachFleetData["Fleet Boss"] = $pulledData["commandername"];
                        $eachFleetData["Fleet ID"] = $pulledData["fleetid"];
                        $eachFleetData["Start Date"] = date("F jS, Y", $pulledData["starttime"]);
                        $eachFleetData["Start Time"] = date("F jS, Y - H:i:s", $pulledData["starttime"]);
                        $eachFleetData["End Time"] = date("F jS, Y - H:i:s", $pulledData["endtime"]);
                        $eachFleetData["Join Time"] = date("F jS, Y - H:i:s", $eachFleetData["join_time"]);
                        $checkData["Fleets"][] = $eachFleetData;
                        
                    }
                    
                }
                
            }
            else {
                
                $checkData["Status"] = "Error";
                error_log("Database Error");
                
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