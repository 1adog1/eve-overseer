<?php
	session_start();

	require $_SERVER['DOCUMENT_ROOT'] . "/../src/auth/accessControl.php";
	
	configureErrorChecking();

	require $_SERVER['DOCUMENT_ROOT'] . "/../config/config.php";
	
	checkForErrors();

	checkLastPage();
	$_SESSION["CurrentPage"] = "Home";

	checkCookies();
	
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
	
	<meta property="og:title" content="Eve Overseer">
	<meta property="og:description" content="The Overseer Website">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo $siteURL; ?>">

</head>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/siteCore.php"; ?>

<body class="background">
	<div class="container-fluid">
		<br>
		<br>
		<div class="row">
			<div class="col-xl-3">			
			</div>
			<div class="col-xl-6">	
			
				<div class="card bg-dark">
					<div class="card-header">
						<div class="display-4 text-center">Welcome to Eve Overseer!</div>
					</div>
                                        
                    <div class="card-body">
                        <div class="text-center">This app allows for the tracking of fleet activity and fleet members.</div>
                        <br>
                        <div class="text-center">To view previous fleet activity or your personal stats, you can login through the <strong>View Statistics</strong> option.</div>
                        <div class="text-center">To host a fleet, login via the <strong>Fleet Commander Login</strong> option.</div>
                    </div>
                    
				</div>
			</div>
			<div class="col-xl-3">
			</div>
		</div>
	</div>
</body>

<?php require $_SERVER['DOCUMENT_ROOT'] . "/../src/site/footer.php"; ?>

</html>