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
    private $token;

    /**
     * Create a new JSON Web Token.
     *
     * @param  string  $token
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = (string) (new AwsCognitoTokenValidator)->check($token);
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function get()
    {
        return $this->token;
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