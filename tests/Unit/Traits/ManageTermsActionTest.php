<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Traits;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversTrait;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Traits\ManageTermsAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('terms')]
#[CoversTrait(ManageTermsAction::class)]
class ManageTermsActionTest extends TestCase
{
    private $client;

    const SAMPLE_DATA = [
        'name' => 'terms-of-use',
        'link' => 'https://example.com/terms-of-use',
    ];

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        //Create AWS Cognito Client
        $this->client = app()->make(AwsCognitoClient::class);
    } // Function ends

    /**
     * Test if the listTerms method exists.
     */
    #[Test]
    public function test_method_listTerms_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'listTerms'));
    } // Function ends

    /**
     * Test the response of the listTerms method with valid parameters.
     */
    #[Test]
    #[Depends('test_method_listTerms_exists')]
    public function test_listTerms_method_valid_response(): void
    {
        $response = $this->client->listTerms();
        $this->assertIsArray($response->toArray());
    } // Function ends

    /**
     * Test the response of the listTerms method with invalid parameters.
     */
    #[Test]
    #[Depends('test_method_listTerms_exists')]
    public function test_listTerms_method_invalid_response(): void
    {
        $this->expectException(HttpException::class);
        $this->client->listTerms(10, 'some_token', 'invalid_parameter');
    } // Function ends

    /**
     * Test if the describeTerms method exists.
     */
    #[Test]
    public function test_method_describeTerms_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'describeTerms'));
    } // Function ends

    /**
     * Test the response of the describeTerms method with an invalid group name.
     */
    #[Test]
    #[Depends('test_method_describeTerms_exists')]
    public function test_describeTerms_method_with_invalid_name(): void
    {
        $this->expectException(HttpException::class);
        $this->client->describeTerms('123e4567-e89b-12d3-a456-9AC7CBDCEE52');
    } // Function ends

    /**
     * Test if the createTerms method exists.
     */
    #[Test]
    public function test_method_createTerms_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'createTerms'));
    } // Function ends

    /**
     * Test the response of the createTerms method with a valid data.
     */
    #[Test]
    #[Depends('test_method_createTerms_exists')]
    public function test_createTerms_method_with_valid_data(): string
    {
        $response = $this->client->createTerms(
            self::SAMPLE_DATA['name'],
            ['cognito:default' => self::SAMPLE_DATA['link']]
        );

        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('Terms', $data);
        $this->assertArrayHasKey('TermsId', $data['Terms']);

        return $data['Terms']['TermsId'];
    } // Function ends

    /**
     * Test the response of the createTerms method with invalid data.
     */
    #[Test]
    #[Depends('test_method_createTerms_exists')]
    public function test_createTerms_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->createTerms(
            self::SAMPLE_DATA['name'],
            ['cognito:default' => 'some description'],
            'wrong_pool_id');
    } //Function ends

    /**
     * Test if the updateTerms method exists.
     */
    #[Test]
    public function test_method_updateTerms_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'updateTerms'));
    } //Function ends

    /**
     * Test the response of the updateTerms method with valid data.
     */
    #[Test]
    #[Depends('test_createTerms_method_with_valid_data')]
    public function test_updateTerms_method_with_valid_data(string $termId): string
    {
        $response = $this->client->updateTerms(
            $termId, 'privacy-policy', [
                'cognito:default' => 'https://example.com/privacy-policy',
            ]);
        $this->assertIsArray($response->toArray());

        return $termId;
    } // Function ends

    /**
     * Test the response of the updateTerms method with an invalid data.
     */
    #[Test]
    #[Depends('test_method_updateTerms_exists')]
    public function test_updateTerms_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->updateTerms(
            'some_invalid_term_id', 'some description',
            ['cognito:default' => 'some_string_of_20_characters']);
    } // Function ends

    /**
     * Test if the deleteTerms method exists.
     */
    #[Test]
    public function test_method_deleteTerms_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteTerms'));
    } // Function ends

    /**
     * Test the response of the deleteTerms method with invalid data.
     */
    #[Test]
    #[Depends('test_method_deleteTerms_exists')]
    public function test_deleteTerms_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->deleteTerms('some_invalid_term_id');
    } // Function ends

    /**
     * Test the response of the deleteTerms method with valid data.
     */
    #[Test]
    #[Depends('test_updateTerms_method_with_valid_data')]
    public function test_deleteTerms_method_with_valid_data(string $termId): void
    {
        $response = $this->client->deleteTerms($termId);
        $this->assertIsArray($response->toArray());
    } // Function ends

} // Class ends
