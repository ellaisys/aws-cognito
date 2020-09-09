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
     * @var array
     */
    private $value;

    /**
     * Create a new JSON Web Token.
     *
     * @param  string  $token
     *
     * @return void
     */
    public function __construct($token, $value=null)
    {
        $this->token = (string) (new AwsCognitoTokenValidator)->check($token);
        $this->value = $value;
    }


    /**
     * Get the token.
     *
     * @return string
     */
    public function get()
    {
        return $this->token;
    } //Function ends


    /**
     * Get the token.
     *
     * @return array
     */
    public function value()
    {
        return $this->value;
    } //Function ends


    /**
     * Get the token when casting to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    } //Function ends

} //Class ends