jQuery(document).ready(function () {
    
    getFleets ();
        
    /*$(document).on("click", ".fleet-list", function(event) {
        
        populateData(event.target.id);
        
    });*/
});

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
                
                keys.sort(function(a, b){return result["Fleet Data"][b]["timestamp"] - result["Fleet Data"][a]["timestamp"]});
                            
                for (fleets of keys) {
                    
                    $("#fleetList").append(
                        $("<a/>")
                            .addClass("list-group-item list-group-item-action list-group-item-primary p-2 text-left mt-2 h6 fleet-list")
                            .attr("href", "#")
                            .attr("id", fleets)
                            .attr("onclick", "populateData(" + fleets + ")")
                            .append(
                                $("<div/>")
                                    .addClass("font-weight-bold")
                                    .text(result["Fleet Data"][fleets]["Name"])
                            )
                            .append(
                                $("<div/>")
                                    .addClass("mt-1 font-italic")
                                    .text(result["Fleet Data"][fleets]["Start Date"])
                            )
                    );
                    
                }
                
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
            .text("Elapsed Time: " + incomingData["Run Time"].toFixed(2) +  " Hours")
    );

}

function formatChart (incomingData) {
    
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
    
    var currentTime = 0;
    
    for (shipData of incomingData["Snapshot History"]){
        
        var eachRow = [{v: currentTime/60, f: currentTime/60 + " Minutes"}];
        
        for (listedShips of sortedShips){
            
            if (listedShips in shipData) {
                
                eachRow.push(shipData[listedShips]);
                
            }
            else {
                
                eachRow.push(null);
                
            }
            
        }
        
        dataRows.push(eachRow);
        
        currentTime += 15;
        
    }
    
    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(setupChart);

    function setupChart () {
        
        var areaChart = new google.visualization.arrayToDataTable(dataRows);
                
        var fullArea = new google.visualization.AreaChart(document.getElementById('snapshotContainer'));
        
        var chartOptions = {title: "Ships Over Time", titleTextStyle: {color: "white", fontSize: 28, bold: false}, isStacked: true, height: 450, focusTarget: "category", backgroundColor: "transparent", hAxis: {title: "Minutes", titleTextStyle: {color: "white", fontSize: 18}, textStyle: {color: "white"}, format: "# Minutes"}, vAxis: {title:"Total Ships", titleTextStyle: {color: "white", fontSize: 18}, textStyle: {color: "white"}}, legend: {position: "top", textStyle: {color: "white"}, maxLines:1}, tooltip: {isHtml: true, showColorCode: true, trigger: "selection", textStyle: {fontSize: 11.5}}};
        
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
                                .addClass("col-3")
                                .text(incomingData[eachMember]["Join Time"])
                        )
                )
        );
        $("#fleetMembers").append(
            $("<div/>")
                .addClass("collapse card text-white bg-secondary mt-1 p-1 removable-item")
                .attr("id", "member-" + eachMember)
                .append(
                    $("<div/>")
                        .addClass("row mt-1")
                        .append(
                            $("<div/>")
                                .addClass("col-4 text-left font-weight-bold")
                                .text("Time in Roles")
                                .append (
                                    $("<div/>")
                                        .addClass("col-12 font-weight-normal")
                                        .attr("id", "roles-" + eachMember)
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-4 text-left font-weight-bold")
                                .text("Time in Ships")
                                .append (
                                    $("<div/>")
                                        .addClass("col-12 font-weight-normal")
                                        .attr("id", "ships-" + eachMember)
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-4 text-center font-weight-bold")
                                .text("Time in Fleet")
                                .append (
                                    $("<div/>")
                                        .addClass("col-12 font-weight-normal")
                                        .attr("id", "times-" + eachMember)
                                )
                        )
                )

        );
        
        for (eachRole in incomingData[eachMember]["time_in_roles"]) {
            
            $("#roles-" + eachMember).append(
                $("<div/>")
                    .addClass("ml-2")
                    .text(eachRole + ": " + (incomingData[eachMember]["time_in_roles"][eachRole]/60).toFixed(2) + " Minutes")
            );
            
        }
        
        for (eachShip in incomingData[eachMember]["time_in_ships"]) {
            
            $("#ships-" + eachMember).append(
                $("<div/>")
                    .addClass("ml-2")
                    .text(incomingData[eachMember]["time_in_ships"][eachShip]["Name"] + ": " + (incomingData[eachMember]["time_in_ships"][eachShip]["Time"]/60).toFixed(2) + " Minutes")
            );            
            
        }
        
        $("#times-" + eachMember).append(
            $("<div/>")
                .addClass("ml-2")
                .text((incomingData[eachMember]["time_in_fleet"]/60).toFixed(2) + " Minutes")
        );
        
    }
        
    
}

function populateData (fleetID) {

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

                topRow(result["Header Data"]);
                formatChart(result["Ship Data"]);
                listMembers(result["Member Data"]);
                
            }
            else {
                
                $("#headerRow").attr('hidden', true);
                $("#snapshotRow").attr('hidden', true);
                $("#breakdownRow").attr('hidden', true);
                
            }
        }
    });
}