<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sunnydesign\Cognito\Http\Parser;

use Illuminate\Http\Request;

class Parser
{
    /**
     * The chain.
     *
     * @var array
     */
    private $chain;

    
    /**
     * The request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;


    /**
     * Constructor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $chain
     *
     * @return void
     */
    public function __construct(Request $request, array $chain = [])
    {
        $this->request = $request;
        $this->chain = $chain;
    } //Function ends


    /**
     * Get the parser chain.
     *
     * @return array
     */
    public function getChain()
    {
        return $this->chain;
    } //Function ends


    /**
     * Set the order of the parser chain.
     *
     * @param  array  $chain
     *
     * @return $this
     */
    public function setChain(array $chain)
    {
        $this->chain = $chain;
        return $this;
    } //Function ends

    /**
     * Alias for setting the order of the chain.
     *
     * @param  array  $chain
     *
     * @return $this
     */
    public function setChainOrder(array $chain)
    {
        return $this->setChain($chain);
    } //Function ends


    /**
     * Iterate through the parsers and attempt to retrieve
     * a value, otherwise return null.
     *
     * @return string|null
     */
    public function parseToken()
    {
        foreach ($this->chain as $parser) {
            if ($response = $parser->parse($this->request)) {
                return $response;
            } //End if
        } //Loop ends
    } //Function ends


    /**
     * Check whether a token exists in the chain.
     *
     * @return bool
     */
    public function hasToken()
    {
        return $this->parseToken() !== null;
    } //Function ends


    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    } //Function ends

} //Class ends