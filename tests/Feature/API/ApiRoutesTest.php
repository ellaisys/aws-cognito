<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Feature\API;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

use Ellaisys\Cognito\Tests\TestCase;

#[Group('api')]
class ApiRoutesTest extends TestCase
{
    #[Test]
    public function test_forgot_password_endpoint_validates_email_payload(): void
    {
        $this->postJson($this->getApiPath('/password/forgot'), [])
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);
    } //Function ends

    #[Test]
    public function test_revalidate_token_endpoint_validates_required_payload(): void
    {
        $this->postJson($this->getApiPath('/token/revalidate'), [])
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);
    } //Function ends

    #[Test]
    public function test_user_profile_endpoint_requires_authentication(): void
    {
        $this->getJson($this->getApiPath('/user/profile'))
            ->assertStatus(401)
            ->assertJsonFragment(['status' => 'error']);
    } //Function ends

    #[Test]
    public function test_device_list_endpoint_requires_authentication(): void
    {
        $this->getJson($this->getApiPath('/device'))
            ->assertStatus(401)
            ->assertJsonFragment(['status' => 'error']);
    } //Function ends

    private function getApiPath(string $path): string
    {
        $prefix = trim((string) config('cognito.api_prefix', 'cognito'), '/');
        return '/api/' . $prefix . '/' . ltrim($path, '/');
    } //Function ends
} //Class ends
