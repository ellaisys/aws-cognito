<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
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
    private $dataUserPool;

    /**
     * The AWS Cognito User Pool data.
     *
     * @var \Ellaisys\Cognito\AwsCognitoClient
     */
    private $client;

    
    /**
     * Create a new AWS Cognito User Pool instance.
     */
    public function __construct(AwsCognitoClient $client)
    {
        $this->client = $client;
        $response = $client->describeUserPool();
        $this->dataUserPool = $response->get('UserPool');
    } //Function ends


    //Setter and Getter
    public function getDataUserPool()
    {
        if(empty($this->dataUserPool)) {
            $response = $client->describeUserPool();
            $this->dataUserPool = $response->get('UserPool');
        } //If ends
        Log::info($this->dataUserPool);
        return $this->dataUserPool;
    } //Function ends
    public function setDataUserPool($dataUserPool)
    {
        $this->dataUserPool = $dataUserPool;
    } //Function ends


    /**
     * Get Password Policy.
     */
    public function getPasswordPolicy(bool $regex = false)
    {
        if(empty($this->dataUserPool)) {
            $this->getDataUserPool();
        } //If ends

        if (!isset($this->dataUserPool['Policies']['PasswordPolicy']) &&
            !is_array($this->dataUserPool['Policies']['PasswordPolicy'])) {
            throw new AwsCognitoException('Password policy not found in user pool.');
        } //If ends
        $passwordPolicy = $this->dataUserPool['Policies']['PasswordPolicy'];

        if ($regex) {
            $regexString = '/^';
            foreach ($passwordPolicy as $key => $value) {
                switch ($key) {
                    case 'MinimumLength':
                        $minValue = $value;
                        break;
                    
                    case 'RequireUppercase':
                        if ($value) {
                            $regexString .= '(?=.*[A-Z])';
                        } //If ends
                        break;

                    case 'RequireLowercase':
                        if ($value) {
                            $regexString .= '(?=.*[a-z])';
                        } //If ends
                        break;

                    case 'RequireNumbers':
                        if ($value) {
                            $regexString .= '(?=.*\d)';
                        } //If ends
                        break;

                    case 'RequireSymbols':
                        if ($value) {
                            $regexString .= '(?=.*[!@#$%^&*])';
                        } //If ends
                        break;
                    
                    default:
                        # code...
                        break;
                } //Switch ends
            } //Foreach ends
            $regexString .= '([^\s]){' . $minValue . ',}';
            $regexString .= '$/';
            return $regexString;
        } else {
            return $passwordPolicy;
        } //If ends
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