import json
import inspect
import traceback
import os
import time
import requests
import schedule
import configparser
import copy
import base64

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
coreInfo = config["NeuCore"]
websiteInfo = config["Website"]

with open(dataFile(dataPathOverride, "/resources/data") + "/geographicInformation.json", "r") as geographyFile:
    geographicInformation = json.load(geographyFile)
        
with open(dataFile(dataPathOverride, "/resources/data") + "/TypeIDs.json", "r") as typeIDFile:
    typeIDList = json.load(typeIDFile)

def runChecks():

    try:
    
        masterDict = {}
        corporationDict = {}
        allianceDict = {}
        knownMembers = []
        knownActives = []
        
        corporationCache = {}
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Starting Analysis...\n")
        startingTime = time.perf_counter()

        sq1Database = DatabaseConnector.connect(user=databaseInfo["DatabaseUsername"], password=databaseInfo["DatabasePassword"], host=databaseInfo["DatabaseServer"] , port=int(databaseInfo["DatabasePort"]), database=databaseInfo["DatabaseName"])

        def writeToLogs(logType, logMessage):
        
            unixTime = time.time()
            
            logCursor = sq1Database.cursor(buffered=True)

            logQuery = ("INSERT INTO logs (timestamp, type, page, actor, details, trueip, forwardip) VALUES (%s, %s, 'Cronjob', '[Server Backend]', %s, 'N/A', 'N/A')")
            logCursor.execute(logQuery, (unixTime,logType,logMessage))
            
            sq1Database.commit()
            logCursor.close()
        
        initialCursor = insertCursor = sq1Database.cursor(buffered=True)
        
        initialStatement = ("SELECT fleetid, commanderid, commandername, starttime, endtime, peakmembers, memberstats FROM fleets ORDER BY starttime DESC LIMIT %s")
        initialCursor.execute(initialStatement, (int(websiteInfo["MaxTableRows"]),))
        
        for (fleetID, commanderID, commanderName, startTime, endTime, peakMembers, memberStats) in initialCursor:

            currentTime = datetime.now()
            readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
            
            print("[" + readableCurrentTime + "] Checking " + str(fleetID) + " with " + str(peakMembers) + " members...")
            
            fleetRecent = False
            
            checkTime = int(time.time())
            
            if (checkTime - int(startTime)) <= 2592000:
                fleetRecent = True
            
            memberDict = json.loads(memberStats)
            
            for eachMember in memberDict:
            
                memberCorp = memberDict[eachMember]["corp_id"]
            
                if memberCorp not in corporationDict:
                    
                    while True:
                        try:
                        
                            corpRequest = requests.get("https://esi.evetech.net/latest/corporations/" + str(memberCorp) + "/?datasource=tranquility")
                            
                            corpData = json.loads(corpRequest.text)
                            
                            corporationDict[memberCorp] = {"Name": corpData["name"], "Alliance ID": 0, "Members": corpData["member_count"], "Represented": 0, "Short Stats": {"Ticker": corpData["ticker"], "Active Members": 0}}
                            
                            if "alliance_id" in corpData:
                            
                                corporationDict[memberCorp]["Alliance ID"] = corpData["alliance_id"]
                            
                                if corpData["alliance_id"] not in allianceDict:
                                
                                    allianceRequest = requests.get("https://esi.evetech.net/latest/alliances/" + str(corpData["alliance_id"]) + "/?datasource=tranquility")
                                    
                                    allianceData = json.loads(allianceRequest.text)
                                    
                                    allianceDict[corpData["alliance_id"]] = {"Name": allianceData["name"], "Represented": 0, "Corporations": [], "Short Stats": {"Active Members": 0}}
                                                                    
                                allianceDict[corpData["alliance_id"]]["Corporations"].append(memberCorp)
                                
                            else:
                                
                                if 0 not in allianceDict:
                                    
                                    allianceDict[0] = {"Name": "[No Alliance]", "Represented": 0, "Corporations": [], "Short Stats": {"Active Members": 0}}
                                    
                                allianceDict[0]["Corporations"].append(memberCorp)
                        
                            break
                        
                        except:
                        
                            print("An Error Occurred While Trying to Get Corporation and Alliance Details for " + str(memberCorp) + ". Trying Again.")
                            
                            time.sleep(1)
                            
                memberAlliance = corporationDict[memberCorp]["Alliance ID"]
                
                if eachMember not in knownMembers:
                    corporationDict[memberCorp]["Represented"] += 1
                    allianceDict[memberAlliance]["Represented"] += 1
                    knownMembers.append(eachMember)
                                    
                if fleetRecent:
                    if eachMember not in knownActives:
                        corporationDict[memberCorp]["Short Stats"]["Active Members"] += 1
                        allianceDict[memberAlliance]["Short Stats"]["Active Members"] += 1
                        knownActives.append(eachMember)
                                    
                alreadyFound = False
                foundID = None
                
                for eachMaster in masterDict:
                    
                    for eachFound in masterDict[eachMaster]["Alts"]:
                    
                        if str(eachMember) == str(eachFound["ID"]):
                            
                            alreadyFound = True
                            foundID = eachMaster
                        
                            break
                            
                    if alreadyFound:
                    
                        break
                    
                if alreadyFound:
                                
                    if fleetID not in masterDict[foundID]["Fleets Attended"]:
                        masterDict[foundID]["Fleets Attended"].append(fleetID)
                
                else:
                                                        
                    authCode = str(coreInfo["AppID"]) + ":" + coreInfo["AppSecret"]
                    encodedString = base64.urlsafe_b64encode(authCode.encode("utf-8")).decode()
                    coreHeader = {"Authorization" : "Bearer " + encodedString}
                    
                    accountURL = coreInfo["AppURL"] + "api/app/v1/player-with-characters/" + str(eachMember)
                    groupsURL = coreInfo["AppURL"] + "api/app/v2/groups/" + str(eachMember)
                    
                    while True:
                    
                        accountRequest = requests.get(accountURL, headers=coreHeader)
                        
                        #Character has Core Account
                        if str(accountRequest.status_code) == "200":
                        
                            accountData = json.loads(accountRequest.text)
                                                        
                            masterDict["core-" + str(accountData["id"])] = {"Name":str(accountData["name"]), "Has Core":1, "Alts":[], "Fleets Attended":[fleetID], "Fleets Commanded":[], "Is FC":0, "Short Stats":{"Last Attended Fleet":0, "30 Days Attended":0, "30 Days Led":0, "Total Attended":0, "Total Led":0}}
                            
                            #Get Player Alts
                            for eachAlt in accountData["characters"]:
                            
                                if "corporation" in eachAlt and eachAlt["corporation"] != None:
                                    
                                    tempCorporationID = eachAlt["corporation"]["id"]
                                    tempCorporation = eachAlt["corporation"]["name"]
                                    
                                    if "alliance" in eachAlt["corporation"] and eachAlt["corporation"]["alliance"] != None:
                                    
                                        tempAllianceID = eachAlt["corporation"]["alliance"]["id"]
                                        tempAlliance = eachAlt["corporation"]["alliance"]["name"]
                                        
                                    else:
                                    
                                        tempAllianceID = 0
                                        tempAlliance = "[No Alliance]"
                                    
                                else:
                                    
                                    tempCorporationID = 0
                                    tempCorporation = "[No Corporation]"
                            
                                masterDict["core-" + str(accountData["id"])]["Alts"].append({
                                    "ID": str(eachAlt["id"]),
                                    "Name": str(eachAlt["name"]),
                                    "Corporation ID": str(tempCorporationID),
                                    "Corporation": str(tempCorporation),
                                    "Alliance ID": str(tempAllianceID),
                                    "Alliance": str(tempAlliance)
                                })
                            
                            #Check if Player is FC
                            while True:
                            
                                groupsRequest = requests.get(groupsURL, headers=coreHeader)
                                
                                if str(groupsRequest.status_code) == "200":
                                
                                    groupsData = json.loads(groupsRequest.text)
                                    
                                    fcGroups = coreInfo["FCGroups"].replace(" ", "").split(",")
                                    
                                    for eachGroup in groupsData:
                                        if eachGroup["name"] in fcGroups:
                                            masterDict["core-" + str(accountData["id"])]["Is FC"] = 1
                                
                                    break
                                
                                else:
                                
                                    print("An error occurred while looking for the groups of " + str(memberDict[eachMember]["name"]) + "... Trying again in a sec.")
                                    
                                    time.sleep(1)
                                    
                            time.sleep(0.5)
                            
                            break
                        
                        #Character Does Not Have Core Account
                        elif str(accountRequest.status_code) == "404":
                        
                            while True:
                                try:
                                
                                    affiliationURL = "https://esi.evetech.net/latest/characters/affiliation/?datasource=tranquility"
                                    affiliationHeaders = {"accept": "application/json", "Content-Type": "application/json"}
                                    affiliationPost = json.dumps([int(eachMember)])
                                    
                                    namesURL = "https://esi.evetech.net/latest/universe/names/?datasource=tranquility"
                                    namesPrePost = []
                                    
                                    affiliationRequest = requests.post(affiliationURL, headers=affiliationHeaders, data=affiliationPost)
                                    
                                    affiliationData = json.loads(affiliationRequest.text)
                                    
                                    altsData = None
                                    
                                    for eachAffiliation in affiliationData:
                                        if str(eachAffiliation["character_id"]) == str(eachMember):
                                            
                                            namesPrePost.append(eachAffiliation["corporation_id"])
                                            
                                            tempCorporationID = eachAffiliation["corporation_id"]
                                            
                                            if "alliance_id" in eachAffiliation:
                                            
                                                namesPrePost.append(eachAffiliation["alliance_id"])
                                                
                                                tempAllianceID = eachAffiliation["alliance_id"]
                                            
                                            else:
                                            
                                                tempAllianceID = 0
                                                tempAlliance = "[No Alliance]"
                                                
                                            if tempCorporationID in corporationCache:
                                            
                                                tempCorporation = corporationCache[tempCorporationID]["Name"]
                                                tempAllianceID = corporationCache[tempCorporationID]["Alliance ID"]
                                                tempAlliance = corporationCache[tempCorporationID]["Alliance"]
                                            
                                            else:
                                                
                                                namesPost = json.dumps(namesPrePost)
                                                    
                                                namesRequest = requests.post(namesURL, headers=affiliationHeaders, data=namesPost)
                                                namesData = json.loads(namesRequest.text)
                                                
                                                for eachName in namesData:
                                                    
                                                    if eachName["category"] == "corporation" and str(eachName["id"]) == str(tempCorporationID):
                                                    
                                                        tempCorporation = eachName["name"]
                                                    
                                                    if eachName["category"] == "alliance" and str(eachName["id"]) == str(tempAllianceID):
                                                    
                                                        tempAlliance = eachName["name"]
                                                        
                                                corporationCache[tempCorporationID] = {"Name": tempCorporation, "Alliance ID": tempAllianceID, "Alliance": tempAlliance}
                                                        
                                            altsData = {
                                                "ID": str(eachMember),
                                                "Name": str(memberDict[eachMember]["name"]),
                                                "Corporation ID": str(tempCorporationID),
                                                "Corporation": str(tempCorporation),
                                                "Alliance ID": str(tempAllianceID),
                                                "Alliance": str(tempAlliance)
                                            }
                                    
                                    break
                                    
                                except:
                                
                                    print("An error occurred while getting affiliation data for " + str(memberDict[eachMember]["name"]) + "... Trying again in a sec.")
                                    
                                    time.sleep(1)
                        
                            masterDict["character-" + str(eachMember)] = {"Name":str(memberDict[eachMember]["name"]), "Has Core":0, "Alts":[altsData], "Fleets Attended":[fleetID], "Fleets Commanded":[], "Is FC":0, "Short Stats":{"Last Attended Fleet":0, "30 Days Attended":0, "30 Days Led":0, "Total Attended":0, "Total Led":0}}
                            
                            break
                        
                        else:
                        
                            print("An error occurred while looking for the core account of " + str(memberDict[eachMember]["name"]) + "... Trying again in a sec.")
                            
                            time.sleep(1)
            
            masterDictCopy = masterDict.copy()
            for eachMaster in masterDictCopy:
            
                if fleetID in masterDictCopy[eachMaster]["Fleets Attended"]:
                
                    #Update Total Attended
                    masterDict[eachMaster]["Short Stats"]["Total Attended"] += 1
                
                    #Update 30 Days Attended
                    if fleetRecent:
                    
                        masterDict[eachMaster]["Short Stats"]["30 Days Attended"] += 1
                
                    #Check if Newest Fleet
                    if int(startTime) > masterDictCopy[eachMaster]["Short Stats"]["Last Attended Fleet"]:
                    
                        masterDict[eachMaster]["Short Stats"]["Last Attended Fleet"] = int(startTime)
                            
                    #Check if FC of This Fleet
                    for eachCheckAlt in masterDictCopy[eachMaster]["Alts"]:
                        if str(eachCheckAlt["ID"]) == str(commanderID):
                        
                            masterDict[eachMaster]["Fleets Commanded"].append(fleetID)
                            
                            masterDict[eachMaster]["Short Stats"]["Total Led"] += 1
                            
                            #Update 30 Days FCed
                            if fleetRecent:
                                masterDict[eachMaster]["Short Stats"]["30 Days Led"] += 1
                                
                            break
                                                    
        initialCursor.close()
        
        playerCounter = 0
        corpCounter = 0
        allianceCounter = 0
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Updating Database...")
        
        deleteCursor = sq1Database.cursor(buffered=True)
        
        deleteStatement = ("DELETE FROM players")
        deleteCursor.execute(deleteStatement)
        
        sq1Database.commit()
        deleteCursor.close()
        
        deleteCursor = sq1Database.cursor(buffered=True)
        
        deleteStatement = ("DELETE FROM corporations")
        deleteCursor.execute(deleteStatement)
        
        sq1Database.commit()
        deleteCursor.close()
        
        deleteCursor = sq1Database.cursor(buffered=True)
        
        deleteStatement = ("DELETE FROM alliances")
        deleteCursor.execute(deleteStatement)
        
        sq1Database.commit()
        deleteCursor.close()
        
        for eachMaster in masterDict:
                        
            updateCursor = sq1Database.cursor(buffered=True)
            
            insertStatement = ("INSERT INTO players (playerid, playername, hascore, playeralts, attendedfleets, shortstats, commandedfleets, isfc) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)")
            
            updateCursor.execute(insertStatement, (eachMaster, masterDict[eachMaster]["Name"], masterDict[eachMaster]["Has Core"], json.dumps(masterDict[eachMaster]["Alts"]), json.dumps(masterDict[eachMaster]["Fleets Attended"]), json.dumps(masterDict[eachMaster]["Short Stats"]), json.dumps(masterDict[eachMaster]["Fleets Commanded"]), masterDict[eachMaster]["Is FC"]))
            
            playerCounter += 1
            
            sq1Database.commit()
            updateCursor.close()
        
        for eachCorporation in corporationDict:
            updateCursor = sq1Database.cursor(buffered=True)
                        
            insertStatement = ("INSERT INTO corporations (corporationid, corporationname, shortstats, represented, members) VALUES (%s, %s, %s, %s, %s)")
            
            updateCursor.execute(insertStatement, (eachCorporation, corporationDict[eachCorporation]["Name"], json.dumps(corporationDict[eachCorporation]["Short Stats"]), corporationDict[eachCorporation]["Represented"], corporationDict[eachCorporation]["Members"]))
            
            corpCounter += 1
        
            sq1Database.commit()
            updateCursor.close()
        
        for eachAlliance in allianceDict:
            updateCursor = sq1Database.cursor(buffered=True)
            
            insertStatement = ("INSERT INTO alliances (allianceid, alliancename, shortstats, represented, corporations) VALUES (%s, %s, %s, %s, %s)")
            
            updateCursor.execute(insertStatement, (eachAlliance, allianceDict[eachAlliance]["Name"], json.dumps(allianceDict[eachAlliance]["Short Stats"]), allianceDict[eachAlliance]["Represented"], json.dumps(allianceDict[eachAlliance]["Corporations"])))
            
            allianceCounter += 1
        
            sq1Database.commit()
            updateCursor.close()
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Analysis Complete!\n")
        
        endingTime = time.perf_counter()
        elapsedTime = (endingTime - startingTime)/60
        
        timeElapsed = "{:0.2f}".format(elapsedTime)
        
        print(str(playerCounter) + " players found.")
        print(str(corpCounter) + " corporations found.")
        print(str(allianceCounter) + " alliances found.")
        print(str(timeElapsed) + " minutes elapsed.")
        
        writeToLogs("Analysis Complete", "Participation Analysis Complete - " + str(playerCounter) + " characters, " + str(corpCounter) + " corporations, and " + str(allianceCounter) + " alliances were found. The process took " + str(timeElapsed) + " minutes.")
        
        sq1Database.close()
                        
    except:
        traceback.print_exc()
        
        error = traceback.format_exc()
        try:
            writeToLogs("Cronjob Error", error)
        except:
            print("Failed to write a log entry!")
        
runChecks()
