<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Traits;

use Config;

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Manages AWS Cognito Client for Group Actions
 */
trait ManageGroupAction
{
    /**
     * List Groups in a user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListGroups.html
     *
     * @param int $maxResults (default: 10, max: 60)
     * @param string|null $nextToken
     *
     * @return \AwsResult
     */
    final public function listGroups(int $maxResults = 10,
        ?string $nextToken = null, ?string $userPoolId = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'Limit' => $maxResults
            ];
            if (!is_null($nextToken)) {
                $payload['NextToken'] = $nextToken;
            } //End if

            return $this->client->listGroups($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageGroupAction:listGroups:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Create a new group in a user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_CreateGroup.html
     *
     * @param string $groupName
     * @param string $description
     * @param int|null $precedence
     * @param string|null $roleArn
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function createGroup(string $groupName,
        string $description = 'Default Group', ?int $precedence = null,
        ?string $roleArn = null, ?string $userPoolId = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'GroupName' => $groupName,
                'Description' => $description,
                'UserPoolId' => $userPoolId ?? $this->poolId
            ];

            if (!is_null($precedence)) {
                $payload['Precedence'] = $precedence;
            } //End if

            if (!is_null($roleArn)) {
                $payload['RoleArn'] = $roleArn;
            } //End if

            return $this->client->createGroup($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageGroupAction:createGroup:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends
} //Trait ends
