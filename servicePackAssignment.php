<?php
/**
 * Service Packs assignment per user in service provider
 * Date: 24/03/14
 * Time: 12:30
 */
require_once 'config.php';
Factory::getOCISchemaSystem();
$client = CoreFactory::getOCIClient(OCIP_HOST);
$client->login(OCIP_USER, OCIP_PASS);
if (!isset($argv[1])) die("No service provider name provided as argument to script\n");
if ($client->send(OCISchemaUser::UserGetListInServiceProviderRequest($argv[1]))) {
    if (!$client->getResponse()) die("No users in $argv[1]\n");
    foreach ($client->getResponse()->userTable['row'] as $item) {
        $userId = $item['col'][0];
        $client->send(OCISchemaUser::UserServiceGetAssignmentListRequest($userId));
        $sps = null;
        foreach ($client->getResponse()->servicePacksAssignmentTable['row'] as $row) {
            if (array_key_exists('col', $row)) {
                if ($row['col'][1] == 'true') $sps[] = $row['col'][0];
            } else {
                if ($row[1] == 'true') $sps[] = $row[0];
            }
        }
        if ($sps) echo "$userId,".implode(',',$sps)."\n";
    }
}
