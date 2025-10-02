@extends('layout')
@section('title','Blokker')
@section('content')

<style>
  .sortable-ghost { opacity: 0.4; background: #f0f0f0; }
  tbody tr { cursor: move; }
  tbody tr:hover { background: #f9f9f9; }
  .drag-handle { cursor: grab; color: #999; margin-right: 0.5rem; user-select: none; }
  .drag-handle:active { cursor: grabbing; }
</style>

<h1>Blokker</h1>
<p><a href="{{ route('blocks.create') }}">+ Ny blokk</a></p>

@if(session('ok'))
<div style="background:#eaffea;padding:.6rem 1rem;margin:.6rem 0;">{{ session('ok') }}</div>
@endif

<table id="blocks-table">
  <thead>
    <tr>
      <th></th>
      <th>Seksjon</th>
      <th>Nøkkel</th>
      <th>Tittel</th>
      <th>Sev</th>
      <th>Vis som standard</th>
      <th>Order</th>
      <th></th>
    </tr>
  </thead>
  <tbody id="sortable-blocks">
    @php $currentSection = null; @endphp
    @foreach($blocks as $b)
    @if($currentSection !== $b->section_id)
        @php $currentSection = $b->section_id; @endphp
        @if(!$loop->first)</tbody></tbody>@endif
        <tbody class="section-group" data-section="{{ $b->section_id }}">
    @endif
    <tr data-id="{{ $b->id }}" data-section="{{ $b->section_id }}">
      <td><span class="drag-handle">⋮⋮</span></td>
      <td>{{ $b->section->label ?? '—' }}</td>
      <td><code>{{ $b->key }}</code></td>
      <td>{{ $b->label }}</td>
      <td>{{ $b->severity }}</td>
      <td>{{ $b->visible_by_default ? 'Ja' : 'Nei' }}</td>
      <td class="order-value">{{ $b->order ?? 0 }}</td>
      <td>
        <a href="{{ route('blocks.edit',$b) }}">Rediger</a>
        <form action="{{ route('blocks.destroy',$b) }}" method="post" style="display:inline">
          @csrf @method('DELETE')
          <button onclick="return confirm('Slette?')">Slett</button>
        </form>
      </td>
    </tr>
    @if($loop->last)</tbody>@endif
    @endforeach
</tbody>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.section-group').forEach(tbody => {
    new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 150,
      ghostClass: 'sortable-ghost',
      onEnd: function(evt) {
        const sectionId = tbody.dataset.section;
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach((row, index) => {
          row.querySelector('.order-value').textContent = index;
        });
        
        const order = Array.from(rows).map((row, index) => ({
          id: parseInt(row.dataset.id),
          order: index,
          section_id: parseInt(sectionId)
        }));
        
        fetch('{{ route("blocks.reorder") }}', {
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
            console.log('Order oppdatert!');
          }
        })
        .catch(error => console.error('Error:', error));
      }
    });
  });
});
</script>
@endsection