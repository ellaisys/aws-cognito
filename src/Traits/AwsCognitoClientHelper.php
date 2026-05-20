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
use Carbon\Carbon;

use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Illuminate\Support\Facades\Log;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client Helper Trait
 */
trait AwsCognitoClientHelper
{
    /**
     * Builds the challenge payload based on the challenge type and value.
     *
     * @param CognitoChallengeTypes $challengeName The name of the challenge.
     * @param string $challengeValue The value associated with the challenge (e.g., MFA code, new password).
     * @param string $username The username of the user responding to the challenge.
     *
     * @return array The constructed challenge payload.
     *
     * @throws BadRequestHttpException If the challenge type or value is invalid.
     */
    protected function buildChallengePayload(CognitoChallengeTypes $challengeName,
        string $challengeValue, string $username): array
    {
        try {
            //Set challenge with username as default data
            $challengePayload=['USERNAME' => $username];

            //Build challenge payload based on the challenge type
            switch ($challengeName) {
                case CognitoChallengeTypes::SELECT_MFA_TYPE:
                    if (!in_array($challengeValue, ['SMS_MFA','EMAIL_MFA','SOFTWARE_TOKEN_MFA'], true)) {
                        throw new BadRequestHttpException('Invalid challenge value');
                    } //End if

                    $challengePayload = array_merge($challengePayload, [
                        'ANSWER' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SMS_MFA:
                    $challengePayload = array_merge($challengePayload, [
                        'SMS_MFA_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SMS_OTP:
                    $challengePayload = array_merge($challengePayload, [
                        'SMS_OTP_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::EMAIL_OTP:
                    $challengePayload = array_merge($challengePayload, [
                        'EMAIL_OTP_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SOFTWARE_TOKEN_MFA:
                    $challengePayload = array_merge($challengePayload, [
                        'SOFTWARE_TOKEN_MFA_CODE' => $challengeValue
                    ]);
                    break;
                
                case CognitoChallengeTypes::NEW_PASSWORD_REQUIRED:
                    $challengePayload = array_merge($challengePayload, [
                        'NEW_PASSWORD' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::WEB_AUTHN:
                    $challengePayload = array_merge($challengePayload, [
                        'CREDENTIAL' => $challengeValue
                    ]);
                    break;

                default:
                    throw new BadRequestHttpException('Invalid challenge type');
                    break;
            } //End Switch
        } catch (Exception $e) {
            Log::error('AwsCognitoClientHelper:buildChallengePayload:Exception');
            throw $e;
        } //Try-catch ends

        return $challengePayload;
    } //Function ends

    /**
     * Creates the Cognito secret hash.
     * @param string $username
     * @param array $payload The payload to which the secret hash will be added.
     * @return array
     */
    protected function cognitoSecretHash(string $username, array $payload): array
    {
        if ($this->boolClientSecret) {
            //Generate secret hash
            $secretHash = $this->hash($username . $this->clientId);

            if (array_key_exists('AuthParameters', $payload)) {
                $payload['AuthParameters'] = array_merge(
                    $payload['AuthParameters'],
                    ['SECRET_HASH' => $secretHash]
                );
            } elseif (array_key_exists('ChallengeResponses', $payload)) {
                $payload['ChallengeResponses'] = array_merge(
                    $payload['ChallengeResponses'],
                    ['SECRET_HASH' => $secretHash]
                );
            } else {
                $payload = array_merge(
                    $payload,
                    ['SecretHash' => $secretHash]
                );
            } //End if
        } //End if

        return $payload;
    } //Function ends

    /**
     * Creates a HMAC from a string.
     *
     * @param string $message
     * @return string
     */
    protected function hash(string $message): string
    {
        $hash = hash_hmac(
            'sha256',
            $message,
            $this->clientSecret,
            true
        );

        return base64_encode($hash);
    } //Function ends

} //Trait ends
