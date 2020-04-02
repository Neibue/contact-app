<?php

namespace Tests\Feature;

use App\Contact;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A test to validate if a contact can be added.
     *
     * @return void
     * 
     * @test
     */
    public function a_contact_can_be_added()
    {
        $this->withoutExceptionHandling();

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
        $this->withoutExceptionHandling();

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
        $contact = factory(Contact::class)->create();

        $response = $this->get('/api/contacts/' . $contact->id);
        $response->assertJsonFragment([
            'name' => $contact->name,
            'email' => $contact->email,
            'birthday' => $contact->birthday,
            'company' => $contact->company,
        ]);
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
        $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create();

        $response = $this->patch('/api/contacts/' . $contact->id, $this->data());

        $contact = $contact->fresh();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('test@example.com', $contact->email);
        $this->assertEquals('05/14/1988', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC String', $contact->company);
    }

    /**
     * 
     * @return void
     * @test
     */
    public function a_contact_can_be_deleted()
    {
        $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create();

        $response = $this->delete('/api/contacts/' . $contact->id);

        $this->assertCount(0, Contact::all());
    }

    private function data()
    {
        return [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'birthday' => '05/14/1988',
            'company' => 'ABC String'
        ];
    }
}
