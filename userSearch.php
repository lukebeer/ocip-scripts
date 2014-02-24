<?php
/*
    Filename: userSearch.php
    Description: User search utility based on lastname, firstname or DN. Spits out JSON, useful cli alias.
    Usage: php findUser.php lastname bloggs
    Output:
            {
                "results": [
                    {
                        "User ID": "joe.bloggs@example.com",
                        "Group ID": "Group-1",
                        "ServiceProviderID": "Enterprise-1",
                        "Last Name": "Bloggs",
                        "First Name": "Joe",
                        "Department": [

                        ],
                        "Phone Number": "+44-1234567890",
                        "Phone Number Activated": "true",
                        "Email Address": "joe.bloggs@example.com",
                        "In Trunk Group?": "false"
                    }
                ],
                "errors": null
            }
*/
header('Content-type: application/json');
require_once 'config.php';
Factory::getOCISchemaSearchCriteria();
$client = CoreFactory::getOCIClient(OCIP_HOST);
$client->login(OCIP_USER, OCIP_PASS);
switch(@$argv[1]) {
    case 'lastname':
        $lastName = OCIBuilder::buildSearch(OCISchemaSearchCriteria::SearchCriteriaUserLastName(OCISearchModes::CONTAINS, $argv[2], true));
        $client->send(OCISchemaUser::UserGetListInSystemRequest(null, $lastName));
        break;
    case 'firstname':
        $firstName = OCIBuilder::buildSearch(OCISchemaSearchCriteria::SearchCriteriaUserFirstName(OCISearchModes::CONTAINS, $argv[2], true));
        $client->send(OCISchemaUser::UserGetListInSystemRequest(null, null, $firstName));
        break;
    case 'dn':
        $dn = OCIBuilder::buildSearch(OCISchemaSearchCriteria::searchCriteriaDn(OCISearchModes::CONTAINS, $argv[2], true));
        $client->send(OCISchemaUser::UserGetListInSystemRequest(null, null, null, $dn));
        break;
    default:
        die("Provide a search criteria, eg: 'php findUser.php lastname bloggs'\n");
}
if ($response = $client->getResponse()) {
    if (!array_key_exists('row', $response->userTable)) die("No results\n");
    foreach($response->userTable['row'] as $item) {
        $item = (array_key_exists('col', $item)) ? $item['col'] : $item;
        $results[] = ['User ID' => $item[0], 'Group ID' => $item[1], 'ServiceProviderID' => $item[2], 'Last Name' => $item[3],
            'First Name' => $item[4], 'Department' => $item[5], 'Phone Number' => $item[6], 'Phone Number Activated' => $item[7],
            'Email Address' => $item[8], 'In Trunk Group?' => $item[11]
        ];
    }
}
$data = json_encode(['results' => $results, 'errors' => $errorControl->getErrors()], JSON_PRETTY_PRINT);
echo $data;