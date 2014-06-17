<?php
/**
 * Description: User list and device report to csv by enterprise or system
 * Date: 27/05/14
 * Time: 12:30
 * Author: Luke Beer
 * Usage: 'php userList.php [enterpriseID]' Leave [enterpriseID] null for system report
 */
require_once 'config.php';
require_once 'Console/ProgressBar.php';
ini_set("max_execution_time", 0);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
Factory::getOCISchemaServiceProvider();
Factory::getOCISchemaGroup();
$client = CoreFactory::getOCIClient(OCIP_HOST);
$client->login(OCIP_USER, OCIP_PASS);
$client->setTimeout(60);
$enterpriseId = $argv[1];
$request = (empty($enterpriseId))
    ? OCISchemaUser::UserGetListInSystemRequest()
    : OCISchemaUser::UserGetListInServiceProviderRequest($enterpriseId);

$filename = (empty($enterpriseId))
    ? 'system_'.date('d-m-Y_H:i:s').'_enterpriseList.csv'
    : $enterpriseId.'_'.date('d-m-Y_H:i:s').'_enterpriseList.csv';

$fh = fopen($filename, 'w');
fputcsv($fh, ['userId', 'serviceProviderId', 'groupId', 'lastName', 'firstName', 'callingLineIdLastName', 'callingLineIdFirstName',
    'callingLineIdPhoneNumber', 'phoneNumber', 'departmentFullPath', 'language', 'timeZone', 'countryCode', 'nationalPrefix',
    'linePort', 'deviceType', 'macAddress']);

$msg = "Fetching users......";
echo $msg;

$client->send($request);
$userTable = $client->getResponse()->userTable['row'];
$totalUsers = count($userTable);
$current = 0;
echo str_repeat(chr(8), strlen($msg));

$bar = new Console_ProgressBar('[%bar%] [current:%current% -%percent% elapsed: %elapsed% remaining: %estimate%]', '=>', ' ', 100, $totalUsers);

foreach ($userTable as $row) {
    $userDetails = null;
    $client->send(OCISchemaUser::UserGetRequest17sp4($row['col'][0]));
    $userDetails['userId'] = $row['col'][0];
    $userDetails['serviceProviderId'] = $client->getResponse()->serviceProviderId;
    $userDetails['groupId'] = $client->getResponse()->groupId;
    $userDetails['lastName'] = $client->getResponse()->lastName;
    $userDetails['firstName'] = $client->getResponse()->firstName;
    $userDetails['callingLineIdLastName'] = $client->getResponse()->callingLineIdLastName;
    $userDetails['callingLineIdFirstName'] = $client->getResponse()->callingLineIdFirstName;
    $userDetails['callingLineIdPhoneNumber'] = $client->getResponse()->callingLineIdPhoneNumber;
    $userDetails['phoneNumber'] = $client->getResponse()->phoneNumber;
    $userDetails['departmentFullPath'] = $client->getResponse()->departmentFullPath;
    $userDetails['language'] = $client->getResponse()->language;
    $userDetails['timeZone'] = $client->getResponse()->timeZone;
    $userDetails['countryCode'] = $client->getResponse()->countryCode;
    $userDetails['nationalPrefix'] = $client->getResponse()->nationalPrefix;
    if (property_exists($client->getResponse(), 'accessDeviceEndpoint')) {
        $userDetails['linePort'] = $client->getResponse()->accessDeviceEndpoint['linePort'];
        $client->send(OCISchemaGroup::GroupAccessDeviceGetRequest16($_GET['id'], $client->getResponse()->groupId,
            $client->getResponse()->accessDeviceEndpoint['accessDevice']['deviceName']));
        $userDetails['deviceType'] = $client->getResponse()->deviceType;
        $userDetails['macAddress'] = $client->getResponse()->macAddress;
    }
    $current++;
    $bar->update($current);
    fputcsv($fh, $userDetails);
}
echo "\n";
?>
