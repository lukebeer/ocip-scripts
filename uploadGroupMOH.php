<?php
/**
 * Description: Group MOH update.
 * Date: 27/05/14
 * Time: 12:30
 * Author: Luke Beer
 * Usage: php uploadGroupMOH.php listofgroups.csv externalMOH.wav [internalMOH.wav] optional.
 *        CSV should be format enterpriseid,groupid
 */


// Configuration - Set these to TRUE/FALSE
$department                          = null;
$isActiveDuringCallHold              = 'true';
$isActiveDuringCallPark              = 'true';
$isActiveDuringBusyCampOn            = 'true';
$useAlternateSourceForInternalCalls  = 'true';
// End config

require_once 'config.php';
require_once OCIP_BASEPATH . 'core/OCIClient.php';
ini_set("max_execution_time", 0);
Factory::getOCISchemaServiceMusicOnHold();
$client = CoreFactory::getOCIClient(OCIP_HOST);
$client->login(OCIP_USER, OCIP_PASS);
$client->setTimeout(60);

if (!$rows = file($argv[1])) die("Something went wrong reading the CSV");
if ($extmusic  = file_get_contents($argv[2])) {
    $source['audioFilePreferredCodec'] = 'None';
    $source['messageSourceSelection']  = 'Custom';
    $source['customSource']['audioFile']['description'] = 'Music on Hold';
    $source['customSource']['audioFile']['mediaType']   = 'WAV';
    $source['customSource']['audioFile']['content']     = base64_encode($extmusic);
} else {
    die("Something went wrong reading the music file");
}
if ($intmusic  = @file_get_contents($argv[3])) {
    $source['audioFilePreferredCodec'] = 'None';
    $source['messageSourceSelection']  = 'Custom';
    $source['customSource']['audioFile']['description'] = 'Music on Hold';
    $source['customSource']['audioFile']['mediaType']   = 'WAV';
    $source['customSource']['audioFile']['content']     = base64_encode($intmusic);
} else {
    $useAlternateSourceForInternalCalls = FALSE;
    $internalSource = null;
    echo "Notice: Not using alternate music source for internal calls\n";
}

foreach ($rows as $row) {
    $detail = str_getcsv($row);
    $client->send(OCISchemaServiceMusicOnHold::GroupMusicOnHoldModifyInstanceRequest16(
        $detail[0],
        $detail[1],
        $department,
        $isActiveDuringCallHold,
        $isActiveDuringCallPark,
        $isActiveDuringBusyCampOn,
        $source,
        $useAlternateSourceForInternalCalls,
        $internalSource
    ));
    if ($client->getResponse()) echo "Uploaded MOH for {$detail[0]} - {$detail[1]}\n";
}
print_r($errorControl->getErrors());
