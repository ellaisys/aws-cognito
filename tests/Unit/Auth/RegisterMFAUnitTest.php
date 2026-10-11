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
use Ellaisys\Cognito\Auth\RegisterMFA;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('register-mfa')]
#[CoversTrait(RegisterMFA::class)]
class RegisterMFAUnitTest extends TestCase
{
    private $class;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Set config values
        Config::set('cognito.mfa_setup', 'OPTIONAL');

        // Create a new fixture
        $this->class = new class {
            use RegisterMFA;
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
     * Test that the class is an instance of the anonymous class using RegisterMFA.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the activate method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_activate_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'activate'),
            'Method activate does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the verify method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_verify_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'verify'),
            'Method verify does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the deactivate method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_deactivate_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'deactivate'),
            'Method deactivate does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the enable method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_enable_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'enable'),
            'Method enable does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the disable method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_disable_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'disable'),
            'Method disable does not exist in the class using RegisterMFA.'
        );
    } // Function ends

} //Class ends
