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

#If you need to run the python part of this app elsewhere for whatever reason, set the above two variables to absolute paths where the config.ini and two .json files will be contained respectively. Otherwise, keep them set to False.

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
elif Path("./config/config.ini").is_file():
    config.read("./config/config.ini")
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
            
                alreadyFound = False
                foundID = None
                
                for eachMaster in masterDict:
                    
                    if str(eachMember) in masterDict[eachMaster]["Alts"]:
                        
                        alreadyFound = True
                        foundID = eachMaster
                    
                        break
                    
                if alreadyFound:
                                
                    if fleetID not in masterDict[foundID]["Fleets Attended"]:
                        masterDict[foundID]["Fleets Attended"].append(fleetID)
                
                else:
                                                        
                    authCode = str(coreInfo["AppID"]) + ":" + coreInfo["AppSecret"]
                    encodedString = base64.urlsafe_b64encode(authCode.encode("utf-8")).decode()
                    coreHeader = {"Authorization" : "Bearer " + encodedString}
                    
                    accountURL = coreInfo["AppURL"] + "api/app/v1/player/" + str(eachMember)
                    altsURL = coreInfo["AppURL"] + "api/app/v1/characters/" + str(eachMember)
                    groupsURL = coreInfo["AppURL"] + "api/app/v2/groups/" + str(eachMember)
                    
                    while True:
                    
                        accountRequest = requests.get(accountURL, headers=coreHeader)
                        
                        #Character has Core Account
                        if str(accountRequest.status_code) == "200":
                        
                            accountData = json.loads(accountRequest.text)
                                                        
                            masterDict["core-" + str(accountData["id"])] = {"Name":str(accountData["name"]), "Has Core":1, "Alts":[], "Fleets Attended":[fleetID], "Fleets Commanded":[], "Is FC":0, "Short Stats":{"Last Attended Fleet":0, "30 Days Attended":0, "30 Days Led":0}}
                            
                            #Get Player Alts
                            while True:
                            
                                altsRequest = requests.get(altsURL, headers=coreHeader)
                            
                                if str(altsRequest.status_code) == "200":
                                
                                    altsData = json.loads(altsRequest.text)
                                    
                                    for eachAlt in altsData:
                                        masterDict["core-" + str(accountData["id"])]["Alts"].append(str(eachAlt["id"]))
                                
                                    break
                                
                                else:
                                
                                    print("An error occurred while looking for the alts of " + str(memberDict[eachMember]["name"]) + "... Trying again in a sec.")
                                    
                                    time.sleep(1)                                    
                            
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
                            
                            break
                        
                        #Character Does Not Have Core Account
                        elif str(accountRequest.status_code) == "404":
                        
                            masterDict["character-" + str(eachMember)] = {"Name":str(memberDict[eachMember]["name"]), "Has Core":0, "Alts":[str(eachMember)], "Fleets Attended":[fleetID], "Fleets Commanded":[], "Is FC":0, "Short Stats":{"Last Attended Fleet":0, "30 Days Attended":0, "30 Days Led":0}}
                            
                            break
                        
                        else:
                        
                            print("An error occurred while looking for the core account of " + str(memberDict[eachMember]["name"]) + "... Trying again in a sec.")
                            
                            time.sleep(1)
                    
                    time.sleep(0.3)
            
            masterDictCopy = masterDict.copy()
            for eachMaster in masterDictCopy:
            
                if fleetID in masterDictCopy[eachMaster]["Fleets Attended"]:
                
                    #Update 30 Days Attended
                    if fleetRecent:
                        masterDict[eachMaster]["Short Stats"]["30 Days Attended"] += 1
                
                    #Check if Newest Fleet
                    if int(startTime) > masterDictCopy[eachMaster]["Short Stats"]["Last Attended Fleet"]:
                        masterDict[eachMaster]["Short Stats"]["Last Attended Fleet"] = int(startTime)
                            
                    #Check if FC of This Fleet
                    for eachCheckAlt in masterDictCopy[eachMaster]["Alts"]:
                        if str(eachCheckAlt) == str(commanderID):
                            masterDict[eachMaster]["Fleets Commanded"].append(fleetID)
                            
                            #Update 30 Days FCed
                            if fleetRecent:
                                masterDict[eachMaster]["Short Stats"]["30 Days Led"] += 1
                                
                            break
                                                    
        initialCursor.close()
        
        addCounter = 0
        updateCounter = 0
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Updating Database...")
        
        for eachMaster in masterDict:
            
            checkCursor = sq1Database.cursor(buffered=True)
            
            checkStatement = ("SELECT playername FROM players WHERE playerid=%s")
            checkCursor.execute(checkStatement, (eachMaster,))
            
            playerFound = False
            
            for throwaway in checkCursor:
                playerFound = True
            
            checkCursor.close()
            
            updateCursor = sq1Database.cursor(buffered=True)
            
            if playerFound:
                
                updateStatement = ("UPDATE players SET playername=%s, hascore=%s, playeralts=%s, attendedfleets=%s, shortstats=%s, commandedfleets=%s, isfc=%s WHERE playerid=%s")
                updateCursor.execute(updateStatement, (masterDict[eachMaster]["Name"], masterDict[eachMaster]["Has Core"], json.dumps(masterDict[eachMaster]["Alts"]), json.dumps(masterDict[eachMaster]["Fleets Attended"]), json.dumps(masterDict[eachMaster]["Short Stats"]), json.dumps(masterDict[eachMaster]["Fleets Commanded"]), masterDict[eachMaster]["Is FC"], eachMaster))
                
                updateCounter += 1
                
            else:
            
                insertStatement = ("INSERT INTO players (playerid, playername, hascore, playeralts, attendedfleets, shortstats, commandedfleets, isfc) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)")
                updateCursor.execute(insertStatement, (eachMaster, masterDict[eachMaster]["Name"], masterDict[eachMaster]["Has Core"], json.dumps(masterDict[eachMaster]["Alts"]), json.dumps(masterDict[eachMaster]["Fleets Attended"]), json.dumps(masterDict[eachMaster]["Short Stats"]), json.dumps(masterDict[eachMaster]["Fleets Commanded"]), masterDict[eachMaster]["Is FC"]))
                
                addCounter += 1
            
            sq1Database.commit()
            updateCursor.close()
        
        currentTime = datetime.now()
        readableCurrentTime = currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")
        print("[" + readableCurrentTime + "] Analysis Complete!\n")
        
        endingTime = time.perf_counter()
        elapsedTime = (endingTime - startingTime)/60
        
        timeElapsed = "{:0.2f}".format(elapsedTime)
        
        print(str(addCounter) + " added.")
        print(str(updateCounter) + " updated.")
        print(str(timeElapsed) + " minutes elapsed.")
        
        writeToLogs("Analysis Complete", "Participation Analysis Complete - " + str(addCounter) + " characters were added, and " + str(updateCounter) + " characters were updated. The process took " + str(timeElapsed) + " minutes.")
        
        sq1Database.close()
        
    except:
        traceback.print_exc()
        
        error = traceback.format_exc()
        try:
            writeToLogs("Cronjob Error", error)
        except:
            print("Failed to write a log entry!")
        
runChecks()
