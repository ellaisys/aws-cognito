<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Validators;

use Firebase\JWT\JWT;
use Ellaisys\Cognito\Services\AwsCognitoJwksService;
use Illuminate\Support\Facades\Log;

use Exception;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;

class AwsCognitoTokenValidator
{
    /**
     * Check the structure of the token.
     * @param string $value
     * @return string|null
     *
     * @throws \Ellaisys\Cognito\Exceptions\InvalidTokenException
     */
    public function check($value): ?string
    {
        return $this->validateToken($value);
    } //Function ends

    /**
     * Decode the JWT token.
     * @param string $value
     * @return string|object|null
     *
     * @throws \Ellaisys\Cognito\Exceptions\InvalidTokenException
     */
    public function decode(string $token): string|object|null
    {
        return $this->validateToken($token, true);
    } //Function ends

    /**
     * Validate and decode the JWT token.
     * @param string $token
     * @param bool $isDecodedToken (return the decoded token or the original token).
     * @return string|object|null
     *
     * @throws \Ellaisys\Cognito\Exceptions\InvalidTokenException
     */
    protected function validateToken(string $token, bool $isDecodedToken=false): string|object|null
    {
        try {
            if ($this->validateStructure($token)) {
                $jwksService = app()->make(AwsCognitoJwksService::class);
                $jwksKeys = $jwksService->getJwks();

                //Allow 10 seconds leeway to account for clock skew
                JWT::$leeway = 10;

                //Decode the token
                $decodedToken = JWT::decode($token, $jwksKeys);
            } else {
                throw new InvalidTokenException();
            } //End if
        } catch ( SignatureInvalidException
            | BeforeValidException
            | ExpiredException
            | Exception $e) {
            throw new InvalidTokenException($e->getMessage());
        } //End try-catch
        
        return ($isDecodedToken) ? $decodedToken : $token;
    } //Function ends

    /**
     * Validate the structure of the JWT token.
     * @param string $token
     * @return bool
     *
     * @throws \Ellaisys\Cognito\Exceptions\InvalidTokenException
     */
    protected function validateStructure($token): bool
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new InvalidTokenException();
            } //End if

            $parts = array_filter(array_map('trim', $parts));

            if (count($parts) !== 3 || implode('.', $parts) !== $token) {
                throw new InvalidTokenException('Malformed token');
            }
        } catch(Exception $exception) {
            throw $exception;
        } //End try-catch
        
        return true;
    } //Function ends

} //Class ends
