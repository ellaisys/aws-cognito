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

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

class AwsCognito
{

    /**
     * The AWS Cognito token.
     *
     * @var \Ellaisys\Cognito\AwsCognitoToken|null
     */
    protected $token;


    /**
     * Get the token.
     *
     * @return \Ellaisys\Cognito\AwsCognitoToken|null
     */
    public function getToken()
    {
        if ($this->token === null) {
            try {
                $this->parseToken();
            } catch (AwsCognitoException $e) {
                $this->token = null;
            }
        } //End if

        return $this->token;
    } //Function ends


    /**
     * Parse the token from the request.
     *
     * @throws \Ellaisys\Cognito\Exceptions\AwsCognitoException
     *
     * @return $this
     */
    public function parseToken()
    {
        if (! $token = $this->parser->parseToken()) {
            throw new AwsCognitoException('The token could not be parsed from the request');
        } //End if

        return $this->setToken($token);
    } //Function ends


    /**
     * Set the token.
     *
     * @param  \Ellaisys\Cognito\AwsCognitoToken|string  $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token instanceof AwsCognitoToken ? $token : new AwsCognitoToken($token);

        return $this;
    } //Function ends


    /**
     * Unset the current token.
     *
     * @return $this
     */
    public function unsetToken()
    {
        $this->token = null;

        return $this;
    } //Function ends

} //Class ends