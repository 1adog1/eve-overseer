jQuery(document).ready(function () {
    populateData();
});


function topRow (incomingData) {
    
    $("#characterOverview").append(
        $("<div/>")
            .text("Name: ")
            .append(
                $("<a/>")
                    .attr("href", "https://zkillboard.com/character/" + incomingData["ID"] + "/")
                    .text(incomingData["Name"])
            )
    );
    $("#characterOverview").append(
        $("<div/>")
            .text("Corporation: ")
            .append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/corp/" + incomingData["Corporation ID"])
                    .text(incomingData["Corporation"])
            )
    );
    $("#characterOverview").append(
        $("<div/>")
            .text("Alliance: ")
            .append(
                $("<a/>")
                    .attr("href", "http://evemaps.dotlan.net/alliance/" + incomingData["Alliance ID"])
                    .text(incomingData["Alliance"])
            )
    );
    
    var time_in_fleets = incomingData["Total Time"] / 3600;
    
    $("#fleetOverview").append(
        $("<div/>")
            .text("Fleets Attended: " + incomingData["Total Fleets"])
    );
    $("#fleetOverview").append(
        $("<div/>")
            .text("Time in Fleets: " + time_in_fleets.toFixed(2) + " Hours")
    );
    $("#fleetOverview").append(
        $("<div/>")
            .text("Last Fleet Attended: " + incomingData["Last Fleet"])
    );
    
}

function formatCalendar (incomingData) {
    
    var dataRows = [];
    
    for (dateData in incomingData){
                
        dataRows.push([new Date(dateData*1000), incomingData[dateData]/60]);
        
    }
    
    google.charts.load("current", {packages:["calendar"]});
    google.charts.setOnLoadCallback(setupCalendar);

    function setupCalendar () {
        
        var calendarChart = new google.visualization.DataTable();
        
        calendarChart.addColumn({type: 'date', id: 'Date'});
        calendarChart.addColumn({type: 'number', id: 'Time in Fleets'});
        calendarChart.addRows(dataRows);
        
        var fullCalendar = new google.visualization.Calendar(document.getElementById('calendarContainer'));

        $("#calendarContainer").height(325);
        
        fullCalendar.draw(calendarChart, {title: "Minutes Spent In Fleets", colorAxis: {values: [0, 240, 300], colors: ["#e7f5fe", "#586674", "ForestGreen"], minValue: 0, maxValue: 300}});
        
        //var calendarLayout = fullCalendar.getChartLayoutInterface();

        //var divHeight = calendarLayout.getBoundingBox('chartarea').height
                
    }

    
}

function formatShips (incomingData) {

    var dataRows = [["Ship Name", "Hours In Fleets"]];
    
    for (shipData in incomingData){
                
        dataRows.push([shipData, incomingData[shipData]/3600]);
        
    }
    
    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(setupShips);
    
    function setupShips () {
    
        var shipChart = google.visualization.arrayToDataTable(dataRows);
        
        var fullShips = new google.visualization.PieChart(document.getElementById('shipContainer'));
        fullShips.draw(shipChart, {title: "Ship Usage (Hours)", titleTextStyle: {color: "white", fontSize: 28, bold: false}, pieHole: 0.4, pieSliceBorderColor: "transparent", backgroundColor: "transparent", legend: {textStyle: {color: "white"}}, height: 400, tooltip: {trigger: "selection"}, pieSliceText: "value"});
    
    }
    
}

function formatTimezones (incomingData) {
    
    var dataRows = [["Timezone", "Hours in Fleets", {role: "style"}]];
    
    var pickColors = {"EUTZ": "ForestGreen", "USTZ": "SteelBlue", "AUTZ": "OrangeRed"}
    
    for (eachTimezone in incomingData){
                
        dataRows.push([eachTimezone, incomingData[eachTimezone]/3600, pickColors[eachTimezone]]);
        
    }

    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(setupTimezones);
    
    function setupTimezones () {
    
        var timezoneChart = google.visualization.arrayToDataTable(dataRows);
        
        var fullTimezones = new google.visualization.BarChart(document.getElementById('timezoneContainer'));
        fullTimezones.draw(timezoneChart, {title: "Timezone Activity (Hours)", titleTextStyle: {color: "white", fontSize: 28, bold: false},vAxis: {textStyle: {color: "white"}}, hAxis: {textStyle: {color: "white"}}, legend: {position: "none"}, backgroundColor: "transparent"});
    
    }
    
}

function formatRoles (incomingData) {
    
    var dataRows = [["Roles", "Hours in Role", {role: "style"}]];
    
    var bootstrapColors = getComputedStyle(document.body);
    
    var pickColors = {"Fleet": bootstrapColors.getPropertyValue('--danger'), "Wing": bootstrapColors.getPropertyValue('--primary'), "Squad": bootstrapColors.getPropertyValue('--success')}
    
    for (eachRole in incomingData){
                
        dataRows.push([eachRole, incomingData[eachRole]/3600, pickColors[eachRole]]);
        
    }

    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(setupRoles);
    
    function setupRoles () {
    
        var rolesChart = google.visualization.arrayToDataTable(dataRows);
        
        var fullRoles = new google.visualization.BarChart(document.getElementById('rolesContainer'));
        fullRoles.draw(rolesChart, {title: "Time as Commander (Hours)", titleTextStyle: {color: "white", fontSize: 28, bold: false},vAxis: {textStyle: {color: "white"}}, hAxis: {textStyle: {color: "white"}}, legend: {position: "none"}, backgroundColor: "transparent"});
    
    }
    
}

function listFleets (incomingData) {
    
    for (eachFleet of incomingData) {
        
        $("#previousFleets").append(
            $("<a/>")
                .addClass("list-group-item list-group-item-action text-white bg-secondary p-1 mt-1")
                .attr("data-toggle", "collapse")
                .attr("href", "#fleet-" + eachFleet["Fleet ID"])
                .attr("aria-expanded", "false")
                .attr("aria-controls", "fleet-" + eachFleet["Fleet ID"])
                .append(
                    $("<div/>")
                        .addClass("row ml-2")
                        .append(
                            $("<div/>")
                                .addClass("col-5")
                                .text(eachFleet["Fleet Name"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-4")
                                .text(eachFleet["Start Date"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3")
                                .text((eachFleet["time_in_fleet"]/60).toFixed(2) + " Minutes")
                        )                        
                )
        );
        $("#previousFleets").append(
            $("<div/>")
                .addClass("collapse card text-white bg-secondary mt-1 p-1")
                .attr("id", "fleet-" + eachFleet["Fleet ID"])
                .append(
                    $("<div/>")
                        .addClass("row mt-1")
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center font-weight-bold")
                                .text("Commander")
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center font-weight-bold")
                                .text("SRP Level")
                        )
                )
                .append(
                    $("<div/>")
                        .addClass("row")
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center")
                                .text(eachFleet["Fleet Boss"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center")
                                .text(eachFleet["SRP Level"])
                        )
                )                
                .append(
                    $("<div/>")
                        .addClass("row mt-4")
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center font-weight-bold")
                                .text("Time in Roles")
                                .append (
                                    $("<div/>")
                                        .addClass("col-12 text-left font-weight-normal")
                                        .attr("id", "roles-" + eachFleet["Fleet ID"])
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center font-weight-bold")
                                .text("Time in Ships")
                                .append (
                                    $("<div/>")
                                        .addClass("col-12 text-left font-weight-normal")
                                        .attr("id", "ships-" + eachFleet["Fleet ID"])
                                )
                        )
                )
                .append(
                    $("<div/>")
                        .addClass("row mt-4")
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center font-weight-bold")
                                .text("Fleet Started")
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center font-weight-bold")
                                .text("Fleet Ended")
                        )
                )
                .append(
                    $("<div/>")
                        .addClass("row")
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center")
                                .text(eachFleet["Start Time"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-6 text-center")
                                .text(eachFleet["End Time"])
                        )
                )
        );
        
        for (eachRole in eachFleet["time_in_roles"]) {
            
            $("#roles-" + eachFleet["Fleet ID"]).append(
                $("<div/>")
                    .text(eachRole + ": " + (eachFleet["time_in_roles"][eachRole]/60).toFixed(2) + " min")
            );
            
        }
        
        for (eachShip in eachFleet["time_in_ships"]) {
            
            $("#ships-" + eachFleet["Fleet ID"]).append(
                $("<div/>")
                    .text(eachFleet["time_in_ships"][eachShip]["Name"] + ": " + (eachFleet["time_in_ships"][eachShip]["Time"]/60).toFixed(2) + " min")
            );            
            
        }
        
    }
        
    
}

function populateData () {
    
    var currentURL = new URL(window.location.href);
    var lookupURL = currentURL.searchParams.get("id");

    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Get", "ID": lookupURL},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                $("#calendarRow").removeAttr('hidden');
                $("#breakdownRow").removeAttr('hidden');

                topRow(result["Header Data"]);
                formatCalendar(result["Dates"]);
                formatShips(result["Ships"]);
                formatTimezones(result["Timezones"]);
                formatRoles(result["Roles"]);
                listFleets(result["Fleets"]);

            }
            else if (result["Status"] == "No Data") {
                                
                topRow(result["Header Data"]);
                
            }
        }
    });
}