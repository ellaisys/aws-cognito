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

use Ellaisys\Cognito\AwsCognitoToken;
use Ellaisys\Cognito\AwsCognitoClaim;
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
     * @var string|null
     */
    protected $token;


    /**
     * The AwsCognito Claim token
     *
     * @var \Ellaisys\Cognito\AwsCognitoClaim|null
     */
    protected $claim;


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
     * Encode the claim.
     *
     * @return \AwsCognitoManager
     */
    public function encode(AwsCognitoClaim $claim)
    {
        $this->claim = $claim;
        $this->token = $claim->getToken();

        return $this;
    } //Function ends


    /**
     * Decode token.
     *
     * @return \boolean
     */
    public function decode()
    {
        return ($this->claim)?$this->claim:null;
    } //Function ends


    /**
     * Persist token.
     *
     * @return \boolean
     */
    public function store()
    {
        $data = $this->claim->getData();
        $durationInSecs = ($data)?(int) $data['ExpiresIn']:3600;
        return $this->storeData($this->token, $this->claim, $durationInSecs);
    } //Function ends


    /**
     * Get Token from store.
     *
     * @return \AwsCognitoManager
     */
    public function fetch(string $token)
    {
        $this->token = $token;
        $this->claim = $this->fetchData($token);

        return $this;
    } //Function ends


    /**
     * Release token.
     *
     * @return \AwsCognitoManager
     */
    public function release(string $token)
    {
        $this->provider->destroy($token);

        return $this;
    } //Function ends


    /**
     * Save challenge object
     *
     * @param  string  $key
     * @param  mixed  $data
     * @param  int  $durationInSecs
     *
     * @return \boolean
     */
    public function storeData(string $key, $data, int $durationInSecs=3600)
    {
        $this->provider->add($key, json_encode($data), $durationInSecs);
        return true;
    } //Function ends


    /**
     * Retrive the data by key.
     *
     * @return \json
     */
    public function fetchData(string $key)
    {
        $data = $this->provider->get($key);
        return $data?json_decode($data, true):null;
    } //Function ends

} //Class ends
