# ExtremeScanner

## Description
Extreme Network scanner, although it isnt vendor specific ;)


This code is able to scan a batch of Network ranges in order to detect and store Extreme Devices, Juniper Devices, and Unknown Devices, etc, for later scanning.
(IP SCAN/HARVEST)

As such it can be used to detect other vendors as required, For the time being while i develop it further. its set to Extreme, Junipers and Ubiquiti.

Once the ipscan harvest function has a list of quickscan ips, It will scan the network and generate a Vis JS network map with the resultant data from the final snmp scan.. I used VisJs but thats not exclusive any network chart that supports the concept of "From" "To" linking could be used.
(SCAN_NETWORK)
nothing is fabricated, for extreme switches this allows you to see the EDP relationships of your devices, and later i will introduce support for CISCO and IEEE8802 LLDP.

I will upload a script that will convert the stencils to the Js files and a new vendor_db.js which i am streamlining and tidying further.


## Extreme Specific Stuff
* It supports Stacking configurations. 
* It supports Extremes Automatic Protection system - but doesnt use it yet.
* It Provides a Micro Mau information (because extreme dosnt support mau oid)
* Future support for Power supply/Fan/Health stats (because extreme does not support the Health Mib)

### Dependencies

* Php
* Libvisio2svg
* VisJS
* PHP-NETSNMP
* Recommended to have a 1080p display to visualize the map.
* All Devices to be routable from the scan site
* Devices that have been EDP stripped due to vlan tag will not be able to be connected in the final map should they be.

### Installing

* Installer later
*

### Executing program

* How to run the program
* Step-by-step bullets
```
code blocks for commands
```

## Help

Any advise for common problems or issues.
```
command to run if program contains helper info
```

## Authors

## Version History

* 0.1
    * Initial Release

## License

This project is licensed under the [NAME HERE] License - see the LICENSE.md file for details

## Acknowledgments

Inspiration, code snippets, etc.
* Lots of good people on the internet
* 
* 
* 
* 



