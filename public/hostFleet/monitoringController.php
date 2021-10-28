<?php

    session_start();

    require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
    
    configureErrorChecking();

    require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
    
    checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "Fleet Commander"];
    checkLastPage();
    $_SESSION["CurrentPage"] = "Host Fleet";
    
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
        $requestURL = "https://login.eveonline.com/v2/oauth/token";
        $requestData = http_build_query(["grant_type" => "refresh_token", "refresh_token" => $refreshToken]);
        $requestOptions = ["http" => ["method" => "POST", "header" => ["Content-Type:application/x-www-form-urlencoded", "Authorization:" . $encodedAuthorization, "Host:login.eveonline.com"], "content" => $requestData]];
        $requestContext = stream_context_create($requestOptions);
        
        $requestReturned = @file_get_contents($requestURL, false, $requestContext);
        $fullRequest = json_decode($requestReturned,true);
        
        if (isset($fullRequest["access_token"])) {
            
            return $fullRequest;
            
        }
        else {
        
            return false;
            
        }
    }
    
    function updateRefreshToken($refreshToken) {
        
        $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE commanders SET refreshtoken=:refreshtoken WHERE id=:id");
        $toUpdate->bindParam(":refreshtoken", $refreshToken);
        $toUpdate->bindParam(":id", $_SESSION["CharacterID"]);
        $toUpdate->execute();
        
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and htmlspecialchars($_POST["Action"]) == "Start") {
                        
            $approvedSRP = ["Fun", "Stratop", "CTA", "Save", "ADM"];
            
            $fleetName = htmlspecialchars($_POST["Name"]);
                        
            if (isset($_POST["Sharing"]) and htmlspecialchars($_POST["Sharing"]) == "true") {
                
                $dataSharing = 1;
                $bytes = random_bytes(8);
                $sharingKey = bin2hex($bytes);
                $sharedWith = json_encode([]);
                
            }
            else {
                
                $dataSharing = 0;
                $sharingKey = null;
                $sharedWith = null;
                
            }
                        
            if (in_array(htmlspecialchars($_POST["SRP"]), $approvedSRP)) {
                
                if (isset($_POST["Voltron"]) and htmlspecialchars($_POST["Voltron"]) == "true") {
                    
                    $fleetSRP = htmlspecialchars($_POST["SRP"]) . " (Voltron)";
                    
                }
                else {
                    
                    $fleetSRP = htmlspecialchars($_POST["SRP"]);
                    
                }
                
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
                    
                        $authData = getAuthCode($queryArrayData[0]["refreshtoken"]);
                        
                        if ($authData !== false) {
                            
                            if ($authData["refresh_token"] != $queryArrayData[0]["refreshtoken"]) {
                                
                                updateRefreshToken($authData["refresh_token"]);
                                
                            }
                            
                            $requestURL = "https://esi.evetech.net/dev/characters/" . $_SESSION["CharacterID"] . "/fleet/?datasource=tranquility";
                            
                            $requestOptions = ["http" => ["method" => "GET", "header" => ["Content-Type:application/json", "Authorization: Bearer " . $authData["access_token"]]]];
                            $requestContext = stream_context_create($requestOptions);
                            
                            $fleetReturned = @file_get_contents($requestURL, false, $requestContext);
                            $testStatus = $http_response_header[0];
                            
                            $fleetRequest = json_decode($fleetReturned,true);
                                                        
                            if ($testStatus == "HTTP/1.1 200 OK" and $fleetRequest["fleet_boss_id"] == $_SESSION["CharacterID"]) {
                                
                                $pulledFleetID = $fleetRequest["fleet_id"];
                                $newStatus = "Active";

                                $toCheck = $GLOBALS['MainDatabase']->prepare("SELECT * FROM fleets WHERE fleetid=:fleetid");
                                $toCheck->bindParam(":fleetid", $pulledFleetID);
                                $toCheck->execute();
                                $checkArrayData = $toCheck->fetchAll();
                                                
                                if (!empty($checkArrayData)) {
                                    
                                    $checkData["Status"] = "Stopped";
                                    $checkData["Error"] = "This fleet has already had its data aggregated. Once this has occurred the fleet can no longer be tracked.";
                                    
                                    error_log("Fleet Already Recorded");
                                    makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Tried to Restart a Fleet");
                                    
                                }
                                else {
                                    
                                    $throwawayValue = "Test";
                                                    
                                    $toInsert = $GLOBALS['MainDatabase']->prepare("INSERT INTO tracking (fleetid, fleetname, srplevel, commanderid, commandername, starttime, status, sharing, sharekey, sharingwith) VALUES (:fleetid, :fleetname, :srplevel, :commanderid, :commandername, :starttime, :status, :sharing, :sharekey, :sharingwith)");
                                    $toInsert->bindParam(":fleetid", $pulledFleetID);
                                    $toInsert->bindParam(":fleetname", $fleetName);
                                    $toInsert->bindParam(":srplevel", $fleetSRP);
                                    $toInsert->bindParam(":commanderid", $_SESSION["CharacterID"]);
                                    $toInsert->bindParam(":commandername", $_SESSION["Character Name"]);
                                    $toInsert->bindParam(":starttime", $currentTime);
                                    $toInsert->bindParam(":status", $newStatus);
                                    $toInsert->bindParam(":sharing", $dataSharing);
                                    $toInsert->bindParam(":sharekey", $sharingKey, PDO::PARAM_STR);
                                    $toInsert->bindParam(":sharingwith", $sharedWith, PDO::PARAM_STR);
                                    
                                    $toInsert->execute();
                                    
                                    $checkData["Status"] = "Active";
                                    
                                    makeLogEntry("Tracking Started", "Host Fleet", $_SESSION["Character Name"], "Tracking has been started for the fleet " . $pulledFleetID . ".");
                                
                                }
                            
                            }
                            else {
                                
                                $checkData["Error"] = "You are not the boss of a fleet. Only fleet bosses can initiate tracking.";
                                
                                error_log("Not in Fleet or Not Fleet Boss");
                                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Tried to Start a Fleet While Not Boss");
                                
                            }
                        
                        }
                        else {
                            
                            $checkData["Error"] = "Failed to get a new authentication code. Please try again. If this error persists please logout and back in.";
                            
                            error_log("Failed to Get Auth Code");
                            makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Failed to Get Auth Code");
                            
                        }

                    }
                    else {
                        
                        $checkData["Error"] = "Somehow we don't have a refresh token on file for you.";
                        
                        error_log("No Commander Logged In");
                        makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "No Refresh Token");
                        
                    }
                    
                }
                else {
                    
                    $checkData["Error"] = "This fleet has already been marked for tracking.";
                    
                    error_log("Tracking Already Running");
                    makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Tried to Track a Fleet That Was Already Tracking");
                    
                }
            }
            else {

                $checkData["Error"] = "Oi! Please don't mess with the form HTML!";
                
                error_log("HTML Error");
                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "User Messed With Fleet Start Form HTML");
                
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
                makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], "Tried to Stop a Fleet Not Being Tracked");
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Subscribe") {
            
            if (isset($_POST["Key"])) {
            
                $pullStatus = "Active";
                $pullShare = 1;
                $pullKey = htmlspecialchars($_POST["Key"]);
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE sharing=:sharing AND sharekey=:sharekey AND status=:status");
                $toPull->bindParam(":sharing", $pullShare);
                $toPull->bindParam(":sharekey", $pullKey);
                $toPull->bindParam(":status", $pullStatus);
                $toPull->execute();
                $pulledArrayData = $toPull->fetchAll();
                            
                if (!empty($pulledArrayData)) {
                    
                    $newShareList = json_decode($pulledArrayData[0]["sharingwith"]);
                    
                    if (!in_array($_SESSION["CharacterID"], $newShareList)) {
                        
                        $newShareList[] = $_SESSION["CharacterID"];
                        
                        $updatedShareList = json_encode($newShareList);
                        
                        $toUpdate = $GLOBALS['MainDatabase']->prepare("UPDATE tracking SET sharingwith=:sharingwith WHERE sharekey=:sharekey");
                        $toUpdate->bindParam(":sharingwith", $updatedShareList);
                        $toUpdate->bindParam(":sharekey", $pullKey);
                        
                        $toUpdate->execute();
                        
                        makeLogEntry("User Database Edit", $_SESSION["CurrentPage"] . " (Data Controller)", $_SESSION["Character Name"], $_SESSION["CharacterID"] . " has subscribed to data for the fleet " . $pulledArrayData[0]["fleetid"] . ".");
                        
                    }
                    
                    $checkData["Status"] = "Data Found";
                    $checkData["Fleet Name"] = htmlspecialchars($pulledArrayData[0]["fleetname"]);
                    
                }
                else {
                    
                    $checkData["Status"] = "Error";
                    $checkData["Error"] = "The share key does not correspond to an active fleet.";
                    
                }
            
            }
            else {
                
                $checkData["Status"] = "Error";
                $checkData["Error"] = "No share key provided.";
                
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
                $checkData["Sharing"] = boolval($pulledArrayData[0]["sharing"]);
                $checkData["Sharing Key"] = $pulledArrayData[0]["sharekey"];

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
            
            $checkData["Shared Data"] = [];
            
            if (isset($_POST["Shared_Fleets"])) {
                
                $sharedFleets = json_decode($_POST["Shared_Fleets"]);
                
                foreach ($sharedFleets as $eachID) {
                    
                    $pullStatus = "Active";
                    $pullShare = 1;
                    $pullKey = htmlspecialchars($eachID);
                    
                    $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM tracking WHERE sharing=:sharing AND sharekey=:sharekey AND status=:status");
                    $toPull->bindParam(":sharing", $pullShare);
                    $toPull->bindParam(":sharekey", $pullKey);
                    $toPull->bindParam(":status", $pullStatus);
                    $toPull->execute();
                    $pulledArrayData = $toPull->fetchAll();
                                
                    if (!empty($pulledArrayData)) {
                        
                        $shareSubs = json_decode($pulledArrayData[0]["sharingwith"]);
                        
                        if (in_array($_SESSION["CharacterID"], $shareSubs)) {
                        
                            $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT * FROM snapshots WHERE fleetid=:fleetid ORDER BY timestamp DESC LIMIT 1");
                            $toQuery->bindParam(":fleetid", $pulledArrayData[0]["fleetid"]);
                            $toQuery->execute();
                            $queryArrayData = $toQuery->fetchAll();
                            
                            if (!empty($queryArrayData)) {
                                
                                $checkData["Shared Data"][$pullKey]["Start Date"] = date("F jS, Y - H:i:s", $pulledArrayData[0]["starttime"]);
                                $checkData["Shared Data"][$pullKey]["Data"] = json_decode($queryArrayData[0]["fleetmembers"], true);
                                $checkData["Shared Data"][$pullKey]["Status"] = "Active";
                                
                            }
                            else {
                                
                                $checkData["Shared Data"][$pullKey]["Status"] = "Stopped";
                                
                            }
                        
                        }
                        
                    }
                    
                }
                
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