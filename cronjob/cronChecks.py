import json
import inspect
import traceback
import os
import time
import requests
import configparser
import base64

from pathlib import Path
from datetime import datetime
from datetime import timezone

import mysql.connector as DatabaseConnector

characterDict = {}
playerDict = {}
corporationDict = {}
allianceDict = {}

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

#########################
#                       #
#  Class for Alliances  #
#                       #
#########################

class alliance:
    def __init__(self, allianceID):
    
        self.id = allianceID
        self.memberCorps = []
        
        self.memberCount = 0
        self.knownMembers = 0
        
        self.shortStats = {"Active Members": 0, "PAP Count": 0, "Recent PAP Count": 0}
    
        if str(allianceID) != "0":
        
            self.checkESI()
            
        else:
        
            self.name = "[No Alliance]"
    
    def checkESI(self):
    
        while True:
        
            try:
            
                allianceRequest = requests.get("https://esi.evetech.net/latest/alliances/" + str(self.id) + "/?datasource=tranquility")
                
                allianceData = json.loads(allianceRequest.text)
                
                self.name = allianceData["name"]
            
                break
            
            except:
                 print("An Error Occurred While Trying to Get Alliance Details for " + str(self.id) + ". Trying again in a sec...")
                 
                 time.sleep(1)
    
    def saveToDatabase(self):
    
        updateCursor = sq1Database.cursor(buffered=True)
        
        insertionVariables = (
            self.id,
            self.name,
            json.dumps(self.shortStats),
            self.knownMembers,
            json.dumps(self.memberCorps)
        )
        
        insertStatement = ("INSERT INTO alliances (allianceid, alliancename, shortstats, represented, corporations) VALUES (%s, %s, %s, %s, %s)")
        
        updateCursor.execute(insertStatement, insertionVariables)
        
        sq1Database.commit()
        updateCursor.close()
    
############################
#                          #
#  Class for Corporations  #
#                          #
############################

class corporation:
    def __init__(self, corporationID):
        
        self.id = corporationID
        self.members = []
        self.actives = []
        
        self.knownMembers = 0
                
        self.shortStats = {"Active Members": 0, "PAP Count": 0, "Recent PAP Count": 0}
        
        self.checkESI()
    
    def checkESI(self):
    
        while True:
        
            try:
            
                corpRequest = requests.get("https://esi.evetech.net/latest/corporations/" + str(self.id) + "/?datasource=tranquility")
                
                corpData = json.loads(corpRequest.text)
                
                self.name = corpData["name"]
                self.memberCount = corpData["member_count"]
                self.shortStats["Ticker"] = corpData["ticker"]
                
                if "alliance_id" in corpData:
                    
                    self.hasAlliance = True
                    self.allianceID = corpData["alliance_id"]
                    
                else:
                
                    self.hasAlliance = False
            
                break
            
            except:
                 print("An Error Occurred While Trying to Get Corporation Details for " + str(self.id) + ". Trying again in a sec...")
                 
                 time.sleep(1)
    
    def saveToDatabase(self):
    
        updateCursor = sq1Database.cursor(buffered=True)
        
        insertionVariables = (
            self.id,
            self.name,
            json.dumps(self.shortStats),
            self.knownMembers,
            self.memberCount,
        )
        
        insertStatement = ("INSERT INTO corporations (corporationid, corporationname, shortstats, represented, members) VALUES (%s, %s, %s, %s, %s)")
        
        updateCursor.execute(insertStatement, insertionVariables)
        
        sq1Database.commit()
        updateCursor.close()
    
############################################
#                                          #
#  Class for Characters and Core Accounts  #
#                                          #
############################################

class player:
    def __init__(self, characterID, characterName):
    
        self.name = characterName
        self.id = "character-" + str(characterID)
    
        self.corporations = []
        self.alliances = []
        
        self.characters = []
        
        self.fc = 0
        self.core = 0
        
        self.recentFleetsAttended = 0
        self.recentTimeAttended = 0
        self.totalFleetsAttended = 0
        self.totalTimeAttended = 0
        
        self.fleetsAttended = []
        self.fleetsCommanded = []
        
        self.recentFleetsCommanded = 0
        self.recentTimeCommanded = 0
        self.totalFleetsCommanded = 0
        self.totalTimeCommanded = 0
        
        self.shortStats = {
            "Last Attended Fleet":0, 
            "Command Stats": {
                "Fleet Command": 0, 
                "Wing Command": 0, 
                "Squad Command": 0
            }, 
            "Recent Command Stats": {
                "Fleet Command": 0,
                "Wing Command": 0,
                "Squad Command": 0
            }
        }
        
        self.checkForCore(characterID)
        
    #Gets details of a player from core
    def checkForCore(self, characterID):
    
        authCode = str(coreInfo["AppID"]) + ":" + coreInfo["AppSecret"]
        encodedString = base64.urlsafe_b64encode(authCode.encode("utf-8")).decode()
        coreHeader = {"Authorization" : "Bearer " + encodedString}
        
        accountURL = coreInfo["AppURL"] + "api/app/v1/player-with-characters/" + str(characterID)
        groupsURL = coreInfo["AppURL"] + "api/app/v2/groups/" + str(characterID)
        
        while True:
        
            time.sleep(0.4)
            accountRequest = requests.get(accountURL, headers=coreHeader)
            
            if str(accountRequest.status_code) == "200":
            
                accountData = json.loads(accountRequest.text)
                
                self.name = str(accountData["name"])
                self.core = 1
                self.id = "core-" + str(accountData["id"])
                
                for eachAlt in accountData["characters"]:
                
                    altData = {
                        "ID": str(eachAlt["id"]),
                        "Name": eachAlt["name"]
                    }
                    
                    if "corporation" in eachAlt and eachAlt["corporation"] != None:
                        altData["Corporation ID"] = eachAlt["corporation"]["id"]
                        altData["Corporation"] = eachAlt["corporation"]["name"]
                        
                        if str(eachAlt["corporation"]["id"]) not in self.corporations:
                            self.corporations.append(str(eachAlt["corporation"]["id"]))
                        
                        if "alliance" in eachAlt["corporation"] and eachAlt["corporation"]["alliance"] != None:
                            altData["Alliance ID"] = eachAlt["corporation"]["alliance"]["id"]
                            altData["Alliance"] = eachAlt["corporation"]["alliance"]["name"]
                            
                            if str(eachAlt["corporation"]["alliance"]["id"]) not in self.alliances:
                                self.alliances.append(str(eachAlt["corporation"]["alliance"]["id"]))
                        
                        else:
                            altData["Alliance ID"] = 0
                            altData["Alliance"] = "[No Alliance]"
                        
                    else:
                        altData["Corporation ID"] = 0
                        altData["Corporation"] = "[No Corporation]"
                        altData["Alliance ID"] = 0
                        altData["Alliance"] = "[No Alliance]"
                        
                    self.characters.append(altData)
                    
                while True:

                    time.sleep(0.4)
                    groupsRequest = requests.get(groupsURL, headers=coreHeader)
                    
                    if str(groupsRequest.status_code) == "200":
                    
                        groupsData = json.loads(groupsRequest.text)
                                                            
                        for eachGroup in groupsData:
                            if int(eachGroup["id"]) in fcGroups:
                                self.fc = 1
                    
                        break
                    
                    else:
                    
                        print("An error occurred while looking for the groups of " + str(memberDict[eachMember]["name"]) + "... Trying again in a sec.")
                        
                        time.sleep(1)
                
                break
                
            elif str(accountRequest.status_code) == "404":
            
                self.checkESI(characterID)
                
                break
            
            else:
            
                print("An error occurred while looking for the core account of " + str(characterID) + ". Trying again in a sec...")
                
                time.sleep(1)
    
    #Gets details of a player that doesn't have a core account.
    def checkESI(self, characterID):
    
        while True:
        
            try:
            
                affiliationURL = "https://esi.evetech.net/latest/characters/affiliation/?datasource=tranquility"
                affiliationHeaders = {"accept": "application/json", "Content-Type": "application/json"}
                affiliationPost = json.dumps([int(characterID)])
                
                namesURL = "https://esi.evetech.net/latest/universe/names/?datasource=tranquility"
                namesToPost = []
                
                affiliationRequest = requests.post(affiliationURL, headers=affiliationHeaders, data=affiliationPost)
                
                affiliationData = json.loads(affiliationRequest.text)
                
                altData = {
                    "ID": str(characterID),
                    "Name": self.name
                }
                
                for eachAffiliation in affiliationData:
                
                    if str(eachAffiliation["corporation_id"]) not in self.corporations:
                        self.corporations.append(str(eachAffiliation["corporation_id"]))
                
                    namesToPost.append(eachAffiliation["corporation_id"])
                    altData["Corporation ID"] = eachAffiliation["corporation_id"]
                    
                    if "alliance_id" in eachAffiliation:
                    
                        if str(eachAffiliation["alliance_id"]) not in self.alliances:
                            self.alliances.append(str(eachAffiliation["alliance_id"]))
                    
                        namesToPost.append(eachAffiliation["alliance_id"])
                        altData["Alliance ID"] = eachAffiliation["alliance_id"]
                        
                    else:
                    
                        altData["Alliance ID"] = 0
                        altData["Alliance"] = "[No Alliance]"
                        
                    if str(altData["Corporation ID"]) in corporationDict:
                        altData["Corporation"] = corporationDict[str(altData["Corporation ID"])].name
                        
                        if corporationDict[str(altData["Corporation ID"])].hasAlliance:
                        
                            altData["Alliance"] = allianceDict[str(corporationDict[str(altData["Corporation ID"])].allianceID)].name
                            
                        else:
                        
                            altData["Alliance"] = "[No Alliance]"
                            
                    else:
                        
                        namesRequest = requests.post(namesURL, headers=affiliationHeaders, data=json.dumps(namesToPost))
                        namesData = json.loads(namesRequest.text)
                        
                        for eachName in namesData:
                            
                            if eachName["category"] == "corporation" and str(eachName["id"]) == str(altData["Corporation ID"]):
                            
                                altData["Corporation"] = eachName["name"]
                            
                            if eachName["category"] == "alliance" and str(eachName["id"]) == str(altData["Alliance ID"]):
                            
                                altData["Alliance"] = eachName["name"]
                                
                    self.characters.append(altData)
                        
                break
            
            except:
                 print("An Error Occurred While Trying to Get Player Details for " + str(characterID) + ". Trying again in a sec...")
                 
                 time.sleep(1)
    
    def saveToDatabase(self):
    
        updateCursor = sq1Database.cursor(buffered=True)
        
        insertionVariables = (
            self.id, 
            self.name, 
            self.core, 
            json.dumps(self.corporations), 
            json.dumps(self.alliances), 
            json.dumps(self.characters), 
            self.recentFleetsAttended, 
            self.recentTimeAttended, 
            self.totalFleetsAttended, 
            self.totalTimeAttended, 
            json.dumps(self.fleetsAttended),
            json.dumps(self.shortStats),
            self.recentFleetsCommanded, 
            self.recentTimeCommanded, 
            self.totalFleetsCommanded, 
            self.totalTimeCommanded, 
            json.dumps(self.fleetsCommanded), 
            self.fc
        )
        
        insertStatement = ("INSERT INTO players (playerid, playername, hascore, playercorps, playeralliances, playeralts, recentattendedfleets, recentattendedtime, totalattendedfleets, totalattendedtime, attendedfleets, shortstats, recentcommandedfleets, recentcommandedtime, totalcommandedfleets, totalcommandedtime, commandedfleets, isfc) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")
        
        updateCursor.execute(insertStatement, insertionVariables)
        
        sq1Database.commit()
        updateCursor.close()
    
################################
#                              #
#  Start of The Actual Checks  #
#                              #
################################

def runChecks():

    try:
    
        global sq1Database
        global fcGroups
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Starting Analysis...\n")
        startingTime = time.perf_counter()
        
        sq1Database = DatabaseConnector.connect(user=databaseInfo["DatabaseUsername"], password=databaseInfo["DatabasePassword"], host=databaseInfo["DatabaseServer"] , port=int(databaseInfo["DatabasePort"]), database=databaseInfo["DatabaseName"])
        
        fcGroups = [];
        
        roleCursor = sq1Database.cursor(buffered=True)
        
        roleStatement = ("SELECT * FROM roles WHERE isfc = 1")
        roleCursor.execute(roleStatement)
        
        for (roleID, roleName, isFC, isHR) in roleCursor:
            fcGroups.append(int(roleID))
        
        roleCursor.close()

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
            
                memberCharacter = str(eachMember)
            
                memberCorp = str(memberDict[eachMember]["corp_id"])
            
                #Creates new corporation if not already on record
                if memberCorp not in corporationDict:
                    
                    corporationDict[memberCorp] = corporation(memberCorp)
                    
                if corporationDict[memberCorp].hasAlliance:
                
                    memberAlliance = str(corporationDict[memberCorp].allianceID)
                                        
                else:
                
                    memberAlliance = "0"
                
                #Creates new alliance if not already on record
                if memberAlliance not in allianceDict:
                
                    allianceDict[memberAlliance] = alliance(memberAlliance)
                    
                #Adds corporation to alliance if not already on record
                if memberCorp not in allianceDict[memberAlliance].memberCorps:
                
                    allianceDict[memberAlliance].memberCount += corporationDict[memberCorp].memberCount
                    allianceDict[memberAlliance].memberCorps.append(memberCorp)
                
                corporationDict[memberCorp].shortStats["PAP Count"] += 1
                allianceDict[memberAlliance].shortStats["PAP Count"] += 1
                
                #Adds character to corporation if not already on record
                if memberCharacter not in corporationDict[memberCorp].members:
                
                    corporationDict[memberCorp].knownMembers += 1
                    allianceDict[memberAlliance].knownMembers += 1
                    corporationDict[memberCorp].members.append(memberCharacter)
                
                if fleetRecent:
                
                    corporationDict[memberCorp].shortStats["Recent PAP Count"] += 1
                    allianceDict[memberAlliance].shortStats["Recent PAP Count"] += 1
                    
                    #Adds character to corporation active list if not already on record
                    if memberCharacter not in corporationDict[memberCorp].actives:
                        corporationDict[memberCorp].shortStats["Active Members"] += 1
                        allianceDict[memberAlliance].shortStats["Active Members"] += 1
                        corporationDict[memberCorp].actives.append(memberCharacter)
                
                #Creates new player and adds references to all associated characters if not already on record
                if memberCharacter not in characterDict:
                
                    characterDict[memberCharacter] = player(memberCharacter, str(memberDict[eachMember]["name"]))
                    playerDict[characterDict[memberCharacter].id] = characterDict[memberCharacter]
                    
                    for eachCharacter in characterDict[memberCharacter].characters:
                    
                        if str(eachCharacter["ID"]) not in characterDict:
                        
                            characterDict[str(eachCharacter["ID"])] = characterDict[memberCharacter]
                            
                if fleetID not in characterDict[memberCharacter].fleetsAttended:
                    characterDict[memberCharacter].fleetsAttended.append(fleetID)
            
            for eachPlayer in playerDict:
            
                currentPlayer = playerDict[eachPlayer]
            
                if fleetID in currentPlayer.fleetsAttended:
                
                    #Update Total Attended
                    currentPlayer.totalFleetsAttended += 1
                    
                    #Update 30 Days Attended
                    if fleetRecent:
                    
                        currentPlayer.recentFleetsAttended += 1
                        
                    #Check if Newest Fleet
                    if int(startTime) > currentPlayer.shortStats["Last Attended Fleet"]:
                    
                        currentPlayer.shortStats["Last Attended Fleet"] = int(startTime)
                        
                    #Check if FC of This Fleet
                    for eachCheckAlt in currentPlayer.characters:
                        if str(eachCheckAlt["ID"]) == str(commanderID) and eachCheckAlt["ID"] in memberDict:
                        
                            currentPlayer.fleetsCommanded.append(fleetID)
                            
                            currentPlayer.totalFleetsCommanded += 1
                            currentPlayer.totalTimeCommanded += memberDict[eachCheckAlt["ID"]]["time_in_fleet"]
                            
                            #Update 30 Days FCed
                            if fleetRecent:
                                currentPlayer.recentFleetsCommanded += 1
                                currentPlayer.recentTimeCommanded += memberDict[eachCheckAlt["ID"]]["time_in_fleet"]
                                
                            break
                            
                    #Check for Times
                    currentMaxTime = 0
                    for eachCheckAlt in currentPlayer.characters:
                        if eachCheckAlt["ID"] in memberDict and memberDict[eachCheckAlt["ID"]]["time_in_fleet"] > currentMaxTime:
                        
                            currentMaxTime = memberDict[eachCheckAlt["ID"]]["time_in_fleet"]
                            
                    currentPlayer.totalTimeAttended += currentMaxTime
                    
                    if fleetRecent:
                        currentPlayer.recentTimeAttended += currentMaxTime
                        
                    #Check for Command Positions
                    alreadyFleet = False
                    alreadyWing = False
                    alreadySquad = False
                    for eachCheckAlt in currentPlayer.characters:
                        if eachCheckAlt["ID"] in memberDict:
                        
                            if memberDict[eachCheckAlt["ID"]]["time_in_roles"]["Fleet Commander"] > 0:
                                alreadyFleet = True
                                
                            elif memberDict[eachCheckAlt["ID"]]["time_in_roles"]["Wing Commander"] > 0:
                                alreadyWing = True
                                
                            elif memberDict[eachCheckAlt["ID"]]["time_in_roles"]["Squad Commander"] > 0: 
                                alreadySquad = True
                                
                    if alreadyFleet:
                    
                        currentPlayer.shortStats["Command Stats"]["Fleet Command"] += 1
                        
                        if fleetRecent:
                        
                            currentPlayer.shortStats["Recent Command Stats"]["Fleet Command"] += 1
                            
                    elif alreadyWing:
                    
                        currentPlayer.shortStats["Command Stats"]["Wing Command"] += 1
                        
                        if fleetRecent:
                        
                            currentPlayer.shortStats["Recent Command Stats"]["Wing Command"] += 1
                            
                    elif alreadySquad:
                    
                        currentPlayer.shortStats["Command Stats"]["Squad Command"] += 1
                        
                        if fleetRecent:
                        
                            currentPlayer.shortStats["Recent Command Stats"]["Squad Command"] += 1
                            
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
        
        for eachPlayer in playerDict:
            
            playerDict[eachPlayer].saveToDatabase()
            playerCounter += 1
        
        for eachCorporation in corporationDict:
            
            corporationDict[eachCorporation].saveToDatabase()
            corpCounter += 1
        
        for eachAlliance in allianceDict:
            
            allianceDict[eachAlliance].saveToDatabase()
            allianceCounter += 1
        
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
