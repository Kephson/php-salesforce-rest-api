<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedClassInspection */

namespace EHAERER\Salesforce\Authentication;

use EHAERER\Salesforce\Exception\SalesforceAuthenticationException;
use EHAERER\Salesforce\Exception\SalesforceException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class PasswordAuthentication implements AuthenticationInterface
{

    /**
     * @var string
     */
    protected string $client;

    /**
     * @var string
     */
    protected string $endPoint;

    /**
     * @var array
     */
    protected array $options;

    /**
     * @var string
     */
    protected string $accessToken;

    /**
     * @var string
     */
    protected string $instanceUrl;

    /**
     * PasswordAuthentication constructor.
     *
     * @param $options
     */
    public function __construct($options)
    {
        $this->endPoint = 'https://login.salesforce.com/';
        $this->options = $options;
    }

    /**
     * @throws SalesforceAuthenticationException
     * @throws SalesforceException
     * @throws GuzzleException
     */
    public function authenticate(): void
    {
        $client = new Client();

        try {
            $request = $client->request(
                "post",
                "{$this->endPoint}services/oauth2/token",
                ['form_params' => $this->options]
            );
        } catch (ClientException $e) {
            throw SalesforceAuthenticationException::fromClientException($e);
        }

        $response = json_decode($request->getBody(), true);

        if ($response) {
            $this->accessToken = $response['access_token'];
            $this->instanceUrl = $response['instance_url'];
        }
    }

    /**
     * @param string $endPoint
     */
    public function setEndpoint(string $endPoint): void
    {
        $this->endPoint = $endPoint;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getInstanceUrl(): string
    {
        return $this->instanceUrl;
    }
}
