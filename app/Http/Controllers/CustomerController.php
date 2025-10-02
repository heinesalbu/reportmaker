<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Project;    

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('name')->paginate(20);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'          => 'required|string|max:255',
            'org_no'        => 'nullable|string|max:50',
            'domains'       => 'nullable|string', // comma-separated i form
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'notes'         => 'nullable|string',
        ]);
        $data['domains'] = isset($data['domains']) && $data['domains'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $data['domains']))))
            : null;

        $customer = Customer::create($data);
        return redirect()->route('customers.edit', $customer)->with('ok','Kunde opprettet');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $r, Customer $customer)
    {
        $data = $r->validate([
            'name'          => 'required|string|max:255',
            'org_no'        => 'nullable|string|max:50',
            'domains'       => 'nullable|string',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'notes'         => 'nullable|string',
        ]);
        $data['domains'] = isset($data['domains']) && $data['domains'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $data['domains']))))
            : null;

        $customer->update($data);
        return back()->with('ok','Kunde oppdatert');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('ok','Kunde slettet');
    }
}

