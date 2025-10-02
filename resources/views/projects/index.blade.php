@extends('layout')
@section('title','Prosjekter')

@section('content')
<style>
    /* Ny, renere styling for prosjekttabellen */
    .project-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .project-table {
        width: 100%;
        border-collapse: collapse;
    }
    .project-table th, .project-table td {
        padding: 8px 12px; /* Redusert padding for tettere layout */
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .project-table th {
        font-weight: 600;
    }
    /* Stil for annenhver rad */
    .project-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .project-table .actions {
        text-align: right;
        white-space: nowrap;
    }
    .project-table .actions a, .project-table .actions button {
        margin-left: 0.5rem;
        display: inline-block;
    }
    .project-table th a {
        color: inherit;
        text-decoration: none;
    }
    .project-table th a:hover {
        text-decoration: underline;
    }
    .pagination-controls {
        margin-top: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<div class="project-header">
    <h1>Prosjekter</h1>
    <a href="{{ route('projects.create') }}">+ Nytt prosjekt</a>
</div>

@if($errors->any())
  <div style="background:#ffecec;border:1px solid #f5b3b3;padding:.6rem 1rem;margin:1rem 0;border-radius: 6px;">
    <strong>En feil oppstod:</strong>
    <ul style="margin:.3rem 0 0 1.2rem; padding:0;">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="pagination-controls">
    <form method="GET" action="{{ route('projects.index') }}">
        <label for="per_page">Viser:</label>
        <select name="per_page" id="per_page" onchange="this.form.submit()">
            <option value="20" @selected($perPage == 20)>20</option>
            <option value="30" @selected($perPage == 30)>30</option>
            <option value="40" @selected($perPage == 40)>40</option>
            <option value="50" @selected($perPage == 50)>50</option>
            <option value="all" @selected($perPage == 'all')>Alle</option>
        </select>
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_direction" value="{{ $sortDirection }}">
    </form>
</div>

<table class="project-table">
    <thead>
        <tr>
            <th>
                {{-- Sorterbar Tittel-kolonne --}}
                <a href="{{ route('projects.index', ['sort_by' => 'title', 'sort_direction' => ($sortBy == 'title' && $sortDirection == 'asc' ? 'desc' : 'asc'), 'per_page' => $perPage]) }}">
                    Tittel @if($sortBy == 'title') {{ $sortDirection == 'asc' ? '▲' : '▼' }} @endif
                </a>
            </th>
            <th>
                {{-- Sorterbar Kunde-kolonne --}}
                <a href="{{ route('projects.index', ['sort_by' => 'customer_id', 'sort_direction' => ($sortBy == 'customer_id' && $sortDirection == 'asc' ? 'desc' : 'asc'), 'per_page' => $perPage]) }}">
                    Kunde @if($sortBy == 'customer_id') {{ $sortDirection == 'asc' ? '▲' : '▼' }} @endif
                </a>
            </th>
            <th>
                {{-- Sorterbar Status-kolonne --}}
                <a href="{{ route('projects.index', ['sort_by' => 'status', 'sort_direction' => ($sortBy == 'status' && $sortDirection == 'asc' ? 'desc' : 'asc'), 'per_page' => $perPage]) }}">
                    Status @if($sortBy == 'status') {{ $sortDirection == 'asc' ? '▲' : '▼' }} @endif
                </a>
            </th>
            <th>Tags</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($projects as $p)
            <tr>
                <td>{{ $p->title }}</td>
                <td>{{ $p->customer?->name }}</td>
                <td>{{ $p->status }}</td>
                <td>{{ $p->tags ? implode(', ', $p->tags) : '—' }}</td>
                <td class="actions">
                    <a href="{{ route('projects.findings', $p) }}">Blokker</a>
                    <a href="{{ route('projects.edit', $p) }}">Rediger</a>
                    <form method="post" action="{{ route('projects.duplicate', $p) }}" style="display:inline">
                        @csrf
                        <button type="submit">Dupliser</button>
                    </form>
                    <form action="{{ route('projects.destroy', $p) }}" method="post" style="display:inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Sikker på at du vil slette prosjektet {{ $p->title }}?')">Slett</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">Ingen prosjekter funnet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-controls">
    @if ($perPage != 'all')
        {{ $projects->withQueryString()->links() }}
    @endif
</div>
@endsection