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
 * Manages AWS Cognito Client for Terms Actions
 */
trait ManageTermsAction
{
    /**
     * List terms in a user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListTerms.html
     *
     * @param int $maxResults (default: 10, max: 60)
     * @param string|null $nextToken
     *
     * @return \AwsResult
     */
    final public function listTerms(int $maxResults = 10,
        ?string $nextToken = null, ?string $userPoolId = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'MaxResults' => $maxResults
            ];
            if (!is_null($nextToken)) {
                $payload['NextToken'] = $nextToken;
            } //End if

            return $this->client->listTerms($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageTermsAction:listTerms:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Describe a term in a user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_DescribeTerms.html
     *
     * @param string $termId
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function describeTerms(string $termId,
        ?string $userPoolId = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'TermId' => $termId
            ];

            return $this->client->describeTerms($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageTermsAction:describeTerms:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Create a new term in a user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_CreateTerms.html
     *
     * @param string $termsName
     * @param array<string, string> $links (string : string) array of links
     * @param string|null $userPoolId
     * @param string|null $clientId
     *
     * @return \AwsResult
     */
    final public function createTerms(string $termsName, array $links,
        ?string $userPoolId = null, ?string $clientId = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $clientId ?? $this->clientId,
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'TermsName' => $termsName,
                'Links' => $links,
                'Enforcement' => 'NONE',
                'TermsSource' => 'LINK'
            ];

            return $this->client->createTerms($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageTermsAction:createTerms:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

} // Trait ends
