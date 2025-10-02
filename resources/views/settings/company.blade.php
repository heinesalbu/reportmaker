@extends('layout')
@section('title','Firmaopplysninger')

@section('content')
<h1>Firmaopplysninger</h1>
@if(session('ok')) <div class="flash">{{ session('ok') }}</div> @endif

<form method="post" action="{{ route('settings.company.save') }}" enctype="multipart/form-data">
  @csrf

  <label>Firmanavn</label>
  <input name="company_name" value="{{ old('company_name',$company_name) }}" required>

  <label>Footer-tekst</label>
  <input name="company_footer" value="{{ old('company_footer',$company_footer) }}">

  <label>Logo (PNG/JPG, valgfritt)</label>
  <input type="file" name="logo" accept="image/*">

  @if($logo_path)
    <p>Nåværende logo:</p>
    <img src="{{ asset('storage/'.$logo_path) }}" alt="logo" style="max-height:60px;">
  @endif

  <p style="margin-top:1rem;"><button type="submit">Lagre</button></p>
</form>
@endsection
