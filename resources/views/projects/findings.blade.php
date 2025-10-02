@extends('layout')
@section('title','Funn & blokker')

@section('content')
<h1>Funn & blokker</h1>

<p style="margin:.5rem 0 1rem;">
  <a href="//{{ request()->getHost() }}{{ route('projects.index', [], false) }}">Prosjekter</a> ·
  <a href="//{{ request()->getHost() }}{{ route('projects.edit', [$project], false) }}">Tilbake til prosjekt</a> ·
  <a href="//{{ request()->getHost() }}{{ route('projects.report.preview', [$project], false) }}">Forhåndsvis rapport</a> ·
  <a href="//{{ request()->getHost() }}{{ route('projects.report.pdf', [$project], false) }}">Last ned PDF</a>
</p>

@if(session('ok'))
  <div style="background:#eaffea;border:1px solid #b7e2b7;padding:.6rem 1rem;margin:.6rem 0;">{{ session('ok') }}</div>
@endif
@if($errors->any())
  <div style="background:#ffecec;border:1px solid #f5b3b3;padding:.6rem 1rem;margin:.6rem 0;">
    <strong>Kunne ikke lagre:</strong>
    <ul style="margin:.3rem 0 0 1.2rem;">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form method="POST"
      action="//{{ request()->getHost() }}{{ route('projects.findings.save', [$project], false) }}"
      autocomplete="on">
  @csrf

  @php
    $pivot = $project->projectBlocks()->get()->keyBy('block_id');
  @endphp

  {{-- Topp-handlinger --}}
  <div style="position:sticky;top:0;background:#fff;padding:.5rem 0 .75rem;z-index:2;border-bottom:1px solid #eee;">
    <button type="submit">💾 Lagre</button>
    <a href="//{{ request()->getHost() }}{{ route('projects.report.preview', [$project], false) }}" class="btn">Forhåndsvis rapport</a>
    <a href="//{{ request()->getHost() }}{{ route('projects.report.pdf', [$project], false) }}" class="btn">Last ned PDF</a>
  </div>

  @foreach($sections as $s)
    <h2 style="margin:1.5rem 0 .6rem;">{{ $s->label }}</h2>

    @foreach($s->blocks as $b)
      @php
        $row = $pivot->get($b->id);
        $checked = (bool)($row->selected ?? false);
        $override = old("blocks.$b->id.override_text", $row->override_text ?? '');
      @endphp

      <fieldset style="border:1px solid #eee;padding:12px 12px 10px;margin:12px 0;">
        <legend style="font-weight:bold;">
          {!! $b->icon !!} {{ $b->label }}
          @if($b->severity)
            <span style="color:#888;"> ({{ $b->severity }})</span>
          @endif
        </legend>

        <div style="display:flex;align-items:center;gap:10px;margin:.25rem 0 .5rem;">
          {{-- send alltid en verdi --}}
          <input type="hidden" name="blocks[{{ $b->id }}][selected]" value="0">
          <label style="display:flex;align-items:center;gap:.4rem;">
            <input type="checkbox" name="blocks[{{ $b->id }}][selected]" value="1" {{ $checked ? 'checked' : '' }}>
            Ta med i rapporten
          </label>
        </div>

        @if(!empty($b->default_text))
          <div style="color:#666;margin:.2rem 0 .4rem;"><em>Standard:</em></div>
          <div style="margin-bottom:.6rem;">{!! nl2br(e($b->default_text)) !!}</div>
        @endif

        <label for="ta-{{ $b->id }}">Override tekst (valgfritt)</label>
        <textarea id="ta-{{ $b->id }}"
                  name="blocks[{{ $b->id }}][override_text]"
                  rows="3"
                  style="display:block;width:100%;">{{ $override }}</textarea>

        @if(!empty($b->tips) && is_array($b->tips))
          <div style="margin-top:.6rem;">
            <strong>Tips:</strong>
            <ul style="margin:.3rem 0 0 1.2rem;">
              @foreach($b->tips as $tip)<li>{{ $tip }}</li>@endforeach
            </ul>
          </div>
        @endif
      </fieldset>
    @endforeach
  @endforeach

  {{-- Bunn-handlinger --}}
  <div style="margin-top:1rem;padding-top:.75rem;border-top:1px solid #eee;">
    <button type="submit">💾 Lagre</button>
    <a href="//{{ request()->getHost() }}{{ route('projects.report.preview', [$project], false) }}" class="btn">Forhåndsvis rapport</a>
    <a href="//{{ request()->getHost() }}{{ route('projects.report.pdf', [$project], false) }}" class="btn">Last ned PDF</a>
    <a href="//{{ request()->getHost() }}{{ route('projects.index', [], false) }}" class="btn">Til prosjekter</a>
  </div>
</form>
@endsection
