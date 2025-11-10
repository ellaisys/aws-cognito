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

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\AwsCognitoManager;
use Ellaisys\Cognito\Http\Parser\Parser;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;

class AwsCognito
{
    /**
     * Indicates if AWSCognito routes will be run.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * Indicates if AWSCognito views will be run.
     *
     * @var bool
     */
    public static $registersViews = true;

    /**
     * Indicates if AWSCognito migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;


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
     * The AwsCognito Claim token
     *
     * @var \Ellaisys\Cognito\AwsCognitoClaim|null
     */
    protected $claim;


    /**
     * The AWS Cognito token.
     *
     * @var \Ellaisys\Cognito\AwsCognitoToken|string|null
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
     * Configure AWS Cognito to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes(): void
    {
        static::$registersRoutes = false;
    } //Function ends


    /**
     * Configure AWS Cognito to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations(): void
    {
        static::$runsMigrations = false;
    } //Function ends


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
            } //try-catch ends
        } //End if

        return $this->token;
    } //Function ends


    /**
     * Parse the token from the request.
     *
     * @throws \Ellaisys\Cognito\Exceptions\AwsCognitoException
     *
     * @return \Ellaisys\Cognito\AwsCognito
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
     * @return \Ellaisys\Cognito\AwsCognito
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
     * @return \Ellaisys\Cognito\AwsCognitoClaim|null
     */
    public function getClaim()
    {
        return (!empty($this->claim))?$this->claim:null;
    } //Function ends


    /**
     * Set the claim.
     *
     * @param  \Ellaisys\Cognito\AwsCognitoClaim  $claim
     *
     * @return \Ellaisys\Cognito\AwsCognito
     */
    public function setClaim(AwsCognitoClaim $claim)
    {
        $this->claim = $claim;
        $this->token = $this->setToken($claim->getToken());

        return $this;
    } //Function ends


    /**
     * Get the challenge data.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function getChallengeData(string $key)
    {
        return $this->manager->fetchData($key);
    } //Function ends


    /**
     * Set the challenge data.
     *
     * @param  string  $key
     * @param  mixed  $data
     * @param  int  $durationInSecs
     *
     * @return mixed
     */
    public function setChallengeData(string $key, $data, int $durationInSecs=3600)
    {
        return $this->manager->storeData($key, $data, $durationInSecs);
    } //Function ends


    /**
     * Unset the current token.
     *
     * @return \Ellaisys\Cognito\AwsCognito
     */
    public function unsetToken(bool $forceForever = false)
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
     * @return \Ellaisys\Cognito\AwsCognito
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
     * @return \Ellaisys\Cognito\AwsCognito|false
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
