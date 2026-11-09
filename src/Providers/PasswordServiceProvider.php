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

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Rules\Password as FortifyPassword;

/**
 * Class PasswordServiceProvider.
 */
class PasswordServiceProvider extends ServiceProvider
{
    /**
     * The Cognito password policy.
     */
    private $cognitoPasswordPolicy;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->cognitoPasswordPolicy = $this->getCognitoPasswordPolicy();

        // Set up the password rules for both Fortify and password validation.
        $this->bootPasswordRules();
    } //Function ends

    /**
     * Boot the password rules for both Fortify and password confirmation.
     *
     * @return void
     */
    protected function bootPasswordRules(): void
    {
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
     * Set symbols requirement for the password based on the Cognito policy.
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
    private function setUncompromised(Password $password): Password
    {
        $isUncompromisedCheck = config('cognito.password_uncompromised_check', true);
        return $isUncompromisedCheck ? $password->uncompromised() : $password;
    } //Function ends

    /**
     * Read the cognito password policy from the configuration.
     *
     * @return array
     */
    private function getCognitoPasswordPolicy(): array
    {
        return config('cognito.password_policy', [
            'MinimumLength' => 8,
            'MaximumLength' => 64,
            'RequireSymbols' => true,
            'RequireUppercase' => true,
            'RequireLowercase' => true,
            'RequireNumbers' => true
        ]);
    } //Function ends

} //Class ends
