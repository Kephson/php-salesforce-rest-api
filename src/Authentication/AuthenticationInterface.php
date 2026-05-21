<?php /** @noinspection PhpUnused */

namespace EHAERER\Salesforce\Authentication;

interface AuthenticationInterface
{

    /**
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * @return string
     */
    public function getInstanceUrl(): string;
}
