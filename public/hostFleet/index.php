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

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/siteCore.php"; ?>

<body class="background">
	<div class="container-fluid">
    
        <h1 class="text-center mt-3">Fleet Hosting</h1>
        <hr>
        
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
        <div class="row">
        
            <div id="name_selection" class="form-group col-xl-4">
            
                <label for="fleet_name">Fleet Name</label>
                <input type="text" class="form-control" id="fleet_name">
            
            </div>
            <div id="srp_selection" class="form-group col-xl-4">

                <label for="fleet_srp">SRP Level</label>
                <select class="form-control" id="fleet_srp">
                
                    <option value="Fun">Fun Fleet</option>
                    <option value="Stratop">Stratop</option>
                    <option value="CTA">CTA</option>
                    <option value="Save">Save Fleet</option>
                    <option value="ADM">ADM Fleet</option>
                
                </select>
            
            </div>
            <div class="form-group col-xl-4 mt-2">
            
                <label for="toggle_button_container"></label>
                <div id="toggle_button_container">
                
                </div>
                
            </div>
        
        </div>
        <br>
		<div class="row" id="overviewRow" hidden>
			<div class="col-xl-4">			
				<div class="card bg-dark">
					<div class="card-header" id="fleet_boss">
                        Fleet Boss: 
					</div>
				</div>
			</div>
			<div class="col-xl-4">			
				<div class="card bg-dark">
					<div class="card-header" id="member_count">
                        Fleet Members: 
					</div>
				</div>
			</div>
			<div class="col-xl-4">			
				<div class="card bg-dark">
					<div class="card-header" id="fleet_started">
                        Fleet Started: 
					</div>
				</div>
			</div>
		</div>
		<div class="row" id="detailRow" hidden>
			<div class="col-xl-3">
				<div class="card bg-dark mt-4">
					<div class="card-header">
                        Ship Breakdown
					</div>
					<div class="card-body">
                        <ul class="list-group" id="ship_breakdown">
                        
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
                        <ul class="list-group" id="affiliation_breakdown">
                        
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
                        <ul class="list-group" id="fleet_overview">
                        
                        </ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/footer.php"; ?>

</html>