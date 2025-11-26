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
     *
     * @param  string  $value
     *
     * @return string
     */
    public function check($value): string|null
    {
        return $this->validateToken($value);
    }


    /**
     * Decode the JWT token.
     *
     * @param  string  $value
     *
     * @return string
     */
    public function decode(string $token): mixed
    {
        return $this->validateToken($token, true);
    }


    /**
     * @param  string  $token
     *
     * @throws \Ellaisys\Cognito\Exceptions\InvalidTokenException
     *
     * @return string
     */
    protected function validateToken(string $token, bool $isDecodedToken=false): mixed
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
                throw new InvalidTokenException('Invalid Token');
            } //End if
        } catch ( SignatureInvalidException
            | BeforeValidException
            | ExpiredException
            | Exception $e) {
            throw new InvalidTokenException($e->getMessage());
        } //End try-catch
        
        return ($isDecodedToken)?$decodedToken:$token;
    } //Function ends


    /**
     * @param  string  $token
     *
     * @throws \Ellaisys\Cognito\Exceptions\InvalidTokenException
     *
     * @return bool
     */
    protected function validateStructure($token): bool
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new InvalidTokenException('Wrong number of segments');
            } //End if

            $parts = array_filter(array_map('trim', $parts));

            if (count($parts) !== 3 || implode('.', $parts) !== $token) {
                throw new InvalidTokenException('Malformed token');
            }
        } catch(InvalidTokenException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new InvalidTokenException($e->getMessage());
        } //End try-catch
        
        return true;
    } //Function ends

} //Class ends
