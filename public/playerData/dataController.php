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
    
    $checkData = ["Status" => "Unknown"];
    
    $rowCounter = 0;
    
    function checkCEORestrictions() {
        
        return (in_array("CEO", $_SESSION["AccessRoles"]) and !in_array("HR", $_SESSION["AccessRoles"]) and !in_array("Super Admin", $_SESSION["AccessRoles"]));
        
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST["Action"]) and $_POST["Action"] == "getGroups") {
            
            $checkData["Alliances"] = [];
            $checkData["Corporations"] = [];
            
            $ceoAllianceRestriction = (checkCEORestrictions()) ? " WHERE allianceid=:allianceid" : "";
            
            $pullAlliances = $GLOBALS['MainDatabase']->prepare("SELECT allianceid, alliancename FROM alliances" . $ceoAllianceRestriction);
            
            if (checkCEORestrictions()) {
                
                $pullAlliances->bindParam(":allianceid", $_SESSION["AllianceID"]);
                
            }
            
            $pullAlliances->execute();
            
            while ($pulledAllianceData = $pullAlliances->fetch(PDO::FETCH_ASSOC)) {
                
                if ($pulledAllianceData["allianceid"] !== "0") {
                    $checkData["Alliances"][] = ["Name" => $pulledAllianceData["alliancename"], "ID" => $pulledAllianceData["allianceid"]];
                }
                
            }
            
            $ceoCorporationRestriction = (checkCEORestrictions()) ? " WHERE corporationid=:corporationid" : "";
            
            $pullCorporations = $GLOBALS['MainDatabase']->prepare("SELECT corporationid, corporationname FROM corporations" . $ceoCorporationRestriction);
            
            if (checkCEORestrictions()) {
                
                $pullCorporations->bindParam(":corporationid", $_SESSION["CorporationID"]);
                
            }
            
            $pullCorporations->execute();
            
            while ($pulledCorporationData = $pullCorporations->fetch(PDO::FETCH_ASSOC)) {
                
                $checkData["Corporations"][] = ["Name" => $pulledCorporationData["corporationname"], "ID" => $pulledCorporationData["corporationid"]];
                
            }
            
            usort($checkData["Alliances"], function($a, $b) {
                return strcasecmp($a["Name"], $b["Name"]);
            });
            
            usort($checkData["Corporations"], function($a, $b) {
                return strcasecmp($a["Name"], $b["Name"]);
            });
            
            $checkData["Status"] = "Success";
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "List") {
            
            $checkData["Player List"] = [];
            $checkData["Fail Counter"] = 0;
            
            if ((htmlspecialchars($_POST["Times"])) === "true") {
                
                $ceoPlayerRestriction = (checkCEORestrictions()) ? " WHERE playercorps LIKE :likecorp " : " ";
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playerid, playername, hascore, recentattendedtime, totalattendedtime, recentcommandedtime, totalcommandedtime, shortstats, isfc FROM players" . $ceoPlayerRestriction . "ORDER BY playername ASC");
                
                if (checkCEORestrictions()) {
                    
                    $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                    
                }
                
                $toPull->execute();
                
                $checkData["Status"] = "No Data";
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $checkData["Status"] = "Data Found";
                    
                    $shortStats = json_decode($pulledData["shortstats"], true);
                                                                
                    $checkData["Player List"][] = ["ID" => $pulledData["playerid"], "Name" => htmlspecialchars($pulledData["playername"]), "Recent Attended" => $pulledData["recentattendedtime"], "Recent Commanded" => $pulledData["recentcommandedtime"], "Total Attended" => $pulledData["totalattendedtime"], "Total Commanded" => $pulledData["totalcommandedtime"], "Last Attended Time" => $shortStats["Last Attended Fleet"], "Last Attended" => date("F jS, Y", $shortStats["Last Attended Fleet"]), "Command Stats" => $shortStats["Command Stats"], "Recent Command Stats" => $shortStats["Recent Command Stats"], "Has Core" => boolval($pulledData["hascore"]), "Is FC" => boolval($pulledData["isfc"])];
                    
                    $rowCounter ++;
                    
                    if ($rowCounter >= $maxTableRows) {
                        break;
                    }
                                    
                }
                
            }
            else {
                
                $ceoPlayerRestriction = (checkCEORestrictions()) ? " WHERE playercorps LIKE :likecorp " : " ";
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playerid, playername, hascore, recentattendedfleets, totalattendedfleets, shortstats, recentcommandedfleets, totalcommandedfleets, isfc FROM players" . $ceoPlayerRestriction . "ORDER BY playername ASC");
                
                if (checkCEORestrictions()) {
                    
                    $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                    
                }
                
                $toPull->execute();
                
                $checkData["Status"] = "No Data";
                
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $checkData["Status"] = "Data Found";
                    
                    $shortStats = json_decode($pulledData["shortstats"], true);
                    
                    $checkData["Player List"][] = ["ID" => $pulledData["playerid"], "Name" => htmlspecialchars($pulledData["playername"]), "Recent Attended" => $pulledData["recentattendedfleets"], "Recent Commanded" => $pulledData["recentcommandedfleets"], "Total Attended" => $pulledData["totalattendedfleets"],"Total Commanded" => $pulledData["totalcommandedfleets"], "Last Attended Time" => $shortStats["Last Attended Fleet"], "Last Attended" => date("F jS, Y", $shortStats["Last Attended Fleet"]), "Command Stats" => $shortStats["Command Stats"], "Recent Command Stats" => $shortStats["Recent Command Stats"], "Has Core" => boolval($pulledData["hascore"]), "Is FC" => boolval($pulledData["isfc"])];
                                        
                    $rowCounter ++;
                    
                    if ($rowCounter >= $maxTableRows) {
                        break;
                    }
                                    
                }
                
            }
            
            $checkData["Counter"] = $rowCounter;
                        
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Get Alts") {
            
            $accountToCheck = htmlspecialchars($_POST["ID"]);
            
            $altIDList = [];
            $checkData["Alt List"] = [];
            
            $ceoPlayerRestriction = (checkCEORestrictions()) ? " AND playercorps LIKE :likecorp " : " ";

            $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playeralts FROM players WHERE playerid=:playerid" . $ceoPlayerRestriction . "LIMIT 1");
            $toPull->bindParam(":playerid", $accountToCheck);
            
            if (checkCEORestrictions()) {
                
                $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                
            }
            
            $toPull->execute();
            $pulledArrayData = $toPull->fetchAll();
            
            $checkData["Status"] = "No Data";
            
            foreach ($pulledArrayData as $throwaway => $pulledData) {
                
                $checkData["Status"] = "Data Found";
                
                $altData = json_decode($pulledData["playeralts"], true);
                
                foreach ($altData as $eachAlt) {
                    
                    $CharacterJson = file_get_contents("http://esi.evetech.net/latest/characters/" . $eachAlt["ID"] . "/?datasource=tranquility");
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
                    
                    $checkData["Alt List"][] = ["ID" => (int)$eachAlt["ID"], "Name" => checkCache("Character", $eachAlt["ID"]), "Corp ID" => $corpID, "Corp Name" => $corpName, "Alliance ID" => $allianceID, "Alliance Name" => $allianceName];
                    
                }
                                
            }
            
        }
        elseif (isset($_POST["Action"]) and $_POST["Action"] == "Filter") {
                        
            $checkData["Player List"] = [];
            $checkData["Fail Counter"] = 0;
            
            if ((htmlspecialchars($_POST["Times"])) === "true") {
                
                $ceoPlayerRestriction = (checkCEORestrictions()) ? " WHERE playercorps LIKE :likecorp " : " ";
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playerid, playername, hascore, playercorps, playeralliances, recentattendedfleets, recentattendedtime, totalattendedtime, recentcommandedfleets, recentcommandedtime, totalcommandedtime, shortstats, isfc FROM players" . $ceoPlayerRestriction . "ORDER BY playername ASC");
                
                if (checkCEORestrictions()) {
                    
                    $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                    
                }
                
                $toPull->execute();
                
                $checkData["Status"] = "No Data";
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $shortStats = json_decode($pulledData["shortstats"], true);
                    $playerCorporations = json_decode($pulledData["playercorps"]);
                    $playerAlliances = json_decode($pulledData["playeralliances"]);
                    
                    if (((htmlspecialchars($_POST["Name"]) === "false" or strpos(htmlspecialchars($pulledData["playername"]), htmlspecialchars($_POST["Name"])) !== false) and (htmlspecialchars($_POST["Alliance"]) === "false" or in_array(htmlspecialchars($_POST["Alliance"]), $playerAlliances)) and (htmlspecialchars($_POST["Corporation"]) === "false" or in_array(htmlspecialchars($_POST["Corporation"]), $playerCorporations)) and ((htmlspecialchars($_POST["Core"]) === "true" and boolval($pulledData["hascore"])) or htmlspecialchars($_POST["Core"]) === "false") and ((htmlspecialchars($_POST["FC"]) === "true" and boolval($pulledData["isfc"])) or htmlspecialchars($_POST["FC"]) === "false")) or htmlspecialchars($_POST["All"]) === "true") {
                        
                        $checkData["Status"] = "Data Found";

                        $failsStandard = false;
                        
                        if ($pulledData["recentcommandedfleets"] < htmlspecialchars($_POST["Commanded"]) or $pulledData["recentattendedfleets"] < htmlspecialchars($_POST["Attended"])) {
                            
                            $failsStandard = true;
                            
                        }

                        $checkData["Player List"][] = ["ID" => $pulledData["playerid"], "Name" => htmlspecialchars($pulledData["playername"]), "Recent Attended" => $pulledData["recentattendedtime"], "Recent Commanded" => $pulledData["recentcommandedtime"], "Total Attended" => $pulledData["totalattendedtime"], "Total Commanded" => $pulledData["totalcommandedtime"], "Last Attended Time" => $shortStats["Last Attended Fleet"], "Last Attended" => date("F jS, Y", $shortStats["Last Attended Fleet"]), "Command Stats" => $shortStats["Command Stats"], "Recent Command Stats" => $shortStats["Recent Command Stats"], "Has Core" => boolval($pulledData["hascore"]), "Is FC" => boolval($pulledData["isfc"]), "Fails Standard" => $failsStandard];
                                        
                    }
                                    
                }
                
            }
            else {
                
                $ceoPlayerRestriction = (checkCEORestrictions()) ? " WHERE playercorps LIKE :likecorp " : " ";
                
                $toPull = $GLOBALS['MainDatabase']->prepare("SELECT playerid, playername, hascore, playercorps, playeralliances, recentattendedfleets, totalattendedfleets, shortstats, recentcommandedfleets, totalcommandedfleets, isfc FROM players" . $ceoPlayerRestriction . "ORDER BY playername ASC");
                
                if (checkCEORestrictions()) {
                    
                    $toPull->bindValue(":likecorp", "%\"" . $_SESSION["CorporationID"] . "\"%");
                    
                }
                
                $toPull->execute();
                
                $checkData["Status"] = "No Data";
                while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
                    
                    $shortStats = json_decode($pulledData["shortstats"], true);
                    $playerCorporations = json_decode($pulledData["playercorps"]);
                    $playerAlliances = json_decode($pulledData["playeralliances"]);
                    
                    if (((htmlspecialchars($_POST["Name"]) === "false" or strpos(htmlspecialchars($pulledData["playername"]), htmlspecialchars($_POST["Name"])) !== false) and (htmlspecialchars($_POST["Alliance"]) === "false" or in_array(htmlspecialchars($_POST["Alliance"]), $playerAlliances)) and (htmlspecialchars($_POST["Corporation"]) === "false" or in_array(htmlspecialchars($_POST["Corporation"]), $playerCorporations)) and ((htmlspecialchars($_POST["Core"]) === "true" and boolval($pulledData["hascore"])) or htmlspecialchars($_POST["Core"]) === "false") and ((htmlspecialchars($_POST["FC"]) === "true" and boolval($pulledData["isfc"])) or htmlspecialchars($_POST["FC"]) === "false")) or htmlspecialchars($_POST["All"]) === "true") {
                        
                        $checkData["Status"] = "Data Found";
                        
                        $failsStandard = false;
                        
                        if ($pulledData["recentcommandedfleets"] < htmlspecialchars($_POST["Commanded"]) or $pulledData["recentattendedfleets"] < htmlspecialchars($_POST["Attended"])) {
                            
                            $failsStandard = true;
                            
                        }
                        
                        $checkData["Player List"][] = ["ID" => $pulledData["playerid"], "Name" => htmlspecialchars($pulledData["playername"]), "Recent Attended" => $pulledData["recentattendedfleets"], "Recent Commanded" => $pulledData["recentcommandedfleets"], "Total Attended" => $pulledData["totalattendedfleets"],"Total Commanded" => $pulledData["totalcommandedfleets"], "Last Attended Time" => $shortStats["Last Attended Fleet"], "Last Attended" => date("F jS, Y", $shortStats["Last Attended Fleet"]), "Command Stats" => $shortStats["Command Stats"], "Recent Command Stats" => $shortStats["Recent Command Stats"], "Has Core" => boolval($pulledData["hascore"]), "Is FC" => boolval($pulledData["isfc"]), "Fails Standard" => $failsStandard];

                    }
                                    
                }
                
            }
                        
            if ($_POST["Sort_By"] == "Name") {
                if ($_POST["Sort_Order"] == "Ascending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return strcasecmp($a["Name"], $b["Name"]);
                    });
                    
                }
                elseif ($_POST["Sort_Order"] == "Descending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return strcasecmp($b["Name"], $a["Name"]);
                    });
                    
                }
            }
            
            elseif ($_POST["Sort_By"] == "RecentAttended") {
                if ($_POST["Sort_Order"] == "Ascending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $b["Recent Attended"] <=> $a["Recent Attended"];
                    });
                    
                }
                elseif ($_POST["Sort_Order"] == "Descending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $a["Recent Attended"] <=> $b["Recent Attended"];
                    });
                    
                }
            }
            
            elseif ($_POST["Sort_By"] == "Attended") {
                if ($_POST["Sort_Order"] == "Ascending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $b["Total Attended"] <=> $a["Total Attended"];
                    });
                    
                }
                elseif ($_POST["Sort_Order"] == "Descending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $a["Total Attended"] <=> $b["Total Attended"];
                    });
                    
                }
            }
            
            elseif ($_POST["Sort_By"] == "RecentLed") {
                if ($_POST["Sort_Order"] == "Ascending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $b["Recent Commanded"] <=> $a["Recent Commanded"];
                    });
                    
                }
                elseif ($_POST["Sort_Order"] == "Descending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $a["Recent Commanded"] <=> $b["Recent Commanded"];
                    });
                    
                }
            }
            
            elseif ($_POST["Sort_By"] == "Led") {
                if ($_POST["Sort_Order"] == "Ascending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $b["Total Commanded"] <=> $a["Total Commanded"];
                    });
                    
                }
                elseif ($_POST["Sort_Order"] == "Descending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $a["Total Commanded"] <=> $b["Total Commanded"];
                    });
                    
                }
            }
            
            elseif ($_POST["Sort_By"] == "LastActive") {
                if ($_POST["Sort_Order"] == "Ascending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $b["Last Attended Time"] <=> $a["Last Attended Time"];
                    });
                    
                }
                elseif ($_POST["Sort_Order"] == "Descending") {
                    
                    uasort($checkData["Player List"], function($a, $b) {
                        return $a["Last Attended Time"] <=> $b["Last Attended Time"];
                    });
                    
                }
            }
            
            $checkData["Player List"] = array_slice($checkData["Player List"], 0, $maxTableRows);
            
            $checkData["Counter"] = count($checkData["Player List"]);
            $checkData["Fail Counter"] = count(array_filter($checkData["Player List"], function($a) {
                return $a["Fails Standard"] == true;
            }));
            
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