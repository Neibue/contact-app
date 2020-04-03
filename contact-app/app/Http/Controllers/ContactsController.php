<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contact;

class ContactsController extends Controller
{
    public function index()
    {
        return request()->user()->contacts;
    }

    public function store()
    {
        request()->user()->contacts()->create($this->validateData());
    }

    public function show(Contact $contact)
    {
        if (request()->user()->isNot($contact->user)) {
            return response([], 403);
        }
        return $contact;
    }

    public function update(Contact $contact)
    {
        if (request()->user()->isNot($contact->user)) {
            return response([], 403);
        }

        $contact->update($this->validateData());
    }

    private function validateData()
    {
        return request()->validate([
            'name' => 'required',
            'email' => 'required|email',
            'birthday' => 'required',
            'company' => 'required',
        ]);
    }

    public function destroy(Contact $contact)
    {
        if (request()->user()->isNot($contact->user)) {
            return response([], 403);
        }
        $contact->delete();
    }
}
