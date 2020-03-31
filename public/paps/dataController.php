<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "PAPs";
    
	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
    $checkData = ["Status" => "Unknown"];
    
    $rowCounter = 0;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "List") {
            
            $checkData["Player List"] = [];
            
            $toPull = $GLOBALS['MainDatabase']->query("SELECT playerid, playername, hascore, shortstats, isfc FROM players ORDER BY playername ASC");
            
            $checkData["Status"] = "No Data";
            
            while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                
                $checkData["Status"] = "Data Found";
                
                $shortStats = json_decode($pulledData["shortstats"], true);
                
                $checkData["Player List"][] = ["ID" => $pulledData["playerid"], "Name" => htmlspecialchars($pulledData["playername"]), "Attended" => $shortStats["30 Days Attended"], "Commanded" => $shortStats["30 Days Led"], "Last Attended" => date("F jS, Y", $shortStats["Last Attended Fleet"]), "Has Core" => boolval($pulledData["hascore"]), "Is FC" => boolval($pulledData["isfc"])];
                
                $rowCounter ++;
                
                if ($rowCounter >= $maxTableRows) {
                    break;
                }
                                
            }
                        
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Get Alts") {
            
            $accountToCheck = htmlspecialchars($_POST["ID"]);
            
            $altIDList = [];
            $checkData["Alt List"] = [];

            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playeralts FROM players WHERE playerid=:playerid LIMIT 1");
            $toPull->bindParam(":playerid", $accountToCheck);
            $toPull->execute();
            $pulledArrayData = $toPull->fetchAll();
            
            $checkData["Status"] = "No Data";
            
            foreach ($pulledArrayData as $throwaway => $pulledData) {
                
                $checkData["Status"] = "Data Found";
                
                $altData = json_decode($pulledData["playeralts"], true);
                
                foreach ($altData as $throwaway => $eachAlt) {
                    
                    $CharacterJson = file_get_contents("http://esi.evetech.net/latest/characters/" . $eachAlt . "/?datasource=tranquility");
                    $CharacterData = json_decode($CharacterJson, TRUE);
                    
                    $corpID = $CharacterData["corporation_id"];
                    
                    $corpName = checkCache("Corporation", $corpID);
                    
                    if (isset($CharacterData["alliance_id"])) {
                        
                        $allianceID = $CharacterData["alliance_id"];
                        $allianceName = checkCache("Alliance", $allianceID);
                        
                    }
                    
                    else {
                        $allianceID = 0;
                        $allianceName = "[No Alliance]";
                    }
                    
                    $checkData["Alt List"][] = ["ID" => (int)$eachAlt, "Name" => checkCache("Character", $eachAlt), "Corp ID" => $corpID, "Corp Name" => $corpName, "Alliance ID" => $allianceID, "Alliance Name" => $allianceName];
                    
                }
                
                error_log(json_encode($altIDList));
                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Filter") {
            
            $checkData["Player List"] = [];
            
            $toPull = $GLOBALS['MainDatabase']->query("SELECT playerid, playername, hascore, shortstats, isfc FROM players ORDER BY playername ASC");
            
            $checkData["Status"] = "No Data";
            
            while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                
                $shortStats = json_decode($pulledData["shortstats"], true);
                
                if (((htmlspecialchars($_POST["Name"]) === "false" or strpos(htmlspecialchars($pulledData["playername"]), htmlspecialchars($_POST["Name"])) !== false) and $shortStats["30 Days Led"] >= htmlspecialchars($_POST["Commanded"]) and $shortStats["30 Days Attended"] >= htmlspecialchars($_POST["Attended"]) and ((htmlspecialchars($_POST["Core"]) === "true" and boolval($pulledData["hascore"])) or htmlspecialchars($_POST["Core"]) === "false") and ((htmlspecialchars($_POST["FC"]) === "true" and boolval($pulledData["isfc"])) or htmlspecialchars($_POST["FC"]) === "false")) or htmlspecialchars($_POST["All"]) === "true") {
            
                    $checkData["Status"] = "Data Found";
                    
                    $checkData["Player List"][] = ["ID" => $pulledData["playerid"], "Name" => htmlspecialchars($pulledData["playername"]), "Attended" => $shortStats["30 Days Attended"], "Commanded" => $shortStats["30 Days Led"], "Last Attended" => date("F jS, Y", $shortStats["Last Attended Fleet"]), "Has Core" => boolval($pulledData["hascore"]), "Is FC" => boolval($pulledData["isfc"])];
                    
                    $rowCounter ++;
                    
                    if ($rowCounter >= $maxTableRows) {
                        break;
                    }
                
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