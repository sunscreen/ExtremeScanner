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

    /*
    public $stack_slot_id;
    public $stack_device_model;
    public $stack_device_operstat;
    public $stack_device_role;
    public $stack_device_PhysicalIndex;
    public $stack_device_cur_image_ver;
    public $stack_device_pri_image_ver;
    public $stack_device_sec_image_ver;
    public $stack_device_bootrom_ver;
    public $stack_device_cur_config;
    public $stack_device_config_selected;
    public $stack_device_image_selected;
    public $stack_device_priority;
    public $stack_device_mgmt_ip;
    public $stack_device_syslocation;
    public $stack_device_autoconfig;
    public $stack_device_status;
    */

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
        var_dump($mac_results);
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
    function scan_address()
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
            var_dump($sysloctemp);
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

        //var_dump($lldp_remote_results;
        //var_dump($eap_results);

    } /* end of scan address */

    function load_product_names()
    {
        //$strJsonFileContents = file_get_contents("/var/www/html/netgraph/devices.json");
        $strJsonFileContents = file_get_contents("/var/www/html/netgraph/vendor_new.json");
        $this->devices_array = [];
        $this->devices_array = json_decode($strJsonFileContents, true);
        $this->devices_array = array_values(array_unique($this->devices_array, SORT_REGULAR));
    }

    function load_oids()
    {
        $strJsonFileContents = file_get_contents("/var/www/html/netgraph/oids.json");
        $oid_array = json_decode($strJsonFileContents, true);

        $this->oids_standard = $oid_array[0]["standard"];
        $this->oids_extreme = $oid_array[0]["device"]["extreme"];
        $this->oids_extreme_eaps = $oid_array[0]["device"]["extreme"]["EAPS"];
        $this->oids_interface = $oid_array[0]["if"];
        $this->oids_ip = $oid_array[0]["ip"];
        $this->oids_lldp = $oid_array[0]["lldp"];

        $this->load_product_names();

        //var_dump($oids_extreme_eaps); // print array

    }
}
/*end of Extreme_scanner class*/

$globalnodes = [];
$nodes = [];
$links = [];

/*
function is_stack_member($extreme_scanner,$mac) {

$m_stackmembers=$extreme_scanner->get_stackmembers();
$found=false;

if ($m_stackmembers) {
for ($stackmem=0;$stackmem<count($m_stackmembers);$stackmem++){
    $mactest=$m_stackmembers[$stackmem]["macaddress"];
    if ($mactest == $mac) $found=true;
}
}

var_dump($found);
var_dump($mac);
return $found;
//exit(0);
}
*/

function lldp_group_peer(array $lldp_array, array $lldpnewitem)
{
    foreach ($lldp_array as $key => $node)
    {
        //var_dump($node);
        if ($node["lldp_remote_mac"] == $lldpnewitem["lldp_remote_mac"])
        {
            //          return true;
            //
            unset($lldp_array, $key);
        }
    }
    return $lldp_array;
}

function lldp_neighbor_exsits(array $lldp_array, array $lldpcheck)
{
    foreach ($lldp_array as $key => $node)
    {
        //var_dump($node);
        if ($node["lldp_remote_mac"] == $lldpcheck["lldp_remote_mac"])
        {
            return true;
        }
    }
    return false;
}

function remove_dupes(array $myarray, $mac)
{
    foreach ($myarray as $key => $myitem)
    {
        if ($myitem["lldp_remote_mac"] == $mac)
        {
            echo "removing dupe" . $mac . "\n";
            unset($myarray[$key]);
        }
    }
    return $myarray;
}

function get_duplicates(array $array)
{
    $i = 0;
    $macarray = array_column($array, "lldp_remote_mac");
    $macdupes = array_unique(array_diff_assoc($macarray, array_unique($macarray)));
    $grouparray = [];

    foreach ($macdupes as $dupekey => $dupe)
    {
        $tmparray = [];
        foreach ($array as $key => $myitem)
        {
            if ($myitem["lldp_remote_mac"] == $dupe)
            {
                array_push($tmparray, $array[$key]);
                echo "dupe storing!!! \n";
            }
        }
        $i++;
        array_push($grouparray, ["bonded_group_$i" => $tmparray]);
    }

    foreach ($macdupes as $key => $delitem)
    {
        $array = remove_dupes($array, $delitem);
    }
    foreach ($grouparray as $key => $additem)
    {
        array_push($array, $additem);
    }
    return $array;
}

function clean_snmp_value_temp($snmpinput)
{
    $pieces = explode(" ", $snmpinput);
    //var_dump($pieces);
    $newresult = str_replace('"', "", trim(substr($snmpinput, strlen($pieces[0]) + 1, strlen($snmpinput))));
    return $newresult;
}

function tidy_lldp(array $lldparray)
{
    print_r($lldparray);
    exit(0);

    $newarray = [];
    $tmparray = [];
    for ($x = 0;$x < 2000;$x++)
    {
        $key = "lldp_remote_" . $x . "_4";
        $key_remote_mac = "lldp_remote_" . $x . "_5";
        $key_remote_portname = "lldp_remote_" . $x . "_8";
        $key_remote_portid = "lldp_remote_" . $x . "_7";
        $key_remote_sysname = "lldp_remote_" . $x . "_9";
        $key_remote_uname = "lldp_remote_" . $x . "_10";

        if (array_key_exists($key, $lldparray))
        {
            $lldp_unknown = $lldparray[$key];
            $lldp_remote_sysname = $lldparray[$key_remote_sysname];
            $lldp_remote_portname = $lldparray[$key_remote_portname];
            $lldp_remote_portid = $lldparray[$key_remote_portid];
            $lldp_remote_mac = $lldparray[$key_remote_mac];
            $lldp_remote_uname = $lldparray[$key_remote_uname];
            $arr_lldp_neighbor = ["lldp_remote_name" => $lldp_remote_sysname, "lldp_remote_mac" => $lldp_remote_mac, "lldp_remote_portname" => $lldp_remote_portname, "lldp_remote_portid" => $lldp_remote_portid, "lldp_remote_uname" => $lldp_remote_uname, "uniqueid" => uniqid("uid_", true) , ];
            array_push($newarray, $arr_lldp_neighbor);
            //$newarray=array_values($newarray);
            //echo "lldp neighbor: ".$lldp_unknown." sysname: ".$lldp_remote_sysname. " portname: ".$lldp_remote_portname." portid: ".$lldp_remote_portid." mac: ".$lldp_remote_mac." uname:".$lldp_remote_uname."\n";

        }
    }

    $newarray = get_duplicates($newarray);
    $newarray = array_values($newarray);
    //var_dump($newarray);
    return $newarray;
}

function vendor_detect($extreme_scanner, $ipaddress)
{
    global $community;

    global $LLDP_OIDS;
    global $IDENTOIDS;
    //$ipaddress=gethostbyname($ipaddress);
    $session = new SNMP(SNMP::VERSION_2C, $ipaddress, $community);
    $session->exceptions_enabled = 0;
    $found = false;
    //    foreach ($IDENTOIDS as $name => $oid) {
    //printf("proto detect:".$name."\n");
    $results = @$session->walk($extreme_scanner->oids_standard["sysobject"], 500000);

    if ($session->getErrno() == SNMP::ERRNO_TIMEOUT)
    {
        $session->close();
        return false;
    }

    if ($results == false)
    {
        //var_dump($session->getError());
        //$session->close();
        //return FALSE;
        $found = false;
    }
    else
    {
        $results = array_values($results);
        //var_dump($results);
        $vendor_result = $extreme_scanner->convert_oid_to_vendor($extreme_scanner->get_snmp_value($results[0], "OID: "));
        //var_dump($vendor_result);
        $session->close();
        $found = true;
        //exit(0);
        //print "Autodetected: " . $name . "\n";
        return $vendor_result;
    }

    //    }
    $session->close();
    return $found;
}

function autodetect_discovery_protocol($ipaddress)
{
    global $community;

    global $LLDP_OIDS;
    global $IDENTOIDS;
    //$ipaddress=gethostbyname($ipaddress);
    $session = new SNMP(SNMP::VERSION_2C, $ipaddress, $community);
    $session->exceptions_enabled = 0;
    $found = false;
    foreach ($IDENTOIDS as $name => $oid)
    {
        //printf("proto detect:".$name."\n");
        $results = @$session->walk($oid);

        if ($session->getErrno() == SNMP::ERRNO_TIMEOUT)
        {
            $session->close();
            return false;
        }

        if ($results == false)
        {
            //var_dump($session->getError());
            //$session->close();
            //return FALSE;
            $found = false;
        }
        else
        {
            //var_dump($results);
            $session->close();
            $found = true;
            //print "Autodetected: " . $name . "\n";
            return $name;
        }
    }
    $session->close();
    return $found;
}

function array_key_exsits_like(array $array, $likestr)
{
    $keys = array_keys($array);
    $found = false;
    foreach ($keys as $key)
    {
        //If the key is found in your string, set $found to true
        if (false !== strpos($likestr, $key))
        {
            $found = true;
        }
    }
    return $found;
}

function check_exists($systemname, &$nodes)
{
    //global $nodes;
    $isInNodes = in_array(trim($systemname) , array_column($nodes, "name"));
    //var_dump($isInNodes);
    //exit(0);
    //foreach ($nodes as $key=>$mynode) {
    //if ($mynode["name"] == $systemname) return true;
    //var_dump($mynode);
    //}
    return $isInNodes;
}

function get_stackmem_nid($systemname)
{
    global $stackdevices;

    foreach ($stackdevices as $smkey => $stackmem)
    {
        if ($systemname == $stackmem["name"])
        {
            return $smkey;
        }
    }
    return false;
}

function edp_response_is_empty($edparray) {
$emptyfound=false;

if ($edparray) {
foreach ($edparray as $key=>$edpchk) {
if (empty($edpchk)) {$emptyfound=true;}
}
}
return $emptyfound;
//return false;
}

function get_node_id_by_mac($mac,&$nodes) {

    foreach ($nodes as $key => & $mynode)
    {
        if (trim($mynode["device_mac"]) === trim($mac))
        {
            return $mynode["id"];
        }
        //var_dump($mynode);
    }
    return false;
}

function get_node_id($systemname, &$nodes)
{
    //global $nodes;
    foreach ($nodes as $key => & $mynode)
    {
        if (trim($mynode["name"]) === trim($systemname))
        {
            return $key;
        }
        //var_dump($mynode);

    }
    return false;
}

function get_node_id2($systemname, &$nodes)
{
    //global $nodes;
    foreach ($nodes as $key => & $mynode)
    {
        if (trim($mynode["name"]) === trim($systemname))
        {
            return $mynode["id"];
        }
        //var_dump($mynode);

    }
    return false;
}

function check_in_stack($mac)
{
    global $stackdevices;
    global $globalnodes;
    foreach ($stackdevices as $skdevkey => $stackitem)
    {
        $member_array = $stackitem["members"];

        for ($i = 0;$i < count($member_array);$i++)
        {
            //$testmac=trim(substr($member_array[$i]["macaddress"],3));
            //print_r($mac." ".$skdevkey." ".$testmac.PHP_EOL);
            //print_r("member mac: ".$member_array[$i]["macaddress"].PHP_EOL);
            //if ($testmac == $mac) { echo "found!!!".PHP_EOL; return $skdevkey; }
            $testmac = $member_array[$i]["macaddress"];
            foreach ($globalnodes as $nkey => $nodeitem)
            {
                $mac = $nodeitem["members"][0]["macaddress"];

                print_r("checking: " . $mac . " vs " . $testmac . PHP_EOL);
                $mac = $nodeitem["system_mac"];
                if ($mac == $testmac)
                {
                    echo "Mac Found!!! " . $skdevkey . PHP_EOL;
                    //return $skdevkey;

                }
            }
        }
    }
    //exit(0);
    return -1;
}

$stackdevices = [];

function create_stack($extreme_scanner, $ipaddress, $uid)
{
    //global $nodes;
    global $stackdevices;

    $systemname = $extreme_scanner->get_system_name();

    $devicemodel = $extreme_scanner->get_device_model();
    //var_dump($devicemodel);
    $stackmembers = $extreme_scanner->get_stackmembers();
    //$macaddress="bla";
    $color = "#9370DB";
    $svgicon = file_get_contents("stacking_icon.svg");
    $systemac = "unknown";
    $systemac_sec="unknown";

    $eap_array = $extreme_scanner->get_eaps_info();
    $macaddress = $extreme_scanner->get_mac_by_port("1001");

    $systemlocation = $extreme_scanner->systemlocation;

    //var_dump($systemlocation);
    //exit(0);
    if ($macaddress == false)
    {
        $macaddress = trim($extreme_scanner->get_mac_by_port("1"));
    }

    //var_dump($eap_array);
    //var_dump($stackmembers[0]["syslocation"]);
    //var_dump($systemname);
    //exit(0);
    //      $stacklocation=$stackmembers[0]["syslocation"];
    //      var_dump($stacklocation);
    //      exit(0);
    //      $stacklocation=$stackmembers[0]["syslocation"];
    //      $stacklocation=$extreme_scanner->systemlocation[0];
    //var_dump($stacklocation);
    //exit(0);
    if ($eap_array != false)
    {
        $systemmac = $extreme_scanner->get_snmp_value($eap_array[0]["primary"]["macaddress"], "Hex-STRING: ");
        $systemmac_sec = $extreme_scanner->get_snmp_value($eap_array[0]["secondary"]["macaddress"], "Hex-STRING: ");
        var_dump($eap_array);
        //var_dump($stackmembers);
//        exit(0);

    }
    $stackip = $extreme_scanner->scanaddress;

    $edp_info = array_unique(array_values($extreme_scanner->get_edp_info()) , SORT_REGULAR);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="80">' . '<rect x="0" y="0" width="100%" height="100%" fill="' . $color . '" rx="8px" ry="8px" stroke-linejoin="round" stroke="darkslateblue" ></rect>' . $svgicon . '<text x="20%" y="25%" text-anchor="left" fill="white" font-size="20" font-family="Arial, Helvetica, sans-serif">' . $systemname . "</text>" . '<text x="20%" y="50%" text-anchor="left" fill="white" font-family="Arial, Helvetica, sans-serif">' . $systemlocation . "</text>" . '<text x="20%" y="75%" text-anchor="left" fill="white" font-family="Arial, Helvetica, sans-serif">' . $stackip . "</text>" . "</svg>";
    $url = "data:image/svg+xml;charset=utf-8," . rawurlencode($svg);

    if (check_exists($systemname, $stackdevices) == false)
    {
        print_r("creating stack node: " . $systemname . PHP_EOL);
        array_push($stackdevices, ["stack_uid" => $uid, "name" => $systemname, "label" => $systemname, "stack_ip" => $stackip, "system_location" => $systemlocation, "device_model" => $devicemodel, "is_stack" => true, "device_mac" => $macaddress, "system_mac" => $systemmac, "system_mac_sec"=>$systemmac_sec,"edp_info" => $edp_info, "members" => $stackmembers, "image" => $url, ]);
    }
}

function create_device_node($extreme_scanner, $ipaddress, $uid, $color)
{
    //global $nodes;
    global $globalnodes;
    $systemname = $extreme_scanner->get_system_name();
    $devicemodel = $extreme_scanner->get_device_model();
    $macaddress = $extreme_scanner->get_mac_by_port("1001");
    $systemmac="";
    $systemmac_sec="";
    if ($macaddress == false)
    {
        $macaddress = trim($extreme_scanner->get_mac_by_port("1"));
    }
    //$systemlocation=trim($extreme_scanner->get_snmp_value($extreme_scanner->systemlocation[0],"STRING: "),'"');
    $systemlocation = $extreme_scanner->systemlocation;
    if ($systemlocation == "")
    {
        $systemlocation = "notconfigured";
    }
    //var_dump($systemlocation);
    //$systemlocation=$extreme_scanner->systemlocation[1];
    //$systemMac=$extreme_scanner->get_system_mac();
    //var_dump($systemMac);
    //exit(0);
    $svgicon = file_get_contents("uniswitch_icon.svg");

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="250" height="80">' . '<rect x="0" y="0" width="100%" height="100%" fill="' . $color . '" rx="8px" ry="8px" stroke-linejoin="round" stroke="darkslateblue" ></rect>' .
    $svgicon . '<text x="20%" y="25%" text-anchor="left" class="small" fill="white" font-size="20" font-family="Arial, Helvetica, sans-serif">' . $systemname . "</text>" . '<text x="20%" y="50%" text-anchor="left" fill="white" font-family="Arial, Helvetica, sans-serif">' . $systemlocation . "</text>" . '<text x="20%" y="75%" text-anchor="left" fill="white" font-family="Arial, Helvetica, sans-serif">' . $ipaddress . "</text>" . "</svg>";

    $url = "data:image/svg+xml;charset=utf-8," . rawurlencode($svg);
    //$systemmac=$macaddress;
    $systemmac = "NOT_EAP_ENABLED";
    $systemmac_sec ="NOT_EAP_ENABLED";
    $eap_array = $extreme_scanner->get_eaps_info();
    $edp_info = array_unique(array_values($extreme_scanner->get_edp_info()) , SORT_REGULAR);

    //      $eapport_primary=$eap_array["port"];
    //      $vlan_partners=$extreme_scanner->get_vlan_partners($eapport_primary);
    //      var_dump($edp_info);
    if ($eap_array != false)
    {
        $systemmac = $extreme_scanner->get_snmp_value($eap_array[0]["primary"]["macaddress"], "Hex-STRING: ");
        $systemmac_sec = $extreme_scanner->get_snmp_value($eap_array[0]["secondary"]["macaddress"], "Hex-STRING: ");

    }
    print_r("storing new node: " . $systemname . " " . $systemmac . PHP_EOL);
    if (check_exists($systemname, $globalnodes) == false)
    {
        array_push($globalnodes, ["name" => $systemname, "label" => $systemname, "systemlocation" => $systemlocation, "ipaddress" => $extreme_scanner->scanaddress, "device_model" => $devicemodel, "device_mac" => $macaddress, "system_mac" => $systemmac, "system_mac_sec"=>$systemmac_sec,"members" => $eap_array, "edp_info" => $edp_info, "image" => $url, "shape" => "image", "id" => $uid, "group" => "255","linked" => 0 ]);
    }
    //      $globalnodes=array_unique($globalnodes, SORT_REGULAR);
    //      $globalnodes = array_map("unserialize", array_unique(array_map("serialize", $globalnodes)));
    /*
        $isInNodes = in_array($systemname, array_column($nodes, 'name'));
        if ($isInNodes == false) {
    //      if (in_array($systemname,$nodes) == false) {
        array_push($nodes, [
            "name" => $systemname,
            "label" => $systemname,
            "device_model"=>$device_model,
            "device_mac"=>$macaddress,
            "image" => $url,
            "shape" => "image",
            "id" => $uid,
            "group" => "255",
        ]);
        }
    */
}

function ipscan_harvest($extreme_scanner, $scanips)
{
    //$children=[];
    $ip_results = [];

    for ($sc = 0;$sc < count($scanips);$sc++)
    {
        $extreme_scanner->init_snmp($scanips[$sc]);
        //$ip=trim($extreme_scanner->get_snmp_value($scanips[$sc],"IP Address:"));
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

    for ($c = 0;$c < count($ip_results);$c++)
    {
        echo "writing ip " . $ip_results[$c] . PHP_EOL;
        file_put_contents("/var/www/html/netgraph/ipscan.txt", $ip_results[$c] . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    $ip_results = array_values(array_unique(array_values($ip_results)));

    print "found : " . count($ip_results) . " address's";
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
            //var_dump($extreme_scanner->get_system_name());
            $extreme_scanner->init_snmp($networkips[$scancount]);
            $extreme_scanner->scan_address();
            $parentuid = $extreme_scanner->get_unique_id();
            //var_dump($parentuid);
            //    if ($protocol == "EDP" && $extreme_scanner->get_eap_enabled() == true) {
            if ($protocol == "EXTREME NETWORKS")
            {
                print "[collect_deviecs] storing extreme " . $networkips[$scancount] . PHP_EOL;

                file_put_contents("/var/www/html/netgraph/extreme_discovered.txt", $networkips[$scancount] . PHP_EOL, FILE_APPEND | LOCK_EX);
                continue;
            }

            if ($protocol == "JUNIPER")
            {
                print "[collect_devices] storing juniper " . $networkips[$scancount] . PHP_EOL;
                file_put_contents("/var/www/html/netgraph/juniper_discovered.txt", $networkips[$scancount] . PHP_EOL, FILE_APPEND | LOCK_EX);
                continue;
            }

            //if ($protocol == "LLDP") {
            print "[collect_devices] storing unknown " . $networkips[$scancount] . PHP_EOL;
            $systemdesc = $extreme_scanner->get_system_desc();
            $storejson = ["ip" => $networkips[$scancount], "systemdesc" => $systemdesc, ];
            var_dump($systemdesc);
            //exit(0);
            file_put_contents("/var/www/html/netgraph/unknown_discovered.json", json_encode($storejson) . PHP_EOL, FILE_APPEND | LOCK_EX);
            //}

        }
    }
}

$IDENTOIDS = [
//    "EDP" => "1.3.6.1.4.1.1916.1.18.1.1.5",
//    "JNX" => "1.3.6.1.4.1.2636.3.1.2.0",
"EDP" => "1.3.6.1.4.1.1916.1.1.2.2.1.4", "JNX" => "1.3.6.1.4.1.2636.3.1.8.1.5.1", "LLDP" => "iso.0.8802.1.1.2.1.4.1.1.9", "CDP" => "1.3.6.1.4.1.9.9.23.1.2.1.1.6", ];

$host = "172.16.98.1";
$community = "public";
$extreme_scanner = new ExtremeScanner();
$extreme_scanner->load_oids();
$scanips = [];
$seedips = [];
$networkips = [];

$readlines = file("/var/www/html/netgraph/quickscan.txt");
foreach ($readlines as $txtline)
{
    array_push($seedips, trim($txtline));
    array_push($networkips, trim($txtline));
}

//$networkips=ipscan_networks($extreme_scanner);
//$networkip
//s=ipscan_harvest($extreme_scanner,$seedips);
//var_dump(count($networkips));
//array_push($networkips,$host);
//collect_devices($extreme_scanner,$networkips);
//exit(0);
//$networkips=array_values(array_unique($networkips,SORT_REGULAR));
//rsort($networkips);
//var_dump($networkips);
//exit(0);
$lldpbulk = [];
for ($scancount = 0;$scancount < count($networkips);$scancount++)
{
    //for ($scancount=0;$scancount<7;$scancount++) {
    echo "$scancount $networkips[$scancount]\n";

    $vendor = vendor_detect($extreme_scanner, $networkips[$scancount]);

    if ($vendor == false)
    {
        print "no valid sysobject response: $networkips[$scancount]" . PHP_EOL;
    }
    else
    {
        print "Vendor for $networkips[$scancount] is $vendor Discovery protocol: EDP" . PHP_EOL;

        $extreme_scanner->init_snmp($networkips[$scancount]);
        $extreme_scanner->scan_address();
        $parentuid = $extreme_scanner->get_unique_id();

        if ($vendor == "EXTREME NETWORKS")
        {
            $isStack = $extreme_scanner->isStack();
            print_r("is stack " . (int)$isStack . PHP_EOL);
            $color = "#9370DB";

            if ((int)$isStack == 1)
            {
                create_stack($extreme_scanner, $networkips[$scancount], $parentuid);
                continue;
            }
            else
            {
                create_device_node($extreme_scanner, $networkips[$scancount], $parentuid, $color);
            }
        }
    }
}
//exit(0);
/*end of scan */
foreach ($stackdevices as $sdkey => $stackdev)
{
    //var_dump($stackdev[0]["stack_children"]);
    if ($stackdev["edp_info"] == null)
    {
        $stackdev["edp_info"] = [];
    }
    $stackdev["edp_info"] = array_unique(array_values($stackdev["edp_info"]) , SORT_REGULAR);

    array_push($nodes, ["name" => $stackdev["name"], "label" => $stackdev["name"], "systemlocation" => $stackdev["system_location"], "system_model" => $stackdev["device_model"], "device_mac" => $stackdev["device_mac"], "system_mac" => $stackdev["system_mac"], "system_mac_sec"=>$stackdev["system_mac_sec"], "members" => $stackdev["members"], "edp_peers" => $stackdev["edp_info"], "image" => $stackdev["image"], "shape" => "image", "id" => $stackdev["stack_uid"], "group" => "1", ]);
}

foreach ($globalnodes as $gnodekey => $gnode)
{
    print_r($gnode["name"] . " | " . $gnode["system_mac"] . " | " . $gnode["device_mac"] . PHP_EOL);
    if ($gnode["edp_info"] == null)
    {
        $gnode["edp_info"] = [];
    }
    $gnode["edp_info"] = array_unique(array_values($gnode["edp_info"]) , SORT_REGULAR);

    array_push($nodes, ["name" => trim($gnode["name"]) , "label" => $gnode["name"] . " " . $gnode["ipaddress"], "system_model" => $gnode["device_model"], "system_ipaddress" => $gnode["ipaddress"], "system_location" => $gnode["systemlocation"],"system_mac" => $gnode["system_mac"],"system_mac_sec"=>$gnode["system_mac_sec"], "device_mac" => $gnode["device_mac"], "edp_info" => $gnode["edp_info"], "image" => $gnode["image"], "shape" => "image", "id" => $gnode["id"], "group" => "255", ]);
}



foreach ($nodes as $gnodekey => & $gnode)
{
    if (empty($gnode["edp_info"]) || edp_response_is_empty($gnode["edp_info"]) === true)
    {
        /* work around code */
        /*
        if ($gnode["system_mac"] !== $gnode["device_mac"]) {
        print_r("system mac search: ".$gnode["name"].PHP_EOL);
        $idfrommac=get_node_id_by_mac($gnode["system_mac"],$globalnodes);
        if ($idfrommac !== false) {
        print_r("found system mac!!! on ".$idfrommac.PHP_EOL);
        $nodes[$gnodekey]["group"]=10;
        array_push($links, ["namefrom" => $gnode["name"], "nameto" => $idfrommac, "from" => $gnode["id"], "to" => $idfrommac, "arrows" => "to", "group"=>"50","color"=>["inherit"=>"both"]]);
        $gnode["linked"]=1;
        }
        }
        */

        continue;
    }

    for ($ncount = 0;$ncount < count($gnode["edp_info"]);$ncount++)
    {
        $nid = get_node_id2($gnode["edp_info"][$ncount], $nodes);
        print_r("peer search: " . $ncount . " " .$gnode["edp_info"][$ncount] . " " . $nid . PHP_EOL);

        if ($nid !== false)
        {
            $gnode["linked"]=1;
            array_push($links, ["namefrom" => $gnode["name"], "nameto" => $nid, "from" => $gnode["id"], "to" => $nid, "arrows" => "to", ]);

            print_r("peer found: " . $ncount." ". $gnode["edp_info"][$ncount] . " id: " . $nid . PHP_EOL);
        }

//      if ($gnode["system_mac"] !== $gnode["device_mac"]) {
//      $idfrommac=get_node_id_by_mac($gnode["system_mac"],$globalnodes);
//      if ($idfrommac != false) {
//          array_push($links, ["namefrom" => $gnode["name"], "nameto" => $idfrommac, "from" => $gnode["id"], "to" => $idfrommac, "arrows" => "to", "group"=>"50"]);
//      }
//      }

    }
}

foreach ($stackdevices as $sdkey => $stackdev)
{
    if (empty($stackdev["edp_info"]))
    {
        continue;
    }
    for ($ncount = 0;$ncount < count($stackdev["edp_info"]);$ncount++)
    {
        $nid = get_node_id2($stackdev["edp_info"][$ncount], $nodes);
        print_r("stack peer search: " . $stackdev["edp_info"][$ncount] . " " . $nid . PHP_EOL);

        if ($nid !== false)
        {
            array_push($links, ["namefrom" => $stackdev["name"], "nameto" => $nid, "from" => $stackdev["stack_uid"], "to" => $nid, "arrows" => "to", ]);

            print_r("stack peer found: " . $stackdev["edp_info"][$ncount] . " id: " . $nid . PHP_EOL);
        }
    }
}


echo "stacking groups: " . count($stackdevices) . PHP_EOL;
echo "global devices: " . count($globalnodes) . PHP_EOL;

file_put_contents("snmp_data.json", json_encode(["nodes" => $nodes, "links" => $links]) , LOCK_EX);

exit(0);

?>
