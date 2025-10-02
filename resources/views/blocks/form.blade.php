@extends('layout')
@section('title', $block->exists ? 'Rediger blokk' : 'Ny blokk')

@section('content')
<style>
    /* ... Annen CSS forblir den samme ... */
    .form-container { max-width: 900px; margin: auto; }
    .form-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-top: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .form-grid label { font-weight: 600; color: #333; }
    .form-grid input[type="text"], .form-grid input[type="number"], .form-grid select, .form-grid textarea { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box; }
    .form-grid input:focus, .form-grid select:focus, .form-grid textarea:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2); }
    .form-actions { text-align: right; margin-top: 1rem; }
    .form-actions button { padding: 0.8rem 1.5rem; background-color: #007bff; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
    .checkbox-group { display: flex; flex-direction: row; align-items: center; gap: .6rem; background-color: #f8f9fa; padding: .75rem; border-radius: 6px; border: 1px solid #ccc; }
    
    /* Ikonvelger stiler - OPPDATERT */
    .icon-picker-trigger { position: relative; display: flex; align-items: center; width: 100%; cursor: pointer; background-color: white; border: 1px solid #ccc; border-radius: 6px; transition: border-color 0.2s, box-shadow 0.2s; }
    .icon-picker-trigger:hover, .icon-picker-trigger:focus-within { border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2); }
    .icon-picker-preview { padding: 0.75rem; font-size: 1.5rem; min-width: 50px; text-align: center; }
    .icon-picker-placeholder { color: #888; }
    .icon-picker-trigger input { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; border: none; }
    .icon-picker-clear { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; display: none; align-items: center; justify-content: center; background-color: #e9ecef; border-radius: 50%; font-family: sans-serif; font-weight: bold; color: #888; cursor: pointer; z-index: 2; }
    .icon-picker-clear:hover { background-color: #ced4da; color: #333; }

    /* Modal stiler (uendret) */
    .icon-picker-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    #iconGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); gap: 10px; min-height: 100px; max-height: 50vh; overflow-y: auto; margin-top: 20px; }
    .icon-item { padding: 10px; text-align: center; font-size: 1.8rem; color: #333; cursor: pointer; border-radius: 4px; }
    .icon-item:hover { background-color: #eee; }
</style>

<div class="form-container">
    <h1>{{ $block->exists ? 'Rediger blokk' : 'Ny blokk' }}</h1>

    <form method="post" action="{{ $block->exists ? route('blocks.update', $block) : route('blocks.store') }}">
        @csrf
        @if($block->exists) @method('PUT') @endif

        <div class="form-grid">
            <div class="form-group">
                <label for="label">Tittel/Label</label>
                <input id="label" name="label" value="{{ old('label', $block->label) }}" required>
            </div>
            <div class="form-group">
                <label for="section_id">Seksjon</label>
                <select name="section_id" id="section_id" required>
                    <option value="">— velg —</option>
                    @foreach($sections as $s)
                        <option value="{{ $s->id }}" @selected(old('section_id', $block->section_id) == $s->id)>{{ $s->label }} ({{ $s->key }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="order">Rekkefølge</label>
                <input id="order" name="order" type="number" min="0" value="{{ old('order', $block->order ?? 0) }}">
            </div>

            <div class="form-group">
                <label for="icon">Font Awesome Ikon</label>
                <div id="icon-picker-trigger" class="icon-picker-trigger" tabindex="0">
                    <span class="icon-picker-preview">
                        <i data-preview-for="icon" class="{{ old('icon', $block->icon) }}"></i>
                    </span>
                    <span class="icon-picker-placeholder">Klikk for å velge et ikon</span>
                    <input id="icon" name="icon" type="text" value="{{ old('icon', $block->icon) }}">
                    <div class="icon-picker-clear" title="Fjern ikon">&times;</div>
                </div>
            </div>

            <div class="form-group">
                <label for="severity">Alvorlighetsgrad</label>
                <select id="severity" name="severity">
                    @foreach(['info', 'warn', 'crit'] as $opt)
                        <option value="{{ $opt }}" @selected(old('severity', $block->severity ?? 'info') == $opt)>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="hidden" name="visible_by_default" value="0">
                    <input type="checkbox" id="visible_by_default" name="visible_by_default" value="1" @checked(old('visible_by_default', $block->visible_by_default ?? true))>
                    <label for="visible_by_default">Vis som standard i rapporter</label>
                </div>
            </div>
            <div class="form-group">
                <label for="default_text">Standardtekst</label>
                <textarea id="default_text" name="default_text" rows="5">{{ old('default_text', $block->default_text) }}</textarea>
            </div>
            <div class="form-group">
                <label for="tips">Tips (kommaseparert)</label>
                <input id="tips" name="tips" value="{{ old('tips', is_array($block->tips) ? implode(', ', $block->tips) : $block->tips) }}">
            </div>
            <div class="form-group">
                <label for="references">Referanser (kommaseparert URLer)</label>
                <input id="references" name="references" value="{{ old('references', is_array($block->references) ? implode(', ', $block->references) : $block->references) }}">
            </div>
            <div class="form-group">
                <label for="tags">Tags (kommaseparert)</label>
                <input id="tags" name="tags" value="{{ old('tags', is_array($block->tags) ? implode(', ', $block->tags) : $block->tags) }}">
            </div>
            <div class="form-actions">
                <button type="submit">Lagre</button>
            </div>
        </div>
    </form>
</div>

<div id="iconPickerModal" class="icon-picker-modal">
    <div class="modal-content">
        <div class="modal-header">
            <input type="text" id="iconSearch" placeholder="Søk etter ikoner...">
            <span class="close">&times;</span>
        </div>
        <div id="iconGrid"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new IconPicker(document.getElementById('icon-picker-trigger'));
});
</script>
@endsection