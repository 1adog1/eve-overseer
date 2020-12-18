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
			curl_setopt($curlPost, CURLOPT_URL, "https://login.eveonline.com/oauth/token/");
			curl_setopt($curlPost, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlPost, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curlPost, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization:" . $encodedAuthorization]);
			curl_setopt($curlPost, CURLOPT_POSTFIELDS, json_encode(["grant_type" => "authorization_code", "code" => $authenticationCode]));

			$response = json_decode(curl_exec($curlPost), true);
			
			if (isset($response["access_token"])) {
			
				$authenticationToken = $response["access_token"];
				$refreshToken = $response["refresh_token"];
				
				curl_close($curlPost);

				$curlGet = curl_init();
				curl_setopt($curlGet, CURLOPT_URL, "https://login.eveonline.com/oauth/verify/");
				curl_setopt($curlGet, CURLOPT_HTTPGET, true);
				curl_setopt($curlGet, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curlGet, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curlGet, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $authenticationToken]);

				$response = json_decode(curl_exec($curlGet), true);
								
				if (isset($response["Scopes"]) and strpos($response["Scopes"], "esi-fleets.read_fleet.v1") !== false and strpos($response["Scopes"], "esi-fleets.write_fleet.v1") !== false) {
                    
                    getCharacterCore($response["CharacterID"]);
                    
                    if (checkForFC($response["CharacterID"], $_SESSION["CoreData"]["Groups"]) or in_array($response["CharacterID"], $superadmins)) {
					
                        $_SESSION["CharacterID"] = $response["CharacterID"];
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

                        curl_close($curlGet);

                        checkCookies();
                    
                        makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Success");
                    
                    }
                    else {
                        
                        checkForErrors();
                        makeLogEntry("User Login", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Failure - Character Not FC");
                        
                    }
					
				}
				else {
				
					$_SESSION["CharacterID"] = $response["CharacterID"];
                    $_SESSION["LoginType"] = "View";
					
					curl_close($curlGet);

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