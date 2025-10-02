<!-- resources/views/projects/form.blade.php -->
@extends('layout')
@section('title', $project->exists ? 'Rediger prosjekt' : 'Nytt prosjekt')

@section('content')
<h1>{{ $project->exists ? 'Rediger prosjekt' : 'Nytt prosjekt' }}</h1>

{{-- Valideringsfeil --}}
@if ($errors->any())
  <div class="flash error" style="margin:8px 0;">
    <ul style="margin:0;padding-left:18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- Hovedskjema: lagre prosjekt --}}
<form method="post" action="{{ $project->exists ? route('projects.update',$project) : route('projects.store') }}">
  @csrf
  @if($project->exists) @method('PUT') @endif

  <label for="customer_id">Kunde</label>
  <select id="customer_id" name="customer_id" required>
    <option value="">Velg kunde…</option>
    @foreach($customers as $c)
      <option value="{{ $c->id }}" @selected(old('customer_id',$project->customer_id)==$c->id)>{{ $c->name }}</option>
    @endforeach
  </select>

  <label for="title">Tittel</label>
  <input id="title" name="title" value="{{ old('title',$project->title) }}" required>

  <div class="row" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
    <div style="min-width:260px;">
      <label for="template_id">Mal (valgfritt)</label>
      <select id="template_id" name="template_id">
        <option value="">— ingen —</option>
        @foreach($templates as $t)
          <option value="{{ $t->id }}" @selected(old('template_id', $project->template_id)==$t->id)>
            {{ $t->name }} ({{ $t->key }})
          </option>
        @endforeach
      </select>
    </div>

    <div style="min-width:200px;">
      <label for="status">Status</label>
      <select id="status" name="status">
        @foreach(['draft','ready','exported'] as $s)
          <option value="{{ $s }}" @selected(old('status',$project->status ?? 'draft')==$s)>{{ $s }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <label for="tags">Tags (komma-separert)</label>
  <input id="tags" name="tags" value="{{ old('tags', $project->tags ? implode(',', (array)$project->tags) : '') }}">

  <label for="description">Beskrivelse</label>
  <textarea id="description" name="description" rows="4">{{ old('description',$project->description) }}</textarea>

  <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
    <button type="submit">💾 Lagre</button>
    @if($project->exists)
      <a class="button" href="{{ route('projects.findings',$project) }}">🧩 Funn & blokker</a>
      <a class="button" href="{{ route('projects.report.preview',$project) }}" target="_blank">👀 Forhåndsvis</a>
      <a class="button" href="{{ route('projects.report.pdf',$project) }}">⬇️ Last ned PDF</a>
    @endif
  </div>
</form>

{{-- Egen liten form: aktiver mal på prosjektet (ikke nest inni hovedskjemaet) --}}
@if($project->exists)
  <hr style="margin:16px 0;">
  <h2>Aktiver mal på dette prosjektet</h2>
  <p>Velg modus og aktiver for å kopiere inn malens blokker i prosjektet.</p>

  <form method="post" action="{{ route('projects.applyTemplate', $project) }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    @csrf
    {{-- Bruk verdien som er valgt i dropdown over (fallback = prosjektets lagrede template_id) --}}
    <input type="hidden" name="template_id" value="{{ old('template_id', $project->template_id) }}">

    <div>
      <label for="mode">Modus</label>
      <select id="mode" name="mode">
        <option value="merge">Slå sammen (bevar eksisterende tekst)</option>
        <option value="replace">Erstatt (nullstill valg og bruk mal)</option>
      </select>
    </div>

    <button type="submit">⚙️ Aktiver mal på prosjektet</button>
    
  </form>
  @if($project->exists)
  <form method="post" action="{{ route('projects.duplicate', $project) }}" style="display:inline-block; margin-left:8px;">
    @csrf
    <button type="submit">Dupliser prosjekt</button>
  </form>
@endif

@endif

@endsection
