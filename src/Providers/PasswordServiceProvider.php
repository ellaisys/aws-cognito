<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Rules\Password as FortifyPassword;

use Ellaisys\Cognito\AwsCognitoUserPool;
use Ellaisys\Cognito\Providers\StorageProvider;

/**
 * Class PasswordServiceProvider.
 */
class PasswordServiceProvider extends ServiceProvider
{
    /**
     * The key used to store the Cognito password policy match in the cache.
     */
    const COGNITO_PASSWORD_POLICY_MATCH_KEY = 'cognito_password_policy_match';

    /**
     * The Cognito password policy.
     */
    private $cognitoPasswordPolicy;

    /**
     * The provider.
     *
     * @var \Ellaisys\Cognito\Providers\StorageProvider
     */
    protected $provider;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        // Create the storage provider instance
        $this->provider = app('ellaisys.aws.cognito.provider.storage');

        // Set up the password rules
        $this->bootPasswordRules();
    } //Function ends

    /**
     * Boot the password rules for both Fortify and password confirmation.
     *
     * @return void
     */
    protected function bootPasswordRules(): void
    {
        // Get the Cognito password policy
        $this->cognitoPasswordPolicy = $this->getCognitoPasswordPolicy();

        // Set up the password rules for password validation.
        $this->bootPasswordValidationRules();
    } //Function ends

    /**
     * Boot the password confirmation rules.
     *
     * @return void
     */
    protected function bootPasswordValidationRules(): void
    {
        Password::defaults(function () {
            // Minimum password length
            $rule = $this->setMinimumLength();

            // Set the maximum length
            $rule = $this->setMaximumLength($rule);

            // Set the appropriate password case
            $rule = $this->setCase($rule);

            // Set numbers
            $rule = $this->setNumbers($rule);

            // Set symbols
            $rule = $this->setSymbols($rule);

            // Set uncompromised
            $rule = $this->setUncompromised($rule);

            return $rule;
        });
    } //Function ends

    /**
     * Set the minimum length for the password.
     *
     * @return \Illuminate\Validation\Rules\Password
     */
    private function setMinimumLength(): Password
    {
        $minLength = $this->cognitoPasswordPolicy['MinimumLength'] ?? 8;
        return Password::min($minLength);
    } //Function ends

    /**
     * Set the maximum length for the password.
     *
     * @return \Illuminate\Validation\Rules\Password
     */
    private function setMaximumLength(Password $password): Password
    {
        if (method_exists(Password::class, 'max')) {
            $maxLength = $this->cognitoPasswordPolicy['MaximumLength'] ?? 64;
            return $password->max($maxLength);
        } // End if
        return $password;
    } //Function ends

    /**
     * Set the appropriate case requirements for the password based on the
     * Cognito policy.
     *
     * @return \Illuminate\Validation\Rules\Password
     */
    private function setCase(Password $password): Password
    {
        $returnValue = $password;
        $isUpperCase = $this->cognitoPasswordPolicy['RequireUppercase'] ?? true;
        $isLowerCase = $this->cognitoPasswordPolicy['RequireLowercase'] ?? true;

        if ($isUpperCase && $isLowerCase) {
            $returnValue = $returnValue->mixedCase();
        } elseif ($isUpperCase || $isLowerCase) {
            $returnValue = $returnValue->letters();
        } // End if
        return $returnValue;
    } //Function ends

    /**
     * Set numbers requirement for the password based on the Cognito policy.
     *
     * @return \Illuminate\Validation\Rules\Password
     */
    private function setNumbers(Password $password): Password
    {
        $requireNumbers = $this->cognitoPasswordPolicy['RequireNumbers'] ?? true;
        return $requireNumbers ? $password->numbers() : $password;
    } //Function ends

    /**
     *
     * @return \Illuminate\Validation\Rules\Password
     */
    private function setSymbols(Password $password): Password
    {
        $requireSymbols = $this->cognitoPasswordPolicy['RequireSymbols'] ?? true;
        return $requireSymbols ? $password->symbols() : $password;
    } //Function ends

    /**
     * Set the uncompromised check for the password.
     *
     * @return \Illuminate\Validation\Rules\Password
     */
    private function setUncompromised(Password $password,
        bool $uncompromised = true): Password
    {
        $isUncompromisedCheck = config('cognito.password_uncompromised_check', true);
        return ($isUncompromisedCheck && $uncompromised) ? $password->uncompromised() : $password;
    } //Function ends

    /**
     * Read the cognito password policy from the configuration.
     *
     * @return array
     */
    private function getCognitoPasswordPolicy(): array
    {
        // Get the password policy from the configuration
        $configPolicy = config('cognito.password_policy', [
            'MinimumLength' => 8,
            'MaximumLength' => 64,
            'RequireSymbols' => true,
            'RequireUppercase' => true,
            'RequireLowercase' => true,
            'RequireNumbers' => true
        ]);

        if ($this->provider && $this->provider->has(self::COGNITO_PASSWORD_POLICY_MATCH_KEY)) {
            return $configPolicy;
        } else {
            // Create AWS Cognito User Pool instance
            $cognitoUserPool = app()->make(AwsCognitoUserPool::class);
            $cognitoPolicy = $cognitoUserPool->getPasswordPolicy(false);

            if ($cognitoPolicy == $configPolicy) {
                $this->provider->add(self::COGNITO_PASSWORD_POLICY_MATCH_KEY, 1, 3600);
            } // End if

            return $cognitoPolicy ?? $configPolicy;
        } // End else
    } //Function ends

} //Class ends
