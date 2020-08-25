<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Site Administration";

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
	$characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Administration</title>
	<link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
	<link rel="icon" href="../resources/images/favicon.ico">
	
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	<script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="../resources/js/admin.js"></script>
	
	<meta property="og:title" content="Site Administration">
	<meta property="og:description" content="The Overseer Website">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo $siteURL; ?>">

</head>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/siteCore.php"; ?>

<body class="background">
	<div class="container-fluid">
        
        <noscript>
            <div class="alert alert-danger text-center">
                It seems you don't have Javascript enabled. Almost every feature of this site requires Javascript to function. 
                <br>
                <br>
                Please enable Javascript if you wish to use this site.
            </div>
        </noscript>
        
        <div class="row">
        
            <div class="col-xl-8">
				<br>
				<h2 class="ColumnHeader">Ongoing Fleets</h2>
				<hr>
                
                <div class="list-group text-left">
                    <div class="list-group-item list-group-item-dark bg-dark border-secondary text-white p-1 mt-1 small">
                        <div class="row ml-2 mr-2">
                            <div class="col-3 align-self-center">
                                <strong>Fleet Name</strong>
                            </div>
                            <div class="col-2 align-self-center">
                                <strong>Commander</strong>
                            </div>
                            <div class="col-2 align-self-center">
                                <strong>Started</strong>
                            </div>
                            <div class="col-2 align-self-center">
                                <strong>Time Elapsed</strong>
                            </div>
                            <div class="col-2 align-self-center">
                                <strong>Snapshots (Missing)</strong>
                            </div>
                            <div class="col-1 text-center align-self-center">
                                <span style="height: 16px; width: 16px;" id="fleets-status-indicator"></span>
                            </div>
                        </div>
                    </div>
                    <div id="ongoing-fleets">
                    
                    </div>
                </div>
                
            </div>
            <div class="col-xl-4">
				<br>
				<h2 class="ColumnHeader">Core Group Permissions</h2>
				<hr>
                <div class="list-group text-left">
                    <div class="list-group-item list-group-item-dark bg-dark border-secondary text-white p-1 mt-1">
                        <div class="row ml-2 mr-2">
                            <div class="col-8 mt-2 p-0 h5 align-self-center">
                                <strong>Core Group</strong>
                            </div>
                            <div class="col-3 mt-2 p-0 h5 align-self-center">
                                <strong>Roles</strong>
                            </div>
                            <div class="col-1 mt-1 p-0 text-center align-self-center">
                                <span style="height: 22px; width: 22px;" id="roles-status-indicator"></span>
                            </div>
                        </div>
                    </div>
                    <div id="core-groups">
                    
                    </div>
                </div>
            </div>        
        </div>
        
	</div>
</body>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/footer.php"; ?>

</html>