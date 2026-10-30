<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Concerns;

trait ManagesPasskey
{
    protected string $passkey = 'is_webauthn_enabled';

    /**
     * Initialize the trait.
     *
     * @return void
     */
    protected function initializeManagesPasskey(): void
    {
        $this->fillable[]    = $this->passkey;
        $this->hidden[]      = $this->passkey;
        $this->casts[$this->passkey] = 'boolean';
    }

    /**
     * Determine if the user has a passkey.
     *
     * @return bool
     */
    public function hasPasskeyTrait(): bool
    {
        return ! is_null($this->{$this->passkey});
    } //Function ends

} //End trait
