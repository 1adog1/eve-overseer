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
	
	$characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Eve Overseer</title>
	<link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
	<link rel="icon" href="../resources/images/favicon.ico">
	
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	<script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>
    
    <script src="../resources/js/paps.js"></script>
	
	<meta property="og:title" content="Eve Overseer">
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
			<div class="col-xl-3">
				<br>
				<h2 class="ColumnHeader">Filter Players</h2>
				<hr>
                
                <div class="form-group">
                    <label for="CharacterName">Character Name</label>
                    <input type="text" name="CharacterName" class="form-control" id="CharacterName">
                </div>
                
                <div class="form-group">
                    <label for="attended">Minimum Attended (30 Days)</label>
                    <input type="text" name="attended" class="form-control" id="attended">
                </div>
                
                <div class="form-group">
                    <label for="commanded">Minimum Commanded (30 Days)</label>
                    <input type="text" name="commanded" class="form-control" id="commanded">
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="core" value="true" id="core"> 
                        <label class="custom-control-label" for="core">Core Only</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="fc" value="true" id="fc"> 
                        <label class="custom-control-label" for="fc">FC Only</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="all" value="true" id="all"> 
                        <label class="custom-control-label" for="all">Show All</label>
                    </div>
                </div>

                <input id="start_filter" class="btn btn-dark btn-large btn-block" type="submit" value="Filter">
			
			</div>
			<div class="col-xl-9">
				<br>
				<h2 class="ColumnHeader">Player Data</h2>
				<hr>
                
                <div class="list-group text-center">
                    <div class="card-header card text-white bg-secondary p-1 mt-1">
                        <div class="row ml-2">
                            <div class="col-2">
                                <strong>Name</strong>
                            </div>
                            <div class="col-2">
                                <strong>Attended (30 Days)</strong>
                            </div>
                            <div class="col-2">
                                <strong>Commanded (30 Days)</strong>
                            </div>
                            <div class="col-2">
                                <strong>Last Fleet Attended</strong>
                            </div>
                            <div class="col-1">
                                <strong>Core?</strong>
                            </div>
                            <div class="col-1">
                                <strong>FC?</strong>
                            </div>
                            <div class="col-2">
                            </div>
                        </div>
                    </div>
                    <div id="player-data">
                    
                    </div>
                </div>
                
			</div>
		</div>

	</div>
</body>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/footer.php"; ?>

</html>