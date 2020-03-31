def getAccessToken(appInfo, refreshToken):
    import base64
    import requests
    import json

    toHeader = appInfo["ClientID"] + ":" + appInfo["ClientSecret"]
    authHeader = "Basic " + base64.urlsafe_b64encode(toHeader.encode('utf-8')).decode()
    authBody = {"grant_type":"refresh_token","refresh_token":refreshToken}

    accessPOST = requests.post("https://login.eveonline.com/oauth/token", headers={"Accept-Charset":'UTF-8', "content-type":"application/x-www-form-urlencoded", "Authorization":authHeader}, data=authBody)
    accessResponse = json.loads(accessPOST.text)
    
    if accessPOST.status_code == 200:
    
        return accessResponse["access_token"]
        
    elif accessPOST.status_code == 400:
    
        return "Bad Token"
        
    else:
        raise Warning("ESI Error Occurred")
        
def getFleetData(characterID, accessToken):
    import requests
    import json
    
    headers = {"authorization" : "Bearer " + accessToken}

    fleetRequest = requests.get("https://esi.evetech.net/dev/characters/" + str(characterID) + "/fleet/?datasource=tranquility", headers=headers)
    
    if fleetRequest.status_code == 200:
    
        fleetData = json.loads(fleetRequest.text)
    
        return fleetData
        
    elif fleetRequest.status_code == 404 or fleetRequest.status_code == 401 or fleetRequest.status_code == 403:
    
        return False
        
    else:
    
        raise Warning("ESI Error Occurred")

def getFleetStructure(fleetID, accessToken):
    import requests
    import json
    
    headers = {"authorization" : "Bearer " + accessToken}

    fleetStructureRequest = requests.get("https://esi.evetech.net/latest/fleets/" + str(fleetID) + "/wings/?datasource=tranquility&language=en-us", headers=headers)

    if fleetStructureRequest.status_code == 200:
    
        fleetStructureData = json.loads(fleetStructureRequest.text)
    
        return fleetStructureData
        
    elif fleetStructureRequest.status_code == 404 or fleetStructureRequest.status_code == 401 or fleetStructureRequest.status_code == 403:
    
        return False
        
    else:
    
        raise Warning("ESI Error Occurred")

def getFleetMembers(fleetID, accessToken):
    import requests
    import json
    
    headers = {"authorization" : "Bearer " + accessToken}

    fleetMemberRequest = requests.get("https://esi.evetech.net/latest/fleets/" + str(fleetID) + "/members/?datasource=tranquility&language=en-us", headers=headers)

    if fleetMemberRequest.status_code == 200:
    
        fleetMemberData = json.loads(fleetMemberRequest.text)
    
        return fleetMemberData
        
    elif fleetMemberRequest.status_code == 404 or fleetMemberRequest.status_code == 401 or fleetMemberRequest.status_code == 403:
    
        return False
        
    else:
    
        raise Warning("ESI Error Occurred")

def getMassIDs(idList):
    import requests
    import json
        
    idPOST = requests.post("https://esi.evetech.net/latest/universe/names/?datasource=tranquility", data=json.dumps(idList))
    idResponse = json.loads(idPOST.text)
    
    return idResponse

def getMassAffiliations(idList):
    import requests
    import json
    
    affiliationPOST = requests.post("https://esi.evetech.net/latest/characters/affiliation/?datasource=tranquility", data=json.dumps(idList))
    affiliationResponse = json.loads(affiliationPOST.text)
    
    return affiliationResponse