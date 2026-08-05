<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Traits;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\AwsCognitoClient;

use Exception;

trait AwsCognitoTrait
{
    /**
     * Get user pool client configuration.
     */
    protected function validateUserPoolClientConfig(Enums\CognitoAuthFlowTypes $authFlowType): bool
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool client configuration
            $response = $client->describeUserPoolClient();

            if ($userPoolClient = $response->get('UserPoolClient')) {
                $explicitAuthFlows = $userPoolClient['ExplicitAuthFlows'] ?? [];

                // Check for AuthFlowTypes
                return in_array('ALLOW_' . $authFlowType->value, $explicitAuthFlows);
            } //End if

        } catch (Exception $exception) {
            $this->error('Error retrieving user pool client configuration.');
        } // Try-catch ends
        return false;
    } //Function ends

} //Trait ends
