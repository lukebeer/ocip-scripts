<?php
/**
 * Default domain audit for enterprise and groups
 * Date: 27/05/14
 * Time: 12:30
 */
require_once 'config.php';
Factory::getOCISchemaGroup();
Factory::getOCISchemaSystem();
Factory::getOCISchemaServiceProvider();
$client = CoreFactory::getOCIClient(OCIP_HOST);
$client->login(OCIP_USER, OCIP_PASS);
$filename = date('d-m-Y_H:i:s').'_defaultDomains.csv';
$fh = fopen($filename, 'w');
fputcsv($fh, ['Enterprise', 'Group', 'Default Domain']);
if ($client->send(OCISchemaServiceProvider::ServiceProviderGetListRequest())) {
    foreach ($client->getResponse()->serviceProviderTable['row'] as $item) {
        $spid = $item['col'][0];
        $client->send(OCISchemaServiceProvider::ServiceProviderDomainGetAssignedListRequest($spid));
        if ($response = $client->getResponse()) {
            fputcsv($fh, [$spid, null, $response->serviceProviderDefaultDomain]);
        }
        $client->send(OCISchemaGroup::GroupGetListInServiceProviderRequest($spid));
        if (array_key_exists('groupTable', $client->getResponse())) {
            $response = $client->getResponse();
            if (!array_key_exists('row', $response->groupTable)) continue;
            foreach ($response->groupTable['row'] as $row) {
                $groupid = (array_key_exists('col', $row)) ? $row['col'][0] : $row[0];
                $client->send(OCISchemaGroup::GroupDomainGetAssignedListRequest($spid, $groupid));
                if ($client->getResponse()) {
                    fputcsv($fh, [$spid, $groupid, $client->getResponse()->groupDefaultDomain]);
                }
            }
        }
    }
}
echo "\nWrote filename: $filename\n";
