@extends('layout')
@section('title','Seksjoner')
@section('content')
<h1>Seksjoner</h1>
<p><a href="{{ route('sections.create') }}">+ Ny seksjon</a></p>
<table>
  <tr><th>Key</th><th>Label</th><th>Rekkefølge</th><th></th></tr>
  @foreach($sections as $s)
  <tr>
    <td>{{ $s->key }}</td>
    <td>{{ $s->label }}</td>
    <td>{{ $s->order }}</td>
    <td>
      <a href="{{ route('sections.edit',$s) }}">Rediger</a>
      <form action="{{ route('sections.destroy',$s) }}" method="post" style="display:inline">
        @csrf @method('DELETE')
        <button onclick="return confirm('Slette?')">Slett</button>
      </form>
    </td>
  </tr>
  @endforeach
</table>
{{ $sections->links() }}
@endsection
