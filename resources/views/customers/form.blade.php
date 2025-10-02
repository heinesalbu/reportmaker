@extends('layout')
@section('title', $customer->exists ? 'Rediger kunde' : 'Ny kunde')
@section('content')
<h1>{{ $customer->exists ? 'Rediger kunde' : 'Ny kunde' }}</h1>
<form method="post" action="{{ $customer->exists ? route('customers.update',$customer) : route('customers.store') }}">
  @csrf @if($customer->exists) @method('PUT') @endif

  <label>Kundenavn</label>
  <input name="name" value="{{ old('name',$customer->name) }}" required>

  <div class="row">
    <div>
      <label>Organisasjonsnummer</label>
      <input name="org_no" value="{{ old('org_no',$customer->org_no) }}">
    </div>
    <div>
      <label>Domener (komma-separert)</label>
      <input name="domains" value="{{ old('domains', $customer->domains ? implode(',', $customer->domains) : '') }}">
    </div>
  </div>

  <div class="row">
    <div>
      <label>Kontaktperson</label>
      <input name="contact_name" value="{{ old('contact_name',$customer->contact_name) }}">
    </div>
    <div>
      <label>Kontakt e-post</label>
      <input name="contact_email" type="email" value="{{ old('contact_email',$customer->contact_email) }}">
    </div>
  </div>

  <label>Notater</label>
  <textarea name="notes" rows="4">{{ old('notes',$customer->notes) }}</textarea>

  <button type="submit">Lagre</button>
</form>
@endsection
