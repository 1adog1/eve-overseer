<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "Fleet Commander", "All"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Personal Stats";

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
	$characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Personal Stats</title>
	<link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
	<link rel="icon" href="../resources/images/favicon.ico">
	
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	<script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="../resources/js/personalStats.js"></script>
	
	<meta property="og:title" content="Personal Stats">
	<meta property="og:description" content="The Overseer Website">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo $siteURL; ?>">

</head>
<style>

    .status-image {
        filter: invert(100%) sepia(100%) saturate(0%) hue-rotate(0deg) brightness(100%) contrast(100%);
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

		<div class="row" id="headerRow">
            
            <div class="col-xl-4 mb-4">

				<div class="card bg-dark">
					<div class="card-header">
                        <h3>Character Overview</h3>
					</div>
                    <div class="card-body" id="characterOverview">
                    
                    </div>
				</div>

            </div>
            
            <div class="col-xl-4 mb-4">
				<div class="card bg-dark">
					<div class="card-header text-center">
                    
                        <h3>Time Period</h3>

                    </div>
                    <div class="card-body">
                        <div class="row">
                            
                            <div class="col-10">
                            
                                <ul class="nav nav-pills nav-fill">
                                    <li class="nav-item border-left border-right border-secondary rounded mr-1">
                                        <a class="time_nav nav-link" id="time_week" href="#">Week</a>
                                    </li>
                                    <li class="nav-item border-left border-right border-secondary rounded mr-1">
                                        <a class="time_nav nav-link" id="time_month" href="#">Month</a>
                                    </li>
                                    <li class="nav-item border-left border-right border-secondary rounded mr-1">
                                        <a class="time_nav nav-link" id="time_year" href="#">Year</a>
                                    </li>
                                    <li class="nav-item border-left border-right border-secondary rounded mr-1">
                                        <a class="time_nav nav-link" id="time_all" href="#">All Time</a>
                                    </li>
                                </ul>
                                
                            </div>
                            <div class="col-2">
                                <div class="w-100 h-100 border border-secondary rounded text-center align-self-center align-middle">
                                    <span id="statusIndicator" class="spinner-border text-light mt-2" style="height: 22px; width: 22px;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
            </div>
            
            <div class="col-xl-4 mb-4">
            
				<div class="card bg-dark">
					<div class="card-header">

                        <h3>Fleet Overview</h3>

                    </div>
                    <div class="card-body" id="fleetOverview">
                    
                    </div>
				</div>

            </div>

		</div>
        
        <br>

		<div class="row" id="calendarRow" hidden>
                        
            <div class="col-xl-3">
            
            </div>
            
            <div class="col-xl-6">

				<div id="calendarContainer">
                
                </div>
                
            </div>
                
            <div class="col-xl-3">
                

            </div>

		</div>
        
        <br>
        
		<div class="row" id="breakdownRow" hidden>
            
            <div class="col-xl-4">
                
                <div class="card bg-dark">
                    <div id="shipContainer">
                    
                    </div>
                </div>
                
                <br>

            </div>

            <div class="col-xl-4">
                
                <div class="card bg-dark p-3">
                
                    <div id="timezoneContainer">
                    
                    </div>
                </div>
                
                <br>
                
                <div class="card bg-dark p-3">
                    <div id="rolesContainer">
                    
                    </div>
                </div>
                
                <br>
                
            </div>
            
            <div class="col-xl-4">
                
				<div class="card bg-dark">
					<div class="card-header">
                        <h3>Attended Fleets</h3>
                        Click Rows For Personalized Stats
					</div>
                    <div class="card-body" style="max-height: 600px; overflow: auto;">
                    
                        <div class="list-group">
                            <div class="card-header card text-white bg-secondary p-1 mt-1">
                                <div class="row ml-2">
                                    <div class="col-5">
                                        <strong>Fleet Name</strong>
                                    </div>
                                    <div class="col-4">
                                        <strong>Start Date</strong>
                                    </div>
                                    <div class="col-3">
                                        <strong>Time In Fleet</strong>
                                    </div>                                    
                                </div>
                            </div>
                            <div id="previousFleets">
                            
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