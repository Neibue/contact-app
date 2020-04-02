<?php

namespace Tests\Feature;

use App\Contact;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     * 
     * @test
     */
    public function a_contact_can_be_added()
    {
        $this->withoutExceptionHandling();

        $this->post('/api/contacts', ['name' => 'Test Name']);

        $this->assertCount(1, Contact::all());
    }
}
