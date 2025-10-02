{{-- resources/views/templates/form.blade.php --}}
@extends('layout')
@section('title', $template->exists ? 'Rediger mal' : 'Ny mal')

@section('content')
<h1>{{ $template->exists ? 'Rediger mal' : 'Ny mal' }}</h1>

{{-- Flash --}}
@if(session('ok'))   <div class="flash ok">{{ session('ok') }}</div> @endif
@if(session('error'))<div class="flash error">{{ session('error') }}</div> @endif

{{-- Valideringsfeil --}}
@if ($errors->any())
  <div class="flash error">
    <ul style="margin:0; padding-left:18px;">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- ============ META-SKJEMA: KEY / NAME / DESCRIPTION ============ --}}
<form method="post" action="{{ $template->exists ? route('templates.update', $template) : route('templates.store') }}" style="margin-bottom:16px;">
  @csrf
  @if($template->exists) @method('PUT') @endif

  <label for="key">Key</label>
  <input id="key" name="key" value="{{ old('key', $template->key) }}" required>

  <label for="name">Navn</label>
  <input id="name" name="name" value="{{ old('name', $template->name) }}" required>

  <label for="description">Beskrivelse</label>
  <textarea id="description" name="description" rows="2">{{ old('description', $template->description) }}</textarea>

  <p style="margin-top:8px;">
    <button type="submit">💾 Lagre</button>
    <a class="button" href="{{ route('templates.index') }}">← Til maler</a>
  </p>
</form>

@if(!$template->exists)
  {{-- Ikke vis struktur før malen er opprettet --}}
  @endsection
  @php return; @endphp
@endif

<hr>

{{-- ============ STRUKTUR-SKJEMA: SEKSJONER & BLOKKER ============ --}}
<h2>Seksjoner & blokker i denne malen</h2>
<form method="post" action="{{ route('templates.saveStructure', $template) }}">
  @csrf

  @foreach($sections as $s)
    @php
      // Finn eventuell seksjons-override (template_sections-raden)
      $ts = optional($template->sections->firstWhere('section_id', $s->id));
    @endphp

    <fieldset style="border:1px solid #eee; padding:12px; margin:16px 0;">
      <legend style="font-weight:600;">
        <label>
          <input type="hidden" name="sections[{{ $s->id }}][included]" value="0">
          <input type="checkbox" name="sections[{{ $s->id }}][included]" value="1" {{ ($ts->included ?? true) ? 'checked' : '' }}>
          Seksjon: {{ $s->label }} <small style="color:#777">({{ $s->key }})</small>
        </label>
      </legend>

      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <div>
          <label for="sec_title_{{ $s->id }}">Tittel-override</label>
          <input id="sec_title_{{ $s->id }}" name="sections[{{ $s->id }}][title_override]"
                 value="{{ old("sections.$s->id.title_override", $ts->title_override) }}">
        </div>

        <div>
          <label for="sec_order_{{ $s->id }}">Rekkefølge</label>
          <input id="sec_order_{{ $s->id }}" type="number" min="0"
                 name="sections[{{ $s->id }}][order_override]"
                 value="{{ old("sections.$s->id.order_override", $ts->order_override ?? 0) }}">
        </div>
      </div>

      <table style="margin-top:10px; width:100%; border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid #eee;">
            <th style="text-align:left; padding:6px;">Include</th>
            <th style="text-align:left; padding:6px;">Nøkkel</th>
            <th style="text-align:left; padding:6px;">Label</th>
            <th style="text-align:left; padding:6px;">Ikon</th>
            <th style="text-align:left; padding:6px;">Severity</th>
            <th style="text-align:left; padding:6px;">Order</th>
            <th style="text-align:left; padding:6px;">Vis som standard</th>
          </tr>
        </thead>
        <tbody>
        @foreach($s->blocks as $b)
          @php
            // Finn eventuell blokk-override (template_blocks-raden)
            $tb = optional($template->blocks->firstWhere('block_id', $b->id));
            // Preutfyll CSV-felter
            $tipsCsv  = isset($tb->tips_override)        ? implode(',', (array)$tb->tips_override)        : '';
            $refsCsv  = isset($tb->references_override)  ? implode(',', (array)$tb->references_override)  : '';
            $tagsCsv  = isset($tb->tags_override)        ? implode(',', (array)$tb->tags_override)        : '';
          @endphp

          <tr class="block-row" style="border-bottom:1px solid #f4f4f4;">
            <td style="padding:6px; vertical-align:top;">
              <input type="hidden" name="blocks[{{ $b->id }}][included]" value="0">
              <input class="blk-include"
                     type="checkbox"
                     name="blocks[{{ $b->id }}][included]"
                     value="1"
                     {{ ($tb->included ?? false) ? 'checked' : '' }}>
            </td>

            <td style="padding:6px; vertical-align:top;">
              <code>{{ $b->key }}</code>
            </td>

            <td style="padding:6px; vertical-align:top; width:22%;">
              <input name="blocks[{{ $b->id }}][label_override]"
                     placeholder="{{ $b->label }}"
                     value="{{ old("blocks.$b->id.label_override", $tb->label_override) }}">
            </td>

            <td style="padding:6px; vertical-align:top; width:12%;">
              <input name="blocks[{{ $b->id }}][icon_override]"
                     placeholder="{{ $b->icon }}"
                     value="{{ old("blocks.$b->id.icon_override", $tb->icon_override) }}">
            </td>

            <td style="padding:6px; vertical-align:top; width:12%;">
              <select name="blocks[{{ $b->id }}][severity_override]">
                <option value="">(arv)</option>
                @foreach(['info','warn','crit'] as $opt)
                  <option value="{{ $opt }}" @selected(old("blocks.$b->id.severity_override", $tb->severity_override) === $opt)>{{ $opt }}</option>
                @endforeach
              </select>
            </td>

            <td style="padding:6px; vertical-align:top; width:10%;">
              <input type="number" min="0"
                     name="blocks[{{ $b->id }}][order_override]"
                     value="{{ old("blocks.$b->id.order_override", $tb->order_override ?? 0) }}">
            </td>

            <td style="padding:6px; vertical-align:top; width:12%;">
              {{-- NULL = arv; true/false = eksplisitt --}}
              <label style="display:inline-flex; gap:6px; align-items:center;">
                <input type="checkbox"
                       name="blocks[{{ $b->id }}][visible_by_default_override]"
                       value="1"
                       @checked((old("blocks.$b->id.visible_by_default_override", $tb->visible_by_default_override)) === true)>
                Vis
              </label>
            </td>
          </tr>

          <tr class="block-row" style="border-bottom:1px solid #f4f4f4;">
            <td></td>
            <td colspan="6" style="padding:6px;">
              <label>Tekst-override</label>
              <textarea name="blocks[{{ $b->id }}][default_text_override]" rows="3" style="width:100%;">{{ old("blocks.$b->id.default_text_override", $tb->default_text_override) }}</textarea>

              <div style="display:flex; gap:12px; margin-top:6px; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                  <label>Tips (CSV)</label>
                  <input name="blocks[{{ $b->id }}][tips_csv]" value="{{ old("blocks.$b->id.tips_csv", $tipsCsv) }}">
                </div>
                <div style="flex:1; min-width:200px;">
                  <label>Referanser (CSV URLer)</label>
                  <input name="blocks[{{ $b->id }}][references_csv]" value="{{ old("blocks.$b->id.references_csv", $refsCsv) }}">
                </div>
                <div style="flex:1; min-width:200px;">
                  <label>Tags (CSV)</label>
                  <input name="blocks[{{ $b->id }}][tags_csv]" value="{{ old("blocks.$b->id.tags_csv", $tagsCsv) }}">
                </div>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </fieldset>
  @endforeach

  <p style="margin-top:10px;">
    <button type="submit">💾 Lagre struktur</button>
  </p>
</form>

{{-- UX: Grå ut felter når "Include" er av --}}
<script>
  (function () {
    function toggleRow(row, enabled) {
      row.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.classList.contains('blk-include')) return; // selve checkboxen skal ikke disables
        el.disabled = !enabled;
      });
    }
    document.querySelectorAll('tr.block-row').forEach(row => {
      const include = row.querySelector('.blk-include');
      if (!include) return;
      toggleRow(row, include.checked);
      include.addEventListener('change', () => toggleRow(row, include.checked));
    });
  })();
</script>
@endsection
