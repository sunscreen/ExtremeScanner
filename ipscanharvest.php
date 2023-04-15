
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
