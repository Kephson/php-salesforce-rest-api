<?php
/*
 * you can test this script with ddev local: https://github.com/drud/ddev/
 * 1. start ddev with 'ddev start'
 * 2. load all packages with 'ddev composer update'
 * 3. copy /example/config.sample.php to /example/config.php and add your Salesforce credentials
 * 4. call the script via https://php-sf-rest-api.local/
*/

use EHAERER\Salesforce\Authentication\PasswordAuthentication;
use EHAERER\Salesforce\SalesforceFunctions;

if (!file_exists('../vendor/autoload.php')) {
    print 'Before you can start: please do a "composer install" or "composer update" to load the dependencies.';
} else if (!file_exists('config.php')) {
    print 'Before you can start: please copy config.example.php to config.php and add your app configuration.';
} else {
    require_once '../vendor/autoload.php';
    $options = require 'config.php';

    $salesforce = new PasswordAuthentication($options);
    $development = true; // set by default to sandbox
    /* Sandbox: https://test.salesforce.com/, Production: https://login.salesforce.com/ */
    $endPoint = $development ? 'https://test.salesforce.com/' : 'https://login.salesforce.com/';
    $salesforce->setEndpoint($endPoint);
    $salesforce->authenticate();

    /* if you need access token or instance url */
    $accessToken = $salesforce->getAccessToken();
    $instanceUrl = $salesforce->getInstanceUrl();

    $salesforceFunctions = new SalesforceFunctions($instanceUrl, $accessToken, "v52.0");

    /* query function */
    $query = 'select Id,Name from ACCOUNT LIMIT 2';
    $queryData = $salesforceFunctions->query($query);
    print '############ query function with 2 accounts ############' . "\r\n";
    print '<pre>';
    print_r($queryData);
    print '</pre>';

    /* create function */
    $createData = $salesforceFunctions->create(
        'Lead',
        ['FirstName' => 'Max', 'LastName' => 'Muster', 'Company' => 'My company', 'Status' => 'new'],
        [],
        true
    );
    print '############ create function with lead ############' . "\r\n";
    print '<pre>';
    print_r($createData);
    print '</pre>';

    /* retrieve function */
    if (isset($createData['id'])) {
        $retrieveData = $salesforceFunctions->retrieve('Lead', 'Id', $createData['id']);
        print '############ retrieve function with lead id ############' . "\r\n";
        print '<pre>';
        print_r($retrieveData);
        print '</pre>';
    }
}