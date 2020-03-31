jQuery(document).ready(function () {
    
    listData();
    
    $(document).on("click", "#start_filter", function(event) {
        
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
                
        filterData(filterOptions);
        
    });    
        
    $(document).on("click", ".a-player", function(event) {
        
        populateData(event.target.id);
        
    });
});

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
                            .addClass("list-group-item list-group-item-action list-group-item-dark bg-secondary text-white p-1 mt-1 removable-item")
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
                                            .addClass("col-2 mt-1")
                                            .text(eachMember["Name"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 mt-1")
                                            .text(eachMember["Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 mt-1")
                                            .text(eachMember["Commanded"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2 mt-1")
                                            .text(eachMember["Last Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 mt-1")
                                            .text(eachMember["Has Core"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1 mt-1")
                                            .text(eachMember["Is FC"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2")
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
                            .addClass("collapse card text-white bg-secondary mt-1 p-1 removable-item")
                            .attr("id", "extra-" + eachMember["ID"])
                            .append(
                                $("<div/>")
                                    .addClass("row font-weight-bold mt-2")
                                    .append(
                                        $("<div/>")
                                            .addClass("col-3")
                                            .text("Character Name")
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-3")
                                            .text("Corporation")
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-3")
                                            .text("Alliance")
                                    )
                            )
                            .append(
                                $("<hr>")
                            )
                            .append(
                                $("<div/>")
                                    .attr("id", "data-" + eachMember["ID"])
                            )
                    );
                }
            }
        }
    });
    
}

function filterData(filterOptions) {
        
    $("#player-data").empty();
        
    $.ajax({
        url: "dataController.php",
        type: "POST",
        data: filterOptions,
        mimeType: "application/json",
        dataType: 'json',
        success: function(result){
            if (result["Status"] == "Data Found") {
                
                for (eachMember of result["Player List"]) {
                                        
                    $("#player-data").append(
                        $("<a/>")
                            .addClass("list-group-item list-group-item-action list-group-item-dark bg-secondary text-white p-1 mt-1 removable-item")
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
                                            .addClass("col-2")
                                            .text(eachMember["Name"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2")
                                            .text(eachMember["Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2")
                                            .text(eachMember["Commanded"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2")
                                            .text(eachMember["Last Attended"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1")
                                            .text(eachMember["Has Core"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-1")
                                            .text(eachMember["Is FC"])
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-2")
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
                            .addClass("collapse card text-white bg-secondary mt-1 p-1 removable-item")
                            .attr("id", "extra-" + eachMember["ID"])
                            .append(
                                $("<div/>")
                                    .addClass("row font-weight-bold mt-2")
                                    .append(
                                        $("<div/>")
                                            .addClass("col-3")
                                            .text("Character Name")
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-3")
                                            .text("Corporation")
                                    )
                                    .append(
                                        $("<div/>")
                                            .addClass("col-3")
                                            .text("Alliance")
                                    )
                            )
                            .append(
                                $("<hr>")
                            )
                            .append(
                                $("<div/>")
                                    .attr("id", "data-" + eachMember["ID"])
                            )
                    );
                }
            }
        }
    });    
}

function populateData(populateID) {
    
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
                                    .addClass("col-3 font-weight-bold")
                                    .append(
                                        $("<a/>")
                                            .addClass("btn btn-dark btn-block mt-1")
                                            .attr("href", "https://evewho.com/character/" + eachMember["ID"])
                                            .text(eachMember["Name"])
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-3 font-weight-bold")
                                    .append(
                                        $("<a/>")
                                            .addClass("btn btn-dark btn-block mt-1")
                                            .attr("href", "https://evemaps.dotlan.net/corp/" + eachMember["Corp ID"])
                                            .text(eachMember["Corp Name"])
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-3 font-weight-bold")
                                    .append(
                                        $("<a/>")
                                            .addClass("btn btn-dark btn-block mt-1")
                                            .attr("href", "https://evemaps.dotlan.net/alliance/" + eachMember["Alliance ID"])
                                            .text(eachMember["Alliance Name"])
                                    )
                            )
                            .append(
                                $("<div/>")
                                    .addClass("col-3")
                                    .append(
                                        $("<a/>")
                                            .addClass("btn btn-dark btn-block mt-1")
                                            .attr("href", "/personalStats/?id=" + eachMember["ID"])
                                            .text("Full Stats")
                                    )
                            )
                    );
                    
                }
                
                $("#" + populateID).remove();
                
            }
        }
    });    
}
