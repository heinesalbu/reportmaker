@extends('layout')
@section('title','Seksjoner')
@section('content')

<style>
  .sortable-ghost { opacity: 0.4; background: #f0f0f0; }
  tbody tr { cursor: move; }
  tbody tr:hover { background: #f9f9f9; }
  .drag-handle { cursor: grab; color: #999; margin-right: 0.5rem; user-select: none; }
  .drag-handle:active { cursor: grabbing; }
</style>

<h1>Seksjoner</h1>
<p><a href="{{ route('sections.create') }}">+ Ny seksjon</a></p>

@if(session('ok'))
<div style="background:#eaffea;padding:.6rem 1rem;margin:.6rem 0;">{{ session('ok') }}</div>
@endif

<table>
  <thead>
    <tr>
      <th></th>
      <th>Key</th>
      <th>Label</th>
      <th>Rekkefølge</th>
      <th></th>
    </tr>
  </thead>
  <tbody id="sortable-sections">
    @foreach($sections as $s)
    <tr data-id="{{ $s->id }}">
      <td><span class="drag-handle">⋮⋮</span></td>
      <td>{{ $s->key }}</td>
      <td>{{ $s->label }}</td>
      <td class="order-value">{{ $s->order }}</td>
      <td>
        <a href="{{ route('sections.edit',$s) }}">Rediger</a>
        <form action="{{ route('sections.destroy',$s) }}" method="post" style="display:inline">
          @csrf @method('DELETE')
          <button onclick="return confirm('Slette?')">Slett</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

{{ $sections->links() }}

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tbody = document.getElementById('sortable-sections');
  
  new Sortable(tbody, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    onEnd: function(evt) {
      const rows = tbody.querySelectorAll('tr');
      
      rows.forEach((row, index) => {
        row.querySelector('.order-value').textContent = index;
      });
      
      const order = Array.from(rows).map((row, index) => ({
        id: parseInt(row.dataset.id),
        order: index
      }));
      
      fetch('{{ route("sections.reorder") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ order: order })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('Seksjoner oppdatert!');
        }
      })
      .catch(error => console.error('Error:', error));
    }
  });
});
</script>
@endsection