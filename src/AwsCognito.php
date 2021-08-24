<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sunnydesign\Cognito;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

use Sunnydesign\Cognito\AwsCognitoClaim;
use Sunnydesign\Cognito\AwsCognitoManager;
use Sunnydesign\Cognito\Http\Parser\Parser;

use Exception;
use Sunnydesign\Cognito\Exceptions\AwsCognitoException;
use Sunnydesign\Cognito\Exceptions\InvalidTokenException;

class AwsCognito
{
    /**
     * The authentication provider.
     *
     * @var \Sunnydesign\Cognito\Contracts\Providers\Auth
     */
    protected $auth;


    /**
     * Aws Cognito Manager
     *
     * @var \Sunnydesign\Cognito\AwsCognitoManager
     */
    protected $manager;


    /**
     * The HTTP parser.
     *
     * @var \Sunnydesign\Cognito\Http\Parser\Parser
     */
    protected $parser;


    /**
     * The AwsCognito Claim token
     * 
     * @var \Sunnydesign\Cognito\AwsCognitoClaim|null
     */
    protected $claim;


    /**
     * The AWS Cognito token.
     *
     * @var \Sunnydesign\Cognito\AwsCognitoToken|string|null
     */
    protected $token;


    /**
     * JWT constructor.
     *
     * @param  \Sunnydesign\Cognito\Manager  $manager
     * @param  \Sunnydesign\Cognito\Http\Parser\Parser  $parser
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
     * @return \Sunnydesign\Cognito\AwsCognitoToken|null
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
     * @throws \Sunnydesign\Cognito\Exceptions\AwsCognitoException
     *
     * @return \Sunnydesign\Cognito\AwsCognito
     */
    public function parseToken()
    {
        //Parse the token
        $token = $this->parser->parseToken();

        if (empty($token)) {
            throw new AwsCognitoException('The token could not be parsed from the request');
        } //End if

        return $this->setToken($token);
    } //Function ends


    /**
     * Set the token.
     *
     * @param  \string  $token
     *
     * @return \Sunnydesign\Cognito\AwsCognito
     */
    public function setToken(string $token)
    {
        $this->token = (new AwsCognitoToken($token));
        if (empty($this->token)) {
            throw new AwsCognitoException('The token could not be validated.');
        } //End if

        return $this;
    } //Function ends


    /**
     * Get the token.
     *
     * @return \Sunnydesign\Cognito\AwsCognitoClaim|null
     */
    public function getClaim()
    {
        return (!empty($this->claim))?$this->claim:null;
    } //Function ends


    /**
     * Set the claim.
     *
     * @param  \Sunnydesign\Cognito\AwsCognitoClaim  $claim
     *
     * @return \Sunnydesign\Cognito\AwsCognito
     */
    public function setClaim(AwsCognitoClaim $claim)
    {
        $this->claim = $claim;
        $this->token = $this->setToken($claim->getToken());

        return $this;
    } //Function ends


    /**
     * Unset the current token.
     *
     * @return \Sunnydesign\Cognito\AwsCognito
     */
    public function unsetToken($forceForever = false)
    {
        $tokenKey = $this->token->get();
        $this->manager->release($tokenKey);
        $this->claim = null;
        $this->token = null;

        return $this;
    } //Function ends


    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Sunnydesign\Cognito\AwsCognito
     */
    public function setRequest(Request $request)
    {
        $this->parser->setRequest($request);

        return $this;
    } //Function ends


    /**
     * Get the Parser instance.
     *
     * @return \Sunnydesign\Cognito\Http\Parser\Parser
     */
    public function parser()
    {
        return $this->parser;
    } //Function ends


    /**
     * Authenticate a user via a token.
     *
     * @return \Sunnydesign\Cognito\AwsCognito|false
     */
    public function authenticate()
    {
        $claim = $this->manager->fetch($this->token->get())->decode();
        $this->claim = $claim;

        if (empty($this->claim)) {
            throw new InvalidTokenException();
        } //End if

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
    } //Function ends


    /**
     * Get the authenticated user.
     * 
     * @throws InvalidTokenException
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function user()
    {
        //Get Claim
        if (empty($this->claim)) {
            throw new InvalidTokenException();
        } //End if

        return $this->claim->getUser();
    } //Function ends


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function storeToken()
    {
        return $this->manager->encode($this->claim)->store();
    } //Function ends

} //Class ends