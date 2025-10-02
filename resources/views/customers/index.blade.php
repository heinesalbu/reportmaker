@extends('layout')
@section('title','Kunder')
@section('content')
<h1>Kunder</h1>
<a href="{{ route('customers.create') }}">+ Ny kunde</a>
<table>
  <thead><tr><th>Navn</th><th>Orgnr</th><th>Domener</th><th></th></tr></thead>
  <tbody>
    @foreach($customers as $c)
      <tr>
        <td>{{ $c->name }}</td>
        <td>{{ $c->org_no }}</td>
        <td>{{ $c->domains ? implode(', ', $c->domains) : '—' }}</td>
        <td class="actions">
          <a href="{{ route('customers.edit',$c) }}">Rediger</a>
          <form action="{{ route('customers.destroy',$c) }}" method="post" style="display:inline">
            @csrf @method('DELETE')
            <button onclick="return confirm('Slette?')">Slett</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
{{ $customers->links() }}
@endsection
