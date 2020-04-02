<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contact;

class ContactsController extends Controller
{
    public function store()
    {
        $data = request()->validate([
            'name' => 'required',
            'email' => 'required',
            'birthday' => 'required',
            'company' => 'required',
        ]);

        Contact::create($data);
    }
}
