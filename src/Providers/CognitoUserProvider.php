<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;
use Illuminate\Contracts\Cache\Repository;

use BadMethodCallException;

final class CognitoUserProvider implements UserProviderContract
{
    /**
     * @var UserProvider
     */
    private $provider;

    /**
     * The cache repository contract.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * The used cache tag.
     *
     * @var string
     */
    protected $tag = 'userprovider.aws.cognito';

    /**
     * Constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     *
     * @return void
     */
    public function __construct(UserProvider $provider,
        Repository $cache)
    {
        $this->provider = $provider;
        $this->cache = $cache;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveByCredentials(array $credentials): ?Authenticatable {
        return $this->provider->retrieveByCredentials($credentials);
    } //Function ends

    /**
     * Retrieve a user by the given identifier.
     *
     * @param  string  $identifier
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveById(string $identifier): ?Authenticatable {
        

        return new User([
            config('cognito.user_subject_uuid', 'sub') => $identifier
        ]);
    } //Function ends

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     *
     * @return bool
     */
    protected function validateCredentials(Authenticatable $user, array $credentials): bool {
        return $this->provider->validateCredentials($user, $credentials);
    } //Function ends

    /**
     * Retrieve a user by the given token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveByToken(mixed $identifier, string $token): ?Authenticatable {
        return $this->provider->retrieveByToken($identifier, $token);
    } //Function ends

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     */
    protected function updateRememberToken(Authenticatable $user, string $token): void {}

} //Class ends
