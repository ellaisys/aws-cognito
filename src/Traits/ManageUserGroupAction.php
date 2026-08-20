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
 * Manages AWS Cognito Client for User Group Actions
 */
trait ManageUserGroupAction
{
    /**
     * Add a user to a group.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminAddUserToGroup.html
     *
     * @param string $username
     * @param string $groupName
     *
     * @return \AwsResult
     */
    final public function adminAddUserToGroup(string $username,
        string $groupName): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'GroupName' => $groupName,
                'UserPoolId' => $this->poolId
            ];

            return $this->client->adminAddUserToGroup($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserGroupAction:adminAddUserToGroup:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Remove a user from a group.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminRemoveUserFromGroup.html
     *
     * @param string $username
     * @param string $groupName
     *
     * @return \AwsResult
     */
    final public function adminRemoveUserFromGroup(string $username,
        string $groupName): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'GroupName' => $groupName,
                'UserPoolId' => $this->poolId
            ];

            return $this->client->adminRemoveUserFromGroup($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserGroupAction:adminRemoveUserFromGroup:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * List groups for a user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminListGroupsForUser.html
     *
     * @param string $username
     * @param string|null $nextToken
     * @param int $maxResults (default: 10, max: 60)
     *
     * @return \AwsResult
     */
    final public function adminListGroupsForUser(string $username,
        ?string $nextToken = null, int $maxResults = 10): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'UserPoolId' => $this->poolId,
                'Limit' => $maxResults
            ];
            if (!is_null($nextToken)) {
                $payload['NextToken'] = $nextToken;
            } //End if

            return $this->client->adminListGroupsForUser($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserGroupAction:adminListGroupsForUser:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * List users in a group.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListUsersInGroup.html
     *
     * @param string $groupName
     * @param string|null $nextToken
     * @param int $maxResults (default: 10, max: 60)
     *
     * @return \AwsResult
     */
    final public function listUsersInGroup(string $groupName,
        ?string $nextToken = null, int $maxResults = 10): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'GroupName' => $groupName,
                'UserPoolId' => $this->poolId,
                'Limit' => $maxResults
            ];
            if (!is_null($nextToken)) {
                $payload['NextToken'] = $nextToken;
            } //End if

            return $this->client->listUsersInGroup($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserGroupAction:listUsersInGroup:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends
} //Trait ends
