@extends('layout')
@section('title','Blokker')
@section('content')
<h1>Blokker</h1>
<p><a href="{{ route('blocks.create') }}">+ Ny blokk</a></p>
<table>
  <tr>
    <th>Seksjon</th><th>Nøkkel</th><th>Tittel</th><th>Sev</th><th>Vis som standard</th><th>Order</th><th></th>
  </tr>
  @foreach($blocks as $b)
  <tr>
    <td>{{ $b->section->label ?? '—' }}</td>
    <td><code>{{ $b->key }}</code></td>
    <td>{{ $b->label }}</td>
    <td>{{ $b->severity }}</td>
    <td>{{ $b->visible_by_default ? 'Ja' : 'Nei' }}</td>
    <td>{{ $b->order ?? 0 }}</td>
    <td>
      <a href="{{ route('blocks.edit',$b) }}">Rediger</a>
      <form action="{{ route('blocks.destroy',$b) }}" method="post" style="display:inline">
        @csrf @method('DELETE')
        <button onclick="return confirm('Slette?')">Slett</button>
      </form>
    </td>
  </tr>
  @endforeach
</table>
{{ $blocks->links() }}
@endsection
