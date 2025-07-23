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
     * Run a SOQL query, returning it's output.
     *
     * @see https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_query.htm
     *
     * @param string $query A SOQL query
     * @param array<string, string> $additionalHeaders
     * @return array<string, mixed>|bool|null Returns the decoded value as an associative array,
     *                                        or null if the JSON cannot be decoded.
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException}
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
     * Retrieve a field from a specified object.
     *
     * @param string $object
     * @param string $field
     * @param string $id
     * @param array<string, mixed> $additionalHeaders
     * @return array<string, mixed>|bool|null Returns the decoded value as an associative array,
     *                                        or null if the JSON cannot be decoded.
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException}
     * @throws SalesforceException On client error (auth, rate limit, etc.)
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
     * Create (insert) an object.
     *
     * @param string $object
     * @param mixed $data A JSON-encodable object
     * @param array<string, mixed> $additionalHeaders
     * @param bool $fullResponse If true, return the full response object. If false, the ID.
     * @return string|mixed The object ID by default. If `$fullResponse` is true, a decoded associative array (or null on failure.)
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException}
     * @throws SalesforceException On client error (auth, rate limit, etc.)
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
     * Update an existing object by ID.
     *
     * @param string $object
     * @param string $id
     * @param mixed $data
     * @param array<string, mixed> $additionalHeaders
     * @return int The request's status code (200, 201, or 204)
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException})
     * @throws SalesforceException On client error (auth, rate limit, etc.)
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
     * Upsert an object. If an ID exists, the object is updated. If not, the object is created. If multiple objects match the ID, a SalesforceException is thrown.
     * @param string $object
     * @param string $field
     * @param string $id
     * @param mixed $data
     * @param array<string, mixed> $additionalHeaders
     * @return int The status code of the request
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException})
     * @throws SalesforceException On client error (auth, rate limit, etc.) or multiple objects matching the ID.
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

        /** @see https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/errorcodes.htm */
        if ($status !== 204 && $status !== 201 && $status !== 200) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $status;
    }

    /**
     * @param string $object
     * @param string $id
     * @param array<string, mixed> $additionalHeaders
     * @return bool
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException}
     * @throws SalesforceException On client error (auth, rate limit, etc.)
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
     * @param string $object
     * @param array<string, mixed> $additionalHeaders
     * @return mixed
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException}
     * @throws SalesforceException On client error (auth, rate limit, etc.)
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
     * @param mixed $data
     * @param int $successStatusCode
     * @param array<string, mixed> $additionalHeaders
     * @return ResponseInterface
     * @throws GuzzleException in case of an error response ({@see \GuzzleHttp\Exception\BadResponseException} or {@see \GuzzleHttp\Exception\RequestException}
     * @throws SalesforceException On client error (auth, rate limit, etc.)
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
     * @param array<string, string> $defaultHeaders
     * @param array<string, mixed> $additionalHeaders
     * @return array<string, mixed>
     */
    protected function getHeaders(array $defaultHeaders, array $additionalHeaders): array
    {
        return array_merge_recursive($defaultHeaders, $additionalHeaders);
    }
}
