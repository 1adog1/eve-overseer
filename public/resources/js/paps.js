jQuery(document).ready(function () {
    
    if (pageName == "Player Data") {
        
        listData();
        
    }
    if (pageName == "Alliance Data") {
        
        getAffiliations();
        
    }
    
    $(".sorting").click(function() {
        
        filterPrecursor($(this).attr("data-sort-by"), $(this).attr("data-sort-order"));
        
    });
    
    function filterPrecursor(sortBy = "Name", sortOrder = "Ascending") {
            
        if ($("#attended").val() === "" || !isFinite($("#attended").val())) {
            
            var attended = 0;
            
        }
        else {
            
            var attended = $("#attended").val();
            
        }
        
        if ($("#commanded").val() === "" || !isFinite($("#commanded").val())) {
            
            var commanded = 0;
            
        }
        else {
            
            var commanded = $("#commanded").val();
            
        }
        
        if ($("#CharacterName").val() === "") {
            
            var nameToCheck = false;
            
        }
        else {
            
            var nameToCheck = $("#CharacterName").val();
            
        }
        
        var filterOptions = {"Action": "Filter", "Name": nameToCheck, "Attended": attended , "Commanded": commanded, "Core": $("#core").is(':checked'), "FC": $("#fc").is(':checked'), "All": $("#all").is(':checked')};
                
        filterData(filterOptions, sortBy, sortOrder);
            
    }
    
    $(document).on("click", "#start_filter", function(event) {
        filterPrecursor();
    });
        
    $(document).on("click", ".a-player", function(event) {
        
        populateData(event.target.id);
        
    });
    
});

function getAffiliations(getGraph = false, graphID = null) {
    
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Affiliations"},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                if (getGraph) {
                    
                    graphAffiliations(result["Affiliation Data"], graphID);
                    
                }
                else {
                    
                    listAffiliations(result["Affiliation Data"]);
                    
                }
            }
        }
    });
    
}

function listAffiliations(incomingData) {
    
    var allianceKeys = Object.keys(incomingData);
    
    allianceKeys.sort(function(a, b){
        return incomingData[b]["Represented"] - incomingData[a]["Represented"];
    });
    
    for (eachAlliance of allianceKeys) {
        
        allianceData = incomingData[eachAlliance];
        
        $("#affiliation-data").append(
            $("<a/>")
                .addClass("list-group-item list-group-item-action list-group-item-dark bg-dark border-secondary text-white p-1 mt-1 an-alliance")
                .attr("data-toggle", "collapse")
                .attr("href", "#corps-" + eachAlliance)
                .attr("id", "head-" + eachAlliance)
                .attr("aria-expanded", "false")
                .attr("aria-controls", "#corps-" + eachAlliance)
                .attr("data-graph-id", eachAlliance)
                .click(function() {
                    if (!$("#corps-" + $(this).attr("data-graph-id")).hasClass("show") && !$("#graph-" + $(this).attr("data-graph-id")).length) {
                        getAffiliations(true, $(this).attr("data-graph-id")); 
                    }
                })
                .append(
                    $("<div/>")
                        .addClass("row ml-2")
                        .append(
                            $("<div/>")
                                .addClass("col-1 p-0 text-left align-self-center")
                                .append(
                                    $("<img>")
                                        .addClass("img-fluid")
                                        .attr("src", " https://images.evetech.net/alliances/" + eachAlliance + "/logo?size=32")
                                )
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3 p-0 align-self-center")
                                .text(allianceData["Name"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3 p-0 align-self-center")
                                .text(allianceData["Short Stats"]["Active Members"])
                        )
                        .append(
                            $("<div/>")
                                .addClass("col-3 p-0 align-self-center")
                                .text(allianceData["Represented"])
                        )
                )
        )
        .append(
            $("<div/>")
                .addClass("collapse ml-4 mt-1 small")
                .attr("id", "corps-" + eachAlliance)
                .append(
                    $("<div/>")
                        .addClass("list-group-item list-group-item-dark bg-dark border-secondary text-white p-1")
                        .append(
                            $("<div/>")
                                .addClass("row ml-2")
                                .append(
                                    $("<div/>")
                                        .addClass("col-1 p-0 font-weight-bold text-left align-self-center")
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-3 p-0 font-weight-bold align-self-center")
                                        .text("Corporation Name")
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 p-0 font-weight-bold align-self-center")
                                        .text("Active Members")
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 p-0 font-weight-bold align-self-center")
                                        .text("Known Members")
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 p-0 font-weight-bold align-self-center")
                                        .text("Total Members")
                                )
                        )
                )
                .append(
                    $("<div/>")
                        .addClass("text-white text-left")
                        .attr("id", "corp-data-" + eachAlliance)
                )
        );
        
        var corporationKeys = Object.keys(incomingData[eachAlliance]["Corporation Data"]);
        
        corporationKeys.sort(function(a, b){
            return incomingData[eachAlliance]["Corporation Data"][b]["Represented"] - incomingData[eachAlliance]["Corporation Data"][a]["Represented"];
        });
        
        for (eachCorporation of corporationKeys) {
            
            corporationData = incomingData[eachAlliance]["Corporation Data"][eachCorporation];
            
            $("#corp-data-" + eachAlliance).append(
                $("<div/>")
                    .addClass("list-group-item list-group-item-dark bg-dark border-secondary text-white p-1 mt-1")
                    .append(
                        $("<div/>")
                            .addClass("row ml-2")
                            .append(
                                $("<div/>")
                                    .addClass("col-1 p-0 text-left align-self-center")
                                    .append(
                                        $("<img>")
                                            .addClass("img-fluid")
                                            .attr("src", " https://images.evetech.net/corporations/" + eachCorporation + "/logo?size=32")
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-3 p-0 align-self-center")
                                    .text(corporationData["Name"])
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-2 p-0 align-self-center")
                                    .text(corporationData["Short Stats"]["Active Members"])
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-2 p-0 align-self-center")
                                    .text(corporationData["Represented"])
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-2 p-0 align-self-center")
                                    .text(corporationData["Members"])
                            )
                    )
            );
            
            var activeMembers = Math.max(0, corporationData["Short Stats"]["Active Members"]);
            var knownMembers = Math.max(0, corporationData["Represented"] - corporationData["Short Stats"]["Active Members"]);
            var unknownMembers = Math.max(0, corporationData["Members"] - corporationData["Represented"]);
            
        }
    
    }
}

function graphAffiliations(incomingData, targetID) {
            
    $("#affiliation-overview").empty();

    google.charts.load("current", {packages:["corechart"]});
        
    var allianceKeys = Object.keys(incomingData);
    
    allianceKeys.sort(function(a, b){
        return incomingData[b]["Represented"] - incomingData[a]["Represented"];
    });
    
    for (eachAlliance of allianceKeys) {
        
        if (eachAlliance == targetID) {
        
            var dataRows = [["Ticker", "Active Members", {role: "tooltip"}, "Known Members", {role: "tooltip"}, "Unknown Members", {role: "tooltip"}]];
            
            allianceData = incomingData[eachAlliance];
            
            $("#affiliation-overview").append(
                $("<div/>")
                    .addClass("card bg-dark border-secondary p-0 mt-3")
                    .append(
                        $("<div/>")
                            .addClass("card-header")
                            .append(
                                $("<h4/>")
                                    .addClass("p-0 m-0")
                                    .text(allianceData["Name"])
                            )
                    )
                    .append(
                        $("<div/>")
                            .addClass("card-body p-0")
                            .attr("id", "graph-" + eachAlliance)
                    )
            );
            
            var corporationKeys = Object.keys(incomingData[eachAlliance]["Corporation Data"]);
            
            corporationKeys.sort(function(a, b){
                return incomingData[eachAlliance]["Corporation Data"][b]["Represented"] - incomingData[eachAlliance]["Corporation Data"][a]["Represented"];
            });
            
            for (eachCorporation of corporationKeys) {
                
                corporationData = incomingData[eachAlliance]["Corporation Data"][eachCorporation];
                
                var activeMembers = Math.max(0, corporationData["Short Stats"]["Active Members"]);
                var knownMembers = Math.max(0, corporationData["Represented"] - corporationData["Short Stats"]["Active Members"]);
                var unknownMembers = Math.max(0, corporationData["Members"] - corporationData["Represented"]);
                
                var activeText = corporationData["Name"] + " \nActive Members: " + activeMembers;
                var knownText = corporationData["Name"] + " \nKnown Members: " + knownMembers;
                var unknownText = corporationData["Name"] + " \nUnknown Members: " + unknownMembers;
                            
                dataRows.push([corporationData["Short Stats"]["Ticker"], activeMembers, activeText, knownMembers, knownText, unknownMembers, unknownText]);
            }
            
            google.charts.setOnLoadCallback(setupBarChart);

            function setupBarChart() {
                
                var barChart = new google.visualization.arrayToDataTable(dataRows);
                        
                var fullBar = new google.visualization.BarChart(document.getElementById("graph-" + eachAlliance));
                
                var chartOptions = {allowHtml: true, chartArea: {width: "65%", top: "5%",left: "10%"}, height: (60 + (dataRows.length * 30)), titlePosition: "none", isStacked: "relative", backgroundColor: "transparent", hAxis: {format: "percent", textStyle: {fontSize: 12, color: "white"}}, vAxis: {textStyle: {fontSize: 12, color: "white"}}, tooltip: {textStyle: {fontSize: 12}, isHtml: true}, legend: {textStyle: {fontSize: 14, color: "white"}}, colors: ["green", "orange", "red"]};
                
                fullBar.draw(barChart, chartOptions);
                
            }
            
            break;
        
        }
    
    }
        
}

function listData() {
        
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "List"},
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                for (eachMember of result["Player List"]) {
                                        
                    $("#player-data").append(
                        $("<a/>")
                            .addClass("list-group-item list-group-item-action list-group-item-dark bg-dark border-secondary text-white p-1 mt-1 removable-item small")
                            .attr("data-toggle", "collapse")
                            .attr("href", "#extra-" + eachMember["ID"])
                            .attr("id", "head-" + eachMember["ID"])
                            .attr("aria-expanded", "false")
                            .attr("aria-controls", "#extra-" + eachMember["ID"])
                            .append(
                                $("<div/>")
                                    .addClass("row ml-2")
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 p-0 text-left align-self-center")
                                            .text(eachMember["Name"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Recent Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Total Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Recent Commanded"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Total Commanded"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 p-0 align-self-center")
                                            .text(eachMember["Last Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 align-self-center")
                                            .text(eachMember["Has Core"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 align-self-center")
                                            .text(eachMember["Is FC"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 align-self-center text-center")
                                            .append(
                                                $("<btn/>")
                                                    .addClass("btn btn-sm btn-primary a-player")
                                                    .attr("id", eachMember["ID"])
                                                    .text("Get Alts")
                                            )
                                    )
                            )
                    );
                    $("#player-data").append(
                        $("<div/>")
                            .addClass("collapse card border-secondary bg-dark ml-4 mt-1 p-1 removable-item")
                            .attr("id", "extra-" + eachMember["ID"])
                            .append(
                                $("<div/>")
                                    .addClass("card-header text-white small text-left p-1")
                                    .append(
                                        $("<div/>")
                                            .addClass("row font-weight-bold ml-2 mt-2")
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 p-1")
                                                    .text("Character Name")
                                            )
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 p-0")
                                                    .text("Corporation")
                                            )
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 p-1")
                                                    .text("Alliance")
                                            )
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 text-center")
                                                    .text("Full Stats")
                                            )
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("card-body text-white small text-left p-1")
                                    .attr("id", "data-" + eachMember["ID"])
                            )
                    );
                }
                
                $("#counting_container").text(result["Counter"] + " Account(s) Loaded");
                
            }
        }
    });
    
}

function filterData(filterOptions, sortBy, sortOrder) {
        
    $("#player-data").empty();
        
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: filterOptions,
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                if (sortBy == "Name") {
                    if (sortOrder == "Ascending") {
                        
                        result["Player List"].sort(function(a, b){
                            return a["Name"].localeCompare(b["Name"]);
                        });
                        
                    }
                    if (sortOrder == "Descending") {
                        
                        result["Player List"].sort(function(a, b){
                            return b["Name"].localeCompare(a["Name"]);
                        });
                        
                    }
                }
                
                if (sortBy == "RecentAttended") {
                    if (sortOrder == "Ascending") {
                        
                        result["Player List"].sort(function(a, b){
                            return b["Recent Attended"] - a["Recent Attended"];
                        });
                        
                    }
                    if (sortOrder == "Descending") {
                        
                        result["Player List"].sort(function(a, b){
                            return a["Recent Attended"] - b["Recent Attended"];
                        });
                        
                    }
                }
                
                if (sortBy == "Attended") {
                    if (sortOrder == "Ascending") {
                        
                        result["Player List"].sort(function(a, b){
                            return b["Total Attended"] - a["Total Attended"];
                        });
                        
                    }
                    if (sortOrder == "Descending") {
                        
                        result["Player List"].sort(function(a, b){
                            return a["Total Attended"] - b["Total Attended"];
                        });
                        
                    }
                }
                
                if (sortBy == "RecentLed") {
                    if (sortOrder == "Ascending") {
                        
                        result["Player List"].sort(function(a, b){
                            return b["Recent Commanded"] - a["Recent Commanded"];
                        });
                        
                    }
                    if (sortOrder == "Descending") {
                        
                        result["Player List"].sort(function(a, b){
                            return a["Recent Commanded"] - b["Recent Commanded"];
                        });
                        
                    }
                }
                
                if (sortBy == "Led") {
                    if (sortOrder == "Ascending") {
                        
                        result["Player List"].sort(function(a, b){
                            return b["Total Commanded"] - a["Total Commanded"];
                        });
                        
                    }
                    if (sortOrder == "Descending") {
                        
                        result["Player List"].sort(function(a, b){
                            return a["Total Commanded"] - b["Total Commanded"];
                        });
                        
                    }
                }
                
                if (sortBy == "LastActive") {
                    if (sortOrder == "Ascending") {
                        
                        result["Player List"].sort(function(a, b){
                            return b["Last Attended Time"] - a["Last Attended Time"];
                        });
                        
                    }
                    if (sortOrder == "Descending") {
                        
                        result["Player List"].sort(function(a, b){
                            return a["Last Attended Time"] - b["Last Attended Time"];
                        });
                        
                    }
                }
                
                for (eachMember of result["Player List"]) {
                                        
                    $("#player-data").append(
                        $("<a/>")
                            .addClass("list-group-item list-group-item-action list-group-item-dark bg-dark border-secondary text-white p-1 mt-1 removable-item small")
                            .attr("data-toggle", "collapse")
                            .attr("href", "#extra-" + eachMember["ID"])
                            .attr("id", "head-" + eachMember["ID"])
                            .attr("aria-expanded", "false")
                            .attr("aria-controls", "#extra-" + eachMember["ID"])
                            .append(
                                $("<div/>")
                                    .addClass("row ml-2")
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 p-0 text-left align-self-center")
                                            .text(eachMember["Name"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Recent Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Total Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Recent Commanded"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 p-0 align-self-center")
                                            .text(eachMember["Total Commanded"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 p-0 align-self-center")
                                            .text(eachMember["Last Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 align-self-center")
                                            .text(eachMember["Has Core"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 align-self-center")
                                            .text(eachMember["Is FC"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 align-self-center text-center")
                                            .append(
                                                $("<btn/>")
                                                    .addClass("btn btn-sm btn-primary a-player")
                                                    .attr("id", eachMember["ID"])
                                                    .text("Get Alts")
                                            )
                                    )
                            )
                    );
                    $("#player-data").append(
                        $("<div/>")
                            .addClass("collapse card border-secondary bg-dark ml-4 mt-1 p-1 removable-item")
                            .attr("id", "extra-" + eachMember["ID"])
                            .append(
                                $("<div/>")
                                    .addClass("card-header text-white small text-left p-1")
                                    .append(
                                        $("<div/>")
                                            .addClass("row font-weight-bold ml-2 mt-2")
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 p-1")
                                                    .text("Character Name")
                                            )
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 p-0")
                                                    .text("Corporation")
                                            )
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 p-1")
                                                    .text("Alliance")
                                            )
                                            .append(
                                                $("<div/>")
                                                    .addClass("col-3 text-center")
                                                    .text("Full Stats")
                                            )
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("card-body text-white small text-left p-1")
                                    .attr("id", "data-" + eachMember["ID"])
                            )
                    );
                }
                
                $("#counting_container").text(result["Counter"] + " Account(s) Loaded");
                
            }
            if (result["Status"] == "No Data") {
                $("#counting_container").text(result["Counter"] + " Account(s) Loaded");
            }
        }
    });    
}

function populateData(populateID) {
    
    if (!$("#" + populateID).hasClass("disabled")) {
    
        $("#" + populateID).text("");
        $("#" + populateID).removeClass("btn btn-sm btn-primary");
        $("#" + populateID).addClass("spinner-border");
        
        $.ajax({
            url: "dataController.php",
            type: "POST",
            data: {"Action": "Get Alts", "ID": populateID},
            mimeType: "application/json",
            dataType: 'json',
            success: function(result){
                if (result["Status"] == "Data Found") {
                    
                    $("#data-" + populateID).empty();
                    
                    for (eachMember of result["Alt List"]) {
                        
                        $("#data-" + populateID).append(
                            $("<div/>")
                                .addClass("row")
                                .append(
                                    $("<div/>")
                                        .addClass("col-3 pl-3 mt-2")
                                        .append(
                                            $("<a/>")
                                                .addClass("ml-2 text-white")
                                                .attr("href", "https://evewho.com/character/" + eachMember["ID"])
                                                .text(eachMember["Name"])
                                        )
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-3 mt-2")
                                        .append(
                                            $("<a/>")
                                                .addClass("text-white")
                                                .attr("href", "https://evemaps.dotlan.net/corp/" + eachMember["Corp ID"])
                                                .text(eachMember["Corp Name"])
                                        )
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-3 mt-2")
                                        .append(
                                            $("<a/>")
                                                .addClass("text-white")
                                                .attr("href", "https://evemaps.dotlan.net/alliance/" + eachMember["Alliance ID"])
                                                .text(eachMember["Alliance Name"])
                                        )
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-3")
                                        .append(
                                            $("<a/>")
                                                .addClass("btn btn-sm btn-primary btn-block mt-1")
                                                .attr("href", "/personalStats/?id=" + eachMember["ID"])
                                                .text("Full Stats")
                                        )
                                )
                        );
                        
                    }
                    
                    $("#" + populateID).removeClass("spinner-border");
                    $("#" + populateID).text("Got Alts");
                    $("#" + populateID).addClass("btn btn-sm btn-success disabled");
                    $("#" + populateID).attr("aria-disabled", "true")
                    
                }
            }
        });
        
    }
}
