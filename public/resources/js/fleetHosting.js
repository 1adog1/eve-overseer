jQuery(document).ready(function () {
    populateData();
    setInterval(populateData, 5000);
});

function startMonitoring() {
    $.ajax({
        url: "monitoringController.php",
        type: "POST",
        mimeType: "application/json",
        dataType: 'json',
        data: {"Action": "Start", "Name": $("#fleet_name").val(), "SRP": $("#fleet_srp").val(), "Voltron": $('#Voltron').prop('checked')},
       
        success: function(result){
           
            if ("Error" in result) {
                                
                $("#errorContainer").empty();
                $("#errorContainer").append(
                    $("<div/>")
                        .addClass("alert alert-danger text-center")
                        .attr("id", "anError")
                        .text(result["Error"])
                );
                
                $("#anError").delay(5000).fadeOut(1000);
                
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
                                    
                    $("#errorContainer").empty();
                    $("#errorContainer").append(
                        $("<div/>")
                            .addClass("alert alert-danger text-center")
                            .attr("id", "anError")
                            .text(result["Error"])
                    );
                    
                    $("#anError").delay(5000).fadeOut(1000);
                    
                }           
               
                populateData();
           }
        });
    }
}

function topRow(incomingData, startTime) {
    
    $("#fleet_boss").empty();
    $("#fleet_boss").append(
        $("<span/>")
            .text("Fleet Boss: ")
    );
    $("#fleet_boss").append(    
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
    
    $("#member_count").empty();
    $("#member_count").append(
        $("<span/>")
            .text("Fleet Members: " + fleetCount)
    );
    $("#fleet_started").empty();
    $("#fleet_started").append(
        $("<span/>")
            .text("Fleet Started: " + startTime)
    );
    
    
}

function shipBreakdown(incomingData) {
    
    var ships = {};
    
    if (incomingData["Fleet"]["Has Commander"]) {
        
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
        for (squadData of wingData["Squads"]) {
            if (squadData["Has Commander"]) {

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
            for (memberData of squadData["Members"]) {

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
    
    $("#ship_breakdown").empty();
    for (eachClass of fullySortedShips) {
        $("#ship_breakdown").append(
            $("<li/>")
                .attr("id", "class_" + eachClass["ID"])
                .addClass("list-group-item list-group-item bg-secondary p-1 mt-1")
        );
        $("#class_" + eachClass["ID"]).append(
            $("<span/>")
                .addClass("badge badge-dark ml-3")
                .text(eachClass["Count"])
        );
        $("#class_" + eachClass["ID"]).append(
            $("<span/>")
                .addClass("font-weight-bold text-light ml-3")
                .text(eachClass["Name"])
        );
        
        for (eachShip of eachClass["Ships"]) {
            
            $("#ship_breakdown").append(
                $("<li/>")
                    .attr("id", "ship_" + eachShip["ID"])
                    .addClass("list-group-item list-group-item bg-secondary p-1 ml-4 mt-1")
            );
            $("#ship_" + eachShip["ID"]).append(
                $("<span/>")
                    .addClass("badge badge-dark ml-3")
                    .text(eachShip["Count"])
            );
            $("#ship_" + eachShip["ID"]).append(
                $("<span/>")
                    .addClass("font-weight-bold text-light ml-3")
                    .text(eachShip["Name"])
            );
            
        }
        
    }
}

function affiliationBreakdown(incomingData) {
    
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
    
    $("#affiliation_breakdown").empty();
    for (eachAffiliation of fullySortedAffiliations) {
        $("#affiliation_breakdown").append(
            $("<li/>")
                .attr("id", "alliance_" + eachAffiliation["ID"])
                .addClass("list-group-item list-group-item bg-secondary p-1 mt-1")
        );
        $("#alliance_" + eachAffiliation["ID"]).append(
            $("<span/>")
                .addClass("badge badge-dark ml-3")
                .text(eachAffiliation["Count"])
        );
        $("#alliance_" + eachAffiliation["ID"]).append(
            $("<a/>")
                .attr("href", "http://evemaps.dotlan.net/alliance/" + eachAffiliation["ID"])
                .addClass("font-weight-bold text-light ml-3")
                .text(eachAffiliation["Name"])
        );
        
        for (eachCorporation of eachAffiliation["Corporations"]) {
            $("#affiliation_breakdown").append(
                $("<li/>")
                    .attr("id", "corporation_" + eachCorporation["ID"])
                    .addClass("list-group-item list-group-item bg-secondary p-1 ml-4 mt-1")
            );
            $("#corporation_" + eachCorporation["ID"]).append(
                $("<span/>")
                    .addClass("badge badge-dark ml-3")
                    .text(eachCorporation["Count"])
            );
            $("#corporation_" + eachCorporation["ID"]).append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/corp/" + eachCorporation["ID"])
                    .addClass("font-weight-bold text-light ml-3")
                    .text(eachCorporation["Name"])
            );
        }
    }
}

function fleetOverview (incomingData) {

    $("#fleet_overview").empty();
    
    var wingCounter = 0;
    var squadCounter = 0;

    if (incomingData["Fleet"]["Has Commander"]) {

        $("#fleet_overview").append(
            $("<li/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"])
                .addClass("list-group-item list-group-item bg-danger p-1 mt-1")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"]).append(
            $("<div/>")
                .addClass("font-weight-bold font-italic text-ligh")
                .text("Fleet")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"]).append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_row")
                .addClass("row ml-1")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row").append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_0")
                .addClass("col-md-3")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_0").append(
            $("<a/>")
                .attr("href", "https://zkillboard.com/character/" + incomingData["Fleet"]["Character ID"] + "/")
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["Character Name"])
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row").append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_1")
                .addClass("col-md-3")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_1").append(
            $("<a/>")
                .attr("href", "http://evemaps.dotlan.net/alliance/" + incomingData["Fleet"]["Alliance ID"])
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["Alliance Name"])
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row").append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_2")
                .addClass("col-md-2")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_2").append(
            $("<a/>")
                .attr("href", "http://evemaps.dotlan.net/map/" + incomingData["Fleet"]["Region"].replace(" ", "_") + "/" + incomingData["Fleet"]["System"].replace(" ", "_"))
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["System"])
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_row").append(
            $("<div/>")
                .attr("id", "character_" + incomingData["Fleet"]["Character ID"] + "_col_3")
                .addClass("col-md-4")
        );
        $("#character_" + incomingData["Fleet"]["Character ID"] + "_col_3").append(
            $("<span/>")
                .addClass("font-weight-bold text-light")
                .text(incomingData["Fleet"]["Ship Name"])
        );
        
    }
    else {
        $("#fleet_overview").append(
            $("<li/>")
                .attr("id", "fleet_command_position")
                .addClass("list-group-item list-group-item bg-danger p-1 mt-1")
        );
        $("#fleet_command_position").append(
            $("<div/>")
                .addClass("font-weight-bold font-italic text-light")
                .text("Fleet")
        );        
    }
    
    for (wingData of incomingData["Fleet"]["Wings"]){
        if (wingData["Has Commander"]) {
            
            $("#fleet_overview").append(
                $("<li/>")
                    .attr("id", "character_" + wingData["Character ID"])
                    .addClass("list-group-item list-group-item bg-primary p-1 mt-1")
            );
            $("#character_" + wingData["Character ID"]).append(
                $("<div/>")
                    .addClass("font-weight-bold font-italic text-light")
                    .text(wingData["Wing Name"])
            );
            $("#character_" + wingData["Character ID"]).append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_row")
                    .addClass("row ml-2")
            );
            $("#character_" + wingData["Character ID"] + "_row").append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_0")
                    .addClass("col-md-3")
            );
            $("#character_" + wingData["Character ID"] + "_col_0").append(
                $("<a/>")
                    .attr("href", "https://zkillboard.com/character/" + wingData["Character ID"] + "/")
                    .addClass("font-weight-bold text-light")
                    .text(wingData["Character Name"])
            );
            $("#character_" + wingData["Character ID"] + "_row").append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_1")
                    .addClass("col-md-3")
            );
            $("#character_" + wingData["Character ID"] + "_col_1").append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/alliance/" + wingData["Alliance ID"])
                    .addClass("font-weight-bold text-light")
                    .text(wingData["Alliance Name"])
            );
            $("#character_" + wingData["Character ID"] + "_row").append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_2")
                    .addClass("col-md-2")
            );
            $("#character_" + wingData["Character ID"] + "_col_2").append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/map/" + wingData["Region"].replace(" ", "_") + "/" + wingData["System"].replace(" ", "_"))
                    .addClass("font-weight-bold text-light")
                    .text(wingData["System"])
            );
            $("#character_" + wingData["Character ID"] + "_row").append(
                $("<div/>")
                    .attr("id", "character_" + wingData["Character ID"] + "_col_3")
                    .addClass("col-md-4")
            );
            $("#character_" + wingData["Character ID"] + "_col_3").append(
                $("<span/>")
                    .addClass("font-weight-bold text-light")
                    .text(wingData["Ship Name"])
            );
            
        }
        else {
            
            $("#fleet_overview").append(
                $("<li/>")
                    .attr("id", "wing_" + wingCounter + "_command_position")
                    .addClass("list-group-item list-group-item bg-primary p-1 mt-1")
            );
            $("#wing_" + wingCounter + "_command_position").append(
                $("<div/>")
                    .addClass("font-weight-bold font-italic text-light")
                    .text(wingData["Wing Name"])
            );
            
        }
        for (squadData of wingData["Squads"]) {
            if (squadData["Has Commander"]) {

                $("#fleet_overview").append(
                    $("<li/>")
                        .attr("id", "character_" + squadData["Character ID"])
                        .addClass("list-group-item list-group-item bg-success p-1 mt-1 ml-2")
                );
                $("#character_" + squadData["Character ID"]).append(
                    $("<div/>")
                        .addClass("font-weight-bold font-italic text-light")
                        .text(squadData["Squad Name"])
                );                
                $("#character_" + squadData["Character ID"]).append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_row")
                        .addClass("row ml-2")
                );
                $("#character_" + squadData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_0")
                        .addClass("col-md-3")
                );
                $("#character_" + squadData["Character ID"] + "_col_0").append(
                    $("<a/>")
                        .attr("href", "https://zkillboard.com/character/" + squadData["Character ID"] + "/")
                        .addClass("font-weight-bold text-light")
                        .text(squadData["Character Name"])
                );
                $("#character_" + squadData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_1")
                        .addClass("col-md-3")
                );
                $("#character_" + squadData["Character ID"] + "_col_1").append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/alliance/" + squadData["Alliance ID"])
                        .addClass("font-weight-bold text-light")
                        .text(squadData["Alliance Name"])
                );
                $("#character_" + squadData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_2")
                        .addClass("col-md-2")
                );
                $("#character_" + squadData["Character ID"] + "_col_2").append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/map/" + squadData["Region"].replace(" ", "_") + "/" + squadData["System"].replace(" ", "_"))
                        .addClass("font-weight-bold text-light")
                        .text(squadData["System"])
                );
                $("#character_" + squadData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + squadData["Character ID"] + "_col_3")
                        .addClass("col-md-4")
                );
                $("#character_" + squadData["Character ID"] + "_col_3").append(
                    $("<span/>")
                        .addClass("font-weight-bold text-light")
                        .text(squadData["Ship Name"])
                );

            }
            else {
                
                $("#fleet_overview").append(
                    $("<li/>")
                        .attr("id", "squad_" + squadCounter + "_command_position")
                        .addClass("list-group-item list-group-item bg-success p-1 mt-1 ml-2")
                );
                $("#squad_" + squadCounter + "_command_position").append(
                    $("<div/>")
                        .addClass("font-weight-bold font-italic text-light")
                        .text(squadData["Squad Name"])
                );
                
            }
            for (memberData of squadData["Members"]) {

                $("#fleet_overview").append(
                    $("<li/>")
                        .attr("id", "character_" + memberData["Character ID"])
                        .addClass("list-group-item list-group-item bg-secondary p-1 mt-1 ml-4")
                );
                $("#character_" + memberData["Character ID"]).append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_row")
                        .addClass("row ml-2")
                );
                $("#character_" + memberData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_0")
                        .addClass("col-md-3")
                );
                $("#character_" + memberData["Character ID"] + "_col_0").append(
                    $("<a/>")
                        .attr("href", "https://zkillboard.com/character/" + memberData["Character ID"] + "/")
                        .addClass("font-weight-bold text-light")
                        .text(memberData["Character Name"])
                );
                $("#character_" + memberData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_1")
                        .addClass("col-md-3")
                );
                $("#character_" + memberData["Character ID"] + "_col_1").append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/alliance/" + memberData["Alliance ID"])
                        .addClass("font-weight-bold text-light")
                        .text(memberData["Alliance Name"])
                );
                $("#character_" + memberData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_2")
                        .addClass("col-md-2")
                );
                $("#character_" + memberData["Character ID"] + "_col_2").append(
                    $("<a/>")
                        .attr("href", "http://evemaps.dotlan.net/map/" + memberData["Region"].replace(" ", "_") + "/" + memberData["System"].replace(" ", "_"))
                        .addClass("font-weight-bold text-light")
                        .text(memberData["System"])
                );
                $("#character_" + memberData["Character ID"] + "_row").append(
                    $("<div/>")
                        .attr("id", "character_" + memberData["Character ID"] + "_col_3")
                        .addClass("col-md-4")
                );
                $("#character_" + memberData["Character ID"] + "_col_3").append(
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
        data: {"Action": "Update"},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
                        
            $("#overviewRow").attr('hidden', true);
            $("#detailRow").attr('hidden', true);

            if ("Error" in result) {
                
                $("#errorContainer").empty();
                $("#errorContainer").append(
                    $("<div/>")
                        .addClass("alert alert-danger text-center")
                        .attr("id", "anError")
                        .text(result["Error"])
                );
                
                $("#anError").delay(5000).fadeOut(1000);
                
            }
            
            if (result["Status"] == "Active") {
                
                $("#toggle_button_container").empty();
                $("#toggle_button_container").append(
                    $("<button/>")
                        .attr("onClick", "stopMonitoring();")
                        .addClass("btn btn-block btn-outline-danger")
                        .text("Stop Monitoring")
                );
                
                $("#fleet_name").attr("disabled", "disabled");
                $("#fleet_srp").attr("disabled", "disabled");
                $("#Voltron").attr("disabled", "disabled"); 
                
                if (result["Found Data"]) {
                    
                    $("#overviewRow").removeAttr('hidden');
                    $("#detailRow").removeAttr('hidden');

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
                
                $("#fleet_name").removeAttr("disabled");
                $("#fleet_srp").removeAttr("disabled");
                $("#Voltron").removeAttr("disabled");

                $("#fleet_boss").empty();
                $("#member_count").empty();
                $("#fleet_started").empty();
                $("#ship_breakdown").empty();
                $("#affiliation_breakdown").empty();
                $("#fleet_overview").empty();
                
            }
        }
    });
    
}