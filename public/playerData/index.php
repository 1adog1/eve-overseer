<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Player PAP";

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
	$characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Player Participation</title>
	<link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
	<link rel="icon" href="../resources/images/favicon.ico">
	
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	<script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>var pageName = "Player Data";</script>
    <script src="../resources/js/paps.js"></script>
	
	<meta property="og:title" content="Player Participation">
	<meta property="og:description" content="The Overseer Website">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo $siteURL; ?>">

</head>
<style>

    .sorting {
        filter: invert(100%) sepia(100%) saturate(0%) hue-rotate(0deg) brightness(100%) contrast(100%);
    }
    
    .tooltip-inner {
        text-align: left;
    }
    
    href {
        color: inherit;
    }
    
</style>

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
                
                <form action="download" method="post" target="_blank">
                    <div class="form-group">
                        <label for="CharacterName">Account Name</label>
                        <input type="text" name="CharacterName" class="form-control" id="CharacterName">
                    </div>
                    
                    <div class="form-group">
                        <label for="AllianceName">Alliance</label>
                        <select class="form-control" name="AllianceName" id="AllianceName">
                        
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="CorporationName">Corporation</label>
                        <select class="form-control" name="CorporationName" id="CorporationName">
                        
                        </select>
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
                    
                    <input type="hidden" id="exportTimes" name="exportTimes" value="false">
                    
                    <input type="submit" class="btn btn-outline-danger btn-large btn-block" value="Download Data as CSV">
                </form>
                
                <br>
                
                <button id="start_filter" class="btn btn-outline-success btn-large btn-block">Filter Data</button>
			
			</div>
			<div class="col-xl-9">
				<br>
				<h2 class="ColumnHeader">Player Data</h2>
				<hr>
                <div class="small text-right text-white font-weight-bold font-italic" id="counting_container">0 Account(s) Loaded</div>
                <div class="small text-right text-warning font-weight-bold font-italic" id="failing_container">0 Account(s) Failing Minimums</div>
                <div class="list-group text-left">
                    <div class="list-group-item list-group-item-dark bg-dark border-secondary text-white p-1 mt-1 small">
                        <div class="row ml-2">
                            <div class="col-2 align-self-center">
                                <div class="row">
                                    <div class="col-1 p-0">
                                        <a href="#" class="img-fluid sorting" data-sort-by="Name" data-sort-order="Ascending"><img src="/resources/images/octicons/triangle-up-16.svg"></a>
                                        <br>
                                        <a href="#" class="img-fluid sorting" data-sort-by="Name" data-sort-order="Descending"><img src="/resources/images/octicons/triangle-down-16.svg"></a>
                                    </div>
                                    <div class="col-10 p-0 align-self-center">
                                        <strong>Name</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 align-self-center">
                                <div class="row">
                                    <div class="col-2 p-0">
                                        <a href="#" class="img-fluid sorting" data-sort-by="RecentAttended" data-sort-order="Ascending"><img src="/resources/images/octicons/triangle-up-16.svg"></a>
                                        <br>
                                        <a href="#" class="img-fluid sorting" data-sort-by="RecentAttended" data-sort-order="Descending"><img src="/resources/images/octicons/triangle-down-16.svg"></a>
                                    </div>
                                    <div class="col-10 align-self-center p-0">
                                        <strong>Attended (Recent)</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 align-self-center">
                                <div class="row">
                                    <div class="col-2 p-0">
                                        <a href="#" class="img-fluid sorting" data-sort-by="Attended" data-sort-order="Ascending"><img src="/resources/images/octicons/triangle-up-16.svg"></a>
                                        <br>
                                        <a href="#" class="img-fluid sorting" data-sort-by="Attended" data-sort-order="Descending"><img src="/resources/images/octicons/triangle-down-16.svg"></a>
                                    </div>
                                    <div class="col-10 align-self-center p-0">
                                        <strong>Attended</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 align-self-center">
                                <div class="row">
                                    <div class="col-2 p-0">
                                        <a href="#" class="img-fluid sorting" data-sort-by="RecentLed" data-sort-order="Ascending"><img src="/resources/images/octicons/triangle-up-16.svg"></a>
                                        <br>
                                        <a href="#" class="img-fluid sorting" data-sort-by="RecentLed" data-sort-order="Descending"><img src="/resources/images/octicons/triangle-down-16.svg"></a>
                                    </div>
                                    <div class="col-10 align-self-center p-0">
                                        <strong>Led (Recent)</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 align-self-center">
                                <div class="row">
                                    <div class="col-2 p-0">
                                        <a href="#" class="img-fluid sorting" data-sort-by="Led" data-sort-order="Ascending"><img src="/resources/images/octicons/triangle-up-16.svg"></a>
                                        <br>
                                        <a href="#" class="img-fluid sorting" data-sort-by="Led" data-sort-order="Descending"><img src="/resources/images/octicons/triangle-down-16.svg"></a>
                                    </div>
                                    <div class="col-10 align-self-center p-0">
                                        <strong>Led</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-2 align-self-center">
                                <div class="row">
                                    <div class="col-1 p-0">
                                        <a href="#" class="img-fluid sorting" data-sort-by="LastActive" data-sort-order="Ascending"><img src="/resources/images/octicons/triangle-up-16.svg"></a>
                                        <br>
                                        <a href="#" class="img-fluid sorting" data-sort-by="LastActive" data-sort-order="Descending"><img src="/resources/images/octicons/triangle-down-16.svg"></a>
                                    </div>
                                    <div class="col-10 align-self-center p-0">
                                        <strong>Last Active</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 align-self-center">
                                <strong>Core?</strong>
                            </div>
                            <div class="col-1 align-self-center">
                                <strong>FC?</strong>
                            </div>
                            <div class="col-2 align-self-center">
                                <div class="custom-control custom-switch float-right mr-2">
                                    <input type="checkbox" class="custom-control-input" id="use_times">
                                    <label class="custom-control-label" for="use_times"><strong>Show Times Instead</strong></label>
                                </div>
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