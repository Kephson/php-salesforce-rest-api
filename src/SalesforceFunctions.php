<?php
/** @noinspection PhpUnused */

/** @noinspection PhpUndefinedClassInspection */

namespace EHAERER\Salesforce;

use EHAERER\Salesforce\Exception\SalesforceException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class SalesforceFunctions
{

    /**
     * @var string
     */
    const apiVersion = "v48.0";

    /**
     * @var string
     */
    protected $instanceUrl;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $apiVersion = "v48.0";

    /**
     * SalesforceFunctions constructor.
     *
     * @param null $instanceUrl
     * @param null $accessToken
     * @param string $apiVersion Default API version is used from constant
     */
    public function __construct($instanceUrl = null, $accessToken = null, $apiVersion = self::apiVersion)
    {
        $this->apiVersion = $apiVersion;

        if ($instanceUrl) {
            $this->setInstanceUrl($instanceUrl);
        }

        if ($accessToken) {
            $this->setAccessToken($accessToken);
        }
    }

    /**
     * @return string
     */
    public function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    /**
     * @param string $instanceUrl
     */
    public function setInstanceUrl($instanceUrl)
    {
        $this->instanceUrl = $instanceUrl;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     */
    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    /**
     * @param string $query
     * @param array $additionalHeaders
     * @return mixed Array or exception
     * @throws GuzzleException
     */
    public function query($query, $additionalHeaders = [])
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/query";

        $headers = $this->getHeaders(
            ['Authorization' => "OAuth {$this->accessToken}"],
            $additionalHeaders
        );

        $client = new Client();
        $request = $client->request(
            'GET',
            $url,
            [
                'headers' => $headers,
                'query' => [
                    'q' => $query
                ]
            ]
        );

        return json_decode($request->getBody(), true);
    }

    /**
     * @param $object
     * @param $field
     * @param $id
     * @param array $additionalHeaders
     * @return mixed
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function retrieve($object, $field, $id, $additionalHeaders = [])
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/sobjects/{$object}/{$field}/{$id}";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}",
                'Content-type' => 'application/json'
            ],
            $additionalHeaders
        );

        $client = new Client();

        try {
            $request = $client->request(
                'GET',
                $url,
                [
                    'headers' => $headers,
                ]
            );
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        $status = $request->getStatusCode();

        if ($status !== 200) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return json_decode($request->getBody(), true);
    }

    /**
     * @param $object
     * @param $data
     * @param array $additionalHeaders
     * @param bool $fullResponse
     * @return mixed
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function create($object, $data, $additionalHeaders = [], $fullResponse = false)
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/sobjects/{$object}/";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}",
                'Content-type' => 'application/json'
            ],
            $additionalHeaders
        );

        $client = new Client();

        try {
            $request = $client->request(
                'POST',
                $url,
                [
                    'headers' => $headers,
                    'json' => $data
                ]
            );

            $status = $request->getStatusCode();
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        if ($status !== 201) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        $response = json_decode($request->getBody(), true);
        if ($fullResponse) {
            return $response;
        }
        return $response["id"];
    }

    /**
     * @param $object
     * @param $id
     * @param $data
     * @param array $additionalHeaders
     * @return int
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function update($object, $id, $data, $additionalHeaders = [])
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/sobjects/{$object}/{$id}";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}",
                'Content-type' => 'application/json'
            ],
            $additionalHeaders
        );

        $client = new Client();

        try {
            $request = $client->request(
                'PATCH',
                $url,
                [
                    'headers' => $headers,
                    'json' => $data
                ]
            );
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        $status = $request->getStatusCode();

        /* @see https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/errorcodes.htm */
        if ($status !== 204 || $status !== 201 || $status !== 200) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $status;
    }

    /**
     * @param $object
     * @param $field
     * @param $id
     * @param $data
     * @param array $additionalHeaders
     * @return int
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function upsert($object, $field, $id, $data, $additionalHeaders = [])
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/sobjects/{$object}/{$field}/{$id}";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}",
                'Content-type' => 'application/json'
            ],
            $additionalHeaders
        );

        $client = new Client();

        try {
            $request = $client->request(
                'PATCH',
                $url,
                [
                    'headers' => $headers,
                    'json' => $data
                ]
            );
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        $status = $request->getStatusCode();

        /* @see https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/errorcodes.htm */
        if ($status !== 204 || $status !== 201 || $status !== 200) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $status;
    }

    /**
     * @param $object
     * @param $id
     * @param array $additionalHeaders
     * @return bool
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function delete($object, $id, $additionalHeaders = [])
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/sobjects/{$object}/{$id}";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}"
            ],
            $additionalHeaders
        );

        try {
            $client = new Client();
            $request = $client->request(
                'DELETE',
                $url,
                [
                    'headers' => $headers
                ]
            );
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        $status = $request->getStatusCode();

        if ($status !== 204) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return true;
    }

    /**
     * @param $object
     * @param array $additionalHeaders
     * @return mixed
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function describe($object, $additionalHeaders = [])
    {
        $url = "{$this->instanceUrl}/services/data/{$this->apiVersion}/sobjects/{$object}/describe/";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}",
                'Content-type' => 'application/json',
            ],
            $additionalHeaders
        );

        $client = new Client();

        try {
            $request = $client->request(
                'GET',
                $url,
                [
                    'headers' => $headers,
                ]
            );
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        $status = $request->getStatusCode();

        if ($status !== 200) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return json_decode($request->getBody(), true);
    }

    /**
     * @param string $customEndpoint all behind /services/
     * @param $data
     * @param int $successStatusCode
     * @param array $additionalHeaders
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws SalesforceException
     */
    public function customEndpoint($customEndpoint, $data, $successStatusCode = 200, $additionalHeaders = [])
    {
        /* customEndpoint could be all behind /services/ */
        $url = "{$this->instanceUrl}/services/{$customEndpoint}";

        $headers = $this->getHeaders(
            [
                'Authorization' => "OAuth {$this->accessToken}",
                'Content-type' => 'application/json',
            ],
            $additionalHeaders
        );

        $client = new Client();

        try {
            $request = $client->request(
                'POST',
                $url,
                [
                    'headers' => $headers,
                    'json' => $data
                ]
            );

            $status = $request->getStatusCode();
        } catch (ClientException $e) {
            throw SalesforceException::fromClientException($e);
        }

        if ($status !== $successStatusCode) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $request;
    }

    /**
     * merge default headers with additional headers
     *
     * @param array $defaultHeaders
     * @param array $additionalHeaders
     * @return array
     */
    protected function getHeaders($defaultHeaders, $additionalHeaders)
    {
        return array_merge_recursive($defaultHeaders, $additionalHeaders);
    }
}
