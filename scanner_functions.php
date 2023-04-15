<?php

function vendor_detect($extreme_scanner, $ipaddress)
{
    $extreme_scanner->init_snmp($ipaddress);
    return $extreme_scanner->vendor_detect();
}

function ipscan_harvest($extreme_scanner, $scanips)
{
    $ip_results = [];

    for ($sc = 0;$sc < count($scanips);$sc++)
    {
        $extreme_scanner->init_snmp($scanips[$sc]);
        print "scaning " . " " . $scanips[$sc] . PHP_EOL;
        $rescon = [];
        $rescon = $extreme_scanner->get_connected_ips();

        if ($rescon != false)
        {
            for ($childcount = 0;$childcount < count($rescon);$childcount++)
            {
                array_push($ip_results, $extreme_scanner->get_snmp_value($rescon[$childcount], "IP Address:"));
            }
        }
    }

    $ip_results = array_values(array_unique(array_values($ip_results)));

    print "found : " . count($ip_results) . " address's".PHP_EOL;
    return $ip_results;
}

function ipscan_networks($extreme_scanner)
{
    $ip_results = [];
    $networklist = file("networks.txt");
    foreach ($networklist as $netwrkey => $networkitem)
    {
        $scanrange = trim($networkitem);
        $pieces = explode("/", $networkitem);
        $scanrange = substr($pieces[0], 0, -1);

        for ($c = 0;$c < 255;$c++)
        {
            $ipnew = $scanrange . $c;
            $err = "";
            $err_string = "";
            array_push($ip_results, $ipnew);
            //port_open($ipnew,116);
            //var_dump($scanrange);

        }
    }
    return $ip_results;
    //exit(0);

}

function collect_devices($extreme_scanner, $networkips)
{
$discovered_vendors=[];

    for ($scancount = 0;$scancount < count($networkips);$scancount++)
    {
        echo "[collect_devices] $scancount $networkips[$scancount]\n";
        $protocol = vendor_detect($extreme_scanner, $networkips[$scancount]);
        if ($protocol == false)
        {
            print "[collect_devices] no sysobject response for $networkips[$scancount]" . PHP_EOL;
        }
        else
        {
            print "protocol for $networkips[$scancount] is " . $protocol . PHP_EOL;

            if ($protocol === "EXTREME NETWORKS")
            {
                print "[collect_deviecs] storing extreme " . $networkips[$scancount] . PHP_EOL;
                array_push($discovered_vendors,"extreme");
                file_put_contents("extreme_discovered.txt", $networkips[$scancount] . PHP_EOL, FILE_APPEND | LOCK_EX);
                continue;
            }

            if ($protocol === "JUNIPER")
            {
                print "[collect_devices] storing juniper " . $networkips[$scancount] . PHP_EOL;
                array_push($discovered_vendors,"juniper");

                file_put_contents("juniper_discovered.txt", $networkips[$scancount] . PHP_EOL, FILE_APPEND | LOCK_EX);
                continue;
            }
            if ($protocol === "UBIQUITI NETWORKS, INC.")
            {
                array_push($discovered_vendors,"ubiquiti");

                print "[collect_devices] storing ubiquiti " . $networkips[$scancount] . PHP_EOL;
                file_put_contents("ubiquiti_discovered.txt", $networkips[$scancount] . PHP_EOL, FILE_APPEND | LOCK_EX);
                continue;
            }

            $credentials=$extreme_scanner->get_credentials();

            print "[collect_devices] storing unknown " . $networkips[$scancount] . PHP_EOL;


            if ($credentials !== false) {
                $systemdesc = $credentials[0]["systemdesc"];
            } else {
                $systemdesc="device does not respond to standard oids";
            }
            $storejson = ["ip" => $networkips[$scancount], "systemdesc" => $systemdesc, ];
            file_put_contents("unknown_discovered.json", json_encode($storejson) . PHP_EOL, FILE_APPEND | LOCK_EX);

        }
    }

    $combined_contents="";
    foreach ($discovered_vendors as $single_file) {
              $combined_contents .= file_get_contents("./".$single_file."_discovered.txt");
    }
    file_put_contents("./quickscan.txt",$combined_contents);

}

?>
