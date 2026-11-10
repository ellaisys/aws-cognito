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
     */
    final public function getPasswordPolicy(bool $regex = false, ?string $userPoolId = null)
    {
        if(empty($this->dataUserPool)) {
            $this->getDataUserPool($userPoolId);
        } //If ends

        if (!isset($this->dataUserPool['Policies']['PasswordPolicy']) &&
            !is_array($this->dataUserPool['Policies']['PasswordPolicy'])) {
            throw new AwsCognitoException('Password policy not found in user pool.');
        } //If ends
        $passwordPolicy = $this->dataUserPool['Policies']['PasswordPolicy'];

        if ($regex) {
            return $this->processPolicy($passwordPolicy);
        } else {
            return $passwordPolicy;
        } //If ends
    } //Function ends

    /**
     * Process Password Policy.
     *
     * @param array $passwordPolicy
     *
     * @return array
     */
    private function processPolicy($passwordPolicy): array
    {
        $regexString = '/^';
        $messageText = '';
        foreach ($passwordPolicy as $key => $value) {
            switch ($key) {
                case 'MinimumLength':
                    $minValue = $value;
                    $messageText .= 'minimum length of ' . $value . ', ';
                    break;
                
                case 'RequireUppercase':
                    if ($value) {
                        $regexString .= '(?=.*[A-Z])';
                        $messageText .= 'one uppercase letter, ';
                    } //If ends
                    break;

                case 'RequireLowercase':
                    if ($value) {
                        $regexString .= '(?=.*[a-z])';
                        $messageText .= 'one lowercase letter, ';
                    } //If ends
                    break;

                case 'RequireNumbers':
                    if ($value) {
                        $regexString .= '(?=.*\d)';
                        $messageText .= 'one number, ';
                    } //If ends
                    break;

                case 'RequireSymbols':
                    if ($value) {
                        //Generate the regex for special characters
                        $regexString .= '(?=.*[\^$*.\[\]{}\(\)?\-\"!@#%&\/,><\':;|_~`=+])';
                        $messageText .= 'one special character, ';
                    } //If ends
                    break;
                
                default:
                    # code...
                    break;
            } //Switch ends
        } //Foreach ends
        $regexString .= '([^\s]){' . $minValue . ',99}';
        $regexString .= '$/';
        return ['regex' => $regexString, 'message' => $messageText];
    } //Function ends

    /**
     * Get Schema Attributes.
     */
    public function getSchemaAttributes()
    {
        if(empty($this->dataUserPool)) {
            $this->getDataUserPool();
        } //If ends

        if (!isset($this->dataUserPool['SchemaAttributes'])) {
            throw new AwsCognitoException('Schema attributes not found in user pool.');
        } //If ends
        return $this->dataUserPool['SchemaAttributes'];
    } //Function ends

} //Class ends
