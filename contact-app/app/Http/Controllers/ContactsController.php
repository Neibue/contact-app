<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contact;

class ContactsController extends Controller
{
    public function store() {
        Contact::create([
            'name' => request('name'),
        ]);
    }
}
