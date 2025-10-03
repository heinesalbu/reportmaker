@extends('layout')
@section('title', 'Velg blokker for ' . $project->title)

@section('content')
<style>
    /* Din eksisterende, kompakte layout */
    .finding-list { margin-top: 1.5rem; }
    .finding-section h2 { font-size: 0.8rem; margin: 1rem 0 0.8rem 0; border-bottom: 2px solid #eee; padding-bottom: 0.4rem; color: gray; font-weight: 300; }
    .finding-block { border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.5rem; background-color: #fff; overflow: hidden; transition: box-shadow 0.2s; }
    .finding-block-main { display: flex; align-items: center; padding: 1px 8px; gap: 0.75rem; }
    .finding-col-select input[type="checkbox"] { width: 1.2rem; height: 1.2rem; cursor: pointer; }
    .finding-col-title { flex-grow: 1; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; }
    .finding-col-title .icon { font-size: 1.2rem; min-width: 25px; text-align: center; color: #555; }
    .finding-col-title label { font-size: 0.95rem; font-weight: 400; }
    .toggle-details-btn { background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 50%; width: 28px; height: 28px; font-size: 1.2rem; line-height: 25px; text-align: center; cursor: pointer; font-weight: bold; color: #555; flex-shrink: 0; }
    .finding-details { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background-color: #fdfdfd; }
    .finding-details.active { max-height: 1000px; transition: max-height 0.4s ease-in; }
    .finding-details-content { padding: 1rem 1.25rem; border-top: 1px solid #eee; display: grid; gap: 1rem; }
    .finding-details-content .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .finding-details-content label { font-weight: 600; font-size: 0.9rem; }
    .finding-details-content input, .finding-details-content textarea { display: block; width: 100%; border: 1px solid #ccc; border-radius: 6px; padding: 0.5rem; font-size: 0.9rem; }
    .default-text-wrapper { border-left: 3px solid #007bff; padding-left: 1rem; color: #444; font-style: italic; margin-top: 0.5rem; font-size: 0.9rem; }
    .icon-picker-trigger { position: relative; display: flex; align-items: center; width: 100%; cursor: pointer; background-color: white; border: 1px solid #ccc; border-radius: 6px; }
    .icon-picker-preview { padding: 0.5rem; font-size: 1.2rem; min-width: 40px; text-align: center; }
    .icon-picker-placeholder { color: #888; font-size: 0.9rem; padding: 0.5rem; }
    .icon-picker-clear { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; font-size: 0.9rem; display: none; align-items: center; justify-content: center; background-color: #e9ecef; border-radius: 50%; font-family: sans-serif; font-weight: bold; color: #888; cursor: pointer; z-index: 2; }
    .icon-picker-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    #iconGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(45px, 1fr)); gap: 8px; min-height: 100px; max-height: 50vh; overflow-y: auto; margin-top: 20px; }
    .icon-item { padding: 8px; text-align: center; font-size: 1.5rem; color: #333; cursor: pointer; border-radius: 4px; }

    /* Stiler for kollapsbare seksjoner */
    .collapsible-section-header { cursor: pointer; display: flex; align-items: center; gap: 0.75rem; user-select: none; }
    .section-toggle-icon { transition: transform 0.3s ease; font-size: 0.8em; }
    .section-blocks-wrapper { max-height: 3000px; overflow: hidden; transition: max-height 0.4s ease-in-out; }
    .finding-section.collapsed .section-blocks-wrapper { max-height: 0; }
    .finding-section.collapsed .section-toggle-icon { transform: rotate(-90deg); }

    /* Stiler for "Lukk/Åpne alle"-knapper */
    .section-toggle-all-controls { margin-bottom: 1rem; }
    .section-toggle-all-controls button { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 0.25rem 0.75rem; font-size: 0.85rem; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; }
    .section-toggle-all-controls button:hover { background-color: #e9ecef; }
    /* Modal stiler */
    .icon-picker-modal { 
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
    }
    .modal-content { 
        background-color: #fefefe; 
        margin: auto;
        padding: 20px; 
        border: 1px solid #888; 
        width: 80%; 
        max-width: 800px; 
        border-radius: 8px;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }
    .modal-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        border-bottom: 1px solid #ddd; 
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    .modal-header input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
        margin-right: 10px;
    }
    .modal-header .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        border: none;
        background: none;
    }
    .modal-header .close:hover {
        color: #000;
    }
    #iconGrid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); 
        gap: 10px; 
        min-height: 100px; 
        max-height: 50vh; 
        overflow-y: auto; 
        padding: 10px;
    }
    .icon-item { 
        padding: 10px; 
        text-align: center; 
        font-size: 1.8rem; 
        color: #333; 
        cursor: pointer; 
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    .icon-item:hover { 
        background-color: #eee; 
    }
    .icon-picker-message {
        text-align: center;
        padding: 2rem;
        color: #888;
        font-style: italic;
        grid-column: 1 / -1;
    }
    .finding-block {
        max-width: 500px;     /* juster til ønsket bredde */
        width: 100%;
    }

    /* Custom block style: a bit different background and indent */
    .custom-block {
        /* Lys blå bakgrunn og innrykk for tilpassede blokker */
        background-color: #e7f4ff;
        margin-left: 1rem;
        border-left: 3px solid #007bff;
    }

    /* Add button style */
    .add-custom-btn {
        margin-left: 0.5rem;
        font-size: 0.75rem;
        background-color: #f8f9fa;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 2px 6px;
        cursor: pointer;
        color: #555;
    }
    .add-custom-btn:hover {
        background-color: #e9ecef;
    }

    /* Fjern-knapp for custom blokker */
    .remove-custom-btn {
        margin-left: 0.5rem;
        font-size: 1rem;
        color: #e3342f;
        background: transparent;
        border: none;
        cursor: pointer;
    }
    
</style>

<h1>Velg blokker for "{{ $project->title }}"</h1>
<p style="margin:.5rem 0 1rem;">
  <a href="{{ route('projects.index') }}">Prosjektoversikt</a> ·
  <a href="{{ route('projects.edit', $project) }}">Rediger prosjekt</a> ·
  <a href="{{ route('projects.report.preview', $project) }}" target="_blank">Forhåndsvis rapport</a> ·
  <a href="{{ route('projects.report.pdf', $project) }}">Last ned PDF</a>
</p>

<div class="section-toggle-all-controls">
    <button type="button" id="expand-all-btn">Åpne alle seksjoner</button>
    <button type="button" id="collapse-all-btn">Lukk alle seksjoner</button>
</div>

@if(session('ok'))<div style="background:#eaffea;padding:.6rem 1rem;margin:.6rem 0;">{{ session('ok') }}</div>@endif

<form method="POST" action="{{ route('projects.findings.save', $project) }}">
    @csrf
    <div class="finding-list">
        @forelse($groupedBlocks as $groupName => $blocks)
            @php
                $sectionId = $blocks->first()?->section_id ?? $blocks->first()?->after_block_id;
                if ($sectionId && $blocks->first() instanceof \App\Models\ProjectCustomBlock) {
                    $afterBlock = \App\Models\Block::find($blocks->first()->after_block_id);
                    $sectionId = $afterBlock?->section_id;
                }
            @endphp
            <section class="finding-section" data-section-id="{{ $sectionId }}">
                <div class="collapsible-section-header">
                    <h2>
                        <span class="section-toggle-icon">▼</span>
                        <span>{{ $groupName }}</span>
                    </h2>
                    @php
                        $projectSection = isset($projectSections) ? $projectSections->get($sectionId) : null;
                        $templateSection = isset($templateSections) ? $templateSections->get($sectionId) : null;
                        $showTitle = $projectSection?->show_title ?? $templateSection?->show_title ?? true;
                    @endphp
                    <label style="display:flex;align-items:center;gap:4px;margin-left:1rem;font-size:0.85rem;font-weight:normal;">
                        <input type="checkbox" name="sections[{{ $sectionId }}][show_title]" value="1" {{ $showTitle ? 'checked' : '' }}>
                        Vis tittel
                    </label>
                </div>
                <div class="section-blocks-wrapper">
                    @foreach($blocks as $b)
                        @if ($b instanceof \App\Models\ProjectCustomBlock)
                            @php
                                // Generer unikt ID for custom blokk-elementer
                                $uniqueId = 'cust'.$b->id;
                                $customLabel = old("custom_blocks.{$b->id}.label", $b->label);
                                $customIcon  = old("custom_blocks.{$b->id}.icon", $b->icon);
                                $customText  = old("custom_blocks.{$b->id}.text", $b->text);
                                $customTips  = old("custom_blocks.{$b->id}.tips", is_array($b->tips) ? implode(', ', $b->tips) : $b->tips);
                                $customSeverity = old("custom_blocks.{$b->id}.severity", $b->severity ?: 'info');
                            @endphp
                            <div class="finding-block custom-block">
                                <div class="finding-block-main">
                                    <div class="finding-col-title">
                                        <span class="icon"><i data-preview-for="icon-{{ $uniqueId }}" class="{{ $customIcon }}"></i></span>
                                        <label><strong>{{ $customLabel ?: __('Custom blokk') }}</strong></label>
                                    </div>
                                    <div class="toggle-details-btn" role="button" title="Vis/skjul detaljer">+</div>
                                    <button type="button" class="remove-custom-btn" data-custom-id="{{ $b->id }}" title="Fjern blokk">×</button>
                                </div>
                                <div class="finding-details">
                                    <div class="finding-details-content">
                                        <div class="form-group">
                                            <label>Tittel</label>
                                            <input name="custom_blocks[{{ $b->id }}][label]" value="{{ $customLabel }}" placeholder="Tittel for blokk">
                                        </div>
                                        <div class="form-group">
                                            <label>Ikon</label>
                                            <div id="icon-picker-trigger-{{ $uniqueId }}" class="icon-picker-trigger" tabindex="0">
                                                <span class="icon-picker-preview"><i data-preview-for="icon-{{ $uniqueId }}" class="{{ $customIcon }}"></i></span>
                                                <span class="icon-picker-placeholder">Klikk for å velge et ikon</span>
                                                <input id="icon-{{ $uniqueId }}" name="custom_blocks[{{ $b->id }}][icon]" type="text" value="{{ $customIcon }}">
                                                <div class="icon-picker-clear" title="Fjern ikon">&times;</div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Tekst</label>
                                            <textarea name="custom_blocks[{{ $b->id }}][text]" rows="4" placeholder="Tekst...">{{ $customText }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Tips (kommaseparert)</label>
                                            <input name="custom_blocks[{{ $b->id }}][tips]" value="{{ $customTips }}">
                                        </div>
                                        <div class="form-group">
                                            <label>Severity</label>
                                            <select name="custom_blocks[{{ $b->id }}][severity]">
                                                <option value="info"  @selected($customSeverity === 'info')>Info</option>
                                                <option value="warn"  @selected($customSeverity === 'warn')>Warn</option>
                                                <option value="crit"  @selected($customSeverity === 'crit')>Crit</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="custom_blocks[{{ $b->id }}][after_block_id]" value="{{ $b->after_block_id }}">
                                    </div>
                                </div>
                            </div>
                        @else
                           {{-- Del av resources/views/projects/findings.blade.php --}}
{{-- Dette er kun delen som handler om synlighet checkboxes i block-details --}}

@php
    $projectBlock = $project->projectBlocks->firstWhere('block_id', $b->id);
    $templateBlock = isset($templateBlocks) ? $templateBlocks->get($b->id) : null;
    
    // Prioritet: Project override → Template override → Block default
    $checked = old("blocks.{$b->id}.selected", $projectBlock?->selected ?? $b->visible_by_default);
    
    $overrideLabel = old("blocks.{$b->id}.override_label", 
        $projectBlock?->override_label ?? ($templateBlock?->label_override ?? ''));
    
    $overrideIcon = old("blocks.{$b->id}.override_icon", 
        $projectBlock?->override_icon ?? ($templateBlock?->icon_override ?? ''));
    
    $overrideText = old("blocks.{$b->id}.override_text", 
        $projectBlock?->override_text ?? ($templateBlock?->default_text_override ?? ''));
    
    // Tips håndtering
    $projectTips = is_array($projectBlock?->override_tips) 
        ? implode(', ', $projectBlock->override_tips) 
        : ($projectBlock?->override_tips ?? '');
    
    $templateTips = $templateBlock && is_array($templateBlock->tips_override) 
        ? implode(', ', $templateBlock->tips_override) 
        : ($templateBlock?->tips_override ?? '');
    
    $overrideTips = old("blocks.{$b->id}.override_tips", 
        $projectTips ?: $templateTips);
    
    // SYNLIGHETSINNSTILLINGER: project → template → default
    $showIcon = $projectBlock?->show_icon ?? $templateBlock?->show_icon ?? true;
    $showLabel = $projectBlock?->show_label ?? $templateBlock?->show_label ?? true;
    $showText = $projectBlock?->show_text ?? $templateBlock?->show_text ?? true;
    $showTips = $projectBlock?->show_tips ?? $templateBlock?->show_tips ?? true;
    $showSeverity = $projectBlock?->show_severity ?? $templateBlock?->show_severity ?? false;
    
    // Effektive verdier for visning (det som faktisk vises)
    $effectiveIcon = $overrideIcon ?: ($templateBlock?->icon_override ?? $b->icon);
    $effectiveLabel = $overrideLabel ?: ($templateBlock?->label_override ?? $b->label);
@endphp

<div class="finding-block">
    <div class="finding-block-main">
        <div class="finding-col-select">
            <input type="hidden" name="blocks[{{ $b->id }}][selected]" value="0">
            <input type="checkbox" id="cb-{{ $b->id }}" name="blocks[{{ $b->id }}][selected]" value="1" @checked($checked)>
        </div>
        <div class="finding-col-title" onclick="document.getElementById('cb-{{ $b->id }}').click()">
            <span class="icon"><i class="{{ $effectiveIcon }}"></i></span>
            <label for="cb-{{ $b->id }}"><strong>{{ $effectiveLabel }}</strong></label>
        </div>
        <div class="toggle-details-btn" role="button" title="Vis/skjul detaljer">+</div>
        <button type="button"
                class="add-custom-btn"
                data-block-id="{{ $b->id }}"
                data-section-id="{{ $b->section_id ?? ($b->section?->id ?? '') }}">
            add
        </button>
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
                    <span class="icon-picker-preview"><i data-preview-for="icon-{{ $b->id }}" class="{{ $overrideIcon ?: $b->icon }}"></i></span>
                    <span class="icon-picker-placeholder">Klikk for å velge et ikon</span>
                    <input id="icon-{{ $b->id }}" name="blocks[{{ $b->id }}][override_icon]" type="text" value="{{ $overrideIcon }}">
                    <div class="icon-picker-clear" title="Fjern ikon">&times;</div>
                </div>
            </div>
            <div class="form-group">
                <label for="text-{{ $b->id }}">Overstyr Standardtekst</label>
                @if(!empty($b->default_text))
                <div class="default-text-wrapper"><p><strong>Standard:</strong> {!! nl2br(e($b->default_text)) !!}</p></div>
                @endif
                <textarea id="text-{{ $b->id }}" name="blocks[{{ $b->id }}][override_text]" rows="4" placeholder="La feltet være tomt for å bruke standardteksten...">{{ $overrideText }}</textarea>
            </div>
            <div class="form-group">
                <label for="tips-{{ $b->id }}">Overstyr Tips (kommaseparert)</label>
                @php
                    $defaultTips = $templateBlock && $templateBlock->tips_override 
                        ? (is_array($templateBlock->tips_override) ? implode(', ', $templateBlock->tips_override) : $templateBlock->tips_override)
                        : (is_array($b->tips) ? implode(', ', $b->tips) : $b->tips);
                @endphp
                @if($defaultTips)
                    <p style="margin:0;font-size:0.9em;font-style:italic;color:#666;">Standard: {{ $defaultTips }}</p>
                @endif
                <input id="tips-{{ $b->id }}" name="blocks[{{ $b->id }}][override_tips]" value="{{ $overrideTips }}">
            </div>
            
            {{-- SYNLIGHET I RAPPORT --}}
            <div class="form-group">
                <label>Synlighet i rapport</label>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;font-size:0.85rem;">
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="hidden" name="blocks[{{ $b->id }}][show_icon]" value="0">
                        <input type="checkbox" name="blocks[{{ $b->id }}][show_icon]" value="1" {{ $showIcon ? 'checked' : '' }}>
                        Ikon
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="hidden" name="blocks[{{ $b->id }}][show_label]" value="0">
                        <input type="checkbox" name="blocks[{{ $b->id }}][show_label]" value="1" {{ $showLabel ? 'checked' : '' }}>
                        Tittel
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="hidden" name="blocks[{{ $b->id }}][show_text]" value="0">
                        <input type="checkbox" name="blocks[{{ $b->id }}][show_text]" value="1" {{ $showText ? 'checked' : '' }}>
                        Tekst
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="hidden" name="blocks[{{ $b->id }}][show_tips]" value="0">
                        <input type="checkbox" name="blocks[{{ $b->id }}][show_tips]" value="1" {{ $showTips ? 'checked' : '' }}>
                        Tips
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="hidden" name="blocks[{{ $b->id }}][show_severity]" value="0">
                        <input type="checkbox" name="blocks[{{ $b->id }}][show_severity]" value="1" {{ $showSeverity ? 'checked' : '' }}>
                        Severity
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
                        @endif
                    @endforeach
                </div>
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
        <div class="modal-header"><input type="text" id="iconSearch" placeholder="Søk etter ikoner..."><span class="close">&times;</span></div>
        <div id="iconGrid"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const findingList = document.querySelector('.finding-list');
    const expandAllBtn = document.getElementById('expand-all-btn');
    const collapseAllBtn = document.getElementById('collapse-all-btn');
    const allSections = document.querySelectorAll('.finding-section');

    // KNAPP: Åpne alle
    expandAllBtn.addEventListener('click', () => {
        allSections.forEach(section => section.classList.remove('collapsed'));
    });

    // KNAPP: Lukk alle
    collapseAllBtn.addEventListener('click', () => {
        allSections.forEach(section => section.classList.add('collapsed'));
    });

    // HOVED-LISTENER FOR ALLE KLIKK
    // Inkluderer toggle av detaljer, kollaps/ekspandere seksjoner, og håndtering av custom blocks
    let customBlockCounter = 0;
    findingList.addEventListener('click', function(event) {
        // 1) Håndter klikk på "add custom block"-knappen
        const addBtn = event.target.closest('.add-custom-btn');
        if (addBtn) {
            event.preventDefault();
            const blockDiv = addBtn.closest('.finding-block');
            const sectionWrapper = blockDiv.parentNode; // .section-blocks-wrapper
            const blockId = addBtn.getAttribute('data-block-id');
            // Generer unik ID for ny custom blokk
            const uniqueId = 'c' + Date.now() + '_' + (customBlockCounter++);
            // Lag HTML for en ny custom blokk
            const customHtml = `
                <div class="finding-block custom-block">
                    <div class="finding-block-main">
                        <div class="finding-col-title">
                            <span class="icon"><i data-preview-for="icon-${uniqueId}" class=""></i></span>
                            <label><strong>Custom blokk</strong></label>
                        </div>
                        <div class="toggle-details-btn" role="button" title="Vis/skjul detaljer">+</div>
                        <button type="button" class="remove-custom-btn" title="Fjern blokk">×</button>
                    </div>
                    <div class="finding-details">   
                        <div class="finding-details-content">
                            <div class="form-group">
                                <label>Tittel</label>
                                <input name="custom_blocks[new_${uniqueId}][label]" placeholder="Tittel for blokk">
                            </div>
                            <div class="form-group">
                                <label>Ikon</label>
                                <div id="icon-picker-trigger-${uniqueId}" class="icon-picker-trigger" tabindex="0">
                                    <span class="icon-picker-preview"><i data-preview-for="icon-${uniqueId}" class=""></i></span>
                                    <span class="icon-picker-placeholder">Klikk for å velge et ikon</span>
                                    <input id="icon-${uniqueId}" name="custom_blocks[new_${uniqueId}][icon]" type="text" value="">
                                    <div class="icon-picker-clear" title="Fjern ikon">&times;</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tekst</label>
                                <textarea name="custom_blocks[new_${uniqueId}][text]" rows="4" placeholder="Tekst..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Tips (kommaseparert)</label>
                                <input name="custom_blocks[new_${uniqueId}][tips]" value="">
                            </div>
                            <div class="form-group">
                                <label>Severity</label>
                                <select name="custom_blocks[new_${uniqueId}][severity]">
                                    <option value="info">Info</option>
                                    <option value="warn">Warn</option>
                                    <option value="crit">Crit</option>
                                </select>
                            </div>
                            <input type="hidden" name="custom_blocks[new_${uniqueId}][after_block_id]" value="${blockId}">
                        </div>
                    </div>
                </div>`;
            const temp = document.createElement('div');
            temp.innerHTML = customHtml.trim();
            const newBlock = temp.firstElementChild;
            // Sett inn den nye blokken rett etter gjeldende blokk
            sectionWrapper.insertBefore(newBlock, blockDiv.nextSibling);
            // Initialiser ikonvelger for nye blokker
            const newTrigger = newBlock.querySelector('.icon-picker-trigger');
            if (typeof IconPicker !== 'undefined') {
                new IconPicker(newTrigger);
            }
            return; // Stopper her så andre klikk-handlere ikke trigges
        }

        // 2) Håndter klikk på fjern-knapp for custom blokker
        const removeBtn = event.target.closest('.remove-custom-btn');
        if (removeBtn) {
            event.preventDefault();
            const blockElement = removeBtn.closest('.finding-block');
            if (blockElement) {
                blockElement.remove();
            }
            return;
        }

        // 3) Håndter klikk på pluss/minus for å vise/skjule detaljer
        const toggleDetailsBtn = event.target.closest('.toggle-details-btn');
        if (toggleDetailsBtn) {
            const details = toggleDetailsBtn.closest('.finding-block').querySelector('.finding-details');
            details.classList.toggle('active');
            toggleDetailsBtn.textContent = details.classList.contains('active') ? '−' : '+';
            return;
        }

        // 4) Håndter klikk på seksjonsoverskriften for å kollapse/ekspandere
        const sectionHeader = event.target.closest('.collapsible-section-header');
        if (sectionHeader) {
            sectionHeader.closest('.finding-section').classList.toggle('collapsed');
        }
    });

    // Initialiserer alle ikonvelgere som allerede finnes
    const iconTriggers = document.querySelectorAll('.icon-picker-trigger');
    iconTriggers.forEach(trigger => { new IconPicker(trigger); });
});
</script>
@endsection
