<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Views;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\Views\Components\CognitoBaseComponent;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('views'), Group('component'), Group('base')]
class CognitoBaseComponentTest extends TestCase
{
    private $class;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Create a new fixture
        $this->class = new class extends CognitoBaseComponent {};

        // Create a Json request
        $this->requestJson = Request::create('/', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => TestCase::APPLICATION_JSON,
                'CONTENT_TYPE' => TestCase::APPLICATION_JSON,
            ]
        );
    } // Function ends

    /**
     * Test that the class is an instance of the anonymous class using
     * CognitoBaseComponent as the base class.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the getUsername method exists.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_getUsername_exists(): void
    {
        $this->assertTrue(method_exists($this->class, 'getUsername'));
    } // Function ends

    /**
     * Test that the getUsername method returns a string.
     */
    #[Test]
    #[Depends('test_method_getUsername_exists')]
    public function test_method_getUsername_returns_default_string(): void
    {
        $returnValue = $this->class->getUsername();
        $this->assertIsString($returnValue);
        $this->assertEquals('cognito-user', $returnValue);
    } // Function ends

    /**
     * Test that the getUsername method returns a custom string from the session.
     */
    #[Test]
    #[Depends('test_method_getUsername_exists')]
    public function test_method_getUsername_returns_custom_string_in_session(): void
    {
        // Simulate a custom username in the session
        session(['claim' => ['username' => 'custom-user']]);

        $returnValue = $this->class->getUsername();
        $this->assertIsString($returnValue);
        $this->assertEquals('custom-user', $returnValue);
    } // Function ends

    /**
     * Test that the getUsername method returns a custom email from the request.
     */
    #[Test]
    #[Depends('test_method_getUsername_exists')]
    #[DataProvider('provideCustomTextData')]
    public function test_method_getUsername_returns_custom_text_in_request(
        array $data, string $matchValue): void
    {
        // Simulate a custom username in the request
        request()->merge($data);

        $returnValue = $this->class->getUsername();
        $this->assertIsString($returnValue);
        $this->assertEquals($matchValue, $returnValue);
    } // Function ends

    /**
     * Data provider for test_method_getUsername_returns_custom_text_in_request.
     *
     * @return array
     */
    public static function provideCustomTextData(): array
    {
        return [
            [[], 'cognito-user'],
            [['username' => 'custom-user'], 'cognito-user'],
            [['username' => 'example@example.com'], 'example@example.com'],
            [['email' => 'someone@domain.com'], 'someone@domain.com'],
            [['username' => '123e4567-e89b-12d3-a456-426614174000'], '123e4567-e89b-12d3-a456-426614174000'],
        ];
    } // Function ends

    /**
     * Test that the isUserAdmin method exists.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_isUserAdmin_exists(): void
    {
        $this->assertTrue(method_exists($this->class, 'isUserAdmin'));
    } // Function ends

    /**
     * Test that the isUserAdmin method returns a boolean output.
     */
    #[Test]
    #[Depends('test_method_isUserAdmin_exists')]
    public function test_isUserAdmin_method_returns_boolean_output(): void
    {
        $returnValue = $this->class->isUserAdmin();
        $this->assertIsBool($returnValue);
    } // Function ends

    /**
     * Test that the render method exists.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_render_exists(): void
    {
        $this->assertTrue(method_exists($this->class, 'render'));
    } // Function ends

    /**
     * Test that the render method returns the expected output.
     */
    #[Test]
    #[Depends('test_method_render_exists')]
    public function test_render_method_returns_expected_output(): void
    {
        $returnValue = $this->class->render();
        $this->assertIsString($returnValue);
    } // Function ends
} // Class ends
