<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "Fleet Commander"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Fleet Stats";

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
	$characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Fleet Stats</title>
	<link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
	<link rel="icon" href="../resources/images/favicon.ico">
	
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	<script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="../resources/js/fleetStats.js"></script>
	
	<meta property="og:title" content="Fleet Stats">
	<meta property="og:description" content="The Overseer Website">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo $siteURL; ?>">

</head>

<style>

    #fleetList {
        max-height: 690px; 
        overflow-y: auto; 
        white-space: pre-wrap; 
        scrollbar-width: thin; 
    }
    
    .tooltip-inner {
        text-align: left;
    }

</style>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/siteCore.php"; ?>

<body class="background">
	<div class="container-fluid">
    
        <h1 class="text-center mt-3">Fleet Statistics</h1>
        <hr>
                
        <noscript>
            <div class="alert alert-danger text-center">
                It seems you don't have Javascript enabled. Almost every feature of this site requires Javascript to function. 
                <br>
                <br>
                Please enable Javascript if you wish to use this site.
            </div>
        </noscript>
        <div class="row">
            <div class="col-lg-3">
            
                <div class="card bg-dark">
                    <div class="card-header">
                        <h3>List of Fleets</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group" id="fleetList">
                        
                        </div>
                    </div>
                </div>
                            
            </div>
            <div class="col-lg-9">
                <div class="row" id="headerRow" hidden>
                    
                    <div class="col-xl-4">

                        <div class="card bg-dark">
                            <div class="card-header">
                                <h3>Fleet Overview</h3>
                            </div>
                            <div class="card-body" id="fleetOverview">
                            
                            </div>
                        </div>

                    </div>
                    
                    <div class="col-xl-4">
                    
                        <br>
                    
                    </div>
                    <div class="col-xl-4">
                    
                        <div class="card bg-dark">
                            <div class="card-header">
                                <h3>Fleet Timetable</h3>
                            </div>
                            <div class="card-body" id="fleetTimes">
                            
                            </div>
                        </div>            
                    
                    </div>
                </div>
                
                <br>

                <div class="row" id="snapshotRow" hidden>
                                
                    <div class="col-xl-2">
                    
                    </div>
                    
                    <div class="col-xl-8">
                        
                        <style>div.google-visualization-tooltip { height:278px; width:225px; font-size:10px; top:86px !important;right:0px !important; overflow-y: scroll;}</style>
                        <div id="snapshotContainer">
                        
                        </div>
                        
                    </div>
                        
                    <div class="col-xl-2">
                        

                    </div>

                </div>
                
                <br>
                
                <div class="row" id="affiliationRow" hidden>
                
                    <div class="col-xl-12">

                        <div class="card bg-dark">
                            <div class="card-header">
                                <h3>Fleet Affiliation Breakdown</h3>
                                <small>Click an alliance to see its corporations</small>
                            </div>
                            <div class="card-body">
                            
                                <div class="list-group" id="fleetAffiliations">
                                
                                
                                
                                </div>
                            </div>
                        </div>

                    </div>
                
                </div>
                
                <br>
                
                <div class="row" id="breakdownRow" hidden>
                    
                    <div class="col-xl-12">
                        
                        <div class="card bg-dark">
                            <div class="card-header">
                                <h3>Fleet Members</h3>
                                <small>Click rows for individual stats</small>
                            </div>
                            <div class="card-body">
                            
                                <div class="list-group" id="fleetMembers">
                                    <div class="card-header card text-white bg-secondary p-1 mt-1">
                                        <div class="row ml-2">
                                            <div class="col-3">
                                                <strong>Name</strong>
                                            </div>
                                            <div class="col-3">
                                                <strong>Alliance</strong>
                                            </div>
                                            <div class="col-3">
                                                <strong>Corporation</strong>
                                            </div>
                                            <div class="col-2">
                                                <strong>Join Time</strong>
                                            </div>
                                            <div class="col-1">
                                                <strong>Alerts</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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