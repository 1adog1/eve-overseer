<?php

	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Site Administration";
    
	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel, false);
    
    $checkData = ["Status" => "Unknown", "Role Data" => [], "Fleet Data" => []];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "Get_Roles") {
            
            $knownGroups = [];
            
            $neucoreToken = base64_encode($appid . ":" . $appsecret);

            $requestURL = $appURL . "api/app/v1/show";
            
            $requestOptions = ["http" => ["method" => "GET", "header" => ["Content-Type:application/json", "Authorization: Bearer " . $neucoreToken]]];
            $requestContext = stream_context_create($requestOptions);
            
            $dataReturned = @file_get_contents($requestURL, false, $requestContext);
            
            $statusCode = $http_response_header[0];
                
            if (strpos($statusCode, "200") !== false) {
                
                $appData = json_decode($dataReturned, true);
                
                foreach ($appData["groups"] as $throwaway => $eachGroup) {
                    
                    $toCheck = $GLOBALS['MainDatabase']->prepare("SELECT * FROM roles WHERE roleid = :roleid");
                    $toCheck->bindParam(":roleid", $eachGroup["id"], PDO::PARAM_INT);
                    
                    if ($toCheck->execute()) {
                        
                        $roleFound = False;
                        $roleNameAccurate = False;
                        
                        while ($checkedData = $toCheck->fetch(PDO::FETCH_ASSOC)) {
                            
                            $roleFound = True;
                            
                            $knownGroups[] = $eachGroup["id"];
                            
                            if ($eachGroup["name"] == $checkedData["rolename"]) {
                                
                                $roleNameAccurate = True;
                                
                            }
                            
                        }
                        
                        if (!$roleFound) {
                            
                            $defaultBool = 0;
                            
                            $toAdd = $GLOBALS['MainDatabase']->prepare("INSERT INTO roles (roleid, rolename, isfc, ishr) VALUES (:roleid, :rolename, :isfc, :ishr)");
                            $toAdd->bindParam(":roleid", $eachGroup["id"], PDO::PARAM_INT);
                            $toAdd->bindParam(":rolename", $eachGroup["name"]);
                            $toAdd->bindParam(":isfc", $defaultBool, PDO::PARAM_INT);
                            $toAdd->bindParam(":ishr", $defaultBool, PDO::PARAM_INT);
                            $toAdd->execute();
                            
                            makeLogEntry("Role Discovered", "Site Administration", "[Server Backend]", "The " . $eachGroup["name"] . " role was discovered and added to the database.");
                            
                        }
                        elseif (!$roleNameAccurate) {
                            
                            $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE roles SET rolename = :rolename WHERE roleid = :roleid");
                            $toUpdate->bindParam(":rolename", $eachGroup["name"]);
                            $toUpdate->bindParam(":roleid", $eachGroup["id"], PDO::PARAM_INT);
                            $toUpdate->execute();
                            
                            makeLogEntry("Role Updated", "Site Administration", "[Server Backend]", "The " . $eachGroup["name"] . " role had its name updated.");
                            
                        }
                        
                    }
                    
                }
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM roles");
                
                if ($toPull->execute()) {
                    
                    $checkData["Status"] = "No Data";
                    
                    while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                        
                        if (in_array($pulledData["roleid"], $knownGroups)) {
                            
                            $checkData["Status"] = "Data Found";
                        
                            $checkData["Role Data"][] = ["Name" => $pulledData["rolename"], "ID" => $pulledData["roleid"], "FC" => boolval($pulledData["isfc"]), "HR" => boolval($pulledData["ishr"])];
                        
                        }
                        else {
                            
                            $toDelete = $GLOBALS['MainDatabase']->prepare("DELETE FROM roles WHERE roleid = :roleid");
                            $toDelete->bindParam(":roleid", $pulledData["roleid"], PDO::PARAM_INT);
                            $toDelete->execute();
                            
                            makeLogEntry("Role Deleted", "Site Administration", "[Server Backend]", "The " . $pulledData["rolename"] . " role was deleted.");
                            
                        }
                        
                    }
                    
                }
                else {
                    
                    $checkData["Status"] = "Error";
                    error_log("Database Error");
                    makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Database Error While Pulling Roles");
                    
                }
                
            }
            else {
                
                $checkData["Status"] = "Error";
                error_log("Core Lookup Error");
                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Error From Core While Pulling Roles");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Get_Fleets") {
            
            $pullStatus = "Active";
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE status=:status");
            $toPull->bindParam(":status", $pullStatus);
            
            if ($toPull->execute()) {
                
                $checkData["Status"] = "No Data";
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $checkData["Status"] = "Data Found";
                    
                    $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT count(*) FROM snapshots WHERE fleetid=:fleetid");
                    $toQuery->bindParam(":fleetid", $pulledData["fleetid"]);
                    
                    if($toQuery->execute()) {
                        
                        $fleetSnapshots = $toQuery->fetchColumn();
                        
                        $checkData["Fleet Data"][] = ["ID" => $pulledData["fleetid"], "Name" => $pulledData["fleetname"], "FC" => $pulledData["commandername"], "Started" => date("F jS, Y - H:i:s", $pulledData["starttime"]), "Elapsed" => time() - $pulledData["starttime"], "Snapshots" => $fleetSnapshots, "Missing" => (floor((time() - $pulledData["starttime"]) / 15) - $fleetSnapshots)];
                                                
                    }
                    else {
                        
                        $checkData["Status"] = "Error";
                        error_log("Database Error");
                        makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Database Error While Counting Snapshots");
                        
                    }
                    
                }
                
            }
            else {
                
                $checkData["Status"] = "Error";
                error_log("Database Error");
                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Database Error While Getting Tracking Data");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Update_Roles") {
            
            $approvedAccess = ["HR" => "ishr", "FC" => "isfc"];
            
            $roleToChange = htmlspecialchars($_POST["Group"]);
            $accessToChange = htmlspecialchars($_POST["Access"]);
            
            if (isset($approvedAccess[$accessToChange])) {
                
                $accessVariable = $approvedAccess[$accessToChange];
                
                $toCheck = $GLOBALS['MainDatabase']->prepare("SELECT * FROM roles WHERE roleid = :roleid");
                $toCheck->bindParam(":roleid", $roleToChange, PDO::PARAM_INT);
                
                if ($toCheck->execute()) {
                    
                    while ($checkedData = $toCheck->fetch(PDO::FETCH_ASSOC)) {
                        
                        $oldAccess = boolval($checkedData[$accessVariable]);
                        $newAccess = (int)!$oldAccess;
                        
                        if ($accessToChange == "FC") {
                            
                            $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE roles SET isfc = :newAccess WHERE roleid = :roleid");
                            
                        }
                        elseif ($accessToChange == "HR") {
                            
                            $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE roles SET ishr = :newAccess WHERE roleid = :roleid");
                            
                        }
                        
                            $toUpdate->bindParam(":newAccess", $newAccess, PDO::PARAM_INT);
                            $toUpdate->bindParam(":roleid", $checkedData["roleid"], PDO::PARAM_INT);
                            $toUpdate->execute();
                        
                            makeLogEntry("Role Updated", "Site Administration", "[Server Backend]", "The " . $checkedData["rolename"] . " role had its " . $accessToChange . " access set to " . ($newAccess ? "true" : "false") . ".");
                            
                            $checkData["Status"] = "Success";
                        
                    }
                                        
                }
                else {
                    
                    $checkData["Status"] = "Error";
                    error_log("Database Error");
                    makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Database Error While Getting Roles to Update");
                    
                }
                
            }
            else{
                
                $checkData["Status"] = "Error";
                
                error_log("Bad Access");
                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Tried to Give An Access Level That Does Not Exist");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Terminate_Fleet") {
            
            $fleetToStop = htmlspecialchars($_POST["ID"]);
            
            $pullStatus = "Active";
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE fleetid=:fleetid AND status=:status");
            $toPull->bindParam(":fleetid", $fleetToStop);
            $toPull->bindParam(":status", $pullStatus);
            $toPull->execute();
            $pulledArrayData = $toPull->fetchAll();

            if (!empty($pulledArrayData)) {
                
                $newStatus = "Stopped";
                
                $currentTime = time();
                
                $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE tracking SET status = :status WHERE fleetid = :fleetid");
                $toUpdate->bindParam(":status", $newStatus);
                $toUpdate->bindParam(":fleetid", $fleetToStop);
                
                $toUpdate->execute();
                
                makeLogEntry("Fleet Stopped", "Site Administration", "[Server Backend]", "The fleet " . $fleetToStop . " was terminated.");
                
            }
            else {
                
                $checkData["Error"] = "There is no fleet currently being tracked.";
                
                error_log("No Tracking to Stop");
                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Tried to Stop a Fleet Not Being Tracked");
                
            }
            
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