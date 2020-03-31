# Eve Overseer

Ever Overseer is a participation tracking application designed for use with [Brave Neucore](https://github.com/bravecollective/brvneucore). It gives real-time statistics when hosting fleets, and stats on the fleet and player level for previous fleets. It can also track participation for core accounts and individual players alike. 

## Requirements
* Apache ≥ 2.4
* PHP ≥ 7
  * The `pdo_mysql` Extension
  * The `curl` Extension
  * The `php_openssl.dll` Extension
* Python ≥ 3.5
  * [requests](https://pypi.org/project/requests/)
  * [schedule](https://pypi.org/project/schedule/)
  * [Python MySQL Connector](https://dev.mysql.com/downloads/connector/python/)
* An SQL Server
  * If you are using MySQL, the Authentication Method **MUST** be the Legacy Version. PDO does not support the use of `caching_sha2_password` Authentication. 
* A Registered Eve Online Application. 
  * This can be setup via the [Eve Online Developers Site](https://developers.eveonline.com/).

## Webapp Setup
* Setup the Configuration File in `/config/config.ini` as needed.
 * If you need to move this file you'll need to change the path it's accessed from in `/config/config.php`
* Ensure Apache is configured to allow `.htaccess` files with use of the rewrite engine.
* Ensure Apache is configured to allow https connections.
* Ensure PHP is configured to allow `.user.ini` files. 
* Set `/public` as Document Root in Apache.

## Checker Setup
The checker is responsible for tracking fleet data in real time. 
* After setting up the `/config/config.ini` file and connecting to the webapp at least once, you can run `/checker/automaticallyRunChecks.py` to begin tracking fleet data. 
 * Starting the tracker before connecting to the webapp will cause errors until the webapp is connected to, as the database is not yet generated. 
 * If (and ONLY if) you can run a script every 15 seconds, to the second, then you can use `/checker/manuallyRunChecks.py`. Runs of this script MUST start exactly 15 seconds apart, regardless of the status of previous runs. 

## Cronjob Setup
The cronjob is used to translate fleet stats into participation stats. It links players to core accounts and bases participation off this data. This can take several hours to run and so you should probably only run this once or twice a day. 
* After setting up the `/config/config.ini` file and connecting to the webapp at least once, you can run `/cronjob/cronChecks.py` to begin tracking fleet data. 

## To Deploy the Checker and Cronjob to Seperate Servers
In the event that it's not easy to deploy the entire app to one server, the Python-Based Cronjob and Checker can be transferred to another server by following the instructions below:
* Make sure to copy the `/config/config.ini` file along with the `/resources/data/geographicInformation.json` and `/resources/data/TypeIDs.json` files somewhere python can access them after they've been setup.
* Move the `/checker` and `/cronjob` folders to wherever you'll be running them from.
* In `/cronjob/cronChecks.py` and `/checker/checker.py` change  the `configPathOverride` variable to an absolute path where your copy of `config.ini` being stored, and `dataPathOverride` to an absolute path where your two .json file copies are being stored.