<?php

namespace Tests\Feature;

use App\Contact;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use function GuzzleHttp\Promise\all;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setup(): void
    {
        parent::setup();

        $this->user = factory(User::class)->create();
    }

    /**
     * A test to validate if a contact can be added.
     *
     * @return void
     * 
     * @test
     */
    public function an_unauthorized_user_should_be_redirected_to_login()
    {
        $response = $this->post(
            '/api/contacts',
            array_merge($this->data(), ['api_token' => ''])
        );

        $response->assertRedirect('/login');
        $this->assertCount(0, Contact::all());
    }

    /**
     * A test to validate if a contact can be added.
     *
     * @return void
     * 
     * @test
     */
    public function a_list_of_contacts_can_be_fetched_for_the_authenticated_user()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();

        $contact = factory(Contact::class)->create(['user_id' => $user->id]);
        $anotherContact = factory(Contact::class)->create(['user_id' => $anotherUser->id]);

        $response = $this->get('/api/contacts?api_token=' . $user->api_token);

        $response->assertJsonCount(1)
            ->assertJson([
                'data' => [
                    ['contact_id' => $contact->id],
                ]
            ]);
    }

    /**
     * A test to validate if a contact can be added.
     *
     * @return void
     * 
     * @test
     */
    public function an_authenticated_user_can_add_a_contact()
    {

        $this->post('/api/contacts', $this->data());

        $contact = Contact::first();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('test@example.com', $contact->email);
        $this->assertEquals('05/14/1988', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC String', $contact->company);
    }

    /**
     * 
     * A test to validate that fields are required and do not add a contact if the required fields aren't there.
     * 
     * @return void
     * @test
     */
    public function fields_are_required()
    {
        collect(['name', 'email', 'birthday', 'company'])
            ->each(function ($field) {
                $response = $this->post(
                    '/api/contacts',
                    array_merge($this->data(), [$field => ''])
                );

                $response->assertSessionHasErrors($field);
                $this->assertCount(0, Contact::all());
            });
    }

    /**
     * 
     * A test to valiate the way an email is send to the api
     * 
     * @return void
     * @test
     */
    public function email_must_be_a_valid_email()
    {
        $response = $this->post(
            '/api/contacts',
            array_merge($this->data(), ['email' => 'NOT AN EMAIL'])
        );

        $response->assertSessionHasErrors('email');
        $this->assertCount(0, Contact::all());
    }

    /**
     * 
     * A test to validate the insertion of the birthday in a correct format.
     * 
     * @return void
     * @test
     */
    public function birthdays_are_properly_stored()
    {
        $response = $this->post(
            '/api/contacts',
            array_merge($this->data())
        );

        $this->assertCount(1, Contact::all());
        $this->assertInstanceOf(Carbon::class, Contact::first()->birthday);
        $this->assertEquals('05-14-1988', Contact::first()->birthday->format('m-d-Y'));
    }

    /**
     * 
     * A test to validate that we can retrieve a contact from the database.
     * 
     * @return void
     * @test
     */
    public function a_contact_can_be_retrieved()
    {
        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->get('/api/contacts/' . $contact->id . '?api_token=' . $this->user->api_token);
        $response->assertJson([
            'data' => [
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'birthday' => $contact->birthday->format('m/d/Y'),
                'company' => $contact->company,
                'last_updated' => $contact->updated_at->diffForHumans(),
            ]
        ]);
    }

    /**
     * @return void
     * @test
     */
    public function only_the_users_contacts_can_be_retrieved()
    {
        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $anotherUser = factory(User::class)->create();

        $response = $this->get('/api/contacts/' . $contact->id . '?api_token=' . $anotherUser->api_token);

        $response->assertStatus(403);
    }

    /**
     * 
     * A test to validate that a contact can be updated
     * 
     * @return void
     * @test
     */
    public function a_contact_can_be_patched()
    {
        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->patch('/api/contacts/' . $contact->id, $this->data());

        $contact = $contact->fresh();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('test@example.com', $contact->email);
        $this->assertEquals('05/14/1988', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC String', $contact->company);
    }

    /**
     * @return void
     * @test
     */
    public function only_the_owner_of_the_contact_can_patch_the_contact()
    {
        $contact = factory(Contact::class)->create();

        $anotherUser = factory(User::class)->create();

        $response = $this->patch(
            '/api/contacts/' . $contact->id,
            array_merge($this->data(), ['api_token' => $anotherUser->api_token])
        );

        $response->assertStatus(403);
    }
    /**
     * 
     * @return void
     * @test
     */
    public function a_contact_can_be_deleted()
    {
        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->delete('/api/contacts/' . $contact->id, ['api_token' => $this->user->api_token]);

        $this->assertCount(0, Contact::all());
    }

    /**
     * @return void
     * @test
     */
    public function only_the_owner_of_the_contact_can_delete_the_contact()
    {
        $contact = factory(Contact::class)->create();

        $anotherUser = factory(User::class)->create();

        $response = $this->delete('/api/contacts/' . $contact->id, ['api_token' => $anotherUser->api_token]);

        $response->assertStatus(403);
    }

    private function data()
    {
        return [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'birthday' => '05/14/1988',
            'company' => 'ABC String',
            'api_token' => $this->user->api_token,
        ];
    }
}
