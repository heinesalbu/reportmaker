@extends('layout')
@section('title', $section->exists ? 'Rediger seksjon' : 'Ny seksjon')
@section('content')
<h1>{{ $section->exists ? 'Rediger seksjon' : 'Ny seksjon' }}</h1>
<form method="post" action="{{ $section->exists ? route('sections.update',$section) : route('sections.store') }}">
  @csrf
  @if($section->exists) @method('PUT') @endif



  <label>Navn</label>
  <input name="label" value="{{ old('label',$section->label) }}" required>

  <label>Rekkefølge</label>
  <input name="order" type="number" min="0" value="{{ old('order',$section->order ?? 0) }}">

  <p><button type="submit">Lagre</button></p>
</form>
@endsection
