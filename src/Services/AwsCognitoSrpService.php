<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Services;

use GMP;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use Ellaisys\Cognito\Providers\StorageProvider;

use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * AWS Cognito SRP Service
 */
class AwsCognitoSrpService
{
    /**
     * Hash algorithm
     */
    private const HASH_ALGO = 'sha256';

    /**
     * Info bits for HKDF
     */
    private const INFO_BITS = 'Caldera Derived Key';

    /**
     * The provider.
     *
     * @var \Ellaisys\Cognito\Providers\StorageProvider
     */
    protected $provider;

    /**
     * Cache prefix for storing ephemeral values
     *
     * @var string
     */
    private string $cachePrefix;

    /**
     * The user pool ID.
     *
     * @var string
     */
    private string $poolId;
    
    /**
     * The client ID.
     *
     * @var string
     */
    private string $clientId;

    /**
     * Flag to indicate if the authentication is for a device
     *
     * @var bool
     */
    private bool $isDeviceAuth = false;

    private GMP $paramN;
    private GMP $paramG;
    private GMP $paramK;

    /**
     * Constructor.
     * @param string $poolId
     * @param string $clientId
     *
     * @return void
     */
    public function __construct(
        StorageProvider $provider, string $cachePrefix,
        string $clientId, string $poolId)
    {
        $this->provider = $provider;
        $this->cachePrefix = $cachePrefix;
        $this->poolId = $poolId;
        $this->clientId = $clientId;

        $this->paramN = gmp_init(config('cognito.srp_parameters.N_HEX'), 16);
        $this->paramG = gmp_init(config('cognito.srp_parameters.G_HEX'), 16);

        /**
         * k = H(N | g)
         */
        $this->paramK = $this->hexHash(
                $this->padHex($this->paramN) . $this->padHex($this->paramG)
            );
    }

    /**
     * Set the device authentication flag
     *
     * @param bool $isDeviceAuth
     * @return void
     */
    public function setIsDeviceAuth(bool $isDeviceAuth): void
    {
        $this->isDeviceAuth = $isDeviceAuth;
    } //Function ends

    /**
     * Generate SRP_A(public ephemeral value) and a (private ephemeral value)
     */
    public function generateEphemeral(?string $sessionKey=null): array
    {
        // Generate a random 20-byte integer for session key
        if ($sessionKey === null) {
            $sessionKey = gmp_init(bin2hex(random_bytes(20)), 16);
            $sessionKey = gmp_strval($sessionKey, 16);
        } //End if

        // Generate a random 128-byte integer for a
        $paramSmallA = gmp_init(bin2hex(random_bytes(128)), 16);

        // Calculate A = g^a mod N
        $paramCapA = gmp_powm($this->paramG, $paramSmallA, $this->paramN);

        // Store the private ephemeral value (a) using the session key
        // as the key for retrieval during challenge processing
        $this->provider->add(
                $this->cachePrefix . $sessionKey,
                gmp_strval($paramSmallA, 16),
                60
            );

        return [
            'session_token' => $sessionKey, // session key in hex format
            'private_key' => gmp_strval($paramSmallA, 16), // a in hex format
            'public_key' => strtoupper(gmp_strval($paramCapA, 16)), // SRP_A in hex format
        ];
    } //Function ends

    /**
     * Build PASSWORD_VERIFIER challenge response
     */
    public function processChallenge(string $challengeValue,
        string $sessionKey, ?string $challengeParams = null): array
    {
        try {
            $payload = json_decode($challengeValue, true);

            /**
             * If the client has computed all response parameters, then
             * return without processing the challenge
             */
            if (isset($payload['PASSWORD_CLAIM_SIGNATURE']))
            {
                return $payload;
            } else {
                //Build challenge response on the server side
                return $this->buildChallengeResponse(
                        $challengeValue, $sessionKey,
                        $challengeParams
                    );
            } //End if
        } catch (Exception $e) {
            Log::error('AwsCognitoSrpService:processChallenge:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Build challenge response on the server side using the parameters
     * from the client request and the challenge parameters from the
     * provider
     */
    private function buildChallengeResponse(string $challengeValue,
        string $sessionKey, ?string $challengeParams = null): array
    {
        try {
            $returnValue = json_decode($challengeValue, true);

            //Check if the required parameters are present
            if (!isset($returnValue['PASSKEY_HASH'])) {
                throw new BadRequestHttpException('Missing required parameters in challenge value');
            } else {
                $paramsData = json_decode($challengeParams, true);
                $payload = array_merge($returnValue, $paramsData);
            } //End if

            // Get the private ephemeral value (a) from the provider using the session token
            $privateEphemeral = '';
            if($this->provider->has($this->cachePrefix . $sessionKey)) {
                $privateEphemeral = $this->provider->get($this->cachePrefix . $sessionKey);
            } else {
                $privateEphemeral = $payload['PRIVATE_KEY'];
            } //End if
            if (empty($privateEphemeral)) {
                throw new HttpException(400, 'Invalid session key or private key not found');
            } //End if

            // Get the SRP parameters and generate A and a from the client request
            $paramSmallA = gmp_init($privateEphemeral, 16);

            // Calculate A = g^a mod N
            $paramCapA = gmp_powm($this->paramG, $paramSmallA, $this->paramN);

            // Set Hex Params
            $salt = gmp_init($payload['SALT'], 16);
            $paramCapB = gmp_init($payload['SRP_B'], 16);

            //Sign with the Salt
            $userPassHash = $returnValue['PASSKEY_HASH'];
            $x = $this->hexHash($this->padHex($salt) . $userPassHash);

            /*
            * u = H(A | B)
            */
            $u = $this->hexHash(
                $this->padHex($paramCapA) . $this->padHex($paramCapB)
            );

            /*
            * S = (B - k * g^x) ^ (a + ux) mod N
            */
            $gModPowXN = gmp_powm($this->paramG, $x, $this->paramN);
            $kgx = gmp_mul($this->paramK, $gModPowXN);
            $intValue2 = gmp_sub($paramCapB, $kgx);
            $exp = gmp_add($paramSmallA, gmp_mul($u, $x));
            $s = gmp_powm($intValue2, $exp, $this->paramN);

            $hkdf = $this->computeHkdf(
                hex2bin($this->padHex($s)),
                hex2bin($this->padHex($u))
            );

            // Build the challenge response
            $message = isset($returnValue['MESSAGE_BASE64']) ? base64_decode($returnValue['MESSAGE_BASE64'], true) : '';
            if (empty($message)) {
                //Check if the secret block is present
                $secretBlock = $returnValue['PASSWORD_CLAIM_SECRET_BLOCK'];

                // Get the current timestamp in the format required by Cognito
                $timestamp = $returnValue['TIMESTAMP'] ?? $this->generateTimestamp();

                if ($this->isDeviceAuth) {
                    $message = $returnValue['DEVICE_GROUP_KEY'] . $returnValue['DEVICE_KEY'];
                } else {
                    // Get the pool name from the pool ID
                    $poolName = $this->getPoolName();

                    $userIdForSrp = $this->isDeviceAuth?$payload['USERNAME']:$payload['USER_ID_FOR_SRP'];
                    $message = $poolName . $userIdForSrp;
                }
                $message .= base64_decode($secretBlock) . $timestamp;
            } //End if

            $signature = hash_hmac(self::HASH_ALGO, $message, $hkdf, true);

            $returnValue = array_merge($returnValue, [
                'PASSWORD_CLAIM_SIGNATURE' => base64_encode($signature),
            ]);

            // Unset some parameters that are not needed in the response
            if (isset($returnValue['PASSKEY_HASH'])) {
                unset($returnValue['PASSKEY_HASH']);
            } //End if
            if (isset($returnValue['MESSAGE_BASE64'])) {
                unset($returnValue['MESSAGE_BASE64']);
            } //End if
            if (isset($returnValue['DEVICE_GROUP_KEY'])) {
                unset($returnValue['DEVICE_GROUP_KEY']);
            } //End if
            if (isset($returnValue['PRIVATE_KEY'])) {
                unset($returnValue['PRIVATE_KEY']);
            } //End if
        } catch (Exception $e) {
            Log::error('AwsCognitoSrpService:buildChallengeResponse:Exception');
            throw $e;
        } //Try-catch ends

        Log::debug($returnValue);
        return $returnValue;
    } //Function ends

    /**
     * HKDF used by Cognito
     */
    private function computeHkdf(string $ikm, string $salt): string
    {
        if (version_compare(PHP_VERSION, '7.1.2', '>=')) {
            return hash_hkdf(self::HASH_ALGO, $ikm, 16, self::INFO_BITS, $salt);
        } else {
            $prk = hash_hmac(self::HASH_ALGO, $ikm, $salt, true);
            $info = self::INFO_BITS . chr(1);
            $hmac = hash_hmac(self::HASH_ALGO, $info, $prk, true);

            // Return the first 16 bytes of the HMAC as the derived key
            return substr($hmac, 0, 16);
        } //End if
    } //Function ends

    /**
     * Calculate a hash from string and left pad with zeros to 64 characters
     *
     * @param  string  $value
     * @return string
     */
    private function hash(string $value): string
    {
        $hash = hash(self::HASH_ALGO, $value);

        return str_repeat('0', 64 - strlen($hash)).$hash;
    } //Function ends

    /**
     * SHA256 returning GMP
     */
    private function hexHash(string $hex): GMP
    {
        $hash = $this->hash(hex2bin($hex));
        return gmp_init($hash, 16);
    } //Function ends

    /**
     * Left pad hex
     */
    private function padHex(GMP $value): string
    {
        $hashStr = $this->normalizeHex($value);

        /**
         * Prevent negative bigint interpretation
         */
        if (strpos('89ABCDEFabcdef', $hashStr[0] ?? '') !== false) {
            $hashStr = '00'.$hashStr;
        } //End if


        return strtoupper($hashStr);
    } //Function ends

    /**
     * Normalize hex
     *
     * No fixed-width padding.
     */
    private function normalizeHex(GMP $value): string {

        $hex = strtoupper(gmp_strval($value, 16));

        /**
         * Ensure even length
         */
        return (strlen($hex) % 2 === 1) ? ('0' . $hex) : $hex;
    } //Function ends

    /**
     * Cognito timestamp
     *
     * Example:
     * Wed May 20 18:05:31 UTC 2026
     */
    private function generateTimestamp(): string
    {
        $now = Carbon::now('UTC');
        return $now->format('D M j H:i:s e Y');
    } //Function ends

    /**
     * Get Pool suffix
     *
     * us-east-1_ABC123 -> ABC123
     */
    private function getPoolName(?string $poolId = null): string
    {
        $poolId = $poolId ?? $this->poolId;
        $poolIdParts = explode('_', $poolId);
        return $poolIdParts[1];
    } //Function ends

} //Class ends
