jQuery(document).ready(function () {
    
    populateRoles();
    populateFleets();
    
    $(document).on("change", ".permission-setting", function() {
                
        updateAccess($(this).attr("data-core-group"), $(this).attr("data-to-modify"));
        
    });
    
    $(document).on("click", ".stop-button", function() {
                
        terminateFleet($(this).attr("data-fleet-id"));
        
    });
    
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

function updateAccess(coreGroup, toUpdate) {
    
    $(".permission-switch").prop("disabled", true);
    
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Update_Roles", "Group": coreGroup, "Access": toUpdate},
        mimeType: "application/json",
        dataType: "json",
        success: function(result){
                        
            $(".permission-switch").prop("disabled", false);
            
        }
        
    });
}

function terminateFleet(fleetID) {
    
    $(".stop-button").prop("disabled", true);
    
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Terminate_Fleet", "ID": fleetID},
        mimeType: "application/json",
        dataType: "json",
        success: function(result){
            
            populateFleets();
                        
        }
        
    });
    
    $(".stop-button").prop("disabled", false);
    
}

function populateFleets() {
    
    $("#ongoing-fleets").empty();
    $("#fleets-status-indicator").empty();
    $("#fleets-status-indicator").addClass("spinner-border");
    
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Get_Fleets"},
        mimeType: "application/json",
        dataType: "json",
        success: function(result){
            
            for (eachfleet of result["Fleet Data"]) {
                
                $("#ongoing-fleets").append(
                    $("<div/>")
                        .addClass("list-group-item list-group-item-dark small bg-dark border-secondary text-white mt-1 p-2")
                        .attr("id", "fleet-" + eachfleet["ID"])
                        .append(
                            $("<div/>")
                                .addClass("row ml-2 mr-2")
                                .append(
                                    $("<div/>")
                                        .addClass("col-3 text-left align-self-center")
                                        .text(eachfleet["Name"])
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 text-left align-self-center")
                                        .text(eachfleet["FC"])
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 text-left align-self-center")
                                        .text(eachfleet["Started"])
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 text-left align-self-center")
                                        .text(getFormattedMinutes(eachfleet["Elapsed"]))
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-2 text-left align-self-center")
                                        .text(eachfleet["Snapshots"] + " (" + eachfleet["Missing"] + ")")
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-1 align-self-center")
                                        .append(
                                            $("<button/>")
                                                .addClass("btn btn-sm btn-outline-danger btn-block stop-button")
                                                .attr("id", "terminate-" + eachfleet["ID"])
                                                .attr("data-fleet-id", eachfleet["ID"])
                                                .text("Stop")
                                        )
                                )
                        )   
                );
                
            }
            
            $("#fleets-status-indicator").empty();
            $("#fleets-status-indicator").removeClass("spinner-border");
            
        }
        
    });
    
}

function populateRoles() {
    
    $("#core-groups").empty();
    $("#roles-status-indicator").empty();
    $("#roles-status-indicator").addClass("spinner-border");
    
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: {"Action": "Get_Roles"},
        mimeType: "application/json",
        dataType: "json",
        success: function(result){
            
            for (eachGroup of result["Role Data"]) {
                
                $("#core-groups").append(
                    $("<div/>")
                        .addClass("list-group-item list-group-item-dark bg-dark border-secondary text-white p-1 mt-1")
                        .attr("id", "group-" + eachGroup["ID"])
                        .append(
                            $("<div/>")
                                .addClass("row ml-2 mr-2")
                                .append(
                                    $("<div/>")
                                        .addClass("col-8 mt-1 p-0 text-left h5 font-weight-bold align-self-center")
                                        .text(eachGroup["Name"])
                                )
                                .append(
                                    $("<div/>")
                                        .addClass("col-4 p-0 text-left align-self-center")
                                        .append(
                                            $("<div/>")
                                                .addClass("custom-control custom-switch permission-setting")
                                                .attr("data-core-group", eachGroup["ID"])
                                                .attr("data-to-modify", "FC")
                                                .append(
                                                    $("<input>")
                                                        .addClass("custom-control-input permission-switch")
                                                        .attr("type", "checkbox")
                                                        .attr("id", "fc-" + eachGroup["ID"])
                                                )
                                                .append(
                                                    $("<label/>")
                                                        .addClass("custom-control-label")
                                                        .attr("for", "fc-" + eachGroup["ID"])
                                                        .text("Fleet Commander")
                                                )
                                        )
                                        .append(
                                            $("<div/>")
                                                .addClass("custom-control custom-switch permission-setting")
                                                .attr("data-core-group", eachGroup["ID"])
                                                .attr("data-to-modify", "HR")
                                                .append(
                                                    $("<input>")
                                                        .addClass("custom-control-input permission-switch")
                                                        .attr("type", "checkbox")
                                                        .attr("id", "hr-" + eachGroup["ID"])
                                                )
                                                .append(
                                                    $("<label/>")
                                                        .addClass("custom-control-label")
                                                        .attr("for", "hr-" + eachGroup["ID"])
                                                        .text("HR")
                                                )
                                        )
                                )
                        )
                );
                
                if (eachGroup["FC"]) {
                    
                    $("#fc-" + eachGroup["ID"]).prop("checked", true);
                    
                }
                if (eachGroup["HR"]) {
                    
                    $("#hr-" + eachGroup["ID"]).prop("checked", true);
                    
                }
                
            }
            
            $("#roles-status-indicator").empty();
            $("#roles-status-indicator").removeClass("spinner-border");
            
        }
    });
    
}