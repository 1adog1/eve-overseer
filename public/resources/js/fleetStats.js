jQuery(document).ready(function () {
    
    getFleets ();
            
});

function getFormattedMinutes(timeframe) {
    
    var totalMinutes = Math.floor(timeframe / 60);
    var totalSeconds = Math.floor(timeframe % 60);
    var totalTime = totalMinutes.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false}) + "m " + totalSeconds.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false}) + "s"
    
    return totalTime;

}

function getFormattedHours(timeframe) {
    
    var rawTime = timeframe / 3600;
    var totalHours = Math.floor(rawTime);
    var totalMinutes = Math.floor((rawTime - totalHours) * 60);
    var totalTime = totalHours + "h " + totalMinutes.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false}) + "m";
    
    return totalTime;
    
}

function getFleets () {
    
    $("#fleetList").empty();

    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "List"},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                var keys = Object.keys(result["Fleet Data"]);
                
                keys.sort(function(a, b){
                    return result["Fleet Data"][b]["timestamp"] - result["Fleet Data"][a]["timestamp"]
                });
                            
                for (fleets of keys) {
                    
                    $("#fleetList").append(
                        $("<a/>")
                            .addClass("small list-group-item list-group-item-action list-group-item-primary p-2 text-left h6 fleet-list")
                            .attr("data-toggle", "tooltip")
                            .attr("title", "<b>Boss:</b> " + result["Fleet Data"][fleets]["Boss"] + " <br/><b>SRP Level:</b> " + result["Fleet Data"][fleets]["SRP Level"] + "<br/><b>Members:</b> " + result["Fleet Data"][fleets]["Total Members"] + " <br/><b>Run Time:</b> " + getFormattedHours(result["Fleet Data"][fleets]["Run Time"]))
                            .attr("href", "#")
                            .attr("id", fleets)
                            .attr("onclick", "populateData(" + fleets + ")")
                            .append(
                                $("<div/>")
                                    .addClass("font-weight-bold")
                                    .text(result["Fleet Data"][fleets]["Name"])
                                    .append(
                                        $("<span/>")
                                            .attr("id", "status-" + fleets)
                                            .attr("style", "height: 16px; width: 16px;")
                                            .addClass("float-right mt-2 text-center align-self-center fleet-status")
                                    )
                                    .append(
                                        $("<span/>")
                                            .attr("id", "voltron-" + fleets)
                                            .addClass("float-right p-1 mt-2 mr-4 text-center align-self-center")
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("mt-1 font-italic")
                                    .text(result["Fleet Data"][fleets]["Start Date"])
                            )
                    );
                    
                    if (result["Fleet Data"][fleets]["Voltron"] == "true") {
                        $("#voltron-" + fleets)
                            .addClass("badge badge-info")
                            .text("Voltron");
                    }
                    
                }
                
                $("[data-toggle='tooltip']").tooltip({html: true, boundary: "window", trigger: "hover", placement: "right"});
                
            }
        }
    });
    
}

function topRow (incomingData) {
    
    $("#fleetOverview").empty();

    $("#fleetOverview").append(
        $("<div/>")
            .text("Fleet Name: " + incomingData["Name"])
    );

    $("#fleetOverview").append(
        $("<div/>")
            .text("SRP Level: " + incomingData["SRP Level"])
    );
    
    $("#fleetOverview").append(
        $("<div/>")
            .text("Fleet Boss: ")
            .append(
                $("<a/>")
                    .attr("href", "https://zkillboard.com/character/" + incomingData["Boss ID"] + "/")
                    .text(incomingData["Boss"])
            )
    );
    $("#fleetOverview").append(
        $("<div/>")
            .text("Unique Members: " + incomingData["Member Count"])
    );
    
    if ("FleetID" in incomingData) {
    
        $("#fleetOverview").append(
            $("<button/>")
                .attr("onClick", "deleteFleet(" + incomingData["FleetID"] + ");")
                .addClass("btn btn-block btn-danger mt-2")
                .text("Delete Fleet")
        );
    
    }
    
    $("#fleetTimes").empty();
    
    $("#fleetTimes").append(
        $("<div/>")
            .text("Start Time: " + incomingData["Start Time"])
    );
    $("#fleetTimes").append(
        $("<div/>")
            .text("End Time: " + incomingData["End Time"])
    );
    $("#fleetTimes").append(
        $("<div/>")
            .text("Elapsed Time: " + getFormattedHours(incomingData["Run Time"]))
    );

}

function formatChart (timeData, incomingData) {
    
    var dataRows = [];
    var sortingValues = {};
    var sortableValues = [];
    var sortedShips = [];
    
    for (shipData of incomingData["Snapshot History"]){
        
        for (listedShips of incomingData["Ship List"]){
            
            if (listedShips in shipData) {
                
                if (!(listedShips in sortingValues)) {
                    
                    sortingValues[listedShips] = 0;
                    
                }
                
                sortingValues[listedShips] += shipData[listedShips];
                
            }
            
        }
        
    }
    
    for (var eachValue in sortingValues) {
        sortableValues.push([eachValue, sortingValues[eachValue]]);
    }
    
    sortableValues.sort(function(a,b) {
       return a[1] - b[1]; 
    });
    
    for (eachNewValue of sortableValues) {
        sortedShips.push(eachNewValue[0]);
    }
    
    var firstRow = ["Time"];
    
    for (listedShips of sortedShips){
        
        firstRow.push(listedShips);
        
    }
    
    dataRows.push(firstRow);
    
    var currentTime = parseInt(timeData["Start Timestamp"]);

    var ticks = [];
    
    var counter = 0;
    var timeCounter = 0;

    for (shipData of incomingData["Snapshot History"]){
        
        var timeToPush = new Date(currentTime * 1000);
        
        var eachRow = [{v: timeToPush, f: timeToPush.toLocaleString('en-US', {timeZone: 'UTC'})}];
        
        for (listedShips of sortedShips){
            
            if (listedShips in shipData) {
                
                eachRow.push(shipData[listedShips]);
                
            }
            else {
                
                eachRow.push(null);
                
            }
            
        }
        
        ticks.push({v: timeToPush, f: getFormattedMinutes(timeCounter)});
        
        dataRows.push(eachRow);
        
        counter += 1;
        timeCounter += 15;
        currentTime += 15;
        
    }
    
    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(setupChart);

    function setupChart () {
        
        var areaChart = new google.visualization.arrayToDataTable(dataRows);
                
        var fullArea = new google.visualization.AreaChart(document.getElementById('snapshotContainer'));
        
        var chartOptions = {title: "Ships Over Time", titleTextStyle: {color: "white", fontSize: 28, bold: false}, isStacked: true, height: 450, focusTarget: "category", backgroundColor: "transparent", hAxis: {title: "Click a point on the chart to view breakdown.", titleTextStyle: {color: "white", fontSize: 14}, ticks: ticks, textStyle: {color: "white"}, maxTextLines: 1}, vAxis: {title:"Total Ships", titleTextStyle: {color: "white", fontSize: 18}, textStyle: {color: "white"}}, legend: {position: "none"}, tooltip: {isHtml: true, showColorCode: true, trigger: "selection", textStyle: {fontSize: 11.5}}};
        
        fullArea.draw(areaChart, chartOptions);
        
    }

    
}

function listMembers (incomingData) {
    
    for (eachMember in incomingData) {
        
        $("#fleetMembers").append(
            $("<a/>")
                .addClass("list-group-item list-group-item-action text-white bg-secondary p-1 mt-1 removable-item")
                .attr("data-toggle", "collapse")
                .attr("href", "#member-" + eachMember)
                .attr("aria-expanded", "false")
                .attr("aria-controls", "member-" + eachMember)
                .append(
                    $("<div/>")
                        .addClass("row ml-2")
                        .append(
                            $("<div/>")
                                .addClass("col-3")
                                .text(incomingData[eachMember]["name"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3")
                                .text(incomingData[eachMember]["alliance_name"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3")
                                .text(incomingData[eachMember]["corp_name"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-2")
                                .text(incomingData[eachMember]["Join Time"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-1 text-left")
                                .attr("id", "alerts-" + eachMember)
                        )
                )
        );
        $("#fleetMembers").append(
            $("<div/>")
                .addClass("collapse card border-light text-white bg-secondary mt-1 p-1 removable-item")
                .attr("id", "member-" + eachMember)
                .append(
                    $("<div/>")
                        .addClass("row mt-1")
                        .append(
                            $("<div/>")
                                .addClass("col-3 text-left font-weight-bold")
                                .append (
                                    $("<table/>")
                                        .addClass("table table-dark small")
                                        .append (
                                            $("<thead/>")
                                                .append (
                                                    $("<tr/>")
                                                        .append (
                                                            $("<th/>")
                                                                .text("Position")
                                                        )
                                                        .append (
                                                            $("<th/>")
                                                                .text("Time")
                                                        )
                                                )
                                        )
                                        .append (
                                            $("<tbody/>")
                                                .addClass("text-left font-weight-normal")
                                                .attr("id", "roles-" + eachMember)
                                        )
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3 text-left font-weight-bold")
                                .append (
                                    $("<table/>")
                                        .addClass("table table-dark small")
                                        .append (
                                            $("<thead/>")
                                                .append (
                                                    $("<tr/>")
                                                        .append (
                                                            $("<th/>")
                                                                .text("Ship")
                                                        )
                                                        .append (
                                                            $("<th/>")
                                                                .text("Time")
                                                        )
                                                )
                                        )
                                        .append (
                                            $("<tbody/>")
                                                .addClass("text-left font-weight-normal")
                                                .attr("id", "ships-" + eachMember)
                                        )
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3 text-left font-weight-bold")
                                .append (
                                    $("<table/>")
                                        .addClass("table table-dark small")
                                        .append (
                                            $("<thead/>")
                                                .append (
                                                    $("<tr/>")
                                                        .append (
                                                            $("<th/>")
                                                                .text("Region")
                                                        )
                                                        .append (
                                                            $("<th/>")
                                                                .text("System")
                                                        )
                                                        .append (
                                                            $("<th/>")
                                                                .text("Time")
                                                        )
                                                )
                                        )
                                        .append (
                                            $("<tbody/>")
                                                .addClass("text-left font-weight-normal")
                                                .attr("id", "regions-" + eachMember)
                                        )
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3 text-center font-weight-bold")
                                .text("Time in Fleet")
                                .append (
                                    $("<div/>")
                                        .addClass("font-weight-normal")
                                        .attr("id", "times-" + eachMember)
                                )
                        )
                )

        );
        
        for (eachRole in incomingData[eachMember]["time_in_roles"]) {
            
            $("#roles-" + eachMember).append(
            
                $("<tr/>")
                    .append (
                        $("<td/>")
                            .addClass("p-2 text-left")
                            .text(eachRole)
                    )
                    .append (
                        $("<td/>")
                            .addClass("p-2 text-right")
                            .text(getFormattedMinutes(incomingData[eachMember]["time_in_roles"][eachRole]))
                    )
            
            );
            
        }
        
        for (eachShip in incomingData[eachMember]["time_in_ships"]) {
            
            $("#ships-" + eachMember).append(
            
                $("<tr/>")
                    .append (
                        $("<td/>")
                            .addClass("p-2 text-left")
                            .text(incomingData[eachMember]["time_in_ships"][eachShip]["Name"])
                    )
                    .append (
                        $("<td/>")
                            .addClass("p-2 text-right")
                            .text(getFormattedMinutes(incomingData[eachMember]["time_in_ships"][eachShip]["Time"]))
                    )
            
            );
            
        }
                
        if ("time_in_systems" in incomingData[eachMember]) {
        
            for (eachRegion in incomingData[eachMember]["time_in_systems"]) {
                
                $("#regions-" + eachMember).append(
                
                    $("<tr/>")
                        .append (
                            $("<td/>")
                                .addClass("p-2 font-weight-bold")
                                .text(eachRegion)
                        )
                        .append (
                            $("<td/>")
                                .addClass("p-2")
                        )
                        .append (
                            $("<td/>")
                                .addClass("p-2 text-right font-weight-bold")
                                .text(getFormattedMinutes(incomingData[eachMember]["time_in_systems"][eachRegion]["Time"]))
                        )
                        
                );
                
                for (eachSystem in incomingData[eachMember]["time_in_systems"][eachRegion]["Systems"]) {
                    
                    $("#regions-" + eachMember).append(
                    
                        $("<tr/>")
                            .append (
                                $("<td/>")
                                    .addClass("p-2")
                            )
                            .append (
                                $("<td/>")
                                    .addClass("p-2 text-left")
                                    .text(eachSystem)
                            )
                            .append (
                                $("<td/>")
                                    .addClass("p-2 text-right")
                                    .text(getFormattedMinutes(incomingData[eachMember]["time_in_systems"][eachRegion]["Systems"][eachSystem]["Time"]))
                            )
                    );
                }
            }
        }
        
        $("#times-" + eachMember).append(
            $("<div/>")
                .text(getFormattedMinutes(incomingData[eachMember]["time_in_fleet"]))
        );
        
        
        
    }
    
}

function listAffiliations(incomingData) {
    
    var allianceKeys = Object.keys(incomingData);
    
    allianceKeys.sort(function(a, b){
        return incomingData[b]["Count"] - incomingData[a]["Count"]
    });
    
    for (eachAlliance of allianceKeys) {
        
        $("#fleetAffiliations").append(
            $("<a/>")
                .addClass("list-group-item list-group-item-action text-white bg-secondary p-1 mt-1 removable-item")
                .attr("data-toggle", "collapse")
                .attr("href", "#alliance-" + eachAlliance)
                .attr("aria-expanded", "false")
                .attr("aria-controls", "alliance-" + eachAlliance)
                .append(
                    $("<div/>")
                        .addClass("row")
                        .append(
                            $("<div/>")
                                .addClass("col-1")
                                .append(
                                    $("<img>")
                                        .addClass("img-fluid rounded")
                                        .attr("src", "https://images.evetech.net/alliances/" + eachAlliance + "/logo?size=32")
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-9 align-self-center text-left")
                                .append (
                                    $("<h6/>")
                                        .addClass("mt-2 font-weight-bold")
                                        .text(incomingData[eachAlliance]["Name"])
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-2 border-left border-light align-self-center text-center")
                                .append (
                                    $("<h6/>")
                                        .addClass("mt-2 font-weight-bold")
                                        .text(incomingData[eachAlliance]["Count"])
                                )                        
                        )
                )
        )
        .append(
            $("<div/>")
                .addClass("collapse removable-item")
                .attr("id", "alliance-" + eachAlliance)

        );
        
        var corporationKeys = Object.keys(incomingData[eachAlliance]["Corporations"]);
        
        corporationKeys.sort(function(a, b){
            return incomingData[eachAlliance]["Corporations"][b]["Count"] - incomingData[eachAlliance]["Corporations"][a]["Count"]
        });
        
        for (eachCorporation of corporationKeys) {
            
            console.log(eachCorporation);
            
            $("#alliance-" + eachAlliance).append(
                $("<div/>")
                    .addClass("list-group-item text-white bg-secondary p-1 mt-1 ml-4 removable-item")
                    .append(
                        $("<div/>")
                            .addClass("row")
                            .append(
                                $("<div/>")
                                    .addClass("col-1")
                                    .append(
                                        $("<img>")
                                            .addClass("img-fluid rounded")
                                            .attr("src", "https://images.evetech.net/corporations/" + eachCorporation + "/logo?size=32")
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-9 align-self-center text-left")
                                    .append (
                                        $("<h6/>")
                                            .addClass("mt-2")
                                            .text(incomingData[eachAlliance]["Corporations"][eachCorporation]["Name"])
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-2 border-left border-light align-self-center text-center")
                                    .append (
                                        $("<h6/>")
                                            .addClass("mt-2")
                                            .text(incomingData[eachAlliance]["Corporations"][eachCorporation]["Count"])
                                    )                        
                            )
                    )
            );
            
        }
        
    }
    
}

function addAlerts(incomingData) {
    
    var regionCounter = {};
    
    for (eachMember in incomingData) {
        
        if ("time_in_systems" in incomingData[eachMember]) {
        
            for (eachRegion in incomingData[eachMember]["time_in_systems"]) {
                
                if (eachRegion in regionCounter) {
                    
                    regionCounter[eachRegion] += 1;
                    
                }
                else {
                    
                    regionCounter[eachRegion] = 1;
                    
                }
                
            }
        
        }
    }
    
    for (eachMember in incomingData) {
        
        var singleSystemAlert = false;
        var commanderAlert = false;
        var uniqueRegionAlert = false;
        
        if ("time_in_systems" in incomingData[eachMember]) {
            
            var systemCounter = 0;
            
            for (eachRegion in incomingData[eachMember]["time_in_systems"]) {
                
                for (eachSystem in incomingData[eachMember]["time_in_systems"][eachRegion]["Systems"]) {
                    
                    systemCounter += 1;
                    
                }
                
                if (eachRegion in regionCounter && regionCounter[eachRegion] == 1) {
                    
                    uniqueRegionAlert = true;
                    
                }
                
            }
            
            if (systemCounter == 1) {
                
                singleSystemAlert = true;
                
            }
            
        }
        
        for (eachRole in incomingData[eachMember]["time_in_roles"]) {
            
            if (incomingData[eachMember]["time_in_roles"][eachRole] != 0 && eachRole != "Squad Member") {
                
                commanderAlert = true;
                
            }
            
        }
        
        if (singleSystemAlert) {
            
            $("#alerts-" + eachMember).append(
                $("<span/>")
                    .addClass("badge badge-warning ml-1")
                    .attr("data-toggle", "tooltip")
                    .attr("data-placement", "top")
                    .attr("title", "Character never left a single system.")
                    .append(
                        $("<img>")
                            .attr("src", "/resources/images/octicons/pulse-16.svg")
                    )
            );
            
        }
        if (commanderAlert) {
            
            $("#alerts-" + eachMember).append(
                $("<span/>")
                    .addClass("badge badge-info ml-1")
                    .attr("data-toggle", "tooltip")
                    .attr("data-placement", "top")
                    .attr("title", "Character occupied a command position.")
                    .append(
                        $("<img>")
                            .attr("src", "/resources/images/octicons/chevron-up-16.svg")
                    )
            );
            
        }
        if (uniqueRegionAlert) {
            
            $("#alerts-" + eachMember).append(
                $("<span/>")
                    .addClass("badge badge-danger ml-1")
                    .attr("data-toggle", "tooltip")
                    .attr("data-placement", "top")
                    .attr("title", "Character was in a different region from the rest of the fleet.")
                    .append(
                        $("<img>")
                            .attr("src", "/resources/images/octicons/globe-16.svg")
                    )
            );
            
        }
        
    }
    
    $('[data-toggle="tooltip"]').tooltip()
    
}

function deleteFleet(idToDelete) {
        
    var confirmation = confirm("Deleting a fleet cannot be undone. Are you sure you want to proceed?");
    
    if (confirmation) {
        $.ajax({
            url: "dataController.php",
            type: "POST",
            mimeType: "application/json",
            dataType: 'json',        
            data: {"Action": "Delete", "FleetID": idToDelete},
            success: function(result){
                
                if (result["Status"] == "Success") {
                    
                    $(".removable-item").remove();
                    $("#headerRow").attr('hidden', true);
                    $("#snapshotRow").attr('hidden', true);
                    $("#breakdownRow").attr('hidden', true);
                    $("#affiliationRow").attr('hidden', true);
                    
                    getFleets ();
                    
                }
                
            }
        });
    }
}

function populateData (fleetID) {
    
    $(".fleet-status").empty();
    $(".fleet-status").removeClass("spinner-border");
    $("#status-" + fleetID).addClass("spinner-border");

    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Get", "FleetID": fleetID},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                $(".removable-item").remove();
                $("#headerRow").removeAttr('hidden');
                $("#snapshotRow").removeAttr('hidden');
                $("#breakdownRow").removeAttr('hidden');
                $("#affiliationRow").removeAttr('hidden');

                topRow(result["Header Data"]);
                formatChart(result["Header Data"], result["Ship Data"]);
                listAffiliations(result["Affiliation Data"]);
                listMembers(result["Member Data"]);
                addAlerts(result["Member Data"]);
                
                $(".fleet-status").empty();
                $(".fleet-status").removeClass("spinner-border");
                $("#status-" + fleetID).append(
                    $("<img>")
                        .attr("src", "/resources/images/octicons/check-16.svg")
                );
                
            }
            else {
                
                $("#headerRow").attr('hidden', true);
                $("#snapshotRow").attr('hidden', true);
                $("#breakdownRow").attr('hidden', true);
                $("#affiliationRow").attr('hidden', true);
                
                $(".fleet-status").empty();
                $(".fleet-status").removeClass("spinner-border");
                $("#status-" + fleetID).append(
                    $("<img>")
                        .attr("src", "/resources/images/octicons/alert-16.svg")
                );
                
            }
        }
    });
    
}