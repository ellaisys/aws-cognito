<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Traits;

/**
 * AWS Cognito Client for Users (Non-Admin Actions)
 */
trait AwsCognitoClientAction
{
    use ManageUserAuthAction;
    use ManagesUserPoolAction;
    use ManageTermsAction;
    use ManageGroupAction;
    use ManageUserGroupAction;
    use ManageDeviceAction;
    use ManagePasskeyWebAuthnAction;
} //Trait ends
