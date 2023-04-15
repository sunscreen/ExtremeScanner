# ExtremeScanner

## Description
Extreme Network (EDP) scanner, although it isnt vendor specific ;)

This code is able to scan a batch of Network ranges in order to detect and store Extreme Devices, Juniper Devices, and Unknown Devices, (IP SCAN/HARVEST) for later scanning.
As such it can be used to detect other vendors as required, For the time being while i develop it further. its set to Extreme, Junipers and Ubiquiti.

Once the ipscan harvest function has a list of quickscan ips, It will scan the network and output a Vis JS network map with the resultant data from the final snmp scan.. nothing is fabricated, for extreme switch this allows you to see the EDP relationships of your devices, multiple EDP neighbors deep, meaning you SEE the reverse references because reality is switched on, but you wont see all the brokeness that is prevalent on the most imminent switch makers equipment. With that said its easy enough to make it only work 1 neighbor deep if you didn't want the cross referencing.

I will upload a script that will convert the stencils to the Js files and a new vendor_db.js which i am streamlining and tidying further.




## Getting Started

### Dependencies

* Php
* Libvisio2svg
* VisJS

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



