{{-- resources/views/templates/form.blade.php --}}
@extends('layout')
@section('title', $template->exists ? 'Rediger mal' : 'Ny mal')

@section('content')
<style>
    .template-container { max-width: 1200px; margin: 0 auto; }
    .meta-form { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
    .meta-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
    .meta-form label { display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.9rem; }
    .meta-form input, .meta-form textarea { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
    .section-list { margin-top: 2rem; }
    .section-block { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 1rem; background: #fff; overflow: hidden; }
    .section-header { display: flex; align-items: center; padding: 0.75rem 1rem; background: #f8f9fa; border-bottom: 1px solid #ddd; gap: 1rem; cursor: pointer; user-select: none; }
    .section-header:hover { background: #e9ecef; }
    .section-toggle { font-size: 1rem; transition: transform 0.3s; }
    .section-block.collapsed .section-toggle { transform: rotate(-90deg); }
    .section-header input[type="checkbox"] { width: 1.3rem; height: 1.3rem; cursor: pointer; }
    .section-title { flex: 1; font-weight: 600; font-size: 1.05rem; }
    .section-overrides { display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: center; }
    .section-overrides input { padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
    .section-overrides input[type="number"] { width: 80px; }
    .section-content { max-height: 3000px; overflow: hidden; transition: max-height 0.4s ease; }
    .section-block.collapsed .section-content { max-height: 0; }
    .block-row { border-bottom: 1px solid #f0f0f0; }
    .block-main { display: flex; align-items: center; padding: 0.5rem 1rem; gap: 1rem; }
    .block-main input[type="checkbox"] { width: 1.2rem; height: 1.2rem; cursor: pointer; }
    .block-key { font-family: 'Courier New', monospace; color: #666; font-size: 0.85rem; min-width: 120px; }
    .block-label { flex: 1; font-size: 0.95rem; }
    .block-toggle-btn { background: #f0f0f0; border: 1px solid #ccc; border-radius: 50%; width: 28px; height: 28px; font-size: 1.2rem; line-height: 25px; text-align: center; cursor: pointer; font-weight: bold; color: #555; flex-shrink: 0; }
    .block-toggle-btn:hover { background: #e0e0e0; }
    .block-details { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background: #fdfdfd; }
    .block-details.active { max-height: 800px; transition: max-height 0.4s ease-in; }
    .block-details-content { padding: 1rem 1.5rem; display: grid; gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .form-group label { font-weight: 600; font-size: 0.85rem; color: #555; }
    .form-group input, .form-group textarea, .form-group select { padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
    .form-group textarea { resize: vertical; }
    .form-row-inline { display: grid; grid-template-columns: 1fr 1fr 100px 120px; gap: 1rem; }
    .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.95rem; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-primary:hover { background: #0056b3; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-secondary:hover { background: #545b62; }
    .flash { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
    .flash.ok { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .flash.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .block-row.disabled { opacity: 0.5; }
</style>

<div class="template-container">
    <h1>{{ $template->exists ? 'Rediger mal' : 'Ny mal' }}</h1>

    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if(session('error'))<div class="flash error">{{ session('error') }}</div>@endif
    @if($errors->any())
      <div class="flash error">
        <ul style="margin:0;padding-left:18px;">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    @endif

    @if(!$template->exists)
        <form method="post" action="{{ route('templates.store') }}" class="meta-form">
            @csrf
<div class="form-row">

    <div>
        <label for="name">Navn</label>
        <input id="name" name="name" value="{{ old('name') }}" required>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('name');
    const keyInput  = document.getElementById('key');
    nameInput.addEventListener('input', function () {
        const slug = this.value
          .toLowerCase()
          .trim()
          .replace(/[^a-z0-9_\\s]+/g, '')   // fjern ugyldige tegn
          .replace(/\\s+/g, '_');           // mellomrom til underscore
        keyInput.value = slug;
    });
});
</script>



                <label for="description">Beskrivelse</label>
                <textarea id="description" name="description" rows="2">{{ old('description') }}</textarea>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Opprett mal</button>
                <a class="btn btn-secondary" href="{{ route('templates.index') }}">Avbryt</a>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('templates.saveStructure', $template) }}" id="template-form" novalidate>
            @csrf

            <div class="meta-form">
                <div class="form-row">
                    <div>
                        <label>Key (autogenereres)</label>
                        <input value="{{ $template->key }}" disabled style="background:#f5f5f5;">
                    </div>
                    <div>
                        <label for="template_name">Navn</label>
                        <input id="template_name" name="template_name" value="{{ old('template_name', $template->name) }}" required>
                    </div>
                </div>
                <div>
                    <label for="template_description">Beskrivelse</label>
                    <textarea id="template_description" name="template_description" rows="2">{{ old('template_description', $template->description) }}</textarea>
                </div>
            </div>

            <hr style="margin:2rem 0;">

            <h2>Seksjoner & blokker</h2>
            <p style="color:#666;font-size:0.9rem;margin-bottom:1rem;">
                Huk av seksjoner og blokker du vil inkludere. Klikk på + for å overstyre standardverdier.
            </p>

            <div class="section-list">
                @foreach($sections as $s)
                    @php $ts = optional($template->sections->firstWhere('section_id', $s->id)); @endphp
                    <div class="section-block">
                        <div class="section-header" onclick="toggleSection(event,this)">
                            <span class="section-toggle">▼</span>
                            <input type="hidden" name="sections[{{ $s->id }}][included]" value="0">
                            <input type="checkbox" name="sections[{{ $s->id }}][included]" value="1" {{ ($ts->included ?? true) ? 'checked' : '' }}>
                            <div class="section-title">{{ $s->label }} <small style="color:#999">({{ $s->key }})</small></div>
                            <div class="section-overrides">
                                <input name="sections[{{ $s->id }}][title_override]" placeholder="Tittel-override" value="{{ old("sections.$s->id.title_override", $ts->title_override) }}">
                                <input type="number" min="0" name="sections[{{ $s->id }}][order_override]" placeholder="Order" value="{{ old("sections.$s->id.order_override", $ts->order_override ?? 0) }}">
                            </div>
                        </div>
                        <div class="section-content">
                            @foreach($s->blocks as $b)
                                @php 
                                    $tb = optional($template->blocks->firstWhere('block_id', $b->id));
                                    $tipsCsv = isset($tb->tips_override) ? implode(',', (array)$tb->tips_override) : '';
                                @endphp
                                <div class="block-row">
                                    <div class="block-main">
                                        <input type="hidden" name="blocks[{{ $b->id }}][included]" value="0">
                                        <input type="checkbox" class="block-include" name="blocks[{{ $b->id }}][included]" value="1" {{ ($tb->included ?? false) ? 'checked' : '' }}>
                                        <div class="block-key">{{ $b->key }}</div>
                                        <div class="block-label">{{ $b->label }}</div>
                                        <div class="block-toggle-btn">+</div>
                                    </div>
                                    <div class="block-details">
                                        <div class="block-details-content">
                                            <div class="form-row-inline">
                                                <div class="form-group">
                                                    <label>Label-override</label>
                                                    <input name="blocks[{{ $b->id }}][label_override]" placeholder="{{ $b->label }}" value="{{ old("blocks.$b->id.label_override", $tb->label_override) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Ikon-override</label>
                                                    <input name="blocks[{{ $b->id }}][icon_override]" placeholder="{{ $b->icon }}" value="{{ old("blocks.$b->id.icon_override", $tb->icon_override) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Order</label>
                                                    <input type="number" min="0" name="blocks[{{ $b->id }}][order_override]" value="{{ old("blocks.$b->id.order_override", $tb->order_override ?? 0) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Severity</label>
                                                    <select name="blocks[{{ $b->id }}][severity_override]">
                                                        <option value="">(arv: {{ $b->severity }})</option>
                                                        @foreach(['info','warn','crit'] as $opt)
                                                          <option value="{{ $opt }}" @selected(old("blocks.$b->id.severity_override", $tb->severity_override) === $opt)>{{ $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Tekst-override</label>
                                                <textarea name="blocks[{{ $b->id }}][default_text_override]" rows="3" placeholder="Standard: {{ Str::limit($b->default_text, 80) }}">{{ old("blocks.$b->id.default_text_override", $tb->default_text_override) }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Tips (kommaseparert)</label>
                                                <input name="blocks[{{ $b->id }}][tips_csv]" placeholder="Standard: {{ is_array($b->tips) ? implode(', ', $b->tips) : $b->tips }}" value="{{ old("blocks.$b->id.tips_csv", $tipsCsv) }}">
                                            </div>
                                            <div class="form-group">
                                                <label style="display:flex;align-items:center;gap:0.5rem;">
                                                    <input type="checkbox" name="blocks[{{ $b->id }}][visible_by_default_override]" value="1" style="width:auto;" @checked((old("blocks.$b->id.visible_by_default_override", $tb->visible_by_default_override)) === true)>
                                                    Vis som standard i rapporter
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:2rem;padding-top:1rem;border-top:2px solid #eee;display:flex;gap:0.5rem;justify-content:flex-end;">
                <a class="btn btn-secondary" href="{{ route('templates.index') }}">← Til maler</a>
                <button type="submit" class="btn btn-primary">💾 Lagre struktur</button>
            </div>
        </form>
    @endif
</div>

<script>
function toggleSection(e,h){if(e.target.tagName==='INPUT'){e.stopPropagation();return;}h.closest('.section-block').classList.toggle('collapsed');}
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('.block-toggle-btn').forEach(b=>b.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();const d=this.closest('.block-row').querySelector('.block-details');d.classList.toggle('active');this.textContent=d.classList.contains('active')?'−':'+';}));
    function u(r){const c=r.querySelector('.block-include');r.classList.toggle('disabled',!c.checked);}
    document.querySelectorAll('.block-row').forEach(r=>{u(r);r.querySelector('.block-include').addEventListener('change',()=>u(r));});
});
</script>
@endsection