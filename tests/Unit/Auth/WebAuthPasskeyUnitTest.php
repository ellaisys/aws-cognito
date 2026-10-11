<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Auth;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversTrait;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\Auth\WebAuthPasskey;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('web-authn-passkey')]
#[CoversTrait(WebAuthPasskey::class)]
class WebAuthPasskeyUnitTest extends TestCase
{
    private $class;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Create a new fixture
        $this->class = new class {
            use WebAuthPasskey;
        };

        // Create a Json request
        $this->requestJson = Request::create('/', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => TestCase::APPLICATION_JSON,
                'CONTENT_TYPE' => TestCase::APPLICATION_JSON,
            ]
        );
    } //Function ends

    /**
     * Test that the class is an instance of the anonymous class using WebAuthPasskey.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the start method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_start_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'start'),
            'Method start does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    /**
     * Test that the complete method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_complete_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'complete'),
            'Method complete does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    /**
     * Test that the challenge method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_challenge_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'challenge'),
            'Method challenge does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    /**
     * Test that the delete method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_delete_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'delete'),
            'Method delete does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends
} //Class ends
