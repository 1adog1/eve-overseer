<?php
    session_start();
    
    require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";

    purgeCookies();
    configureErrorChecking();
    
    checkLastPage();
    $_SESSION["CurrentPage"] = "Eve Authentication";
    
    require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";

    $encodedAuthorization = "Basic " . base64_encode($clientid . ":" . $clientsecret);
    
    if (isset($_GET["code"]) and isset($_GET["state"]) and isset($_SESSION["UniqueState"])) {
        
        if (htmlspecialchars($_GET["state"]) == $_SESSION["UniqueState"]) {
    
            $authenticationCode = htmlspecialchars($_GET["code"]);

            $curlPost = curl_init();
            curl_setopt($curlPost, CURLOPT_URL, "https://login.eveonline.com/v2/oauth/token");
            curl_setopt($curlPost, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlPost, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curlPost, CURLOPT_HTTPHEADER, ["Content-Type:application/x-www-form-urlencoded", "Authorization:" . $encodedAuthorization, "Host:login.eveonline.com"]);
			curl_setopt($curlPost, CURLOPT_POSTFIELDS, http_build_query(["grant_type" => "authorization_code", "code" => $authenticationCode]));

            $response = json_decode(curl_exec($curlPost), true);
            
            if (isset($response["access_token"])) {
            
                $authenticationToken = $response["access_token"];
                $refreshToken = $response["refresh_token"];
                
                curl_close($curlPost);
                
                $accessArray = explode(".", $authenticationToken);
                $accessHeader = json_decode(base64_decode($accessArray[0]), true);
                $accessPayload = json_decode(base64_decode($accessArray[1]), true);
                $accessSignature = $accessArray[2];
                
                $accessSubject = explode(":", $accessPayload["sub"]);
                $accessCharacterID = $accessSubject[2];
                
                if (isset($accessPayload["scp"]) and !array_diff(["esi-fleets.read_fleet.v1", "esi-fleets.write_fleet.v1"], $accessPayload["scp"])) {
                    
                    getCharacterCore($accessCharacterID);
                    
                    if (checkForFC($accessCharacterID, $_SESSION["CoreData"]["Groups"]) or in_array($accessCharacterID, $superadmins)) {
                    
                        $_SESSION["CharacterID"] = $accessCharacterID;
                        $_SESSION["LoginType"] = "FC";
                        
                        $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM commanders WHERE id=:id");
                        $toPull->bindParam(':id', $_SESSION["CharacterID"]);
                        $toPull->execute();
                        $commanderData = $toPull->fetchAll();
                        
                        if (empty($commanderData)) {
                            
                            $toInsert = $GLOBALS['MainDatabase']->prepare("INSERT INTO commanders (id, refreshtoken) VALUES (:id, :refreshtoken)");
                            $toInsert->bindParam(':id', $_SESSION["CharacterID"]);
                            $toInsert->bindParam(':refreshtoken', $refreshToken);
                            $toInsert->execute();
                            
                            makeLogEntry("Automated Database Edit", "Access Control", "[Server Backend]", "An commander entry has been generated for " . $_SESSION["CharacterID"] . ".");

                        }
                        else {
                            
                            $toDelete = $GLOBALS['MainDatabase']->prepare("DELETE FROM commanders WHERE id=:id");
                            $toDelete->bindParam(':id', $_SESSION["CharacterID"]);
                            $toDelete->execute();
                            
                            $toInsert = $GLOBALS['MainDatabase']->prepare("INSERT INTO commanders (id, refreshtoken) VALUES (:id, :refreshtoken)");
                            $toInsert->bindParam(':id', $_SESSION["CharacterID"]);
                            $toInsert->bindParam(':refreshtoken', $refreshToken);
                            $toInsert->execute();
                            
                            makeLogEntry("Automated Database Edit", "Access Control", "[Server Backend]", "An commander entry has been regenerated for " . $_SESSION["CharacterID"] . ".");                        
                            
                        }
                        
                        checkCookies();
                    
                        makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Success");
                    
                    }
                    else {
                        
                        checkForErrors();
                        makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Failure - Character Not FC");
                        
                    }
                    
                }
                else {
                
                    $_SESSION["CharacterID"] = $accessCharacterID;
                    $_SESSION["LoginType"] = "View";
                    
                    checkCookies();
                
                    makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Success");
                }
            
            }
            else {
                curl_close($curlPost);
                
                checkForErrors();
                makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Failure");

            }
        }
        else {
            checkForErrors();
            makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Failure - STATES DO NOT MATCH");            
        }
    }
    else {
        
        checkForErrors();
        makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Failure");
    }

    ob_flush();
    header("Location: /");
    ob_end_flush();
    die();
?>