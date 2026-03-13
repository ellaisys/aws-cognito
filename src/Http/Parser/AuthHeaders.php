<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Parser;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
//use Ellaisys\Cognito\Contracts\Http\Parser as ParserContract;

use Exception;

class AuthHeaders //implements ParserContract
{
    
    /**
     * The header name.
     *
     * @var string
     */
    protected $header = 'authorization';


    /**
     * The header prefix.
     *
     * @var string
     */
    protected $prefix = 'bearer';


    /**
     * Attempt to parse the token from some other possible headers.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    protected function fromAltHeaders(Request $request)
    {
        return $request->server->get('HTTP_AUTHORIZATION') ?: $request->server->get('REDIRECT_HTTP_AUTHORIZATION');
    } //Function ends


    /**
     * Try to parse the token from the request header.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    public function parse(Request $request)
    {
        try {
            $header = $request->headers->get($this->header) ?: $this->fromAltHeaders($request);

            if ($header && preg_match('/'.$this->prefix.'\s*(\S+)\b/i', $header, $matches)) {
                return $matches[1];
            } //End if
        } catch (Exception $e) {
            Log::error('AuthHeaders:parse:Exception');
            return null;
        }
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
