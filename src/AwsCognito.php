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

use Ellaisys\Cognito\AwsCognitoManager;
use Ellaisys\Cognito\Http\Parser\Parser;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\TokenInvalidException;

class AwsCognito
{
    /**
     * The authentication provider.
     *
     * @var \Ellaisys\Cognito\Contracts\Providers\Auth
     */
    protected $auth;


    /**
     * Aws Cognito Manager
     *
     * @var \Ellaisys\Cognito\AwsCognitoManager
     */
    protected $manager;


    /**
     * The HTTP parser.
     *
     * @var \Ellaisys\Cognito\Http\Parser\Parser
     */
    protected $parser;


    /**
     * The AWS Cognito token.
     *
     * @var \Ellaisys\Cognito\AwsCognitoToken|null
     */
    protected $token;


    /**
     * JWT constructor.
     *
     * @param  \Ellaisys\Cognito\Manager  $manager
     * @param  \Ellaisys\Cognito\Http\Parser\Parser  $parser
     *
     * @return void
     */
    public function __construct(AwsCognitoManager $manager, Parser $parser)
    {
        $this->manager = $manager;
        $this->parser = $parser;
    }


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
        //Parse the token
        $token = $this->parser->parseToken();

        if (empty($token)) {
            throw new AwsCognitoException('The token could not be parsed from the request');
        } else {
            $awsToken = (new AwsCognitoToken($token))->get();
            if (empty($awsToken)) {
                throw new AwsCognitoException('The token could not be validated.');
            } //End if
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
    public function setToken($token, $value=null)
    {
        $this->token = ($token instanceof AwsCognitoToken)?$token:(new AwsCognitoToken($token, $value));

        return $this;
    } //Function ends


    /**
     * Unset the current token.
     *
     * @return $this
     */
    public function unsetToken()
    {
        $tokenKey = $this->token->get();
        $this->token = null;
        $this->manager->release($tokenKey);

        return $this;
    } //Function ends


    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->parser->setRequest($request);

        return $this;
    } //Function ends


    /**
     * Get the Parser instance.
     *
     * @return \Ellaisys\Cognito\Http\Parser\Parser
     */
    public function parser()
    {
        return $this->parser;
    } //Function ends


    /**
     * Authenticate a user via a token.
     *
     * @return \Ellaisys\Cognito\Contracts\JWTSubject|false
     */
    public function authenticate()
    {
        $token = $this->manager->fetch($this->token->get())->decode();
        $this->token = $token;

        return $this; //->user();
    } //Function ends


    /**
     * Alias for authenticate().
     *
     * @return \Tymon\JWTAuth\Contracts\JWTSubject|false
     */
    public function toUser()
    {
        return $this->authenticate();
    }

    /**
     * Get the authenticated user.
     * 
     * @throws TokenInvalidException
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function user()
    {
        $value = $username = null;

        //Get username from token
        $value = $this->token->value();
        if (empty($value)) {
            throw new TokenInvalidException();
        } //End if
        $username = $value['username'];

        dd($username);


        return $this->auth->user();
    }


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function storeToken()
    {
        return $this->manager->encode($this->token)->store();
    } //Function ends

} //Class ends