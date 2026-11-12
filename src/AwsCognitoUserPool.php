<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;

class AwsCognitoUserPool
{
    /**
     * The AWS Cognito User Pool data.
     *
     * @var \Ellaisys\Cognito\AwsCognitoUserPool
     */
    private array $dataUserPool;

    /**
     * The AWS Cognito User Pool data.
     *
     * @var \Ellaisys\Cognito\AwsCognitoClient
     */
    private AwsCognitoClient $client;
    
    /**
     * Create a new AWS Cognito User Pool instance.
     */
    public function __construct(AwsCognitoClient $client, ?string $userPoolId = null)
    {
        $this->client = $client;
        $this->dataUserPool = $this->getDataUserPool($userPoolId);
    } //Function ends

    //Setter and Getter
    final public function getDataUserPool(?string $userPoolId = null): array
    {
        if(empty($this->dataUserPool)) {
            $response = $this->client->describeUserPool($userPoolId);
            $this->dataUserPool = $response->get('UserPool');
        } //If ends
        return $this->dataUserPool;
    } //Function ends
    final public function setDataUserPool(array $dataUserPool): void
    {
        $this->dataUserPool = $dataUserPool;
    } //Function ends

    /**
     * Get a Password Policy.
     * @param string|null $userPoolId
     *
     * @return array
     */
    final public function getPasswordPolicy(?string $userPoolId = null): array
    {
        if(empty($this->dataUserPool)) {
            $this->getDataUserPool($userPoolId);
        } //If ends

        if (!isset($this->dataUserPool['Policies']['PasswordPolicy']) &&
            !is_array($this->dataUserPool['Policies']['PasswordPolicy'])) {
            throw new AwsCognitoException('Password policy not found in user pool.');
        } //If ends
        $passwordPolicy = $this->dataUserPool['Policies']['PasswordPolicy'];

        return $passwordPolicy;
    } //Function ends

} //Class ends
