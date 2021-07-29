<?php
    session_start();

    require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
    
    configureErrorChecking();

    require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
    
    checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR", "CEO"];
    checkLastPage();
    $_SESSION["CurrentPage"] = "Alliance PAP";
    
    checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
    
    function checkCEORestrictions() {
        
        return (in_array("CEO", $_SESSION["AccessRoles"]) and !in_array("HR", $_SESSION["AccessRoles"]) and !in_array("Super Admin", $_SESSION["AccessRoles"]));
        
    }
    
    if (isset($_GET["alliance"])) {
        
        $allianceToExport = htmlspecialchars($_GET["alliance"]);
        
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
        
        $logString = "Download request made for the alliance " . $allianceToExport . " / File Signature: " . $fileSignature . " / File Tag: [" . substr($originalTag, 0, -2) . "]";
        
        if (checkCEORestrictions()) {
            
            $logString .= (" / Corporation CEO Restriction: " . $_SESSION["CorporationID"]);
            
        }
        
        makeLogEntry("User Database Edit", $_SESSION["CurrentPage"] . " (Download)", $_SESSION["Character Name"], $logString);
        
        $downloadData = [["Alliance Name" . $fileTag[0] . $fileTag[1], "Active Members" . $fileTag[2] . $fileTag[3], "Known Members" . $fileTag[4] . $fileTag[5], "Recent PAPs / Active Members" . $fileTag[6] . $fileTag[7], "Recent PAPs / Known Members" . $fileTag[8] . $fileTag[9], "Recent PAPs" . $fileTag[10] . $fileTag[11], "Total PAPs" . $fileTag[12] . $fileTag[13]]];
        
        $toPull = $GLOBALS['MainDatabase']->prepare("SELECT * FROM alliances WHERE allianceid=:allianceid ORDER BY represented DESC");
        if (checkCEORestrictions()) {
            
            $toPull->bindParam(":allianceid", $_SESSION["AllianceID"]);
            
        }
        else {
            
            $toPull->bindParam(":allianceid", $allianceToExport, PDO::PARAM_INT);
            
        }
        $toPull->execute();
        
        while ($pulledData = $toPull->fetch(PDO::FETCH_ASSOC)) {
            
            $allianceShortStats = json_decode($pulledData["shortstats"], true);
            $allianceCorporationList = json_decode($pulledData["corporations"]);
            
            if ($allianceShortStats["Active Members"] !== 0) {
                
                $allianceActiveRatio = $allianceShortStats["Recent PAP Count"] / $allianceShortStats["Active Members"];
                
            }
            else {
                
                $allianceActiveRatio = "N/A";
                
            }
            
            if ($pulledData["represented"] !== 0) {
                
                $allianceKnownRatio = $allianceShortStats["Recent PAP Count"] / $pulledData["represented"];
                
            }
            else {
                
                $allianceKnownRatio = "N/A";
                
            }
            
            $downloadData[] = [htmlspecialchars($pulledData["alliancename"]), $allianceShortStats["Active Members"], $pulledData["represented"], $allianceActiveRatio, $allianceKnownRatio, $allianceShortStats["Recent PAP Count"], $allianceShortStats["PAP Count"]];
            
            $downloadData[] = [$fileTag];
            $downloadData[] = ["Corporation Name" . $fileTag[0] . $fileTag[1], "Active Members" . $fileTag[2] . $fileTag[3], "Known Members" . $fileTag[4] . $fileTag[5], "Total Members" . $fileTag[6] . $fileTag[7], "Recent PAPs / Active Members" . $fileTag[8] . $fileTag[9], "Recent PAPs / Known Members" . $fileTag[10] . $fileTag[11], "Recent PAPs" . $fileTag[12] . $fileTag[13], "Total PAPs"];
            
            if (checkCEORestrictions()) {
                
                $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT * FROM corporations WHERE corporationid=:corporationid ORDER BY represented DESC");
                $toQuery->bindParam(":corporationid", $_SESSION["CorporationID"], PDO::PARAM_INT);
                $toQuery->execute();
                
                while ($queryData = $toQuery->fetch(PDO::FETCH_ASSOC)) {
                    
                    $corporationShortStats = json_decode($queryData["shortstats"], true);
                    
                    if ($corporationShortStats["Active Members"] !== 0) {
                        
                        $corporationActiveRatio = $corporationShortStats["Recent PAP Count"] / $corporationShortStats["Active Members"];
                        
                    }
                    else {
                        
                        $corporationActiveRatio = "N/A";
                        
                    }
                    
                    if ($queryData["represented"] !== 0) {
                        
                        $corporationKnownRatio = $corporationShortStats["Recent PAP Count"] / $queryData["represented"];
                        
                    }
                    else {
                        
                        $corporationKnownRatio = "N/A";
                        
                    }
                    
                    $downloadData[] = [htmlspecialchars($queryData["corporationname"]), $corporationShortStats["Active Members"], $queryData["represented"], $queryData["members"], $corporationActiveRatio, $corporationKnownRatio, $corporationShortStats["Recent PAP Count"], $corporationShortStats["PAP Count"]];
                    
                }
                
            }
            else {
            
                foreach ($allianceCorporationList as $eachCorporation) {
                
                    $toQuery = $GLOBALS['MainDatabase']->prepare("SELECT * FROM corporations WHERE corporationid=:corporationid ORDER BY represented DESC");
                    $toQuery->bindParam(":corporationid", $eachCorporation, PDO::PARAM_INT);
                    $toQuery->execute();
                    
                    while ($queryData = $toQuery->fetch(PDO::FETCH_ASSOC)) {
                        
                        $corporationShortStats = json_decode($queryData["shortstats"], true);
                        
                        if ($corporationShortStats["Active Members"] !== 0) {
                            
                            $corporationActiveRatio = $corporationShortStats["Recent PAP Count"] / $corporationShortStats["Active Members"];
                            
                        }
                        else {
                            
                            $corporationActiveRatio = "N/A";
                            
                        }
                        
                        if ($queryData["represented"] !== 0) {
                            
                            $corporationKnownRatio = $corporationShortStats["Recent PAP Count"] / $queryData["represented"];
                            
                        }
                        else {
                            
                            $corporationKnownRatio = "N/A";
                            
                        }
                        
                        $downloadData[] = [htmlspecialchars($queryData["corporationname"]), $corporationShortStats["Active Members"], $queryData["represented"], $queryData["members"], $corporationActiveRatio, $corporationKnownRatio, $corporationShortStats["Recent PAP Count"], $corporationShortStats["PAP Count"]];
                        
                    }
                
                }
            
            }
                            
        }
        
        header("Content-type: text/csv");
        header("Cache-Control: no-store, no-cache");
        header("Content-Disposition: attachment; filename=overseer_alliance_pap_" . $fileSignature . ".csv");
        $fileOutput = fopen("php://output", "w");
        
        foreach ($downloadData as $eachLine) {
            
            fputcsv($fileOutput, $eachLine);
            
        }
        
        fclose($fileOutput);
                
    }
    else {
        
        error_log("Download Request Missing Alliance Specifier");
        makeLogEntry("Page Error", $_SESSION["CurrentPage"] . " (Download)", $_SESSION["Character Name"], "Download Request Missing Alliance Specifier");
        
    }
?>