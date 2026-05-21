<?php /** @noinspection PhpUnused */

namespace EHAERER\Salesforce\Exception;

use Exception;
use GuzzleHttp\Exception\ClientException;

class SalesforceException extends Exception
{

    /**
     * @var string
     */
    private static string $errorMessage = "Salesforce request error";

    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @param ClientException $e
     * @return SalesforceException
     */
    public static function fromClientException(ClientException $e): SalesforceException
    {
        $responseString = $e->getResponse()->getBody()->getContents();
        $responseData = json_decode($responseString, true);
        $ret = new self(self::$errorMessage . ': ' . $e->getMessage(), $e->getResponse()->getStatusCode());
        $ret->setErrors($responseData);
        return $ret;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }
}
