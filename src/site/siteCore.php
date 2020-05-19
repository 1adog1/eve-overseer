<nav class="navbar navbar-expand-xl bg-dark navbar-dark sticky-top">
	
    <a class="navbar-brand" href="/">Eve Overseer</a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
    
		<ul class="navbar-nav">
        
		<?php 
		
		$pageList = ["Personal Stats" => ["Required Roles" => ["Super Admin", "HR", "Fleet Commander", "All"], "Link" => "/personalStats/", "Need FC" => false], "Fleet Stats" => ["Required Roles" => ["Super Admin", "HR", "Fleet Commander"], "Link" => "/fleetStats/", "Need FC" => false], "Host Fleet" => ["Required Roles" => ["Super Admin", "Fleet Commander"], "Link" => "/hostFleet/", "Need FC" => true], "Player PAP" => ["Required Roles" => ["Super Admin", "HR"], "Link" => "/playerData/", "Need FC" => false], "Alliance PAP" => ["Required Roles" => ["Super Admin", "HR"], "Link" => "/allianceData/", "Need FC" => false], "Site Logs" => ["Required Roles" => ["Super Admin"], "Link" => "/logView/", "Need FC" => false]];
		
		if ($_SESSION["AccessRoles"] != ["None"] and $_SESSION["AccessRoles"] != []) {
			
			foreach ($pageList as $pageTitle => $pageInfo) {
				
				foreach ($pageInfo["Required Roles"] as $throwaway => $roles) {
					
					if (in_array($roles, $_SESSION["AccessRoles"])) {
                        
                        if (($pageInfo["Need FC"] === true and $_SESSION["LoginType"] == "FC") or ($pageInfo["Need FC"] === false)) {
						
                            echo "
                            <li class='nav-item'>
                                <a class='nav-link' href='" . $pageInfo["Link"] . "'>" . $pageTitle . "</a>
                            </li>
                            ";
                            
                            break;
                            
						}
                        
					}
					
				}
				
			}
			
		}
		
		?>
		
		
        </ul>
    
        <ul class="navbar-nav ml-auto">
        
            <?php
            
                $bytes = random_bytes(8);
                $_SESSION["UniqueState"] = bin2hex($bytes);
                
            ?>
            
            <?php if ($_SESSION["AccessRoles"] == ["None"]) : ?>
            
            <li class="nav-item mt-2 mr-2" style="text-align: center;">
                <strong>Fleet Commander Login</strong>
                <a href="https://login.eveonline.com/oauth/authorize?response_type=code&redirect_uri=<?php echo $clientredirect; ?>&client_id=<?php echo $clientid; ?>&scope=<?php echo $clientscopes; ?>&state=<?php echo $_SESSION["UniqueState"]; ?>">
                    <img class="LoginImage" src="../../resources/images/sso_image.png">
                </a>		
            </li>
            
            
            
            <li class="nav-item mt-2 mr-2" style="text-align: center;">
                <strong>View Statistics</strong>
                <a href="https://login.eveonline.com/oauth/authorize?response_type=code&redirect_uri=<?php echo $clientredirect; ?>&client_id=<?php echo $clientid; ?>&scope=&state=<?php echo $_SESSION["UniqueState"]; ?>">
                    <img class="LoginImage" src="../../resources/images/sso_image.png">
                </a>
            </li>
                
            <?php else : ?>
            
                <li class="nav-item mr-2">
                
                    <?php 
                        if ($_SESSION["CharacterID"] != 0) {

                            echo "
                            <div class='h4 mt-2 mr-3'><strong>[" . $_SESSION["LoginType"] . "] " . $_SESSION["Character Name"] . "</strong></div>";
                        }
                    ?>				
                
                </li>
            
                <li class="nav-item mr-3">
                    <?php 
                        if ($_SESSION["CharacterID"] != 0) {

                            echo "
                            <strong>Corporation: </strong>" . $_SESSION["Corporation Name"] . "<br>
                            <strong>Alliance: </strong>" . $_SESSION["Alliance Name"]
                            ;
                        }
                    ?>
                </li>

                <li class="nav-item mt-2">
                    <a href="/eveauth/logout?callback=<?php echo $_SERVER["REQUEST_URI"] ?>" class="btn btn-outline-danger" role="button">Logout</a>
                </li>
                
            <?php endif; ?>
            
        </ul>
    </div>
</nav>