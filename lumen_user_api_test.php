<?php

use App\Models\User;
use App\Models\SecurityToken;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_checks_index_response()
    {
        $response = $this->call('GET', '/');

        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function it_registers_a_new_user()
    {
        $this->post('/users',
            [
                'company'      => 'Company',
                'email'        => 'john.doe@company.lt',
                'first_name'   => 'John',
                'last_name'    => 'Doe',
                'mobile_phone' => '+37069037984',
                'password'     => 'supersecret123ABC',
            ]
        )->seeJson(
            [
                'company'      => 'Company',
                'email'        => 'john.doe@company.lt',
                'first_name'   => 'John',
                'last_name'    => 'Doe',
                'mobile_phone' => '+37069037984',
            ]
        )->assertResponseStatus(200);
    }

    /** @test */
    public function it_logins_a_user()
    {
        factory(User::class)->create(
            [
                'email'        => 'john.doe@comapny.lt',
                'password'     => '$2y$10$e/HwYvBXm8.4SALiQbzQsuCvTbk9Cq2RnIIvFGBlM8memJCzCo1Wq',
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
            ]
        );

        $this->post('/auth/token',
            [
                'email'    => 'john.doe@company.lt',
                'password' => 'supersecret123ABC',
            ]
        )->seeJson(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
            ]
        )->assertResponseStatus(200);
    }

    /** @test */
    public function it_requests_to_reset_user_password()
    {
        factory(User::class)->create(
            [
                'email' => 'john.doe@company.lt',
            ]
        );

        $this->post('/user/password/reset',
            [
                'email' => 'john.doe@company.lt',
            ]
        )->assertResponseStatus(204);
    }

    /**
     * @test
     * @group test
     */
    public function it_resets_user_password()
    {
        $user = factory(User::class)->create(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
            ]
        );

        factory(SecurityToken::class)->create(
            [
                'security_token' => 'pjn7MiHsqJAPemIT2OW7Qzr60dtp8MCo',
                'user_id'        => $user->id,
            ]
        );

        $this->patch('/user/password/reset/pjn7MiHsqJAPemIT2OW7Qzr60dtp8MCo',
            [
                'password'          => 'supersecret123ABC123',
                'repeated_password' => 'supersecret123ABC123',
            ]
        )->seeJson(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
            ]
        )->assertResponseStatus(200);
    }

    /** @test */
    public function it_sends_user_confirmation_email()
    {
        $user = factory(User::class)->create(
            [
                'email' => 'john.doe@company.lt',
            ]
        );

        $this->actingAs($user)->post('/user/email-confirmation')->assertResponseStatus(204);
    }

    /** @test */
    public function it_confirms_user_email()
    {
        $user = factory(User::class)->create(
            [
                'email'        => 'john.doe@comapany.lt',
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
            ]
        );

        factory(SecurityToken::class)->create(
            [
                'security_token' => 'pjn7MiHsqJAPemIT2OW7Qzr60dtp8MCo',
                'type'           => 'Email',
                'user_id'        => $user->id,
            ]
        );

        $this->actingAs($user)->patch('/user/email-confirmation',
            [
                'confirmation_token' => 'pjn7MiHsqJAPemIT2OW7Qzr60dtp8MCo',
            ]
        )->seeJson(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
            ]
        )->assertResponseStatus(200);
    }

    /** @test */
    public function it_checks_if_password_reset_token_is_valid()
    {
        factory(SecurityToken::class)->create(
            [
                'security_token' => 'pjn7MiHsqJAPemIT2OW7Qzr60dtp8MCo',
            ]
        );

        $this->post('/user/password/reset/validate',
            [
                'password_reset_token' => 'pjn7MiHsqJAPemIT2OW7Qzr60dtp8MCo'
            ]
        )->seeJson(
            [
                'is_valid' => true,
            ]
        )->assertResponseStatus(200);
    }

    /** @test */
    public function it_shows_a_user()
    {
        $user = factory(User::class)->create(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
                'company'      => 'Company',
                'email'        => 'john.doe@company.lt',
                'first_name'   => 'John',
                'last_name'    => 'Doe',
                'mobile_phone' => '+37069037984',
                'password'     => '$2y$10$e/HwYvBXm8.4SALiQbzQsuCvTbk9Cq2RnIIvFGBlM8memJCzCo1Wq',
            ]
        );

        $this->actingAs($user)->get('/user')
            ->seeJson(
                [
                    'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
                    'company'      => 'Company',
                    'email'        => 'john.doe@company.lt',
                    'first_name'   => 'John',
                    'last_name'    => 'Doe',
                    'mobile_phone' => '+37069037984',
                ]
            )->assertResponseStatus(200);
    }

    /** @test */
    public function it_updates_a_user()
    {
        $user = factory(User::class)->create(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
                'company'      => 'Company',
                'email'        => 'john.doe@company.lt',
                'first_name'   => 'John',
                'last_name'    => 'Doe',
                'mobile_phone' => '+37069037984',
                'password'     => '$2y$10$e/HwYvBXm8.4SALiQbzQsuCvTbk9Cq2RnIIvFGBlM8memJCzCo1Wq',
            ]
        );

        $this->actingAs($user)->patch('/user',
            [
                'first_name' => 'Valentino',
                'last_name'  => 'Morose',
            ]
        )->seeJson(
            [
                'access_token' => 'EOryR8qub5XiiNm9oyLIFZ4iKHP662UC',
                'company'      => 'Company',
                'email'        => 'john.doe@company.lt',
                'first_name'   => 'Valentino',
                'last_name'    => 'Morose',
                'mobile_phone' => '+37069037984',
            ]
        )->assertResponseStatus(200);
    }

    /** @test */
    public function it_changes_user_password()
    {
        $user = factory(User::class)->create(
            [
                'password' => '$2y$10$e/HwYvBXm8.4SALiQbzQsuCvTbk9Cq2RnIIvFGBlM8memJCzCo1Wq',
            ]
        );

        $this->actingAs($user)->put('/user/password',
            [
                'new_password'  => 'supersecret123ABC123',
                'old_password'  => 'supersecret123ABC',
            ]
        )->assertResponseStatus(204);
    }

    /** @test */
    public function it_deletes_a_user()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)->delete('/user')->assertResponseStatus(204);
    }
}
