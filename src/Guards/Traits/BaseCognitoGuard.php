<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Guards\Traits;

/**
 * Trait Base Cognito Guard
 */
trait BaseCognitoGuard
{

    /**
	 * Get the AWS Cognito object
     * 
	 * @return \Ellaisys\Cognito\AwsCognito
	 */
    public function cognito() {
        return $this->cognito;
    } //Function ends

} //Trait ends