jQuery(document).ready(function () {
    
    errorCounter = 0;
    sharedCounter = 0;
    sharedIDs = {};
    
    populateData();
    setInterval(populateData, 10000);
    
    $("#fleetTabs a").on("click", function (data) {
        data.preventDefault();
        if (!$(this).hasClass("new-shared")) {
            $(this).tab("show");
        }
    });
    
    $(document).on("click", ".close-tab", function (data) {
        
        tabToClose = $(this).attr("data-tab-id");
        
        if ($("#pane-" + tabToClose).hasClass("active")) {
            $("#main-tab").tab("show");
            $("#main-pane").addClass("active show");
        }
        
        $("#tab-" + tabToClose).remove();
        $("#pane-" + tabToClose).remove();
        
        for (eachID in sharedIDs) {
            if (sharedIDs[eachID] == tabToClose) {
                delete sharedIDs[eachID];
            }
        }
        
    });
    
    $(".new-shared").on("click", function (data) {
        $("#new-share-button").before(
            $("<li/>")
                .addClass("nav-item")
                .attr("role", "presentation")
                .append(
                    $("<a/>")
                        .addClass("nav-link")
                        .attr("id", "tab-" + sharedCounter)
                        .attr("data-toggle", "tab")
                        .attr("role", "tab")
                        .attr("href", "#pane-" + sharedCounter)
                        .attr("aria-controls", "pane-" + sharedCounter)
                        .attr("aria-selected", "false")
                        .append(
                            $("<span/>")
                                .attr("id", "tab-" + sharedCounter + "-name")
                                .text("New Shared Fleet")
                        )
                        .append(
                            $("<img/>")
                                .addClass("close-tab ml-2")
                                .attr("data-tab-id", sharedCounter)
                                .attr("src", "../resources/images/octicons/x-24.svg")
                        )
                )
        );
        
        newTab();
        
    });
    
});

function startMonitoring() {
    $.ajax({
        url: "monitoringController.php",
        type: "POST",
        mimeType: "application/json",
        dataType: 'json',
        data: {"Action": "Start", "Name": $("#fleet_name").val(), "SRP": $("#fleet_srp").val(), "Voltron": $('#Voltron').prop('checked'), "Sharing": $('#Sharing').prop('checked')},
       
        success: function(result){
           
            if ("Error" in result) {
                                
                $("#errorContainer").append(
                    $("<div/>")
                        .addClass("alert alert-danger text-center")
                        .attr("id", "anError_" + errorCounter)
                        .text(result["Error"])
                );
                
                $("#anError_" + errorCounter).delay(5000).fadeOut(1000);
                errorCounter++;
                
            }
           
            populateData();
       }
    });
}

function stopMonitoring() {
    var confirmation = confirm("After you've stopped monitoring for a fleet, you won't be able to restart it. Are you sure you want to proceed?");
    
    if (confirmation) {
        $.ajax({
            url: "monitoringController.php",
            type: "POST",
            mimeType: "application/json",
            dataType: 'json',        
            data: {"Action": "Stop"},
           
            success: function(result){
               
                if ("Error" in result) {
                                    
                    $("#errorContainer").append(
                        $("<div/>")
                            .addClass("alert alert-danger text-center")
                            .attr("id", "anError_" + errorCounter)
                            .text(result["Error"])
                    );
                    
                    $("#anError_" + errorCounter).delay(5000).fadeOut(1000);
                    errorCounter++;
                    
                }           
               
                populateData();
           }
        });
    }
}

function subscribeToShared(subTab) {
    
    $("#sub_button_" + subTab).prop("disabled", true);
    $("#share_key_" + subTab).prop("disabled", true);
    
    shareKey = $("#share_key_" + subTab).val();
    
    $.ajax({
        url: "monitoringController.php",
        type: "POST",
        mimeType: "application/json",
        dataType: 'json',        
        data: {"Action": "Subscribe", "Key": shareKey},
       
        success: function(result){
            
            if (result["Status"] == "Data Found") {
                
                if (!(shareKey in sharedIDs)) {
                    
                    sharedIDs[shareKey] = subTab;
                    $("#tab-" + subTab + "-name").text(result["Fleet Name"]);
                    
                }
                else {
                    
                    $("#sub_button_" + subTab).prop("disabled", false);
                    $("#share_key_" + subTab).prop("disabled", false);
                    
                    $("#errorContainer").append(
                        $("<div/>")
                            .addClass("alert alert-danger text-center")
                            .attr("id", "anError_" + errorCounter)
                            .text("Shared fleet is already being tracked in another tab.")
                    );
                    
                    $("#anError_" + errorCounter).delay(5000).fadeOut(1000);
                    errorCounter++;
                    
                }
            }
           
            if ("Error" in result) {
                
                $("#sub_button_" + subTab).prop("disabled", false);
                $("#share_key_" + subTab).prop("disabled", false);
                                
                $("#errorContainer").append(
                    $("<div/>")
                        .addClass("alert alert-danger text-center")
                        .attr("id", "anError_" + errorCounter)
                        .text(result["Error"])
                );
                
                $("#anError_" + errorCounter).delay(5000).fadeOut(1000);
                errorCounter++;
                
            }
           
            populateData();
       }
    });
    
}

function newTab() {

    const tabTemplate = ({tabID}) => `
        <div class="tab-pane fade" id="pane-${tabID}" role="tabpanel" aria-labelledby="tab-${tabID}">
            <div class="row">
            
                <div id="share_input_${tabID}" class="form-group col-xl-4">
                    <label for="share_key_${tabID}">Share Key</label>
                    <input type="text" class="form-control" id="share_key_${tabID}">
                </div>
                
                <div class="form-group col-xl-2 mt-2">
                    <button class="btn btn-block btn-outline-success mt-4" id="sub_button_${tabID}" onClick="subscribeToShared(${tabID});">Subscribe to Fleet</button>
                </div>
            
            </div>
            <br>
            <div class="row" id="overviewRow_${tabID}" hidden>
                <div class="col-xl-4">			
                    <div class="card bg-dark">
                        <div class="card-header" id="fleet_boss_${tabID}">
                            Fleet Boss: 
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">			
                    <div class="card bg-dark">
                        <div class="card-header" id="member_count_${tabID}">
                            Fleet Members: 
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">			
                    <div class="card bg-dark">
                        <div class="card-header" id="fleet_started_${tabID}">
                            Fleet Started: 
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="detailRow_${tabID}" hidden>
                <div class="col-xl-3">
                    <div class="card bg-dark mt-4">
                        <div class="card-header row">
                            <div class="col-xl-6">
                            Ship Breakdown
                            </div>
                            <div class="form-group col-xl-6">
                            
                                <div class="custom-control custom-switch align-self-center float-right">
                                    <input type="checkbox" class="custom-control-input" id="trash_filter_${tabID}" value="true">
                                    <label class="custom-control-label" for="trash_filter_${tabID}"><small>Only Ships With FC</small></label>
                                </div>
                                
                            </div>
                            
                        </div>
                        <div class="card-body">
                            <ul class="list-group" id="ship_breakdown_${tabID}">
                            
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
                            <ul class="list-group" id="affiliation_breakdown_${tabID}">
                            
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
                            <ul class="list-group" id="fleet_overview_${tabID}">
                            
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $("#fleet-contents").append([
        { tabID: sharedCounter },
    ].map(tabTemplate).join(""));
    
    sharedCounter++;
    
}

function topRow(incomingData, startTime, currentTab = "main") {
    
    $("#fleet_boss_" + currentTab).empty();
    $("#fleet_boss_" + currentTab).append(
        $("<span/>")
            .text("Fleet Boss: ")
    );
    $("#fleet_boss_" + currentTab).append(    
        $("<a/>")
            .attr("href", "https://evewho.com/character/" + incomingData["Boss ID"])
            .text(incomingData["Boss Name"])
    );
    
    var fleetCount = 0;
    
    if (incomingData["Fleet"]["Has Commander"]) {
        fleetCount ++;
    }
    
    for (wingData of incomingData["Fleet"]["Wings"]){
        if (wingData["Has Commander"]) {
            fleetCount ++;
        }
        for (squadData of wingData["Squads"]) {
            if (squadData["Has Commander"]) {
                fleetCount ++;
            }
            for (memberData of squadData["Members"]) {
                fleetCount ++;
            }
        }
    }
    
    $("#member_count_" + currentTab).empty();
    $("#member_count_" + currentTab).append(
        $("<span/>")
            .text("Fleet Members: " + fleetCount)
    );
    $("#fleet_started_" + currentTab).empty();
    $("#fleet_started_" + currentTab).append(
        $("<span/>")
            .text("Fleet Started: " + startTime)
    );
    
    
}

function shipBreakdown(incomingData, currentTab = "main") {
    
    var filterTrash = $("#trash_filter_" + currentTab).is(':checked');
    
    var ships = {};
    
    if (incomingData["Fleet"]["Has Commander"]) {
        
        var bossSystem = incomingData["Fleet"]["System"];
        
        if (incomingData["Fleet"]["Ship Class ID"] in ships) {
            ships[incomingData["Fleet"]["Ship Class ID"]]["Count"]++;
        }
        else {
            ships[incomingData["Fleet"]["Ship Class ID"]] = {"Count": 1, "Name": incomingData["Fleet"]["Ship Class"], "Ships": {}};
        }
        
        if (incomingData["Fleet"]["Ship ID"] in ships[incomingData["Fleet"]["Ship Class ID"]]["Ships"]) {
            ships[incomingData["Fleet"]["Ship Class ID"]]["Ships"][incomingData["Fleet"]["Ship ID"]]["Count"]++;
        }
        else {
            ships[incomingData["Fleet"]["Ship Class ID"]]["Ships"][incomingData["Fleet"]["Ship ID"]] = {"Count": 1, "Name": incomingData["Fleet"]["Ship Name"]};
        }
        
    }
    
    for (wingData of incomingData["Fleet"]["Wings"]){
        if (wingData["Has Commander"]) {
            
            if (!filterTrash || !incomingData["Fleet"]["Has Commander"] || (filterTrash && incomingData["Fleet"]["Has Commander"] && wingData["System"] == bossSystem)) {
            
                if (wingData["Ship Class ID"] in ships) {
                    ships[wingData["Ship Class ID"]]["Count"]++;
                }
                else {
                    ships[wingData["Ship Class ID"]] = {"Count": 1, "Name": wingData["Ship Class"], "Ships": {}};
                }
                
                if (wingData["Ship ID"] in ships[wingData["Ship Class ID"]]["Ships"]) {
                    ships[wingData["Ship Class ID"]]["Ships"][wingData["Ship ID"]]["Count"]++;
                }
                else {
                    ships[wingData["Ship Class ID"]]["Ships"][wingData["Ship ID"]] = {"Count": 1, "Name": wingData["Ship Name"]};
                }
            
            }
            
        }
        for (squadData of wingData["Squads"]) {
            if (squadData["Has Commander"]) {
                
                if (!filterTrash || !incomingData["Fleet"]["Has Commander"] || (filterTrash && incomingData["Fleet"]["Has Commander"] && squadData["System"] == bossSystem)) {

                    if (squadData["Ship Class ID"] in ships) {
                        ships[squadData["Ship Class ID"]]["Count"]++;
                    }
                    else {
                        ships[squadData["Ship Class ID"]] = {"Count": 1, "Name": squadData["Ship Class"], "Ships": {}};
                    }

                    if (squadData["Ship ID"] in ships[squadData["Ship Class ID"]]["Ships"]) {
                        ships[squadData["Ship Class ID"]]["Ships"][squadData["Ship ID"]]["Count"]++;
                    }
                    else {
                        ships[squadData["Ship Class ID"]]["Ships"][squadData["Ship ID"]] = {"Count": 1, "Name": squadData["Ship Name"]};
                    }
                
                }

            }
            for (memberData of squadData["Members"]) {
                
                if (!filterTrash || !incomingData["Fleet"]["Has Commander"] || (filterTrash && incomingData["Fleet"]["Has Commander"] && memberData["System"] == bossSystem)) {

                    if (memberData["Ship Class ID"] in ships) {
                        ships[memberData["Ship Class ID"]]["Count"]++;
                    }
                    else {
                        ships[memberData["Ship Class ID"]] = {"Count": 1, "Name": memberData["Ship Class"], "Ships": {}};
                    }

                    if (memberData["Ship ID"] in ships[memberData["Ship Class ID"]]["Ships"]) {
                        ships[memberData["Ship Class ID"]]["Ships"][memberData["Ship ID"]]["Count"]++;
                    }
                    else {
                        ships[memberData["Ship Class ID"]]["Ships"][memberData["Ship ID"]] = {"Count": 1, "Name": memberData["Ship Name"]};
                    }
                
                }
                
            }
        }
    }
    
    var shipsSorted = [];
    for (eachClass in ships) {
        var subShips = [];
        
        for (eachShip in ships[eachClass]["Ships"]) {
            
            subShips.push({"Name": ships[eachClass]["Ships"][eachShip]["Name"], "ID": eachShip, "Count": ships[eachClass]["Ships"][eachShip]["Count"]})
            
        }
        
        shipsSorted.push({"Name": ships[eachClass]["Name"], "ID": eachClass, "Count": ships[eachClass]["Count"], "Ships": subShips})
        
    }
    
    var fullySortedShips = shipsSorted.slice(0);
    fullySortedShips.sort(function(a, b) {
        return b["Count"] - a["Count"];
    });
    
    $("#ship_breakdown_" + currentTab).empty();
    for (eachClass of fullySortedShips) {
        $("#ship_breakdown_" + currentTab).append(
            $("<li/>")
                .attr("id", "class_" + eachClass["ID"] + "_" + currentTab)
                .addClass("list-group-item list-group-item bg-secondary p-1 mt-1")
        );
        $("#class_" + eachClass["ID"] + "_" + currentTab).append(
            $("<span/>")
                .addClass("badge badge-dark ml-3")
                .text(eachClass["Count"])
        );
        $("#class_" + eachClass["ID"] + "_" + currentTab).append(
            $("<span/>")
                .addClass("font-weight-bold text-light ml-3")
                .text(eachClass["Name"])
        );
        
        for (eachShip of eachClass["Ships"]) {
            
            $("#ship_breakdown_" + currentTab).append(
                $("<li/>")
                    .attr("id", "ship_" + eachShip["ID"] + "_" + currentTab)
                    .addClass("list-group-item list-group-item bg-secondary p-1 ml-4 mt-1")
            );
            $("#ship_" + eachShip["ID"] + "_" + currentTab).append(
                $("<span/>")
                    .addClass("badge badge-dark ml-3")
                    .text(eachShip["Count"])
            );
            $("#ship_" + eachShip["ID"] + "_" + currentTab).append(
                $("<span/>")
                    .addClass("font-weight-bold text-light ml-3")
                    .text(eachShip["Name"])
            );
            
        }
        
    }
}

function affiliationBreakdown(incomingData, currentTab = "main") {
    
    var affilations = {};
    
    if (incomingData["Fleet"]["Has Commander"]) {
        if (affilations.hasOwnProperty(incomingData["Fleet"]["Alliance ID"])) {
            affilations[incomingData["Fleet"]["Alliance ID"]]["Count"]++;
        }
        else {
            affilations[incomingData["Fleet"]["Alliance ID"]] = {"Count": 1, "Name": incomingData["Fleet"]["Alliance Name"], "Corporations": {}};
        }
        if (affilations[incomingData["Fleet"]["Alliance ID"]]["Corporations"].hasOwnProperty(incomingData["Fleet"]["Corporation ID"])) {
            affilations[incomingData["Fleet"]["Alliance ID"]]["Corporations"][incomingData["Fleet"]["Corporation ID"]]["Count"]++;
        }
        else {
            affilations[incomingData["Fleet"]["Alliance ID"]]["Corporations"][incomingData["Fleet"]["Corporation ID"]] = {"Count": 1, "Name": incomingData["Fleet"]["Corporation Name"]};
        }
    }
    
    for (wingData of incomingData["Fleet"]["Wings"]){
        if (wingData["Has Commander"]) {
            
            if (affilations.hasOwnProperty(wingData["Alliance ID"])) {
                affilations[wingData["Alliance ID"]]["Count"]++;
            }
            else {
                affilations[wingData["Alliance ID"]] = {"Count": 1, "Name": wingData["Alliance Name"], "Corporations": {}};
            }
            if (affilations[wingData["Alliance ID"]]["Corporations"].hasOwnProperty(wingData["Corporation ID"])) {
                affilations[wingData["Alliance ID"]]["Corporations"][wingData["Corporation ID"]]["Count"]++;
            }
            else {
                affilations[wingData["Alliance ID"]]["Corporations"][wingData["Corporation ID"]] = {"Count": 1, "Name": wingData["Corporation Name"], "Corporations": {}};
            }
            
        }
        for (squadData of wingData["Squads"]) {
            if (squadData["Has Commander"]) {

                if (affilations.hasOwnProperty(squadData["Alliance ID"])) {
                    affilations[squadData["Alliance ID"]]["Count"]++;
                }
                else {
                    affilations[squadData["Alliance ID"]] = {"Count": 1, "Name": squadData["Alliance Name"], "Corporations": {}};
                }
                if (affilations[squadData["Alliance ID"]]["Corporations"].hasOwnProperty(squadData["Corporation ID"])) {
                    affilations[squadData["Alliance ID"]]["Corporations"][squadData["Corporation ID"]]["Count"]++;
                }
                else {
                    affilations[squadData["Alliance ID"]]["Corporations"][squadData["Corporation ID"]] = {"Count": 1, "Name": squadData["Corporation Name"], "Corporations": {}};
                }

            }
            for (memberData of squadData["Members"]) {

                if (affilations.hasOwnProperty(memberData["Alliance ID"])) {
                    affilations[memberData["Alliance ID"]]["Count"]++;
                }
                else {
                    affilations[memberData["Alliance ID"]] = {"Count": 1, "Name": memberData["Alliance Name"], "Corporations": {}};
                }
                if (affilations[memberData["Alliance ID"]]["Corporations"].hasOwnProperty(memberData["Corporation ID"])) {
                    affilations[memberData["Alliance ID"]]["Corporations"][memberData["Corporation ID"]]["Count"]++;
                }
                else {
                    affilations[memberData["Alliance ID"]]["Corporations"][memberData["Corporation ID"]] = {"Count": 1, "Name": memberData["Corporation Name"], "Corporations": {}};
                }                
                
            }
        }
    }
    
    var affilationsSorted = [];
    for (eachAffiliation in affilations) {
        var newAlliance = {"ID": eachAffiliation, "Name": affilations[eachAffiliation]["Name"], "Count": affilations[eachAffiliation]["Count"], "Corporations": []};
        
        for (eachSubAffiliation in affilations[eachAffiliation]["Corporations"]) {
            
            newAlliance["Corporations"].push({"Name": affilations[eachAffiliation]["Corporations"][eachSubAffiliation]["Name"], "ID": eachSubAffiliation, "Count": affilations[eachAffiliation]["Corporations"][eachSubAffiliation]["Count"]});
            
        }
        
        affilationsSorted.push(newAlliance);
        
    }
    
    var firstAffiliations = affilationsSorted.slice(0);
    firstAffiliations.sort(function(a, b) {
        return b["Count"] - a["Count"];
    });
    
    var fullySortedAffiliations = [];
    
    for (eachFullAffiliation of firstAffiliations) {
        
        var secondAffiliations = eachFullAffiliation;
        secondAffiliations["Corporations"].sort(function(a, b) {
            return b["Count"] - a["Count"];
        });
        
        fullySortedAffiliations.push(secondAffiliations);
        
    }
    
    $("#affiliation_breakdown_" + currentTab).empty();
    for (eachAffiliation of fullySortedAffiliations) {
        $("#affiliation_breakdown_" + currentTab).append(
            $("<li/>")
                .attr("id", "alliance_" + eachAffiliation["ID"] + "_" + currentTab)
                .addClass("list-group-item list-group-item bg-secondary p-1 mt-1")
        );
        $("#alliance_" + eachAffiliation["ID"] + "_" + currentTab).append(
            $("<span/>")
                .addClass("badge badge-dark ml-3")
                .text(eachAffiliation["Count"])
        );
        $("#alliance_" + eachAffiliation["ID"] + "_" + currentTab).append(
            $("<a/>")
                .attr("href", "http://evemaps.dotlan.net/alliance/" + eachAffiliation["ID"])
                .addClass("font-weight-bold text-light ml-3")
                .text(eachAffiliation["Name"])
        );
        
        for (eachCorporation of eachAffiliation["Corporations"]) {
            $("#affiliation_breakdown_" + currentTab).append(
                $("<li/>")
                    .attr("id", "corporation_" + eachCorporation["ID"] + "_" + currentTab)
                    .addClass("list-group-item list-group-item bg-secondary p-1 ml-4 mt-1")
            );
            $("#corporation_" + eachCorporation["ID"] + "_" + currentTab).append(
                $("<span/>")
                    .addClass("badge badge-dark ml-3")
                    .text(eachCorporation["Count"])
            );
            $("#corporation_" + eachCorporation["ID"] + "_" + currentTab).append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/corp/" + eachCorporation["ID"])
                    .addClass("font-weight-bold text-light ml-3")
                    .text(eachCorporation["Name"])
            );
        }
    }
}

function fleetOverview (incomingData, currentTab = "main") {

    $("#fleet_overview_" + currentTab).empty();
    
    var wingCounter = 0;
    var squadCounter = 0;

    if (incomingData["Fleet"]["Has Commander"]) {

        $("#fleet_overview_" + currentTab).append(
            $("<li/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_" + currentTab)
                .addClass("list-group-item list-group-item bg-dark border-danger text-danger p-2 mt-1")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_" + currentTab).append(
            $("<div/>")
                .addClass("font-weight-bold font-italic")
                .text("Fleet")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_" + currentTab).append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_row_" + currentTab)
                .addClass("row")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row_" + currentTab).append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_0_" + currentTab)
                .addClass("col-md-3")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_0_" + currentTab).append(
            $("<a/>")
                .attr("href", "https://zkillboard.com/character/" + incomingData["Fleet"]["Character ID"] + "/")
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["Character Name"])
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row_" + currentTab).append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_1_" + currentTab)
                .addClass("col-md-3")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_1_" + currentTab).append(
            $("<a/>")
                .attr("href", "http://evemaps.dotlan.net/alliance/" + incomingData["Fleet"]["Alliance ID"])
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["Alliance Name"])
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row_" + currentTab).append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_2_" + currentTab)
                .addClass("col-md-2")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_2_" + currentTab).append(
            $("<a/>")
                .attr("href", "http://evemaps.dotlan.net/map/" + incomingData["Fleet"]["Region"].replace(" ", "_") + "/" + incomingData["Fleet"]["System"].replace(" ", "_"))
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["System"])
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row_" + currentTab).append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_3_" + currentTab)
                .addClass("col-md-4")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_3_" + currentTab).append(
            $("<span/>")
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["Ship Name"])
        );
        
    }
    else {
        $("#fleet_overview_" + currentTab).append(
            $("<li/>")
                .attr("id", "fleet_command_position_" + currentTab)
                .addClass("list-group-item list-group-item bg-dark border-danger text-danger p-2 mt-1")
        );
        $("#fleet_command_position_" + currentTab).append(
            $("<div/>")
                .addClass("font-weight-bold font-italic")
                .text("Fleet")
        );        
    }
    
    for (wingData of incomingData["Fleet"]["Wings"]){
        if (wingData["Has Commander"]) {
            
            $("#fleet_overview_" + currentTab).append(
                $("<li/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_" + currentTab)
                    .addClass("list-group-item list-group-item bg-dark border-warning text-warning p-2 mt-3")
            );
            $("#character_" + wingData["Character ID"] + "_" + currentTab).append(
                $("<div/>")
                    .addClass("font-weight-bold font-italic")
                    .text(wingData["Wing Name"])
            );
            $("#character_" + wingData["Character ID"] + "_" + currentTab).append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_row_" + currentTab)
                    .addClass("row")
            );
            $("#character_" + wingData["Character ID"] + "_row_" + currentTab).append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_0_" + currentTab)
                    .addClass("col-md-3")
            );
            $("#character_" + wingData["Character ID"] + "_col_0_" + currentTab).append(
                $("<a/>")
                    .attr("href", "https://zkillboard.com/character/" + wingData["Character ID"] + "/")
                    .addClass("font-weight-bold text-light")
                    .text(wingData["Character Name"])
            );
            $("#character_" + wingData["Character ID"] + "_row_" + currentTab).append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_1_" + currentTab)
                    .addClass("col-md-3")
            );
            $("#character_" + wingData["Character ID"] + "_col_1_" + currentTab).append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/alliance/" + wingData["Alliance ID"])
                    .addClass("font-weight-bold text-light")
                    .text(wingData["Alliance Name"])
            );
            $("#character_" + wingData["Character ID"] + "_row_" + currentTab).append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_2_" + currentTab)
                    .addClass("col-md-2")
            );
            $("#character_" + wingData["Character ID"] + "_col_2_" + currentTab).append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/map/" + wingData["Region"].replace(" ", "_") + "/" + wingData["System"].replace(" ", "_"))
                    .addClass("font-weight-bold text-light")
                    .text(wingData["System"])
            );
            $("#character_" + wingData["Character ID"] + "_row_" + currentTab).append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_3_" + currentTab)
                    .addClass("col-md-4")
            );
            $("#character_" + wingData["Character ID"] + "_col_3_" + currentTab).append(
                $("<span/>")
                    .addClass("font-weight-bold text-light")
                    .text(wingData["Ship Name"])
            );
            
        }
        else {
            
            $("#fleet_overview_" + currentTab).append(
                $("<li/>")
                    .attr("id", "wing_" + wingCounter + "_command_position_" + currentTab)
                    .addClass("list-group-item list-group-item bg-dark border-warning text-warning p-2 mt-3")
            );
            $("#wing_" + wingCounter + "_command_position_" + currentTab).append(
                $("<div/>")
                    .addClass("font-weight-bold font-italic")
                    .text(wingData["Wing Name"])
            );
            
        }
        for (squadData of wingData["Squads"]) {
            if (squadData["Has Commander"]) {

                $("#fleet_overview_" + currentTab).append(
                    $("<li/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_" + currentTab)
                        .addClass("list-group-item list-group-item bg-dark border-success text-success p-2 mt-3 ml-2")
                );
                $("#character_" + squadData["Character ID"] + "_" + currentTab).append(
                    $("<div/>")
                        .addClass("font-weight-bold font-italic")
                        .text(squadData["Squad Name"])
                );                
                $("#character_" + squadData["Character ID"] + "_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_row_" + currentTab)
                        .addClass("row")
                );
                $("#character_" + squadData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_0_" + currentTab)
                        .addClass("col-md-3")
                );
                $("#character_" + squadData["Character ID"] + "_col_0_" + currentTab).append(
                    $("<a/>")
                        .attr("href", "https://zkillboard.com/character/" + squadData["Character ID"] + "/")
                        .addClass("font-weight-bold text-light")
                        .text(squadData["Character Name"])
                );
                $("#character_" + squadData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_1_" + currentTab)
                        .addClass("col-md-3")
                );
                $("#character_" + squadData["Character ID"] + "_col_1_" + currentTab).append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/alliance/" + squadData["Alliance ID"])
                        .addClass("font-weight-bold text-light")
                        .text(squadData["Alliance Name"])
                );
                $("#character_" + squadData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_2_" + currentTab)
                        .addClass("col-md-2")
                );
                $("#character_" + squadData["Character ID"] + "_col_2_" + currentTab).append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/map/" + squadData["Region"].replace(" ", "_") + "/" + squadData["System"].replace(" ", "_"))
                        .addClass("font-weight-bold text-light")
                        .text(squadData["System"])
                );
                $("#character_" + squadData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_3_" + currentTab)
                        .addClass("col-md-4")
                );
                $("#character_" + squadData["Character ID"] + "_col_3_" + currentTab).append(
                    $("<span/>")
                        .addClass("font-weight-bold text-light")
                        .text(squadData["Ship Name"])
                );

            }
            else {
                
                $("#fleet_overview_" + currentTab).append(
                    $("<li/>")
                        .attr("id", "squad_" + squadCounter + "_command_position_" + currentTab)
                        .addClass("list-group-item list-group-item bg-dark border-success text-success p-2 mt-3 ml-2")
                );
                $("#squad_" + squadCounter + "_command_position_" + currentTab).append(
                    $("<div/>")
                        .addClass("font-weight-bold font-italic")
                        .text(squadData["Squad Name"])
                );
                
            }
            for (memberData of squadData["Members"]) {

                $("#fleet_overview_" + currentTab).append(
                    $("<li/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_" + currentTab)
                        .addClass("list-group-item list-group-item bg-dark border-secondary p-2 mt-3 ml-4")
                );
                $("#character_" + memberData["Character ID"] + "_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_row_" + currentTab)
                        .addClass("row")
                );
                $("#character_" + memberData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_0_" + currentTab)
                        .addClass("col-md-3")
                );
                $("#character_" + memberData["Character ID"] + "_col_0_" + currentTab).append(
                    $("<a/>")
                        .attr("href", "https://zkillboard.com/character/" + memberData["Character ID"] + "/")
                        .addClass("font-weight-bold text-light")
                        .text(memberData["Character Name"])
                );
                $("#character_" + memberData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_1_" + currentTab)
                        .addClass("col-md-3")
                );
                $("#character_" + memberData["Character ID"] + "_col_1_" + currentTab).append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/alliance/" + memberData["Alliance ID"])
                        .addClass("font-weight-bold text-light")
                        .text(memberData["Alliance Name"])
                );
                $("#character_" + memberData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_2_" + currentTab)
                        .addClass("col-md-2")
                );
                $("#character_" + memberData["Character ID"] + "_col_2_" + currentTab).append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/map/" + memberData["Region"].replace(" ", "_") + "/" + memberData["System"].replace(" ", "_"))
                        .addClass("font-weight-bold text-light")
                        .text(memberData["System"])
                );
                $("#character_" + memberData["Character ID"] + "_row_" + currentTab).append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_3_" + currentTab)
                        .addClass("col-md-4")
                );
                $("#character_" + memberData["Character ID"] + "_col_3_" + currentTab).append(
                    $("<span/>")
                        .addClass("font-weight-bold text-light")
                        .text(memberData["Ship Name"])
                );

            }
            
            squadCounter++;
            
        }
        
        wingCounter++;
        
    }    

}

function populateData () {
    
    $.ajax({
        url: "monitoringController.php",
        type: "POST",
        data: {"Action": "Update", "Shared_Fleets": JSON.stringify(Object.keys(sharedIDs))},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
                        
            $("#overviewRow_main").attr('hidden', true);
            $("#detailRow_main").attr('hidden', true);
            $("#shareKeyContainer").attr('hidden', true);
            $("#share_key").empty();

            if ("Error" in result) {
                
                $("#errorContainer").append(
                    $("<div/>")
                        .addClass("alert alert-danger text-center")
                        .attr("id", "anError_" + errorCounter)
                        .text(result["Error"])
                );
                
                $("#anError_" + errorCounter).delay(5000).fadeOut(1000);
                errorCounter++;
                
            }
            
            if (result["Status"] == "Active") {
                
                $("#toggle_button_container").empty();
                $("#toggle_button_container").append(
                    $("<button/>")
                        .attr("onClick", "stopMonitoring();")
                        .addClass("btn btn-block btn-outline-danger")
                        .text("Stop Monitoring")
                );
                
                $("#fleet_name").prop("disabled", true);
                $("#fleet_srp").prop("disabled", true);
                $("#Voltron").prop("disabled", true);
                $("#Sharing").prop("disabled", true);
                
                if (result["Sharing"]) {
                    
                    $("#share_key").attr("value", result["Sharing Key"]);
                    $("#shareKeyContainer").removeAttr('hidden');
                    
                }
                
                if (result["Found Data"]) {
                    
                    $("#overviewRow_main").removeAttr('hidden');
                    $("#detailRow_main").removeAttr('hidden');

                    topRow(result["Data"], result["Start Date"]);
                    shipBreakdown(result["Data"]);
                    affiliationBreakdown(result["Data"]);
                    fleetOverview(result["Data"]);
                
                }

            }
            else if (result["Status"] == "Stopped") {
               
                $("#toggle_button_container").empty();
                $("#toggle_button_container").append(
                    $("<button/>")
                        .attr("onClick", "startMonitoring();")
                        .addClass("btn btn-block btn-outline-primary")
                        .text("Start Monitoring")
                );
                
                $("#fleet_name").prop("disabled", false);
                $("#fleet_srp").prop("disabled", false);
                $("#Voltron").prop("disabled", false);
                $("#Sharing").prop("disabled", false);

                $("#fleet_boss_main").empty();
                $("#member_count_main").empty();
                $("#fleet_started_main").empty();
                $("#ship_breakdown_main").empty();
                $("#affiliation_breakdown_main").empty();
                $("#fleet_overview_main").empty();
                
            }
            
            for (eachTab in sharedIDs) {
                
                $("#overviewRow_" + sharedIDs[eachTab]).attr('hidden', true);
                $("#detailRow_" + sharedIDs[eachTab]).attr('hidden', true);
                
            }
            
            for (eachShared in result["Shared Data"]) {
                
                if (eachShared in sharedIDs) {
                    
                    if (result["Shared Data"][eachShared]["Status"] = "Active") {
                        
                        $("#overviewRow_" + sharedIDs[eachShared]).removeAttr('hidden');
                        $("#detailRow_" + sharedIDs[eachShared]).removeAttr('hidden');

                        topRow(result["Shared Data"][eachShared]["Data"], result["Shared Data"][eachShared]["Start Date"], sharedIDs[eachShared]);
                        shipBreakdown(result["Shared Data"][eachShared]["Data"], sharedIDs[eachShared]);
                        affiliationBreakdown(result["Shared Data"][eachShared]["Data"], sharedIDs[eachShared]);
                        fleetOverview(result["Shared Data"][eachShared]["Data"], sharedIDs[eachShared]);
                    
                    }
                
                }
                
            }
            
        }
    });
    
}