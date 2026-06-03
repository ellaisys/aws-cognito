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
                case CognitoChallengeTypes::PASSWORD:
                     $challengePayload = array_merge($challengePayload, [
                        'PASSWORD' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::PASSWORD_SRP:
                     $challengePayload = array_merge($challengePayload, [
                        'SRP_A' => $challengeValue
                    ]);
                    break;

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

                case CognitoChallengeTypes::SOFTWARE_TOKEN_MFA:
                    $challengePayload = array_merge($challengePayload, [
                        'SOFTWARE_TOKEN_MFA_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SELECT_CHALLENGE:
                    $challengePayload = array_merge($challengePayload, [
                        'ANSWER' => $challengeValue
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

                case CognitoChallengeTypes::WEB_AUTHN:
                    $challengePayload = array_merge($challengePayload, [
                        'CREDENTIAL' => $challengeValue
                    ]);
                    break;                    

                case CognitoChallengeTypes::NEW_PASSWORD_REQUIRED:
                    $challengePayload = array_merge($challengePayload, [
                        'NEW_PASSWORD' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::PASSWORD_VERIFIER:
                case CognitoChallengeTypes::DEVICE_SRP_AUTH:
                case CognitoChallengeTypes::DEVICE_PASSWORD_VERIFIER:
                    $challengePayload = json_decode($challengeValue, true);
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
     * Creates a HMAC from a string for the Cognito secret hash.
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

    /**
     * Builds the payload for inviting a user, including attributes,
     * client metadata, and message action.
     * @param array $payload The initial payload to be built upon.
     * @param string|null $password (optional)
     * @param array|null $attributes (optional)
     * @param array|null $clientMetadata (optional)
     * @param string|null $messageAction (optional)
     * @return array The constructed payload for inviting a user.
     */
    protected function buildInviteUserPayload(
        array $payload, ?string $password = null,
        ?array $attributes = [], ?array $clientMetadata = null,
        ?string $messageAction = null): array
    {
        try {
            //Validate phone for MFA
            if (config('cognito.mfa_setup')=="MFA_ENABLED" && empty($attributes['phone_number'])) {
                throw new HttpException(400, 'ERROR_MFA_ENABLED_PHONE_MISSING');
            } //End if
            
            //Force validate email
            if (!empty($attributes['email'])) {
                $attributes['email_verified'] = config('cognito.force_new_user_email_verified', false)? 'true' : 'false';
            } //End if

            // Add user attributes
            if (!empty($attributes)) {
                $payload['UserAttributes'] = $this->formatAttributes($attributes);
            } //End if

            //Set Client Metadata
            if (!empty($clientMetadata)) {
                $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
            } //End if

            //Set Temporary password
            if (!empty($password)) {
                $payload['TemporaryPassword'] = $password;
            } //End if

            //Set Message Action
            if (!empty($messageAction) && in_array($messageAction, ['RESEND', 'SUPPRESS'])) {
                $payload['MessageAction'] = $messageAction;
            } //End If

            //Set Delivery Mediums
            if (config('cognito.add_user_delivery_mediums')!="NONE") {
                if (config('cognito.add_user_delivery_mediums')=="BOTH") {
                    $payload['DesiredDeliveryMediums'] = ['EMAIL', 'SMS'];
                } else {
                    $defaultDeliveryMedium = config('cognito.add_user_delivery_mediums', "EMAIL");
                    $payload['DesiredDeliveryMediums'] = [ $defaultDeliveryMedium ];
                } //End if
            } //End if
            
            if (config('cognito.mfa_setup')=="MFA_ENABLED") {
                $defaultDeliveryMedium = 'SMS';
                $payload['DesiredDeliveryMediums'] = [ $defaultDeliveryMedium ];
            } //End if
        } catch (Exception $e) {
            Log::error('AwsCognitoClientHelper:buildInviteUserPayload:Exception');
            throw $e;
        } //Try-catch ends

        return $payload;
    } //Function ends

    /**
     * Format attributes in Name/Value array.
     *
     * @param array $attributes
     * @return array
     */
    protected function formatAttributes(array $attributes): array
    {
        $userAttributes = [];

        foreach ($attributes as $key => $value) {
            $userAttributes[] = [
                'Name' => $key,
                'Value' => $value,
            ];
        } //Loop ends

        return $userAttributes;
    } //Function ends

} //Trait ends
