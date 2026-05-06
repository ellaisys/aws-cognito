<?php

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

} //Trait ends
