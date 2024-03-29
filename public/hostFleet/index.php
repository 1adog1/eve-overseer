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
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
    
    if ($_SESSION["LoginType"] !== "FC") {
        
        makeLogEntry("Page Access Denied", $_SESSION["CurrentPage"], $_SESSION["Character Name"], "Login Type: [" . json_encode($_SESSION["LoginType"]) . "] / Required: FC");
        
        ob_flush();
        header("Location: /accessDenied");
        ob_end_flush();
        die();        
        
    }
    
    $characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
    
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fleet Hosting</title>
    <link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
    <link rel="icon" href="../resources/images/favicon.ico">
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>
    
    <script src="../resources/js/fleetHosting.js"></script>
    
    <meta property="og:title" content="Fleet Hosting">
    <meta property="og:description" content="The Overseer Website">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $siteURL; ?>">

</head>
<style>

    .close-tab {
        filter: invert(49%) sepia(7%) saturate(563%) hue-rotate(167deg) brightness(91%) contrast(88%);
    }
    
    .close-tab:hover {
        filter: invert(44%) sepia(80%) saturate(4919%) hue-rotate(333deg) brightness(87%) contrast(98%);
    }

</style>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/siteCore.php"; ?>

<body class="background">
    <div class="container-fluid">
    
        <br>
        
        <noscript>
            <div class="alert alert-danger text-center">
                It seems you don't have Javascript enabled. Almost every feature of this site requires Javascript to function. 
                <br>
                <br>
                Please enable Javascript if you wish to use this site.
            </div>
        </noscript>
        
        <div id="errorContainer">
        
        </div>
        <ul class="nav nav-tabs" id="fleetTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="main-tab" data-toggle="tab" href="#main-pane" role="tab" aria-controls="main-pane" aria-selected="true">Your Fleet</a>
            </li>
            <li class="nav-item" id="new-share-button">
                <a class="nav-link new-shared" href="#" role="tab">+</a>
            </li>
        </ul>
        <div class="tab-content mt-4" id="fleet-contents">
            <div class="tab-pane fade show active" id="main-pane" role="tabpanel" aria-labelledby="main-tab">
                <div class="row">
                
                    <div id="name_selection" class="form-group col-xl-4">
                    
                        <label for="fleet_name">Fleet Name</label>
                        <input type="text" class="form-control" id="fleet_name">
                        
                    </div>
                    <div id="srp_selection" class="form-group col-xl-3">

                        <label for="fleet_srp">SRP Level</label>
                        <select class="form-control" id="fleet_srp">
                        
                        <?php
                        
                            $typesPull = $GLOBALS['MainDatabase']->prepare("SELECT typename FROM fleettypes");
                            $typesPull->execute();
                            $approvedSRP = $typesPull->fetchAll(PDO::FETCH_COLUMN);
                            
                            foreach ($approvedSRP as $eachLevel) {
                                
                                echo "<option value='" . htmlspecialchars($eachLevel) . "'>" . htmlspecialchars($eachLevel) . "</option>";
                                
                            }
                            
                        ?>
                        
                        </select>
                        
                    </div>
                    <div id="voltron_selection" class="form-group col-xl-1">
                    
                        <div class="custom-control custom-checkbox align-self-center mt-4">
                            <input type="checkbox" class="custom-control-input" id="Voltron" value="true">
                            <label class="custom-control-label" for="Voltron">Coalition Fleet</label>
                        </div>
                        <div class="custom-control custom-checkbox align-self-center">
                            <input type="checkbox" class="custom-control-input" id="Sharing" value="true">
                            <label class="custom-control-label" for="Sharing">Allow Sharing</label>
                        </div>
                        
                    </div>
                    <div class="form-group col-xl-2">
                    
                        <div id="shareKeyContainer" hidden>
                        
                            <label for="share_key">Share Key</label>
                            <input type="text" class="form-control" id="share_key" readonly>
                        
                        </div>
                        
                    </div>
                    <div class="form-group col-xl-2 mt-2">
                    
                        <label for="toggle_button_container"></label>
                        <div id="toggle_button_container">
                        
                        </div>
                        
                    </div>
                
                </div>
                <br>
                <div class="row" id="overviewRow_main" hidden>
                    <div class="col-xl-4">            
                        <div class="card bg-dark">
                            <div class="card-header" id="fleet_boss_main">
                                Fleet Boss: 
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">            
                        <div class="card bg-dark">
                            <div class="card-header" id="member_count_main">
                                Fleet Members: 
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">            
                        <div class="card bg-dark">
                            <div class="card-header" id="fleet_started_main">
                                Fleet Started: 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" id="detailRow_main" hidden>
                    <div class="col-xl-3">
                        <div class="card bg-dark mt-4">
                            <div class="card-header row">
                                <div class="col-xl-6">
                                Ship Breakdown
                                </div>
                                <div class="form-group col-xl-6">
                                
                                    <div class="custom-control custom-switch align-self-center float-right">
                                        <input type="checkbox" class="custom-control-input" id="trash_filter_main" value="true">
                                        <label class="custom-control-label" for="trash_filter_main"><small>Only Ships With FC</small></label>
                                    </div>
                                    
                                </div>
                                
                            </div>
                            <div class="card-body">
                                <ul class="list-group" id="ship_breakdown_main">
                                
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3">            
                        <div class="card bg-dark mt-4">
                            <div class="card-header">
                                Affiliation Breakdown
                            </div>
                            <div class="card-body">
                                <ul class="list-group" id="affiliation_breakdown_main">
                                
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card bg-dark mt-4">
                            <div class="card-header">
                                Fleet Overview
                            </div>
                            <div class="card-body">
                                <ul class="list-group" id="fleet_overview_main">
                                
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/footer.php"; ?>

</html>