<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito;

use Ellaisys\Cognito\Validators\AwsCognitoTokenValidator;

class AwsCognitoToken
{
    /**
     * @var string
     */
    private $value;

    /**
     * Create a new JSON Web Token.
     *
     * @param  string  $value
     *
     * @return void
     */
    public function __construct($value)
    {
        $this->value = (string) (new AwsCognitoTokenValidator)->check($value);
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Get the token when casting to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }

} //Class ends