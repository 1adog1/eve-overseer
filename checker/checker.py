import json
import inspect
import traceback
import os
import time
import requests
import schedule
import configparser
import copy
import threading

import ESI

from pathlib import Path
from datetime import datetime
from datetime import timezone

import mysql.connector as DatabaseConnector


#################
# PATH OVERRIDE #
#################
configPathOverride = "/var/app"
dataPathOverride = False

#If you need to run the python part of this app elsewhere for whatever reason, set the above two variables to absolute paths where the config.ini and three .json files will be contained respectively. Otherwise, keep them set to False.

def dataFile(pathOverride, extraFolder):
    
    if not pathOverride:
    
        filename = inspect.getframeinfo(inspect.currentframe()).filename
        path = os.path.join(os.path.dirname(os.path.abspath(filename)), "..")
        
        dataLocation = str(path) + extraFolder
        
        return(dataLocation)
    
    else:
        return(pathOverride)

config = configparser.ConfigParser()
if Path(configPathOverride + "/config/config.ini").is_file():
    config.read(configPathOverride + "/config/config.ini")
elif Path("../config/config.ini").is_file():
    config.read("../config/config.ini")
else:
    raise Warning("No Configuration File Found!")
databaseInfo = config["Database"]
appInfo = config["Authentication"]

with open(dataFile(dataPathOverride, "/resources/data") + "/geographicInformation.json", "r") as geographyFile:
    geographicInformation = json.load(geographyFile)
        
with open(dataFile(dataPathOverride, "/resources/data") + "/TypeIDs.json", "r") as typeIDFile:
    typeIDList = json.load(typeIDFile)
    
with open(dataFile(dataPathOverride, "/resources/data") + "/aggregateTypes.json", "r") as typeToGroupFile:
    typeToGroupList = json.load(typeToGroupFile)

def runChecks():

    try:
    
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Starting Tracking...\n")
        
        sq1Database = DatabaseConnector.connect(user=databaseInfo["DatabaseUsername"], password=databaseInfo["DatabasePassword"], host=databaseInfo["DatabaseServer"] , port=int(databaseInfo["DatabasePort"]), database=databaseInfo["DatabaseName"])

        def writeToLogs(logType, logMessage):
        
            unixTime = time.time()
            
            logCursor = sq1Database.cursor(buffered=True)

            logQuery = ("INSERT INTO logs (timestamp, type, page, actor, details, trueip, forwardip) VALUES (%s, %s, 'Checker', '[Server Backend]', %s, 'N/A', 'N/A')")
            logCursor.execute(logQuery, (unixTime,logType,logMessage))
            
            sq1Database.commit()
            logCursor.close()
        
        def stopTracking(fleetID):
            updateCursor = sq1Database.cursor(buffered=True)
            
            updateStatement = "UPDATE tracking SET status=%s WHERE fleetid=%s"
            updateCursor.execute(updateStatement, ("Stopped",fleetID))
            
            sq1Database.commit()
            updateCursor.close()
                        
        aggregateCursor = sq1Database.cursor(buffered=True)
        
        aggregateQuery = ("SELECT tracking.* FROM tracking WHERE status=%s AND fleetid NOT IN(SELECT fleetid FROM fleets)")
        aggregateCursor.execute(aggregateQuery, ("Stopped",))
        
        for (fleetID, fleetName, SRPLevel, commanderID, commanderName, startTime, fleetStatus) in aggregateCursor:
            aggregateDataCursor = sq1Database.cursor(buffered=True)
        
            aggregateDataQuery = ("SELECT * FROM snapshots WHERE fleetid=%s ORDER BY timestamp ASC")
            aggregateDataCursor.execute(aggregateDataQuery, (fleetID,))
            
            aggregatedData = {}
            shipStats = []
            listOfShips = []
            
            snapshotFound = False
            
            for (snapshotFleetID, snapshotTimestamp, snapshotRawData) in aggregateDataCursor:
                snapshotFound = True
            
                snapshotData = json.loads(snapshotRawData)
                
                tempShipStats = {}
                
                if snapshotData["Fleet"]["Has Commander"]:
                    if snapshotData["Fleet"]["Character ID"] not in aggregatedData:
                    
                        aggregatedData[snapshotData["Fleet"]["Character ID"]] = {
                            "name": snapshotData["Fleet"]["Character Name"],
                            "corp_id": snapshotData["Fleet"]["Corporation ID"],
                            "corp_name": snapshotData["Fleet"]["Corporation Name"],
                            "alliance_id": snapshotData["Fleet"]["Alliance ID"],
                            "alliance_name": snapshotData["Fleet"]["Alliance Name"],
                            "time_in_fleet": 0,
                            "join_time": snapshotTimestamp,
                            "time_in_roles": {
                                "Fleet Commander": 0,
                                "Wing Commander": 0,
                                "Squad Commander": 0,
                                "Squad Member": 0
                            },
                            "time_in_ships": {},
                            "time_in_systems": {}
                        }
                    
                    aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_fleet"] += 15
                    aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_roles"]["Fleet Commander"] += 15
                    
                    if snapshotData["Fleet"]["Ship Name"] not in listOfShips:
                        listOfShips.append(snapshotData["Fleet"]["Ship Name"])
                    
                    if snapshotData["Fleet"]["Ship Name"] not in tempShipStats:
                        tempShipStats[snapshotData["Fleet"]["Ship Name"]] = 0
                        
                    tempShipStats[snapshotData["Fleet"]["Ship Name"]] += 1
                    
                    if snapshotData["Fleet"]["Region"] not in aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_systems"]:
                        aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_systems"][snapshotData["Fleet"]["Region"]] = {"Time":0, "Systems":{}}
                        
                    if snapshotData["Fleet"]["System"] not in aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_systems"][snapshotData["Fleet"]["Region"]]["Systems"]:
                        aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_systems"][snapshotData["Fleet"]["Region"]]["Systems"][snapshotData["Fleet"]["System"]] = {"Time":0}
                    
                    aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_systems"][snapshotData["Fleet"]["Region"]]["Time"] += 15
                    
                    aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_systems"][snapshotData["Fleet"]["Region"]]["Systems"][snapshotData["Fleet"]["System"]]["Time"] += 15
                    
                    if snapshotData["Fleet"]["Ship ID"] not in aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_ships"]:
                        aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_ships"][snapshotData["Fleet"]["Ship ID"]] = {"Name": snapshotData["Fleet"]["Ship Name"], "Time":0}
                        
                    aggregatedData[snapshotData["Fleet"]["Character ID"]]["time_in_ships"][snapshotData["Fleet"]["Ship ID"]]["Time"] += 15
                    
                for aggregateWing in snapshotData["Fleet"]["Wings"]:
                    if aggregateWing["Has Commander"]:
                        if aggregateWing["Character ID"] not in aggregatedData:
                        
                            aggregatedData[aggregateWing["Character ID"]] = {
                                "name": aggregateWing["Character Name"],
                                "corp_id": aggregateWing["Corporation ID"],
                                "corp_name": aggregateWing["Corporation Name"],
                                "alliance_id": aggregateWing["Alliance ID"],
                                "alliance_name": aggregateWing["Alliance Name"],
                                "time_in_fleet": 0,
                                "join_time": snapshotTimestamp,
                                "time_in_roles": {
                                    "Fleet Commander": 0,
                                    "Wing Commander": 0,
                                    "Squad Commander": 0,
                                    "Squad Member": 0
                                },
                                "time_in_ships": {},
                                "time_in_systems": {}
                            }
                        
                        aggregatedData[aggregateWing["Character ID"]]["time_in_fleet"] += 15
                        aggregatedData[aggregateWing["Character ID"]]["time_in_roles"]["Wing Commander"] += 15
                        
                        if aggregateWing["Ship Name"] not in listOfShips:
                            listOfShips.append(aggregateWing["Ship Name"])
                        
                        if aggregateWing["Ship Name"] not in tempShipStats:
                            tempShipStats[aggregateWing["Ship Name"]] = 0
                            
                        tempShipStats[aggregateWing["Ship Name"]] += 1
                        
                        if aggregateWing["Region"] not in aggregatedData[aggregateWing["Character ID"]]["time_in_systems"]:
                            aggregatedData[aggregateWing["Character ID"]]["time_in_systems"][aggregateWing["Region"]] = {"Time":0, "Systems":{}}
                            
                        if aggregateWing["System"] not in aggregatedData[aggregateWing["Character ID"]]["time_in_systems"][aggregateWing["Region"]]["Systems"]:
                            aggregatedData[aggregateWing["Character ID"]]["time_in_systems"][aggregateWing["Region"]]["Systems"][aggregateWing["System"]] = {"Time":0}
                        
                        aggregatedData[aggregateWing["Character ID"]]["time_in_systems"][aggregateWing["Region"]]["Time"] += 15
                        
                        aggregatedData[aggregateWing["Character ID"]]["time_in_systems"][aggregateWing["Region"]]["Systems"][aggregateWing["System"]]["Time"] += 15
                        
                        if aggregateWing["Ship ID"] not in aggregatedData[aggregateWing["Character ID"]]["time_in_ships"]:
                            aggregatedData[aggregateWing["Character ID"]]["time_in_ships"][aggregateWing["Ship ID"]] = {"Name": aggregateWing["Ship Name"], "Time":0}
                            
                        aggregatedData[aggregateWing["Character ID"]]["time_in_ships"][aggregateWing["Ship ID"]]["Time"] += 15
                        
                    for aggregateSquad in aggregateWing["Squads"]:
                        if aggregateSquad["Has Commander"]:
                            if aggregateSquad["Character ID"] not in aggregatedData:
                            
                                aggregatedData[aggregateSquad["Character ID"]] = {
                                    "name": aggregateSquad["Character Name"],
                                    "corp_id": aggregateSquad["Corporation ID"],
                                    "corp_name": aggregateSquad["Corporation Name"],
                                    "alliance_id": aggregateSquad["Alliance ID"],
                                    "alliance_name": aggregateSquad["Alliance Name"],
                                    "time_in_fleet": 0,
                                    "join_time": snapshotTimestamp,
                                    "time_in_roles": {
                                        "Fleet Commander": 0,
                                        "Wing Commander": 0,
                                        "Squad Commander": 0,
                                        "Squad Member": 0
                                    },
                                    "time_in_ships": {},
                                    "time_in_systems": {}
                                }
                            
                            aggregatedData[aggregateSquad["Character ID"]]["time_in_fleet"] += 15
                            aggregatedData[aggregateSquad["Character ID"]]["time_in_roles"]["Squad Commander"] += 15
                            
                            if aggregateSquad["Ship Name"] not in listOfShips:
                                listOfShips.append(aggregateSquad["Ship Name"])
                            
                            if aggregateSquad["Ship Name"] not in tempShipStats:
                                tempShipStats[aggregateSquad["Ship Name"]] = 0
                                
                            tempShipStats[aggregateSquad["Ship Name"]] += 1
                            
                            if aggregateSquad["Region"] not in aggregatedData[aggregateSquad["Character ID"]]["time_in_systems"]:
                                aggregatedData[aggregateSquad["Character ID"]]["time_in_systems"][aggregateSquad["Region"]] = {"Time":0, "Systems":{}}
                                
                            if aggregateSquad["System"] not in aggregatedData[aggregateSquad["Character ID"]]["time_in_systems"][aggregateSquad["Region"]]["Systems"]:
                                aggregatedData[aggregateSquad["Character ID"]]["time_in_systems"][aggregateSquad["Region"]]["Systems"][aggregateSquad["System"]] = {"Time":0}
                            
                            aggregatedData[aggregateSquad["Character ID"]]["time_in_systems"][aggregateSquad["Region"]]["Time"] += 15
                            
                            aggregatedData[aggregateSquad["Character ID"]]["time_in_systems"][aggregateSquad["Region"]]["Systems"][aggregateSquad["System"]]["Time"] += 15
                            
                            if aggregateSquad["Ship ID"] not in aggregatedData[aggregateSquad["Character ID"]]["time_in_ships"]:
                                aggregatedData[aggregateSquad["Character ID"]]["time_in_ships"][aggregateSquad["Ship ID"]] = {"Name": aggregateSquad["Ship Name"], "Time":0}
                                
                            aggregatedData[aggregateSquad["Character ID"]]["time_in_ships"][aggregateSquad["Ship ID"]]["Time"] += 15
                            
                        for aggregateMembers in aggregateSquad["Members"]:
                            if aggregateMembers["Character ID"] not in aggregatedData:
                            
                                aggregatedData[aggregateMembers["Character ID"]] = {
                                    "name": aggregateMembers["Character Name"],
                                    "corp_id": aggregateMembers["Corporation ID"],
                                    "corp_name": aggregateMembers["Corporation Name"],
                                    "alliance_id": aggregateMembers["Alliance ID"],
                                    "alliance_name": aggregateMembers["Alliance Name"],
                                    "time_in_fleet": 0,
                                    "join_time": snapshotTimestamp,
                                    "time_in_roles": {
                                        "Fleet Commander": 0,
                                        "Wing Commander": 0,
                                        "Squad Commander": 0,
                                        "Squad Member": 0
                                    },
                                    "time_in_ships": {},
                                    "time_in_systems": {}
                                }
                            
                            aggregatedData[aggregateMembers["Character ID"]]["time_in_fleet"] += 15
                            aggregatedData[aggregateMembers["Character ID"]]["time_in_roles"]["Squad Member"] += 15
                            
                            if aggregateMembers["Ship Name"] not in listOfShips:
                                listOfShips.append(aggregateMembers["Ship Name"])
                            
                            if aggregateMembers["Ship Name"] not in tempShipStats:
                                tempShipStats[aggregateMembers["Ship Name"]] = 0
                                
                            tempShipStats[aggregateMembers["Ship Name"]] += 1                            
                            
                            if aggregateMembers["Region"] not in aggregatedData[aggregateMembers["Character ID"]]["time_in_systems"]:
                                aggregatedData[aggregateMembers["Character ID"]]["time_in_systems"][aggregateMembers["Region"]] = {"Time":0, "Systems":{}}
                                
                            if aggregateMembers["System"] not in aggregatedData[aggregateMembers["Character ID"]]["time_in_systems"][aggregateMembers["Region"]]["Systems"]:
                                aggregatedData[aggregateMembers["Character ID"]]["time_in_systems"][aggregateMembers["Region"]]["Systems"][aggregateMembers["System"]] = {"Time":0}
                            
                            aggregatedData[aggregateMembers["Character ID"]]["time_in_systems"][aggregateMembers["Region"]]["Time"] += 15
                            
                            aggregatedData[aggregateMembers["Character ID"]]["time_in_systems"][aggregateMembers["Region"]]["Systems"][aggregateMembers["System"]]["Time"] += 15
                            
                            if aggregateMembers["Ship ID"] not in aggregatedData[aggregateMembers["Character ID"]]["time_in_ships"]:
                                aggregatedData[aggregateMembers["Character ID"]]["time_in_ships"][aggregateMembers["Ship ID"]] = {"Name": aggregateMembers["Ship Name"], "Time":0}
                                
                            aggregatedData[aggregateMembers["Character ID"]]["time_in_ships"][aggregateMembers["Ship ID"]]["Time"] += 15
                
                shipStats.append(tempShipStats)
                endTime = snapshotTimestamp
                
            aggregateDataCursor.close()
            
            if snapshotFound:
            
                peakMembers = len(aggregatedData)
                memberStats = json.dumps(aggregatedData)
                encodedShipStats = json.dumps(shipStats)
                encodedShipList = json.dumps(listOfShips)
                
                insertCursor = sq1Database.cursor(buffered=True)
                
                insertStatement = ("INSERT INTO fleets (fleetid, fleetname, srplevel, commanderid, commandername, starttime, endtime, peakmembers, memberstats, shipstats, shiplist) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")
                insertCursor.execute(insertStatement, (fleetID, fleetName, SRPLevel, commanderID, commanderName, startTime, endTime, peakMembers, memberStats, encodedShipStats, encodedShipList))
                sq1Database.commit()
                
                insertCursor.close()
                
                cleanupCursor = sq1Database.cursor(buffered=True)
                
                cleanupStatement = ("DELETE FROM snapshots WHERE fleetid=%s")
                cleanupCursor.execute(cleanupStatement, (fleetID,))
                sq1Database.commit()
                
                cleanupCursor.close()
                
                deleteCursor = sq1Database.cursor(buffered=True)
                
                deleteStatement = ("DELETE FROM tracking WHERE fleetid=%s")
                deleteCursor.execute(deleteStatement, (fleetID,))
                sq1Database.commit()
                
                deleteCursor.close()                
                
                print("Aggregated data from " + commanderName + "'s fleet.")
                
                writeToLogs("Tracking Concluded", "Concluded tracking and aggregated data for the fleet " + str(fleetID) + ".")
                
            else:
                
                deleteCursor = sq1Database.cursor(buffered=True)
                
                deleteStatement = ("DELETE FROM tracking WHERE fleetid=%s")
                deleteCursor.execute(deleteStatement, (fleetID,))
                sq1Database.commit()
                
                deleteCursor.close()
                
                print("No snapshots found for " + commanderName + "'s fleet. Deleting Entry.")
        
        aggregateCursor.close()

        pullCursor = sq1Database.cursor(buffered=True)
        
        pullQuery = ("SELECT * FROM tracking WHERE status=%s")
        pullCursor.execute(pullQuery, ("Active",))
        
        trackingActive = False
        
        for (fleetID, fleetName, SRPLevel, commanderID, commanderName, startTime, fleetStatus) in pullCursor:
        
            print("Starting checks of " + commanderName + "'s fleet...")
            
            trackingActive = True
        
            tokenCursor = sq1Database.cursor(buffered=True)
            
            tokenQuery = ("SELECT * FROM commanders WHERE id=%s")
            tokenCursor.execute(tokenQuery, (commanderID,))
            
            for (refreshID, refreshToken) in tokenCursor:
                accessCode = ESI.getAccessToken(appInfo, refreshToken)
                
                if accessCode == "Bad Token":
                    print("Tracking was stopped for " + commanderName + " due to a bad token.")
                    writeToLogs("Fleet Stopped", "Tracking was stopped for " + commanderName + " due to a bad token.")
                    stopTracking(fleetID)
                    
                else:
                
                    fleetData = ESI.getFleetData(commanderID, accessCode)
                    
                    if not fleetData:
                        print("Tracking was stopped for " + commanderName + " due to them not being in a fleet.")
                        writeToLogs("Fleet Stopped", "Tracking was stopped for " + commanderName + " due to them not being in a fleet (Fleet Data).")
                        stopTracking(fleetID)
                    
                    elif "fleet_boss_id" in fleetData and str(fleetData["fleet_boss_id"]) == str(commanderID):
                    
                        fleetStructure = ESI.getFleetStructure(fleetID, accessCode)
                        
                        if not fleetStructure:
                            print("Tracking was stopped for " + commanderName + " due to them not being in a fleet.")
                            writeToLogs("Fleet Stopped", "Tracking was stopped for " + commanderName + " due to them not being in a fleet (Fleet Structure).")
                            stopTracking(fleetID)
                            
                        else:
                            
                            fleetBossName = commanderName
                            fleetBossID = commanderID
                            fleetStartTime = startTime
                            rawFleetStructure = {}
                            fcFound = False
                            
                            affiliationIDs = []
                            firstParsedIDs = []
                            secondParsedIDs = []
                            
                            for wings in fleetStructure:
                                rawFleetStructure[wings["id"]] = {"Wing Name": wings["name"], "Has Commander": False, "Squads": {}}
                                
                                for squads in wings["squads"]:
                                    rawFleetStructure[wings["id"]]["Squads"][squads["id"]] = {"Squad Name": squads["name"], "Has Commander": False, "Members": {}}
                                    
                            fleetMembers = ESI.getFleetMembers(fleetID, accessCode)
                            
                            if not fleetMembers:
                                print("Tracking was stopped for " + commanderName + " due to them not being in a fleet.")
                                writeToLogs("Fleet Stopped", "Tracking was stopped for " + commanderName + " due to them not being in a fleet (Fleet Members).")
                                stopTracking(fleetID)
                                
                            else:
                            
                                for members in fleetMembers:
                                    affiliationIDs.append(members["character_id"])
                                    
                                    if members["ship_type_id"] not in secondParsedIDs:
                                        secondParsedIDs.append(members["ship_type_id"])
                                    
                                    if "region" not in geographicInformation[str(members["solar_system_id"])]:
                                        regionToAdd = "Unknown Region"
                                    else:
                                        regionToAdd = geographicInformation[str(members["solar_system_id"])]["region"]
                                    
                                    defaultData = {"Character Name": "Unknown Character", "Character ID": members["character_id"], "Corporation Name": "Unknown Corporation", "Corporation ID": 0, "Alliance Name": "No Alliance", "Alliance ID": 0, "Ship Name": "Unknown Ship", "Ship ID": members["ship_type_id"], "Ship Class":"Unknown Class", "Ship Class ID": "0", "System": geographicInformation[str(members["solar_system_id"])]["name"], "Region": regionToAdd}
                                    
                                    if str(defaultData["Ship ID"]) in typeToGroupList:
                                        defaultData["Ship Class"] = typeToGroupList[str(defaultData["Ship ID"])]["Group Name"]
                                        defaultData["Ship Class ID"] = typeToGroupList[str(defaultData["Ship ID"])]["Group ID"]
                                
                                    if members["role"] == "fleet_commander":
                                        fcFound = True
                                        fcData = defaultData
                                    
                                    elif members["role"] == "wing_commander":
                                        rawFleetStructure[members["wing_id"]]["Has Commander"] = True
                                        rawFleetStructure[members["wing_id"]].update(defaultData)
                                    
                                    elif members["role"] == "squad_commander":
                                        rawFleetStructure[members["wing_id"]]["Squads"][members["squad_id"]]["Has Commander"] = True
                                        rawFleetStructure[members["wing_id"]]["Squads"][members["squad_id"]].update(defaultData)
                                    
                                    elif members["role"] == "squad_member":
                                        rawFleetStructure[members["wing_id"]]["Squads"][members["squad_id"]]["Members"][members["character_id"]] = defaultData
                                        
                                    else:
                                        raise Warning("The developer screwed something up!")
                                
                                affiliationTriples = ESI.getMassAffiliations(affiliationIDs)
                                for affiliations in affiliationTriples:
                                    if affiliations["character_id"] not in firstParsedIDs:
                                        firstParsedIDs.append(affiliations["character_id"])
                                    if affiliations["corporation_id"] not in firstParsedIDs:
                                        firstParsedIDs.append(affiliations["corporation_id"])
                                    if "alliance_id" in affiliations and affiliations["alliance_id"] not in firstParsedIDs:
                                        firstParsedIDs.append(affiliations["alliance_id"])
                                                                            
                                    if fcFound:
                                        if affiliations["character_id"] == fcData["Character ID"]:
                                            fcData["Corporation ID"] = affiliations["corporation_id"]
                                            
                                            if "alliance_id" in affiliations:
                                            
                                                fcData["Alliance ID"] = affiliations["alliance_id"]
                                                
                                    for eachWing in rawFleetStructure:
                                        if rawFleetStructure[eachWing]["Has Commander"]:
                                            if affiliations["character_id"] == rawFleetStructure[eachWing]["Character ID"]:
                                                rawFleetStructure[eachWing]["Corporation ID"] = affiliations["corporation_id"]
                                                
                                                if "alliance_id" in affiliations:
                                                
                                                    rawFleetStructure[eachWing]["Alliance ID"] = affiliations["alliance_id"]
                                                    
                                        for eachSquad in rawFleetStructure[eachWing]["Squads"]:
                                            if rawFleetStructure[eachWing]["Squads"][eachSquad]["Has Commander"]:
                                                if affiliations["character_id"] == rawFleetStructure[eachWing]["Squads"][eachSquad]["Character ID"]:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Corporation ID"] = affiliations["corporation_id"]
                                                    
                                                    if "alliance_id" in affiliations:
                                                    
                                                        rawFleetStructure[eachWing]["Squads"][eachSquad]["Alliance ID"] = affiliations["alliance_id"]
                                                        
                                            for eachMember in rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"]:
                                                if affiliations["character_id"] == rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Character ID"]:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Corporation ID"] = affiliations["corporation_id"]
                                                    
                                                    if "alliance_id" in affiliations:
                                                    
                                                        rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Alliance ID"] = affiliations["alliance_id"]
                                
                                firstMassIDs = ESI.getMassIDs(firstParsedIDs)
                                secondMassIDs = ESI.getMassIDs(secondParsedIDs)
                                
                                for firstIDs in firstMassIDs:
                                    idToFind = str(firstIDs["id"])
                                    nameToReplace = str(firstIDs["name"])
                                
                                    if fcFound:
                                        if str(fcData["Character ID"]) == idToFind:
                                            fcData["Character Name"] = nameToReplace
                                        if str(fcData["Corporation ID"]) == idToFind:
                                            fcData["Corporation Name"] = nameToReplace
                                        if str(fcData["Alliance ID"]) == idToFind:
                                            fcData["Alliance Name"] = nameToReplace
                                            
                                    for eachWing in rawFleetStructure:
                                        if rawFleetStructure[eachWing]["Has Commander"]:                                        
                                            if str(rawFleetStructure[eachWing]["Character ID"]) == idToFind:
                                                rawFleetStructure[eachWing]["Character Name"] = nameToReplace
                                            if str(rawFleetStructure[eachWing]["Corporation ID"]) == idToFind:
                                                rawFleetStructure[eachWing]["Corporation Name"] = nameToReplace
                                            if str(rawFleetStructure[eachWing]["Alliance ID"]) == idToFind:
                                                rawFleetStructure[eachWing]["Alliance Name"] = nameToReplace

                                        for eachSquad in rawFleetStructure[eachWing]["Squads"]:
                                            if rawFleetStructure[eachWing]["Squads"][eachSquad]["Has Commander"]:
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Character ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Character Name"] = nameToReplace
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Corporation ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Corporation Name"] = nameToReplace
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Alliance ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Alliance Name"] = nameToReplace
                                            
                                            for eachMember in rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"]:
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Character ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Character Name"] = nameToReplace
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Corporation ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Corporation Name"] = nameToReplace
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Alliance ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Alliance Name"] = nameToReplace
                                                    
                                for secondIDs in secondMassIDs:
                                    idToFind = str(secondIDs["id"])
                                    nameToReplace = str(secondIDs["name"])
                                
                                    if fcFound:
                                        if str(fcData["Ship ID"]) == idToFind:
                                            fcData["Ship Name"] = nameToReplace
                                            
                                    for eachWing in rawFleetStructure:
                                        if rawFleetStructure[eachWing]["Has Commander"]:                                        
                                            if str(rawFleetStructure[eachWing]["Ship ID"]) == idToFind:
                                                rawFleetStructure[eachWing]["Ship Name"] = nameToReplace

                                        for eachSquad in rawFleetStructure[eachWing]["Squads"]:
                                            if rawFleetStructure[eachWing]["Squads"][eachSquad]["Has Commander"]:
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Ship ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Ship Name"] = nameToReplace
                                            
                                            for eachMember in rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"]:
                                                if str(rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Ship ID"]) == idToFind:
                                                    rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember]["Ship Name"] = nameToReplace
                                    
                                finishingData = {"Boss Name": fleetBossName, "Boss ID": fleetBossID, "Start Time": fleetStartTime, "Fleet": {}}
                                finishingData["Fleet"]["Has Commander"] = fcFound
                                finishingData["Fleet"]["Wings"] = []
                                
                                tempWingStructure = copy.deepcopy(rawFleetStructure)
                                tempSquadStructure = copy.deepcopy(rawFleetStructure)
                                
                                if fcFound:
                                    finishingData["Fleet"].update(fcData)
                                
                                for eachWing in sorted(rawFleetStructure):
                                    tempWingStructure[eachWing]["Squads"] = []
                                    tempWingStructure[eachWing]["Wing ID"] = eachWing
                                
                                    finishingData["Fleet"]["Wings"].append(tempWingStructure[eachWing])
                                                                    
                                    for eachSquad in sorted(rawFleetStructure[eachWing]["Squads"]):
                                        
                                        for eachSecondWing in finishingData["Fleet"]["Wings"]:
                                            if eachSecondWing["Wing ID"] == eachWing:
                                                
                                                tempSquadStructure[eachWing]["Squads"][eachSquad]["Members"] = []
                                                tempSquadStructure[eachWing]["Squads"][eachSquad]["Squad ID"] = eachSquad
                                                
                                                eachSecondWing["Squads"].append(tempSquadStructure[eachWing]["Squads"][eachSquad])
                                                                                    
                                                for eachMember in sorted(rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"]):
                                                
                                                    for eachSecondSquad in eachSecondWing["Squads"]:
                                                        if eachSecondSquad["Squad ID"] == eachSquad:
                                                
                                                            eachSecondSquad["Members"].append(rawFleetStructure[eachWing]["Squads"][eachSquad]["Members"][eachMember])
                                                            
                                                            break
                                                    
                                                break
                                
                                insertCursor = sq1Database.cursor(buffered=True)
                                
                                snapshotTime = int(time.time())
                                membersToInsert = json.dumps(finishingData)
                                
                                insertStatement = ("INSERT INTO snapshots (fleetid, timestamp, fleetmembers) VALUES (%s, %s, %s)")
                                insertCursor.execute(insertStatement, (fleetID,snapshotTime,membersToInsert))
                                
                                sq1Database.commit()
                                insertCursor.close()
                    
                    else:
                        print("Tracking was stopped for " + commanderName + " due to them no longer being the boss of a fleet.")
                        writeToLogs("Fleet Stopped", "Tracking was stopped for " + commanderName + " due to them no longer being the boss of a fleet.")
                        stopTracking(fleetID)
            
            tokenCursor.close()
            
            print("Finished checking " + commanderName + "'s fleet.\n")

        pullCursor.close()
        
        if not trackingActive:
                        
            snapshotsFound = False
            
            idsToDelete = []
            
            checkCursor = sq1Database.cursor(buffered=True)
            
            checkStatement = ("SELECT snapshots.fleetid FROM snapshots WHERE fleetid IN(SELECT fleetid FROM fleets)")
            checkCursor.execute(checkStatement)
            
            for fleetIDtoDelete in checkCursor:
                snapshotsFound = True
                idsToDelete.append(int(fleetIDtoDelete[0]))
            
            checkCursor.close()
            
            if snapshotsFound:
                print("Orphan Snapshots Found! Clearing the Table.\n")
                writeToLogs("Database Cleanup", "Orphaned snapshots were found and deleted.")
                
                for eachID in idsToDelete:
                    deleteCursor = sq1Database.cursor(buffered=True)
                    
                    deleteStatement = ("DELETE FROM snapshots WHERE fleetid=%s")
                    deleteCursor.execute(deleteStatement, (eachID,))
                    
                    sq1Database.commit()
                    deleteCursor.close()
                        
        sq1Database.close()
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Tracking Complete!\n")
        
    except:
        traceback.print_exc()

        error = traceback.format_exc()
        try:
            writeToLogs("Checker Error", error)
        except:
            print("Failed to write a log entry!")

def runInParallel():
    try:
        newThread = threading.Thread(target=runChecks)
        newThread.start()
    except:
        traceback.print_exc()

def automateChecks():
    schedule.every(15).seconds.do(runInParallel)

    currentTime = datetime.now()
    readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
    print(" --- [" + readableCurrentTime + "] EVE OVERSEER - CHECKER SUCCESSFULLY STARTED --- ")
            
    while True:
        schedule.run_pending()
        time.sleep(1)
