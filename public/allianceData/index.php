<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

    $PageMinimumAccessLevel = ["Super Admin", "HR"];
	checkLastPage();
	$_SESSION["CurrentPage"] = "Alliance PAP";

	checkCookies();
    
    determineAccess($_SESSION["AccessRoles"], $PageMinimumAccessLevel);
	
	$characterImageLink = "https://images.evetech.net/characters/" . $_SESSION["CharacterID"] . "/portrait";
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Alliance Participation</title>
	<link rel="stylesheet" href="../resources/stylesheets/styleMasterSheet.css">
	<link rel="icon" href="../resources/images/favicon.ico">
	
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../resources/bootstrap/css/bootstrap.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	<script src="../resources/bootstrap/js/bootstrap.bundle.js"></script>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>var pageName = "Alliance Data";</script>
    <script src="../resources/js/paps.js"></script>
	
	<meta property="og:title" content="Alliance Participation">
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
				<h2 class="ColumnHeader">Alliance Breakdown</h2>
				<hr>
                <div class="small text-right text-white font-italic">Click an alliance to view its corporations and generate an overview graph.</div>
                <div class="list-group text-left">
                    <div class="list-group-item list-group-item-dark bg-dark border-secondary text-white p-1 mt-1">
                        <div class="row ml-0 mr-0">
                            <div class="col-1 p-1 align-self-center">
                            </div>
                            <div class="col-3 p-1 align-self-center">
                                <strong>Alliance Name</strong>
                            </div>
                            <div class="col-1 p-1 align-self-center">
                                <strong>Active</strong>
                            </div>
                            <div class="col-1 p-1 align-self-center">
                                <strong>Known</strong>
                            </div>
                            <div class="col-2 p-1 align-self-center">
                                <strong>Recent PAPs ÷ Active</strong>
                            </div>
                            <div class="col-2 p-1 align-self-center">
                                <strong>Recent PAPs ÷ Known</strong>
                            </div>
                            <div class="col-2 p-1 align-self-center">
                                <strong>PAPs (Recent)</strong>
                            </div>
                        </div>
                    </div>
                    <div id="affiliation-data">
                    
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
				<br>
				<h2 class="ColumnHeader">Alliance Overview</h2>
				<hr>
                
                <div id="affiliation-overview">
                
                </div>
            </div>
        </div>
        
	</div>
</body>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/footer.php"; ?>

</html>