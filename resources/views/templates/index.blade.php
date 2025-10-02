@extends('layout')
@section('title','Maler')
@section('content')
<h1>Maler</h1>
<p><a href="{{ route('templates.create') }}">+ Ny mal</a></p>
<table>
  <tr><th>Key</th><th>Navn</th><th></th></tr>
  @foreach($templates as $t)
  <tr>
    <td><code>{{ $t->key }}</code></td>
    <td>{{ $t->name }}</td>
    <td>
      <a href="{{ route('templates.edit',$t) }}">Rediger</a>
      <form action="{{ route('templates.destroy',$t) }}" method="post" style="display:inline">
        @csrf @method('DELETE')
        <button onclick="return confirm('Slette?')">Slett</button>
      </form>
    </td>
  </tr>
  @endforeach
</table>
{{ $templates->links() }}
@endsection
