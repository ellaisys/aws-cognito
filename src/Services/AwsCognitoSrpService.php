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

    // 3072-bit group from AWS Cognito
    private const N_HEX = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD1'.
        '29024E088A67CC74020BBEA63B139B22514A08798E3404DD'.
        'EF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245'.
        'E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED'.
        'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3D'.
        'C2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F'.
        '83655D23DCA3AD961C62F356208552BB9ED529077096966D'.
        '670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B'.
        'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9'.
        'DE2BCBF6955817183995497CEA956AE515D2261898FA0510'.
        '15728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64'.
        'ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7'.
        'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6B'.
        'F12FFA06D98A0864D87602733EC86A64521F2B18177B200C'.
        'BBE117577A615D6C770988C0BAD946E208E24FA074E5AB31'.
        '43DB5BFCE0FD108E4B82D120A93AD2CAFFFFFFFFFFFFFFFF';

    private const G_HEX = '2';

    private const INFO_BITS = 'Caldera Derived Key';

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
    public function __construct(string $poolId, string $clientId)
    {
        $this->poolId = $poolId;
        $this->clientId = $clientId;

        $this->paramN = gmp_init(self::N_HEX, 16);
        $this->paramG = gmp_init(self::G_HEX, 16);

        /**
         * k = H(N | g)
         */
        $this->paramK = $this->hexHash(
                $this->padHex($this->paramN) . $this->padHex($this->paramG)
            );
    }

    /**
     * Generate SRP_A(public ephemeral value) and a (private ephemeral value)
     */
    public function generateEphemeral(): array
    {
        // Generate a random 128-byte integer for a
        $paramSmallA = gmp_init(bin2hex(random_bytes(128)), 16);

        // Calculate A = g^a mod N
        $paramCapA = gmp_powm($this->paramG, $paramSmallA, $this->paramN);

        return [
            'private_key' => gmp_strval($paramSmallA, 16), // a in hex format
            'public_key' => strtoupper(gmp_strval($paramCapA, 16)), // SRP_A in hex format
        ];
    } //Function ends

    /**
     * Build PASSWORD_VERIFIER challenge response
     */
    public function processChallenge(string $challengeValue,
        string $privateEphemeral): array
    {
        try {
            $payload = json_decode($challengeValue, true);

            // Set the timestamp to the current time in the required format if not present in the payload
            $timestamp = (isset($payload['TIMESTAMP'])) ? $payload['TIMESTAMP'] : $this->generateTimestamp();

            //Check if the required parameters are present
            if (!isset($payload['USER_ID_FOR_SRP']) ||
                !isset($payload['SALT']) ||
                !isset($payload['SECRET_BLOCK']) ||
                !isset($payload['SRP_B']) ||
                !isset($payload['PASSKEY_HASH'])) {
                throw new BadRequestHttpException('Missing required parameters in challenge value');
            }

            //Check if the secret block is present
            $secretBlock = $payload['SECRET_BLOCK'];

            // Get the pool name from the pool ID
            $poolName = $this->getPoolName();

            // Get the SRP parameters and generate A and a from the client request
            $paramSmallA = gmp_init($privateEphemeral, 16);

            // Calculate A = g^a mod N
            $paramCapA = gmp_powm($this->paramG, $paramSmallA, $this->paramN);

            // Set Hex Params
            $salt = gmp_init($payload['SALT'], 16);
            $paramCapB = gmp_init($payload['SRP_B'], 16);
            $userIdForSrp = $payload['USER_ID_FOR_SRP'];
            $userPassHash = $payload['PASSKEY_HASH'];

            //Sign with the Salt
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
            Log::debug('intValue2: ' . gmp_strval($intValue2, 16));

            $exp = gmp_add($paramSmallA, gmp_mul($u, $x));
            Log::debug('Exp: ' . gmp_strval($exp, 16));

            $s = gmp_powm($intValue2, $exp, $this->paramN);
            Log::debug('S: ' . gmp_strval($s, 16));

            $hkdf = $this->computeHkdf(
                hex2bin($this->padHex($s)),
                hex2bin($this->padHex($u))
            );

            // Build the challenge response
            $message = $poolName . $userIdForSrp . base64_decode($secretBlock) . $timestamp;
            $signature = hash_hmac(self::HASH_ALGO, $message, $hkdf, true);

            return [
                'TIMESTAMP' => $timestamp,
                'USERNAME' => $userIdForSrp,
                'PASSWORD_CLAIM_SECRET_BLOCK' => $secretBlock,
                'PASSWORD_CLAIM_SIGNATURE' => base64_encode($signature),
            ];
        } catch (Exception $e) {
            Log::error('AwsCognitoClientHelper:processChallenge:Exception');
            throw $e;
        } //Try-catch ends
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
