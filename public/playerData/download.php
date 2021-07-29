<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR", "CEO"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Player PAP";
    
	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	    
    $rowCounter = 0;
    
    function checkCEORestrictions() {
        
        return (in_array("CEO", $_SESSION["AccessRoles"]) and !in_array("HR", $_SESSION["AccessRoles"]) and !in_array("Super Admin", $_SESSION["AccessRoles"]));
        
    }
    
    function getFormattedHours($timeframe) {
        
        $rawTime = $timeframe / 3600;
        $totalHours = floor($rawTime);
        $totalMinutes = floor(($rawTime - $totalHours) * 60);
        $totalSeconds = $timeframe % 60;
        $totalTime = $totalHours . ":" . sprintf("%02d", $totalMinutes) . ":" . sprintf("%02d", $totalSeconds);
        
        return $totalTime;
        
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
            if (isset($_POST["CharacterName"]) and isset($_POST["AllianceName"]) and isset($_POST["CorporationName"]) and isset($_POST["exportTimes"])) {
                
            $signatureBytes = random_bytes(4);
            $fileSignature = bin2hex($signatureBytes);
            $approvedChars = [2, 28, 29, 31];
            $originalTag = "";
            $fileTag = "";
            
            foreach (range(0, 13) as $throwaway) {
                
                $newInt = random_int(0, (count($approvedChars) - 1));
                
                $originalTag .= ($approvedChars[$newInt] . ", ");
                $fileTag .= chr($approvedChars[$newInt]);
                
            }
            
            $logString = "Download request made for a group of players with conditions: [Name: " . htmlspecialchars($_POST["CharacterName"]) . ", Alliance: " . htmlspecialchars($_POST["AllianceName"]) . ", Corporation: " . htmlspecialchars($_POST["CorporationName"]) . ", Core Only: " . (isset($_POST["core"]) ? "True" : "False") . ", FC Only: " . (isset($_POST["fc"]) ? "True" : "False") . ", Show All: " . (isset($_POST["all"]) ? "True" : "False") . ", Use Times: " . htmlspecialchars($_POST["exportTimes"]) . "] / File Signature: " . $fileSignature . " / File Tag: [" . substr($originalTag, 0, -2) . "]";
            
            if (checkCEORestrictions()) {
                
                $logString .= (" / Corporation CEO Restriction: " . $_SESSION["CorporationID"]);
                
            }
            
            makeLogEntry("User Database Edit", $_SESSION["CurrentPage"] . " (Download)", $_SESSION["Character Name"], $logString);
        
            if ((htmlspecialchars($_POST["exportTimes"])) === "true") {
                
                $downloadData = [["Name" . $fileTag[0] . $fileTag[1], "Time Recently Attended" . $fileTag[2] . $fileTag[3], "Total Time Attended" . $fileTag[4] . $fileTag[5], "Time Recently Led" . $fileTag[6] . $fileTag[7], "Total Time Led" . $fileTag[8] . $fileTag[9], "Last Active" . $fileTag[10] . $fileTag[11], "Has Core?" . $fileTag[12] . $fileTag[13], "Is FC?"]];
                
                $ceoPlayerRestriction = (checkCEORestrictions()) ? " WHERE playercorps LIKE :likecorp " : " ";
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playerid, playername, hascore, playercorps, playeralliances, recentattendedfleets, recentattendedtime, totalattendedtime, recentcommandedfleets, recentcommandedtime, totalcommandedtime, shortstats, isfc FROM players" . $ceoPlayerRestriction . "ORDER BY playername ASC");
                
                if (checkCEORestrictions()) {
                    
                    $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                    
                }
                
                $toPull->execute();
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $playerCorporations = json_decode($pulledData["playercorps"]);
                    $playerAlliances = json_decode($pulledData["playeralliances"]);
                    
                    if (((htmlspecialchars($_POST["CharacterName"]) === "" or strpos(htmlspecialchars($pulledData["playername"]), htmlspecialchars($_POST["CharacterName"])) !== false) and (htmlspecialchars($_POST["AllianceName"]) === "" or in_array(htmlspecialchars($_POST["AllianceName"]), $playerAlliances)) and (htmlspecialchars($_POST["CorporationName"]) === "" or in_array(htmlspecialchars($_POST["CorporationName"]), $playerCorporations)) and (!isset($_POST["core"]) or (htmlspecialchars($_POST["core"]) === "true" and boolval($pulledData["hascore"]))) and (!isset($_POST["fc"]) or (htmlspecialchars($_POST["fc"]) === "true" and boolval($pulledData["isfc"])))) or (isset($_POST["all"]) and htmlspecialchars($_POST["all"]) === "true")) {
                        
                        $shortStats = json_decode($pulledData["shortstats"], true);

                        $downloadData[] = [
                            htmlspecialchars($pulledData["playername"]), 
                            getFormattedHours($pulledData["recentattendedtime"]), 
                            getFormattedHours($pulledData["totalattendedtime"]), 
                            getFormattedHours($pulledData["recentcommandedtime"]), 
                            getFormattedHours($pulledData["totalcommandedtime"]), 
                            date("F j, Y", $shortStats["Last Attended Fleet"]),
                            (boolval($pulledData["hascore"]) ? "True" : "False"),
                            (boolval($pulledData["isfc"]) ? "True" : "False")
                        ];
                                        
                    }
                                    
                }
                
            }
            else {
                
                $downloadData = [["Name" . $fileTag[0] . $fileTag[1], "Recently Attended Fleets" . $fileTag[2] . $fileTag[3], "Total Attended Fleets" . $fileTag[4] . $fileTag[5], "Recently Led Fleets" . $fileTag[6] . $fileTag[7], "Total Led Fleets" . $fileTag[8] . $fileTag[9], "Last Active" . $fileTag[10] . $fileTag[11], "Has Core?" . $fileTag[12] . $fileTag[13], "Is FC?"]];
                
                $ceoPlayerRestriction = (checkCEORestrictions()) ? " WHERE playercorps LIKE :likecorp " : " ";
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playerid, playername, hascore, playercorps, playeralliances, recentattendedfleets, totalattendedfleets, shortstats, recentcommandedfleets, totalcommandedfleets, isfc FROM players" . $ceoPlayerRestriction . "ORDER BY playername ASC");
                
                if (checkCEORestrictions()) {
                    
                    $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                    
                }
                
                $toPull->execute();
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $playerCorporations = json_decode($pulledData["playercorps"]);
                    $playerAlliances = json_decode($pulledData["playeralliances"]);
                    
                    if (((htmlspecialchars($_POST["CharacterName"]) === "" or strpos(htmlspecialchars($pulledData["playername"]), htmlspecialchars($_POST["CharacterName"])) !== false) and (htmlspecialchars($_POST["AllianceName"]) === "" or in_array(htmlspecialchars($_POST["AllianceName"]), $playerAlliances)) and (htmlspecialchars($_POST["CorporationName"]) === "" or in_array(htmlspecialchars($_POST["CorporationName"]), $playerCorporations)) and (!isset($_POST["core"]) or (htmlspecialchars($_POST["core"]) === "true" and boolval($pulledData["hascore"]))) and (!isset($_POST["fc"]) or (htmlspecialchars($_POST["fc"]) === "true" and boolval($pulledData["isfc"])))) or (isset($_POST["all"]) and htmlspecialchars($_POST["all"]) === "true")) {
                        
                        $shortStats = json_decode($pulledData["shortstats"], true);
                        
                        $downloadData[] = [
                            htmlspecialchars($pulledData["playername"]), 
                            $pulledData["recentattendedfleets"], 
                            $pulledData["totalattendedfleets"], 
                            $pulledData["recentcommandedfleets"], 
                            $pulledData["totalcommandedfleets"], 
                            date("F j, Y", $shortStats["Last Attended Fleet"]),
                            (boolval($pulledData["hascore"]) ? "True" : "False"),
                            (boolval($pulledData["isfc"]) ? "True" : "False")
                        ];
                    }
                                    
                }
                
            }
            
            header("Content-type: text/csv");
            header("Cache-Control: no-store, no-cache");
            header("Content-Disposition: attachment; filename=overseer_player_pap_" . $fileSignature . ".csv");
            $fileOutput = fopen("php://output", "w");
            
            foreach ($downloadData as $eachLine) {
                
                fputcsv($fileOutput, $eachLine);
                
            }
            
            fclose($fileOutput);
            
        }
        else {
            
            error_log("Download Request Missing Required Conditions");
            makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Download)", $_SESSION["Character Name"], "Download Request Missing Required Conditions");
            
        }
        
    }
    else {
        
        error_log("Bad Method");
        makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Download)", $_SESSION["Character Name"], "Bad Method");
        
    }
	
?>