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
date_default_timezone_set('Europe/London');
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
    ? 'system_'.date('d-m-Y_H-i-s').'_enterpriseList.csv'
    : $enterpriseId.'_'.date('d-m-Y_H-i-s').'_enterpriseList.csv';

$fh = fopen($filename, 'w');
fputcsv($fh, ['userId', 'serviceProviderId', 'groupId', 'lastName', 'firstName', 'callingLineIdLastName', 'callingLineIdFirstName',
    'callingLineIdPhoneNumber', 'phoneNumber', 'departmentFullPath', 'language', 'timeZone', 'countryCode', 'nationalPrefix',
    'macAddress', 'deviceType', 'URI', 'currentTime', 'Expiration', 'LinePort']);

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
    $userResponse = $client->getResponse();
    $client->send(OCISchemaUser::UserGetRegistrationListRequest($row['col'][0]));
    $registrationResponse = $client->getResponse();
    $userDetails['userId'] = $row['col'][0];
    $userDetails['serviceProviderId'] = $userResponse->serviceProviderId;
    $userDetails['groupId'] = $userResponse->groupId;
    $userDetails['lastName'] = $userResponse->lastName;
    $userDetails['firstName'] = $userResponse->firstName;
    $userDetails['callingLineIdLastName'] = $userResponse->callingLineIdLastName;
    $userDetails['callingLineIdFirstName'] = $userResponse->callingLineIdFirstName;
    $userDetails['callingLineIdPhoneNumber'] = $userResponse->callingLineIdPhoneNumber;
    $userDetails['phoneNumber'] = $userResponse->phoneNumber;
    $userDetails['departmentFullPath'] = $userResponse->departmentFullPath;
    $userDetails['language'] = $userResponse->language;
    $userDetails['timeZone'] = $userResponse->timeZone;
    $userDetails['countryCode'] = $userResponse->countryCode;
    $userDetails['nationalPrefix'] = $userResponse->nationalPrefix;
    if (property_exists($userResponse, 'accessDeviceEndpoint')) {
        $client->send(OCISchemaGroup::GroupAccessDeviceGetRequest16($userResponse->serviceProviderId, $userResponse->groupId,
            $userResponse->accessDeviceEndpoint['accessDevice']['deviceName']));
        $deviceResponse = $client->getResponse();
        $userDetails['macAddress'] = $deviceResponse->macAddress;
        $userDetails['deviceType'] = $deviceResponse->deviceType;
        if (array_key_exists('row', $registrationResponse->registrationTable)) {
            foreach ($registrationResponse->registrationTable['row'] as $registration) {
                $registration = (array_key_exists('col', $registration)) ? $registration['col'] : $registration;
                if ($registration[1] == $userResponse->accessDeviceEndpoint['accessDevice']['deviceName']) {
                    $userDetails['URI'] = $registration[3];
                    $userDetails['currentTime'] = date("D M j G:i:s T Y");
                    $userDetails['Expiration'] = $registration[4];
                    $userDetails['LinePort'] = $registration[5];
                }
            }
        }
    }
    $current++;
    $bar->update($current);
    fputcsv($fh, $userDetails);
}
echo "\nWrote file: $filename\n";
?>