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

trait ManagesRegistration
{
    protected string $registrationType = 'register_type';

    /**
     * Initialize the trait.
     *
     * @return void
     */
    protected function initializeManagesRegistration(): void
    {
        $this->fillable[]    = $this->registrationType;
        $this->hidden[]      = $this->registrationType;
        $this->casts[$this->registrationType] = 'string';

        $this->fillable[]    = 'registered_at';
        $this->hidden[]      = 'registered_at';
        $this->casts['registered_at'] = 'datetime';
    }

    /**
     * Determine if the user has a registration type.
     *
     * @return bool
     */
    public function hasRegistrationTrait(): bool
    {
        return ! is_null($this->{$this->registrationType});
    } //Function ends

} //End trait
