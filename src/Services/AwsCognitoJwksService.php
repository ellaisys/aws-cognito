<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Services;

use Firebase\JWT\JWK;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


/**
 * A class for downloading and caching the Cognito JWKS for the given user pool and
 * region.
 *
 */
class AwsCognitoJwksService
{
    /**
     * The AWS region in which the user pool is located.
     *
     * @var string
     */
    protected $region;

    /**
     * The user pool ID.
     *
     * @var string
     */
    protected $poolId;


    /**
     * Constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     *
     * @return void
     */
    public function __construct(string $region, string $poolId)
    {
        $this->region = $region;
        $this->poolId = $poolId;
    }


    /**
     * @param string $region
     * @param string $poolId
     * @return array
     */
    public function getJwks(): array
    {
        $json = Cache::remember('aws-cognito:jwks-' . $this->poolId, 3600, function () {
            return $this->downloadJwks();
        });

        $keys = json_decode($json, true);
        return JWK::parseKeySet($keys);
    } //Function ends


    /**
     * Download the jwks for the configured user pool
     *
     * @return string
     */
    public function downloadJwks(): string
    {
        $url      = sprintf('https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json', $this->region, $this->poolId);
        $response = Http::get($url);
        $response->throw();

        return $response->body();
    } //Function ends

} //Class ends