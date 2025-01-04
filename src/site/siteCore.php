<nav class="navbar navbar-expand-xl bg-dark navbar-dark sticky-top">
    
    <a class="navbar-brand" href="/">Eve Overseer</a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
    
        <ul class="navbar-nav">
        
        <?php 
        
        $pageList = ["Personal Stats" => ["Required Roles" => ["Super Admin", "HR", "Fleet Commander", "All"], "Link" => "/personalStats/", "Need FC" => false], "Fleet Stats" => ["Required Roles" => ["Super Admin", "Fleet Commander"], "Link" => "/fleetStats/", "Need FC" => false], "Host Fleet" => ["Required Roles" => ["Super Admin", "Fleet Commander"], "Link" => "/hostFleet/", "Need FC" => true], "Player PAP" => ["Required Roles" => ["Super Admin", "HR", "CEO"], "Link" => "/playerData/", "Need FC" => false], "Alliance PAP" => ["Required Roles" => ["Super Admin", "HR", "CEO"], "Link" => "/allianceData/", "Need FC" => false], "Admin" => ["Required Roles" => ["Super Admin"], "Link" => "/admin/", "Need FC" => false], "Site Logs" => ["Required Roles" => ["Super Admin"], "Link" => "/logView/", "Need FC" => false]];
        
        if ($_SESSION["AccessRoles"] != ["None"] and $_SESSION["AccessRoles"] != []) {
            
            foreach ($pageList as $pageTitle => $pageInfo) {
                
                foreach ($pageInfo["Required Roles"] as $throwaway => $roles) {
                    
                    if (in_array($roles, $_SESSION["AccessRoles"])) {
                        
                        if (($pageInfo["Need FC"] === true and $_SESSION["LoginType"] == "FC") or ($pageInfo["Need FC"] === false)) {
                            if ($_SESSION["CurrentPage"] == $pageTitle) {
                        
                                echo "
                                <li class='nav-item'>
                                    <a class='nav-link active' href='" . $pageInfo["Link"] . "'>" . $pageTitle . "</a>
                                </li>
                                ";
                            
                            }
                            else {
                                
                                echo "
                                <li class='nav-item'>
                                    <a class='nav-link' href='" . $pageInfo["Link"] . "'>" . $pageTitle . "</a>
                                </li>
                                ";
                                
                            }
                            
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
                
                $commanderquerystring = http_build_query([
                    "response_type" => "code", 
                    "redirect_uri" => $clientredirect, 
                    "client_id" => $clientid, 
                    "scope" => $clientscopes, 
                    "state" => $_SESSION["UniqueState"]
                ]);
                
                $statsquerystring = http_build_query([
                    "response_type" => "code", 
                    "redirect_uri" => $clientredirect, 
                    "client_id" => $clientid, 
                    "scope" => "", 
                    "state" => $_SESSION["UniqueState"]
                ]);
                
            ?>
            
            <?php if ($_SESSION["AccessRoles"] == ["None"]) : ?>
            
            <li class="nav-item mt-2 mr-2" style="text-align: center;">
                <strong>Fleet Commander Login</strong>
                <a href="https://login.eveonline.com/v2/oauth/authorize/?<?php echo $commanderquerystring; ?>">
                    <img class="LoginImage" src="../../resources/images/sso_image.png">
                </a>        
            </li>
            
            
            
            <li class="nav-item mt-2 mr-2" style="text-align: center;">
                <strong>View Statistics</strong>
                <a href="https://login.eveonline.com/v2/oauth/authorize/?<?php echo $statsquerystring; ?>">
                    <img class="LoginImage" src="../../resources/images/sso_image.png">
                </a>
            </li>
                
            <?php else : ?>
            
                <li class="nav-item mr-2">
                
                    <?php 
                        if ($_SESSION["CharacterID"] != 0) {
                            
                            if ($_SESSION["LoginType"] == "FC") {
                                
                                echo "
                                <div class='h4 mt-2 mr-3'><img class='login-type-icon' src='/resources/images/octicons/broadcast-24.svg'><strong> " . $_SESSION["Character Name"] . "</strong></div>";
                                
                            }
                            else {
                                
                                echo "
                                <div class='h4 mt-2 mr-3'><img class='login-type-icon' src='/resources/images/octicons/person-24.svg'><strong> " . $_SESSION["Character Name"] . "</strong></div>";
                                
                            }
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
                    <a href="/eveauth/logout.php?callback=<?php echo $_SERVER["REQUEST_URI"] ?>" class="btn btn-outline-danger" role="button">Logout</a>
                </li>
                
            <?php endif; ?>
            
        </ul>
    </div>
</nav>