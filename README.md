# PHP Salesforce REST API wrapper

Forked from:
```bjsmasth/php-salesforce-rest-api``` ```Cleeng/php-salesforce-rest-api``` ```jerkob/php-salesforce-rest-api-forked```

## Install

Via **[composer](https://getcomposer.org/)**

``` bash
composer require ehaerer/php-salesforce-rest-api
```

# Getting Started

Setting up a Connected App

1. Log into your Salesforce org
2. Click on Setup in the upper right-hand menu
3. Under Build click ```Create > Apps ```
4. Scroll to the bottom and click ```New``` under Connected Apps.
5. Enter the following details for the remote application:
    - Connected App Name
    - API Name
    - Contact Email
    - Enable OAuth Settings under the API dropdown
    - Callback URL
    - Select access scope (If you need a refresh token, specify it here)
6. Click Save

After saving, you will now be given a _consumer key_ and _consumer secret_. Update your config file with values for ```consumerKey``` and ```consumerSecret```

# Setup

### Example

See a full example which could be tested with [ddev local](https://github.com/drud/ddev/) in [/example folder](example/) folder.

### Authentication

```bash
    $options = [
        'grant_type' => 'password',
        'client_id' => 'CONSUMERKEY', /* insert consumer key here */
        'client_secret' => 'CONSUMERSECRET', /* insert consumer secret here */
        'username' => 'SALESFORCE_USERNAME', /* insert Salesforce username here */
        'password' => 'SALESFORCE_PASSWORD' . 'SECURITY_TOKEN' /* insert Salesforce user password and security token here */
    ];

    $salesforce = new \EHAERER\Salesforce\Authentication\PasswordAuthentication($options);
    /* if you want to login to a Sandbox change the url to https://test.salesforce.com/ */
    $endPoint = 'https://login.salesforce.com/';
    $salesforce->setEndpoint($endPoint);
    $salesforce->authenticate();

    /* if you need access token or instance url */
    $accessToken = $salesforce->getAccessToken();
    $instanceUrl = $salesforce->getInstanceUrl();

    /* create salesforceFunctions object with instance, accesstoken and API version */
    $salesforceFunctions = new \EHAERER\Salesforce\SalesforceFunctions($instanceUrl, $accessToken, "v52.0");
```

#### Query

```bash
    $query = 'SELECT Id,Name FROM ACCOUNT LIMIT 100';

    $additionalHeaders = ['key' => 'value'];

    /* returns array with the queried data */
    $data = $salesforceFunctions->query($query, $additionalHeaders);

```

#### Create

```bash

    $data = [
       'Name' => 'Some name',
    ];
    $additionalHeaders = ['key' => 'value'];

    /* returns the id of the created object or full response */
    $accountId = $salesforceFunctions->create('Account', $data, $additionalHeaders);
    $fullResponse = $salesforceFunctions->create('Account', $data, $additionalHeaders, true);
```

#### Update

```bash
    $newData = [
       'Name' => 'another name',
    ];
    $additionalHeaders = ['key' => 'value'];

    /* returns statuscode */
    $salesforceFunctions->update('Account', $id, $newData, $additionalHeaders);
```

#### Upsert

```bash
    $newData = [
       'Name' => 'another name',
    ];
    $additionalHeaders = ['key' => 'value'];

    /* returns statuscode */
    $salesforceFunctions->upsert('Account', 'API Name/ Field Name', 'value', $newData, $additionalHeaders);
```

#### Delete

```bash
    $additionalHeaders = ['key' => 'value'];
    $salesforceFunctions->delete('Account', $id, $additionalHeaders);
```

#### Describe

```bash
    $additionalHeaders = ['key' => 'value'];
    $salesforceFunctions->describe('Account', $additionalHeaders);
```

#### Custom endpoint

```bash
    $additionalHeaders = ['key' => 'value'];
    $salesforceFunctions->customEndpoint('apex/myCustomEndpoint', $data, 200, $additionalHeaders);
```

#### Changelog: ####
##### 29.03.2022 #####
- updated Guzzle dependency to ^7.4
- added full example in /example folder with ddev local configuration
- added option to add additional headers like on https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/headers.htm
- updated documentation

##### 06.05.2021 #####
- [breaking] switched version parameter in constructor to the end

##### 01.03.2021 #####
 - added method to use custom endpoints

##### 08.09.2020 #####
 - added describe method

##### 18.01.2020 #####
 - switched to PHP >7.0
 - renamed class from CRUD to SalesforceFunctions
 - added dependency to ext-json in composer package
