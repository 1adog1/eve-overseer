<?php

	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "Fleet Commander"];

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel, false);
    
    if ($_SESSION["LoginType"] !== "FC") {
        
		makeLogEntry("Page Access Denied", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Type: [" . json_encode($_SESSION["LoginType"]) . "] / Required: FC");
		
		ob_flush();
		header("Location: /accessDenied");
		ob_end_flush();
		die();        
        
    }
    
    $checkData = ["Status" => "Unknown"];
    
    function getAuthCode($refreshToken) {
        
        require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
        
        $encodedAuthorization = "Basic " . base64_encode($clientid . ":" . $clientsecret);
        $requestURL = "https://login.eveonline.com/oauth/token";
        $requestData = json_encode(["grant_type" => "refresh_token", "refresh_token" => $refreshToken]);
        $requestOptions = ["http" => ["method" => "POST", "header" => ["Content-Type:application/json", "Authorization:" . $encodedAuthorization, "Accept-Charset:UTF-8"], "content" => $requestData]];
        $requestContext = stream_context_create($requestOptions);
        
        $requestReturned = @file_get_contents($requestURL, false, $requestContext);
        $fullRequest = json_decode($requestReturned,true);
        
        if (isset($fullRequest["access_token"])) {
            
            return $fullRequest["access_token"];
            
        }
        else {
        
            return false;
            
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "Start") {
            
            $approvedSRP = ["Fun", "Stratop", "CTA", "Save", "ADM"];
            
            $fleetName = htmlspecialchars($_POST["Name"]);
            
            if (in_array(htmlspecialchars($_POST["SRP"]), $approvedSRP)) {
                
                $fleetSRP = htmlspecialchars($_POST["SRP"]);
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE commanderid=:commanderid");
                $toPull->bindParam(":commanderid", $_SESSION["CharacterID"]);
                $toPull->execute();
                $pulledArrayData = $toPull->fetchAll();
                            
                if (empty($pulledArrayData)) {
                    
                    $currentTime = time();
                    
                    $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT * FROM commanders WHERE id=:id");
                    $toQuery->bindParam(":id", $_SESSION["CharacterID"]);
                    $toQuery->execute();
                    $queryArrayData = $toQuery->fetchAll();
                                    
                    if (!empty($queryArrayData)) {
                    
                        $authCode = getAuthCode($queryArrayData[0]["refreshtoken"]);
                        
                        if ($authCode !== false) {
                            
                            $requestURL = "https://esi.evetech.net/dev/characters/" . $_SESSION["CharacterID"] . "/fleet/?datasource=tranquility";
                            
                            $requestOptions = ["http" => ["method" => "GET", "header" => ["Content-Type:application/json", "Authorization: Bearer " . $authCode]]];
                            $requestContext = stream_context_create($requestOptions);
                            
                            $fleetReturned = @file_get_contents($requestURL, false, $requestContext);
                            $testStatus = $http_response_header[0];
                            
                            $fleetRequest = json_decode($fleetReturned,true);
                            
                            //error_log($fleetRequest["fleet_boss_id"] . " - " . $_SESSION["CharacterID"] . " - " . json_encode($testStatus));
                            
                            if ($testStatus == "HTTP/1.1 200 OK" and $fleetRequest["fleet_boss_id"] == $_SESSION["CharacterID"]) {
                                
                                $pulledFleetID = $fleetRequest["fleet_id"];
                                $newStatus = "Active";

                                $toCheck = $GLOBALS['MainDatabase']->prepare("SELECT * FROM fleets WHERE fleetid=:fleetid");
                                $toCheck->bindParam(":fleetid", $pulledFleetID);
                                $toCheck->execute();
                                $checkArrayData = $toCheck->fetchAll();
                                                
                                if (!empty($checkArrayData)) {
                                    
                                    $checkData["Status"] = "Stopped";
                                    $checkData["Error"] = "This fleet has already had its data aggregated. Once this has occured the fleet can no longer be tracked.";
                                    
                                    error_log("Fleet Already Recorded");
                                    
                                }
                                else {
                                    
                                    $throwawayValue = "Test";
                                                    
                                    $toInsert = $GLOBALS['MainDatabase']->prepare("INSERT INTO tracking (fleetid, fleetname, srplevel, commanderid, commandername, starttime, status) VALUES (:fleetid, :fleetname, :srplevel, :commanderid, :commandername, :starttime, :status)");
                                    $toInsert->bindParam(":fleetid", $pulledFleetID);
                                    $toInsert->bindParam(":fleetname", $fleetName);
                                    $toInsert->bindParam(":srplevel", $fleetSRP);
                                    $toInsert->bindParam(":commanderid", $_SESSION["CharacterID"]);
                                    $toInsert->bindParam(":commandername", $_SESSION["Character Name"]);
                                    $toInsert->bindParam(":starttime", $currentTime);
                                    $toInsert->bindParam(":status", $newStatus);
                                    
                                    $toInsert->execute();
                                    
                                    $checkData["Status"] = "Active";
                                    
                                    makeLogEntry("Tracking Started", "Host Fleet", $_SESSION["Character Name"], "Tracking has been started for the fleet " . $pulledFleetID . ".");
                                
                                }
                            
                            }
                            else {
                                
                                $checkData["Error"] = "You are not the boss of a fleet. Only fleet bosses can initiate tracking.";
                                
                                error_log("Not in Fleet or Not Fleet Boss");
                                
                            }
                        
                        }
                        else {
                            
                            $checkData["Error"] = "Failed to get a new authentication code. Please try again. If this error persists please logout and back in.";
                            
                            error_log("Failed to Get Auth Code");
                            
                        }

                    }
                    else {
                        
                        $checkData["Error"] = "Somehow we don't have a refresh token on file for you.";
                        
                        error_log("No Commander Logged In");
                        
                    }
                    
                }
                else {
                    
                    $checkData["Error"] = "This fleet has already been marked for tracking.";
                    
                    error_log("Tracking Already Running");
                    
                }
            }
            else {

                $checkData["Error"] = "Oi! Please don't mess with the form HTML!";
                
                error_log("HTML Error");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Stop") {
            
            $pullStatus = "Active";
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE commanderid=:commanderid AND status=:status");
            $toPull->bindParam(":commanderid", $_SESSION["CharacterID"]);
            $toPull->bindParam(":status", $pullStatus);
            $toPull->execute();
            $pulledArrayData = $toPull->fetchAll();

            if (!empty($pulledArrayData)) {
                
                $newStatus = "Stopped";
                
                $currentTime = time();
                
                $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE tracking SET status = :status WHERE commanderid = :commanderid");
                $toUpdate->bindParam(":status", $newStatus);
                $toUpdate->bindParam(":commanderid", $_SESSION["CharacterID"]);
                
                $toUpdate->execute();
            }
            else {
                
                $checkData["Error"] = "There is no fleet currently being tracked.";
                
                error_log("No Tracking to Stop");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Update") {

            $pullStatus = "Active";
            
            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE commanderid=:commanderid AND status=:status");
            $toPull->bindParam(":commanderid", $_SESSION["CharacterID"]);
            $toPull->bindParam(":status", $pullStatus);
            $toPull->execute();
            $pulledArrayData = $toPull->fetchAll();
                        
            if (empty($pulledArrayData)) {
                
                $checkData["Status"] = "Stopped";
                
            }
            else {
                                
                $checkData["Status"] = "Active";

                $dataToCheck = $pulledArrayData[0]["fleetid"];
                
                $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT * FROM snapshots WHERE fleetid=:fleetid ORDER BY timestamp DESC LIMIT 1");
                $toQuery->bindParam(":fleetid", $dataToCheck);
                $toQuery->execute();
                $queryArrayData = $toQuery->fetchAll();
                
                if (!empty($queryArrayData)) {
                    
                    $checkData["Start Date"] = date("F jS, Y - H:i:s", $pulledArrayData[0]["starttime"]);
                    $checkData["Data"] = json_decode($queryArrayData[0]["fleetmembers"], true);
                    $checkData["Found Data"] = true;
                    
                }
                else {
                    
                    $checkData["Found Data"] = false;
                    
                }
                
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