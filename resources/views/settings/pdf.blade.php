@extends('layout')
@section('title', 'PDF-innstillinger')

@section('content')
<style>
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; max-width: 800px; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    label { font-weight: 600; }
    input, select { padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
    .full-width { grid-column: 1 / -1; }
</style>

<h1>PDF-innstillinger</h1>
<p>Her kan du justere utseendet på de genererte PDF-rapportene.</p>

@if(session('ok'))
    <div style="background:#eaffea;padding:.6rem 1rem;margin:1rem 0;">{{ session('ok') }}</div>
@endif

<form method="POST" action="{{ route('settings.pdf.save') }}">
    @csrf
    <div class="form-grid">
        <div class="form-group">
            <label for="font_family">Font</label>
            <select id="font_family" name="font_family">
                @php $currentFont = $settings['font_family'] ?? 'DejaVu Sans, Arial, sans-serif'; @endphp
                <option value="DejaVu Sans, Arial, sans-serif" @selected($currentFont == 'DejaVu Sans, Arial, sans-serif')>Standard (DejaVu Sans)</option>
                <option value="Times New Roman, serif" @selected($currentFont == 'Times New Roman, serif')>Times New Roman</option>
                <option value="Verdana, sans-serif" @selected($currentFont == 'Verdana, sans-serif')>Verdana</option>
                <option value="Helvetica, Arial, sans-serif" @selected($currentFont == 'Helvetica, Arial, sans-serif')>Helvetica</option>
            </select>
        </div>
        <div class="form-group">
            <label for="font_size">Fontstørrelse (pt)</label>
            <input type="number" id="font_size" name="font_size" value="{{ $settings['font_size'] ?? 11 }}" min="8" max="16">
        </div>
        <div class="form-group">
            <label for="separator_style">Linje mellom blokker</label>
            <select id="separator_style" name="separator_style">
                @php $currentSep = $settings['separator_style'] ?? 'none'; @endphp
                <option value="solid" @selected($currentSep == 'solid')>Ja (Solid linje)</option>
                <option value="dashed" @selected($currentSep == 'dashed')>Ja (Stiplet linje)</option>
                <option value="none" @selected($currentSep == 'none')>Nei</option>
            </select>
        </div>
        <div class="form-group">
            <label for="separator_thickness">Ligntykkelse (px)</label>
            <input type="number" id="separator_thickness" name="separator_thickness" value="{{ $settings['separator_thickness'] ?? 1 }}" min="1" max="10">
        </div>
        <div class="form-group">
            <label for="separator_color">Linjefarge</label>
            <input type="color" id="separator_color" name="separator_color" value="{{ $settings['separator_color'] ?? '#dddddd' }}">
        </div>
        <div class="form-group">
            <label for="margin_top">Topp­margin (mm)</label>
            <input type="number" id="margin_top" name="margin_top"
                value="{{ $settings['margin_top'] ?? 18 }}" min="5" max="40">
        </div>
        <div class="form-group">
            <label for="margin_right">Høyre­margin (mm)</label>
            <input type="number" id="margin_right" name="margin_right"
                value="{{ $settings['margin_right'] ?? 18 }}" min="5" max="40">
        </div>
        <div class="form-group">
            <label for="margin_bottom">Bunn­margin (mm)</label>
            <input type="number" id="margin_bottom" name="margin_bottom"
                value="{{ $settings['margin_bottom'] ?? 18 }}" min="5" max="40">
        </div>
        <div class="form-group">
            <label for="margin_left">Venstre­margin (mm)</label>
            <input type="number" id="margin_left" name="margin_left"
                value="{{ $settings['margin_left'] ?? 18 }}" min="5" max="40">
        </div>

    </div>

    <p><button type="submit">Lagre innstillinger</button></p>
</form>
@endsection