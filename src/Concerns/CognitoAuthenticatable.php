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

trait CognitoAuthenticatable
{
    use ManagesSubject;
    use ManagesRegistration;
    use ManagesPasskey;
} //End trait
