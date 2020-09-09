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

use Ellaisys\Cognito\AwsCognitoToken;
use Ellaisys\Cognito\Providers\StorageProvider;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\TokenBlacklistedException;

class AwsCognitoManager
{
    /**
     * The provider.
     *
     * @var \Ellaisys\Cognito\Providers\StorageProvider
     */
    protected $provider;


    /**
     * The blacklist.
     *
     * @var \Tymon\JWTAuth\Blacklist
     */
    protected $blacklist;


    /**
     * The AWS Cognito token.
     *
     * @var \Ellaisys\Cognito\AwsCognitoToken|null
     */
    protected $token;


    /**
     * The AWS Cognito token key.
     *
     * @var \string|null
     */
    protected $tokenKey;


    /**
     * The AWS Cognito token value.
     *
     * @var \array|null
     */
    protected $tokenValue;


    /**
     * Constructor.
     *
     * @param  \Ellaisys\Cognito\Providers\StorageProvider  $provider
     * @param  \Tymon\JWTAuth\Blacklist  $blacklist
     * @param  \Tymon\JWTAuth\Factory  $payloadFactory
     *
     * @return void
     */
    public function __construct(StorageProvider $provider, $blacklist=null)
    {
        $this->provider = $provider;
        $this->blacklist = $blacklist;
    }


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function encode(AwsCognitoToken $token)
    {
        $this->token = $token;
        $this->tokenKey = $token->get();
        $this->tokenValue = $token->value();

        return $this;
    } //Function ends


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function decode()
    {
        return new AwsCognitoToken($this->tokenKey, $this->tokenValue);
    } //Function ends


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function store()
    {
        $key = $this->tokenKey;
        $value = json_encode($this->tokenValue);
        $duration = ($this->tokenValue)?(int) $this->tokenValue['ExpiresIn']:3600;

        $this->provider->add($key, $value, $duration);

        return true;
    } //Function ends


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function fetch(string $tokenKey)
    {
        $this->tokenKey = $tokenKey;
        $value = $this->provider->get($tokenKey);
        $this->tokenValue = $value?json_decode($value, true):null;

        return $this;
    } //Function ends


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function release(string $tokenKey)
    {
        $this->provider->destroy($tokenKey);

        return $this;
    } //Function ends

} //Class ends