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

use Aws\Result as AwsResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

use Ellaisys\Cognito\Validators\AwsCognitoTokenValidator;

use Exception;

class AwsCognitoClaim
{

    /**
     * @var string
     */
    public $token;


    /**
     * @var object
     */
    public $data;


    /**
     * @var string
     */
    public $username;


    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;


    /**
     * @var \mixed
     */
    public $sub;


    /**
     * Create a new JSON Web Token.
     *
     * @param  string  $token
     *
     * @return void
     */
    public function __construct(AwsResult $result, Authenticatable $user=null, string $username)
    {
        try {
            $authResult = $result['AuthenticationResult'];
            if (!is_array($authResult)) {
                throw new Exception('Malformed AWS Authentication Result.', 400);
            } //End if

            //Create token object
            $token = $authResult['AccessToken'];

            $this->token = (string) (new AwsCognitoTokenValidator)->check($token);
            $this->data = $authResult;
            $this->username = $username;
            $this->user = $user;
            $this->sub = $user['id'];

        } catch(Exception $e) {
            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Get the token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    } //Function ends


    /**
     * Get the data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    } //Function ends


    /**
     * Get the User.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getUser()
    {
        return $this->user;
    } //Function ends


    /**
     * Get the Sub Data.
     *
     * @return mixed
     */
    public function getSub()
    {
        return $this->sub;
    } //Function ends


    /**
     * Get the token when casting to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getToken();
    } //Function ends

} //Class ends
