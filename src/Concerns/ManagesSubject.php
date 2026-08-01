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

trait ManagesSubject
{
    protected string $subjectKey = 'sub';

    /**
     * Initialize the trait.
     *
     * @return void
     */
    protected function initializeManagesSubject(): void
    {
        $this->subjectKey = config('cognito.user_subject_uuid', 'sub');
        $this->fillable[]    = $this->subjectKey;
        $this->hidden[]      = $this->subjectKey;
        $this->casts[$this->subjectKey] = 'string';
    }

    /**
     * Determine if the user has a subject identifier.
     *
     * @return bool
     */
    public function hasSubTrait(): bool
    {
        return ! is_null($this->{$this->subjectKey});
    } //Function ends

} //End trait
