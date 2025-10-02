@extends('layout')
@section('title', 'Velg blokker for ' . $project->title)

@section('content')
<style>
    /* Den pene layouten, nå med all funksjonalitet */
    .finding-list { margin-top: 2rem; }
    .finding-section h2 { font-size: 1.5rem; margin: 2rem 0 1rem 0; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; }
    .finding-block { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 1rem; background-color: #fff; overflow: hidden; transition: box-shadow 0.2s; }
    .finding-block:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .finding-block-main { display: flex; align-items: center; padding: 0.75rem 1rem; gap: 1rem; }
    .finding-col-select input[type="checkbox"] { width: 1.5rem; height: 1.5rem; cursor: pointer; }
    .finding-col-title { flex-grow: 1; display: flex; align-items: center; gap: 0.8rem; cursor: pointer; }
    .finding-col-title .icon { font-size: 1.5rem; min-width: 30px; text-align: center; color: #555; }
    .finding-col-title strong { font-size: 1.1rem; }
    .toggle-details-btn { background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 50%; width: 32px; height: 32px; font-size: 1.5rem; line-height: 28px; text-align: center; cursor: pointer; font-weight: bold; color: #555; flex-shrink: 0; }
    .toggle-details-btn:hover { background-color: #e0e0e0; }
    .finding-details { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background-color: #fdfdfd; }
    .finding-details.active { max-height: 1000px; transition: max-height 0.4s ease-in; }
    .finding-details-content { padding: 1.5rem; border-top: 1px solid #eee; display: grid; gap: 1.5rem; }
    .finding-details-content .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .finding-details-content label { font-weight: 600; }
    .finding-details-content input, .finding-details-content textarea { display: block; width: 100%; border: 1px solid #ccc; border-radius: 6px; padding: 0.5rem; font-size: 1rem; }
    .default-text-wrapper { border-left: 3px solid #007bff; padding-left: 1rem; color: #444; font-style: italic; margin-top: 0.5rem; }

    /* Stiler for ikonvelger */
    .icon-picker-trigger { position: relative; display: flex; align-items: center; width: 100%; cursor: pointer; background-color: white; border: 1px solid #ccc; border-radius: 6px; transition: border-color 0.2s, box-shadow 0.2s; }
    .icon-picker-trigger:hover, .icon-picker-trigger:focus-within { border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2); }
    .icon-picker-preview { padding: 0.5rem; font-size: 1.5rem; min-width: 40px; text-align: center; }
    .icon-picker-placeholder { color: #888; padding: 0.5rem; }
    .icon-picker-trigger input { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; border: none; }
    .icon-picker-clear { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; display: none; align-items: center; justify-content: center; background-color: #e9ecef; border-radius: 50%; font-family: sans-serif; font-weight: bold; color: #888; cursor: pointer; z-index: 2; }
    .icon-picker-clear:hover { background-color: #ced4da; color: #333; }

    /* Modal stiler */
    .icon-picker-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    .modal-header input { width: 100%; padding: 10px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; }
    .modal-content .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
    #iconGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); gap: 10px; min-height: 100px; max-height: 50vh; overflow-y: auto; margin-top: 20px; }
    .icon-item { padding: 10px; text-align: center; font-size: 1.8rem; color: #333; cursor: pointer; border-radius: 4px; }
    .icon-item:hover { background-color: #eee; }
    .icon-picker-message { width: 100%; text-align: center; color: #888; font-style: italic; padding: 2rem 0; grid-column: 1 / -1; }
</style>

<h1>Velg blokker for "{{ $project->title }}"</h1>
<p style="margin:.5rem 0 1rem;">
  <a href="{{ route('projects.index') }}">Prosjektoversikt</a> ·
  <a href="{{ route('projects.edit', $project) }}">Rediger prosjekt</a> ·
  <a href="{{ route('projects.report.preview', $project) }}" target="_blank">Forhåndsvis rapport</a> ·
  <a href="{{ route('projects.report.pdf', $project) }}">Last ned PDF</a>
</p>
@if(session('ok'))<div style="background:#eaffea;padding:.6rem 1rem;margin:.6rem 0;">{{ session('ok') }}</div>@endif

<form method="POST" action="{{ route('projects.findings.save', $project) }}">
    @csrf
    <div class="finding-list">
        @forelse($groupedBlocks as $groupName => $blocks)
            <section class="finding-section">
                <h2>{{ $groupName }}</h2>
                @foreach($blocks as $b)
                    @php
                        $projectBlock = $project->projectBlocks->firstWhere('block_id', $b->id);
                        $checked = old("blocks.{$b->id}.selected", $projectBlock?->selected ?? $b->visible_by_default);
                        $overrideLabel = old("blocks.{$b->id}.override_label", $projectBlock?->override_label ?? '');
                        $overrideIcon = old("blocks.{$b->id}.override_icon", $projectBlock?->override_icon ?? '');
                        $overrideText = old("blocks.{$b->id}.override_text", $projectBlock?->override_text ?? '');
                        $overrideTips = old("blocks.{$b->id}.override_tips", is_array($projectBlock?->override_tips) ? implode(', ', $projectBlock->override_tips) : ($projectBlock?->override_tips ?? ''));
                    @endphp
                    <div class="finding-block">
                        <div class="finding-block-main">
                            <div class="finding-col-select">
                                <input type="hidden" name="blocks[{{ $b->id }}][selected]" value="0">
                                <input type="checkbox" id="cb-{{ $b->id }}" name="blocks[{{ $b->id }}][selected]" value="1" @checked($checked)>
                            </div>
                            <div class="finding-col-title" onclick="document.getElementById('cb-{{ $b->id }}').click()">
                                <span class="icon"><i class="{{ $overrideIcon ?: $b->icon }}"></i></span>
                                <label for="cb-{{ $b->id }}"><strong>{{ $overrideLabel ?: $b->label }}</strong></label>
                            </div>
                            <div class="toggle-details-btn" role="button" title="Vis/skjul detaljer">+</div>
                        </div>
                        <div class="finding-details">
                            <div class="finding-details-content">
                                <div class="form-group">
                                    <label for="label-{{ $b->id }}">Overstyr Tittel</label>
                                    <input id="label-{{ $b->id }}" name="blocks[{{ $b->id }}][override_label]" value="{{ $overrideLabel }}" placeholder="{{ $b->label }}">
                                </div>
                                
                                <div class="form-group">
                                    <label for="icon-picker-trigger-{{ $b->id }}">Overstyr Ikon</label>
                                    <div id="icon-picker-trigger-{{ $b->id }}" class="icon-picker-trigger" tabindex="0">
                                        <span class="icon-picker-preview">
                                            <i data-preview-for="icon-{{ $b->id }}" class="{{ $overrideIcon ?: $b->icon }}"></i>
                                        </span>
                                        <span class="icon-picker-placeholder">Klikk for å velge et ikon</span>
                                        <input id="icon-{{ $b->id }}" name="blocks[{{ $b->id }}][override_icon]" type="text" value="{{ $overrideIcon }}">
                                        <div class="icon-picker-clear" title="Fjern ikon">&times;</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="text-{{ $b->id }}">Overstyr Standardtekst</label>
                                    @if(!empty($b->default_text))
                                    <div class="default-text-wrapper">
                                        <p><strong>Standard:</strong> {!! nl2br(e($b->default_text)) !!}</p>
                                    </div>
                                    @endif
                                    <textarea id="text-{{ $b->id }}" name="blocks[{{ $b->id }}][override_text]" rows="4" placeholder="La feltet være tomt for å bruke standardteksten...">{{ $overrideText }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="tips-{{ $b->id }}">Overstyr Tips (kommaseparert)</label>
                                    @if(!empty($b->tips))
                                        <p style="margin:0;font-size:0.9em;font-style:italic;color:#666;">Standard: {{ is_array($b->tips) ? implode(', ', $b->tips) : $b->tips }}</p>
                                    @endif
                                    <input id="tips-{{ $b->id }}" name="blocks[{{ $b->id }}][override_tips]" value="{{ $overrideTips }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>
        @empty
            <p>Ingen blokker funnet for denne malen.</p>
        @endforelse
    </div>
    <div style="margin-top:2rem;padding-top:1rem;border-top:2px solid #eee;text-align:right;">
        <button type="submit" style="padding:10px 20px;font-size:1.1rem;background-color:#007bff;color:white;border:none;border-radius:6px;cursor:pointer;">💾 Lagre endringer</button>
    </div>
</form>

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
document.addEventListener('DOMContentLoaded', function() {
    // Logikk for å vise/skjule detaljer
    document.querySelector('.finding-list').addEventListener('click', function(event) {
        const toggleButton = event.target.closest('.toggle-details-btn');
        if (!toggleButton) return;
        const details = toggleButton.closest('.finding-block').querySelector('.finding-details');
        details.classList.toggle('active');
        toggleButton.textContent = details.classList.contains('active') ? '−' : '+';
    });

    // Initialiserer en ikonvelger for HVER blokk
    const iconTriggers = document.querySelectorAll('.icon-picker-trigger');
    iconTriggers.forEach(trigger => {
        new IconPicker(trigger);
    });
});
</script>
@endsection