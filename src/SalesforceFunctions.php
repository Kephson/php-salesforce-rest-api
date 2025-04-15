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
    private const apiVersion = "v48.0";

    /**
     * @var string
     */
    protected string $instanceUrl;

    /**
     * @var string
     */
    protected string $accessToken;

    /**
     * @var string
     */
    protected $apiVersion = "v48.0";

    /**
     * SalesforceFunctions constructor.
     *
     * @param string|null $instanceUrl
     * @param string|null $accessToken
     * @param string $apiVersion Default API version is used from constant
     */
    public function __construct(?string $instanceUrl = null, ?string $accessToken = null, string $apiVersion = self::apiVersion)
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
    public function getInstanceUrl(): string
    {
        return $this->instanceUrl;
    }

    /**
     * @param string $instanceUrl
     */
    public function setInstanceUrl(string $instanceUrl): void
    {
        $this->instanceUrl = $instanceUrl;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     */
    public function setApiVersion(string $apiVersion): void
    {
        $this->apiVersion = $apiVersion;
    }

    /**
     * @param string $query
     * @param array $additionalHeaders
     * @return mixed Array or exception
     * @throws GuzzleException
     */
    public function query(string $query, array $additionalHeaders = []): mixed
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
    public function retrieve(string $object, string $field, string $id, array $additionalHeaders = []): mixed
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
    public function create(string $object, mixed $data, array $additionalHeaders = [], bool $fullResponse = false): mixed
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
    public function update(string $object, string $id, mixed $data, array $additionalHeaders = []): mixed
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
        if ($status !== 204 && $status !== 201 && $status !== 200) {
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
    public function upsert(string $object, string $field, string $id, mixed $data, array $additionalHeaders = []): int
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
        if ($status !== 204 && $status !== 201 && $status !== 200) {
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
    public function delete(string $object, string $id, array $additionalHeaders = []): bool
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
    public function describe(string $object, array $additionalHeaders = []): mixed
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
    public function customEndpoint(string $customEndpoint, string $data, int $successStatusCode = 200, array $additionalHeaders = [], string $method = 'POST'): ResponseInterface
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
                $method,
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
    protected function getHeaders(array $defaultHeaders, array $additionalHeaders): array
    {
        return array_merge_recursive($defaultHeaders, $additionalHeaders);
    }
}
