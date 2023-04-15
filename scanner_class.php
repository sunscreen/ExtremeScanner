<?php
include('scanner_class.php');
include('scanner_functions.php');

//error_reporting(~E_ALL ^ ~E_WARNING);

$community = "public";
$seedips = [];
$networkips = [];
$extreme_scanner = new ExtremeScanner();
$extreme_scanner->load_oids();

$readlines = file("seedips.txt");

foreach ($readlines as $txtline)
{
    array_push($seedips, trim($txtline));
}

$networkips=ipscan_harvest($extreme_scanner,$seedips);
collect_devices($extreme_scanner,$networkips);
exit(0);


?>
root@bhr:~# cat scanner_class.php
<?php
class ExtremeScanner
{
    public $scanaddress;
    public $connected_ips;
    public $uniqueid;
    public $oids_standard;
    public $oids_extreme;
    public $oids_extreme_eaps;
    public $oids_interface;
    public $oids_ip;
    public $session;

    public $systemname;
    public $systemdesc;
    public $systemlocation;
    public $macaddress;

    public $eap_enabled = false;
    public $eaps_info;

    public $edp_info;
    public $mac_results;
    public $lldp_info;
    public $basic_info;
    public $routing_info;

    public $pri_ring_port1;
    public $pri_ring_port2;

    public $sec_ring_port1;
    public $sec_ring_port2;

    public $isStackable = false;
    public $stackmembers;
    public $stack_system_mac;

    public $devices_array;
    public $device_model;

    function hex2str($hex)
    {
        for ($i = 0;$i < strlen($hex);$i += 2)
        {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }

        return $str;
    }

    function convert_oid_to_vendor($oidinput)
    {
        //    foreach ($this->devices_array[0] as $key=> $item) {
        foreach ($this->devices_array as $key => $item)
        {
            //echo "Loaded: ".$item["name"].PHP_EOL;
            //      if ($this->convert_oid_to_iso($item["oid"]) == $oidinput) return $item["name"];
            $chk = $this->convert_oid_to_iso($item["sysobjectid"]);
            //      var_dump($chk);
            //      var_dump($oidinput);
            if ($chk == $oidinput)
            {
                return $item["vendor"];
            }
        }
        return "unknown_vendor $oidinput";
    }

    function convert_oid_to_product($oidinput)
    {
        foreach ($this->devices_array as $key => $item)
        {
            //echo "Loaded: ".$item["name"].PHP_EOL;
            //      if ($this->convert_oid_to_iso($item["oid"]) == $oidinput) return $item["name"];
            if ($this->convert_oid_to_iso($item["sysobjectid"]) == $oidinput)
            {
                return $item["description"];
            }
        }
        return "unknown_extreme_device $oidinput";
    }

    function convert_oid_to_iso($oidinput)
    {
        $iso_oid = "iso." . substr($oidinput, 2, strlen($oidinput));
        //var_dump($iso_oid);
        //exit(0);
        return $iso_oid;
    }

    function snmp_reindex_interface_table($item, $replace_with, array $array)
    {
        $updated_array = [];
        foreach ($array as $key => $value)
        {
            if (!is_array($value) && strpos($key, $item))
            {
                $updated_array = array_merge($updated_array, [$replace_with => $value, ]);
                continue;
            }
            $oid_res_portion = substr($key, strlen($item) , strlen($item));
            $pieces = explode(".", $oid_res_portion);
            //var_dump($pieces);
            $newprefix = $replace_with . "_" . $pieces[1];
            $updated_array = array_merge($updated_array, [$newprefix => $this->clean_snmp_value($value) , ]);
        }
        return $updated_array;
    }

    function snmp_reindex_lldp($item, $replace_with, array $array)
    {
        $updated_array = [];
        foreach ($array as $key => $value)
        {
            if (!is_array($value) && strpos($key, $item))
            {
                $updated_array = array_merge($updated_array, [$replace_with => $value, ]);
                continue;
            }
            $oid_res_portion = substr($key, strlen($item) , strlen($item));
            $pieces = explode(".", $oid_res_portion);
            //var_dump($pieces);
            //$m_count++;
            $tst++;
            //$newprefix=$replace_with."_".$pieces[3]."_".$pieces[1];
            $newprefix = "_" . $pieces[3] . "_" . $pieces[1];
            $updated_array = array_merge($updated_array, [$newprefix => $value, ]);
        }
        return $updated_array;
    }

    function eaps_key_replace($item, $replace_with, array $array)
    {
        $updated_array = [];
        foreach ($array as $key => $value)
        {
            if (!is_array($value) && strpos($key, $item))
            {
                $updated_array = array_merge($updated_array, [$replace_with => $value, ]);
                continue;
            }
            $oid_res_portion = substr($key, strlen($item) , strlen($item));
            $pieces = explode(".", $oid_res_portion);
            //var_dump($pieces);
            $newprefix = $replace_with . "_" . $pieces[1] . "_" . $pieces[2];
            $updated_array = array_merge($updated_array, [$newprefix => $this->clean_snmp_value($value) , ]);
        }
        return $updated_array;
    }

    function snmp_truth($val)
    {
        //var_dump(substr($val,-1));
        //exit(0);
        if (substr($val, -1) == "1")
        {
            return true;
        }
        if (substr($val, -1) == "0")
        {
            return false;
        }
        //exit(0);
        return false;
    }

    function eaps_mode($val)
    {
        if ($val == "1")
        {
            return "master";
        }
        if ($val == "2")
        {
            return "transit";
        }
        if ($val == "0")
        {
            return "invalid";
        }
    }
    function eaps_state($val)
    {
        if ($val == "1")
        {
            return "complete";
        }
        if ($val == "2")
        {
            return "failed";
        }
        if ($val == "3")
        {
            return "linksup";
        }
        if ($val == "4")
        {
            return "linkdown";
        }
        if ($val == "5")
        {
            return "portforwarding";
        }
        if ($val == "0")
        {
            return "idle";
        }
    }
    function stack_oper_state($val)
    {
        if ($val == "1")
        {
            return "up";
        }
        if ($val == "2")
        {
            return "down";
        }
        if ($val == "3")
        {
            return "mismatch";
        }
    }

    function stack_role_state($val)
    {
        if ($val == "1")
        {
            return "master";
        }
        if ($val == "2")
        {
            return "slave";
        }
        if ($val == "3")
        {
            return "backup";
        }
    }

    function stack_selected($val)
    {
        if ($val == "1")
        {
            return "primary";
        }
        if ($val == "2")
        {
            return "secondary";
        }
    }

    function array_to_string(array $inarray)
    {
        return implode("", $inarray);
    }

    function get_snmp_value($snmpinputval, $valtype)
    {
        return substr($snmpinputval, strlen($valtype) , strlen($snmpinputval));
    }

    function clean_snmp_value($snmpinput)
    {
        //if (strpos($snmpinput,'"')==false) return $snmpinput;
        //$pieces=explode(" ",$snmpinput);
        //var_dump($pieces);
        //   $newresult=str_replace('"', "",trim(substr($snmpinput,strlen($snmpinput)+1,strlen($snmpinput))));
        $snmpinput = trim($snmpinput);
        $newresult = ltrim($snmpinput, '"');
        $newresult = rtrim($newresult, '"');

        return $newresult;
    }

    function init_snmp($host)
    {
        if ($this->session)
        {
            $session = $this->session;
            $session->close();
        }

        //var_dump($host);
        $this->scanaddress = $host;
        $this->session = new SNMP(SNMP::VERSION_2C, $host, "public", 1500000, 6);
        // exit(0);
        return true;
    }

    function set_scan_address($name)
    {
        $this->scanaddress = $name;
    }

    // Methods
    function detect_eaps()
    {
    }

    function isStack()
    {
        //var_dump($this->isStackable);
        return $this->snmp_truth($this->isStackable);
    }

    function get_stack_mac()
    {
        return $this->stack_device_mac;
    }

    function get_device_model()
    {
        return $this->device_model;
    }

    function get_eaps_info()
    {
        return $this->eaps_info;
    }
    function get_lldp_info()
    {
        return $this->lldp_info;
    }

    function get_eap_enabled()
    {
        return $this->eap_enabled;
    }
    function get_edp_info()
    {
        return $this->edp_info;
    }

    function get_stackmembers()
    {
        return $this->stackmembers;
    }

    function get_systemmac($port)
    {
    }

    function get_mac_by_port($port)
    {
        $session = $this->session;
        $macfromport = $session->walk([$this->oids_extreme["mac_from_port"] . "." . $port, ]);
        if ($macfromport == false)
        {
            return false;
        }
        $macfromport = array_values($macfromport);
        $macfromport_result = trim($this->get_snmp_value(trim($macfromport[0]) , "Hex-STRING: "));

        //    var_dump($macfromport_result);
        return $macfromport_result;
    }

    function get_connected_ips()
    {
        $session = $this->session;
        $ip_mediatable_results = $session->walk([$this->oids_ip["ipNetToMediaTable"], ]);
        if ($ip_mediatable_results != false)
        {
            $this->connected_ips = array_values($ip_mediatable_results);
        }

        //var_dump($this->connected_ips);
        //exit(0);
        return $this->connected_ips;
    }

    function get_routing_info()
    {
        $session = $this->session;
        $ip_route_table_results = $session->walk([$this->oids_ip["ipRouteTable"], ]);
        if ($ip_route_table_results != false)
        {
            $this->routing_info = array_values($ip_route_table_results);
        }

        return $this->routing_info;
    }

    function get_system_name()
    {
        return $this->clean_snmp_value($this->get_snmp_value($this->systemname[0], "STRING:"));
    }
    function get_system_desc()
    {
        return $this->clean_snmp_value($this->get_snmp_value($this->systemdesc[0], "STRING:"));
    }

    function get_unique_id()
    {
        $this->uniqueid = uniqid("uid_", true);
        return $this->uniqueid;
    }

    function handle_eaps_info(array $eaps_results)
    {
         //var_dump($eaps_results);
        //exit(0);
        $this->eaps_info = [];
        //  $pri_ring_port1_key="ifEntryPhys_".$eaps_results["EapsEntry_6_4"];
        //$sec_ring_port1_key="ifEntryPhys_".$eaps_results["EapsEntry_7_4"];
        //  $pri_ring_port_mac=$this->mac_results[$pri_ring_port1_key];
        //  $sec_ring_port_mac=$this->mac_results[$sec_ring_port1_key];
        // var_dump($pri_ring_port_mac);
        $pri_ring_port1_key = "ifEntryPhys_" . $this->get_snmp_value($eaps_results[10], "INTEGER: ");
        $sec_ring_port1_key = "ifEntryPhys_" . $this->get_snmp_value($eaps_results[11], "INTEGER: ");
        $pri_ring_port_mac = $this->mac_results[$pri_ring_port1_key];
        $sec_ring_port_mac = $this->mac_results[$sec_ring_port1_key];
        // var_dump($pri_ring_port_mac);
        $arrayitem_primary = ["domain_name" => $eaps_results[0], "eaps_mode" => $eaps_results[2], "eaps_state" => $eaps_results[4], "eaps_fail_flag" => $eaps_results[6], "eaps_enabled" => $eaps_results[8], "ringport1" => $eaps_results[10], "ringport2" => $eaps_results[11], "macaddress" => $pri_ring_port_mac, ];

        /*  $arrayitem_primary=array("domain_name"=>$eaps_results["EapsEntry_1_4"],
                          "eaps_mode"=>$eaps_results["EapsEntry_2_4"],
                          "eaps_state"=>$eaps_results["EapsEntry_3_4"],
                          "eaps_fail_flag"=>$eaps_results["EapsEntry_4_4"],
                          "eaps_enabled"=>$eaps_results["EapsEntry_5_4"],
                          "ringport1"=>$eaps_results["EapsEntry_6_4"],
                          "ringport2"=>$eaps_results["EapsEntry_7_4"],
                          "macaddress"=>$pri_ring_port_mac

                         );
        */

        $arrayitem_secondary = ["domain_name" => $eaps_results[1], "eaps_mode" => $eaps_results[3], "eaps_state" => $eaps_results[5], "eaps_fail_flag" => $eaps_results[7], "eaps_enabled" => $eaps_results[9], "ringport1" => $eaps_results[12], "ringport2" => $eaps_results[13], "macaddress" => $sec_ring_port_mac, ];

        array_push($this->eaps_info, ["primary" => $arrayitem_primary, "secondary" => $arrayitem_secondary, ]);
        //var_dump($this->eaps_info);
        // return eaps

    }

    function process_eaps(array $eaps_results)
    {
        //$eaps_results = $this->eaps_key_replace($this->convert_oid_to_iso($this->oids_extreme_eaps["extremeEapsEntry"]),"EapsEntry",$eaps_results);
        $eaps_results = array_values($eaps_results);
        return $eaps_results;
    }

    function process_macs(array $mac_results)
    {
        //var_dump($mac_results);
        //exit(0);
        $mac_results = $this->snmp_reindex_interface_table($this->convert_oid_to_iso($this->oids_interface["ifmac"]) , "ifEntryPhys", $mac_results);

        //var_dump($mac_results);
        return $mac_results;
    }

    function process_lldp(array $lldp_results)
    {
        $lldp_results = $this->snmp_reindex_lldp($this->convert_oid_to_iso($this->oids_extreme["lldp_method"]) , "lldp_remote", $lldp_results);
        return $lldp_results;
    }

    function process_descriptions(array $desc_results)
    {
        $desc_results = $this->snmp_reindex_interface_table($this->convert_oid_to_iso($this->oids_interface["ifdesc"]) , "ifEntryDesc", $desc_results);
        return $desc_results;
    }

    //function init_snmp() {
    //    $this->init_snmp();
    //}
    function vendor_detect() {
        $session = $this->session;
        $systemobject = $session->walk([$this->oids_standard["sysobject"]]);
        if ($systemobject == false)
        {
            return false;;
        }
        if ($systemobject != false)
        {
            $systemobject = array_values($systemobject);
            //$this->device_model = $this->convert_oid_to_product($this->get_snmp_value($systemobject[0], "OID: "));
            //exit(0);
            $vendor=$this->convert_oid_to_vendor($this->get_snmp_value($systemobject[0], "OID: "));
            return $vendor;
        }
    return $false;
    }


    function get_credentials(){
        $session = $this->session;

        $systemdesc = $session->walk([$this->oids_standard["sysdesc"]]);

        if ($systemdesc == false) return false;

        $systemname = $session->walk([$this->oids_standard["sysname"]]);
        $systemcontact = $session->walk([$this->oids_standard["contact"]]);
        $systemuptime = $session->walk([$this->oids_standard["uptime"]]);
        $systemlocation = $session->walk([$this->oids_standard["location"]]);
        $ret=[];
        $systemname=array_values($systemname);
        $systemcontact=array_values($systemcontact);
        $systemuptime=array_values($systemuptime);
        $systemlocation=array_values($systemlocation);
        $systemdesc=array_values($systemdesc);

        array_push($ret,[
                "systemname"=>trim($this->get_snmp_value($systemname[0],"STRING: "),'"'),
                "systemdesc"=>trim($this->get_snmp_value($systemdesc[0],"STRING: "),'"'),
                "systemcontact"=>trim($this->get_snmp_value($systemcontact[0],"STRING: "),'"'),
                "systemlocation"=>trim($this->get_snmp_value($systemlocation[0],"STRING: "),'"'),
                "systemuptime"=>trim($this->get_snmp_value($systemuptime[0],"STRING: "),'"')
                 ]);
        return $ret;
    }



    function extreme_scan_address()
    {
        $extremeStackTable = [];
        $edp_portlist = [];
        $this->edp_info = [];
        $this->stackmembers = [];
        $this->mac_results = [];
        $this->eap_info=[];

        $session = $this->session;
        $systemobject = $session->walk([$this->oids_standard["sysobject"]]);
        if ($systemobject == false)
        {
            return;
        }

        $systemdesc = $session->walk([$this->oids_standard["sysdesc"]]);
        $systemname = $session->walk([$this->oids_standard["sysname"]]);
        $systemcontact = $session->walk([$this->oids_standard["contact"]]);
        $systemuptime = $session->walk([$this->oids_standard["uptime"]]);
        $systemlocation = $session->walk([$this->oids_standard["location"]]);
        $eap_results = $session->walk([$this->oids_extreme_eaps["extremeEapsEntry"], ]);
        $ifcount_result = $session->walk([$this->oids_interface["ifnumber"]]);
        $mac_results = $session->walk([$this->oids_interface["ifmac"]]);
        $ifdesc_results = $session->walk([$this->oids_interface["ifdesc"]]);

        //$lldp_remote_results = $session->walk(array($this->oids_extreme["lldp_method"]));
        $lldp_remote_results = null;

        $edpTable_results = [];
        $edpTable_results = $session->walk([$this->oids_extreme["edpTable"]]);

        if ($edpTable_results != false)
        {
            //    var_dump($edpTable_results);
            //      exit(0);
            foreach ($edpTable_results as $edpkey => $edpitem)
            {
                $pieces = explode(".", $edpkey);
                array_push($edp_portlist, [$pieces[12]]);
            }

            //    var_dump($edp_portlist);
            //exit(0);
            $edpTable_results = array_values($edpTable_results);
            for ($c = 0;$c < count($edpTable_results);$c++)
            {
                array_push($this->edp_info, $this->clean_snmp_value($this->get_snmp_value($edpTable_results[$c], "STRING: ")));
            }
        }
        else
        {
            $lldp_remote_sys = [];
            $lldp_remote_sys = $session->walk([$this->oids_lldp["remotesysname"]], true);
            if ($lldp_remote_sys != false)
            {
                foreach ($lldp_remote_sys as $peerkey => $peer)
                {
                    //array_push($this->edp_info, $this->clean_snmp_value($this->get_snmp_value($peer, "STRING: ")));
                }
            }
        }

        /*


        $edpTable_results=[];
        $edpTable_results = $session->walk(array($this->oids_extreme["edpTable"]));

        if ($edpTable_results != false) {
        $this->edp_info=[];
        $edpTable_results = array_values($edpTable_results);
        for ($c=0;$c<count($edpTable_results);$c++) {
        array_push($this->edp_info,$this->clean_snmp_value($this->get_snmp_value($edpTable_results[$c],"STRING: ")));
        }

        } else {
        $this->edp_info=[];
        $lldp_remote_sys=[];
        $lldp_remote_sys = $session->walk(array($this->oids_lldp["remotesysname"]),true);
        foreach ($lldp_remote_sys as $peerkey=>$peer) {
        //          array_push($this->edp_info,$this->clean_snmp_value($this->get_snmp_value($peer,"STRING: ")));
        }
        print_r("using non EDP approach :(".PHP_EOL);
        //      var_dump($this->edp_info);
        //      var_dump($systemdesc);
        //      exit(0);
        }
        */

        $isStackable = false;
        $this->isStackable = false;
        if ($systemdesc != false)
        {
            $this->systemdesc = array_values($systemdesc);
        }
        if ($systemname != false)
        {
            $this->systemname = array_values($systemname);
        }
        if ($systemlocation != false)
        {
            $systemlocation = array_values($systemlocation);
            $sysloctemp = $this->get_snmp_value($systemlocation[0], "STRING: ");
            $sysloctemp = trim($sysloctemp, '"');
            //var_dump($sysloctemp);
            $this->systemlocation = $sysloctemp;
        }

        $isStackable = $session->walk([$this->oids_extreme["extremeStackable"], ]);

        if ($isStackable != false)
        {
            $this->isStackable = $this->array_to_string($isStackable);

            if ((int)$this->snmp_truth($this->isStackable) == 1)
            {
                //$lldp_remote_results=$session->walk(array($this->oids_extreme["lldp_method"]));
                $extremeStackTable = $session->walk([$this->oids_extreme["extremeStackMemberTable"], ]);
                $intf = 0;
                $all_members = [];
                for ($intf = 1;$intf < 8;$intf++)
                {
                    $member_array = [];
                    $tmp = 0;
                    foreach ($extremeStackTable as $stackkey => $stackitem)
                    {
                        $pieces = explode(".", $stackkey);
                        //print_r($pieces[12]." ".$stackitem.PHP_EOL);
                        if ($intf == $pieces[12])
                        {
                            //print_r($intf." ".$tmp." ".$stackitem.PHP_EOL);
                            array_push($member_array, $stackitem);
                            $tmp++;
                            //array_push([$intf=>$stackitem;

                        }
                    }
                    if (count($member_array) > 0)
                    {
                        $all_members[$intf] = $member_array;
                    }
                }
                //$this->stackmembers=$all_members;
                //    exit(0);
                $member_entry = [];
                for ($intf = 1;$intf < count($all_members) + 1;$intf++)
                {
                    $member_entry = ["id" => $all_members[$intf][0], "device_model" => $this->convert_oid_to_product($this->get_snmp_value($all_members[$intf][1], "OID: ")) , "operstatus" => $all_members[$intf][2], "role" => $all_members[$intf][3], "physindex" => $all_members[$intf][4], "macaddress" => $this->get_snmp_value(trim($all_members[$intf][5]) , "Hex-STRING: ") , "curimage" => $all_members[$intf][6], "pri_image" => $all_members[$intf][7], "sec_image" => $all_members[$intf][8], "rom_version" => $all_members[$intf][9], "cur_config" => $all_members[$intf][10], "sel_config" => $all_members[$intf][11], "sel_image" => $all_members[$intf][12], "priority" => $all_members[$intf][13], "mgmt_ip" => $all_members[$intf][14], "syslocation" => trim($this->get_snmp_value($all_members[$intf][15], "STRING: ") , '"') , "autoconfig" => $all_members[$intf][16], "stackstatus" => $all_members[$intf][17], "image_booted" => $all_members[$intf][18], "boot_time" => $all_members[$intf][19], ];

                    array_push($this->stackmembers, $member_entry);
                }
            }
        }

        if ($systemobject != false)
        {
            $systemobject = array_values($systemobject);
            $this->device_model = $this->convert_oid_to_product($this->get_snmp_value($systemobject[0], "OID: "));
            //exit(0);

        }

        if ($mac_results != false)
        {
            $this->mac_results = $this->process_macs($mac_results);
        }

        if ($ifdesc_results != false)
        {
            $ifdesc_results = $this->process_descriptions($ifdesc_results);
        }

        if ($lldp_remote_results != false)
        {
            //$lldp_remote_results = $this->process_lldp($lldp_remote_results);
            //$this->lldp_info = tidy_lldp($lldp_remote_results);
            //var_dump($this->lldp_info);
            //exit(0);

        }

        if ($eap_results != false)
        {
            $this->eap_enabled = true;
            $eap_results = $this->process_eaps($eap_results);
            $this->handle_eaps_info($eap_results);
        }

    } /* end of scan address */

    function load_product_names()
    {
        $strJsonFileContents = file_get_contents("vendor_new.json");
        $this->devices_array = [];
        $this->devices_array = json_decode($strJsonFileContents, true);
        $this->devices_array = array_values(array_unique($this->devices_array, SORT_REGULAR));
    }

    function load_oids()
    {
        $strJsonFileContents = file_get_contents("oids.json");
        $oid_array = json_decode($strJsonFileContents, true);

        $this->oids_standard = $oid_array[0]["standard"];
        $this->oids_extreme = $oid_array[0]["device"]["extreme"];
        $this->oids_extreme_eaps = $oid_array[0]["device"]["extreme"]["EAPS"];
        $this->oids_interface = $oid_array[0]["if"];
        $this->oids_ip = $oid_array[0]["ip"];
        $this->oids_lldp = $oid_array[0]["lldp"];
        $this->load_product_names();


    }
}
/*end of Extreme_scanner class*/

?>
