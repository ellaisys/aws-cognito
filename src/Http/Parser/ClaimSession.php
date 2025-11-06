<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Parser;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Exception;
//use Ellaisys\Cognito\Contracts\Http\Parser as ParserContract;

class ClaimSession //implements ParserContract
{
    
    /**
     * The session key name.
     *
     * @var string
     */
    protected $sessionKey = 'claim';


    /**
     * The header prefix.
     *
     * @var string
     */
    protected $prefix = 'bearer';


    /**
     * Try to parse the token from the request header.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    public function parse(Request $request): string|null
    {
        try {
            $claim = $request->session()->has($this->sessionKey)?$request->session()->get($this->sessionKey):null;
            if ($claim && is_array($claim) && array_key_exists('token', $claim)) {
                return $claim['token'];
            } //End if
        } catch (Exception $e) {
            Log::error('ClaimSession:parse:Exception');
            return null;
        } //Try-catch ends
    } //Function ends


    /**
     * Set the header name.
     *
     * @param  string  $headerName
     *
     * @return $this
     */
    public function setHeaderName($headerName)
    {
        $this->header = $headerName;
        return $this;
    } //Function ends


    /**
     * Set the header prefix.
     *
     * @param  string  $headerPrefix
     *
     * @return $this
     */
    public function setHeaderPrefix($headerPrefix)
    {
        $this->prefix = $headerPrefix;
        return $this;
    } //Function ends

} //Class ends
