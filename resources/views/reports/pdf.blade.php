<!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <title>{{ $project->title }}</title>
  <style>
    @page { size: A4; margin: 18mm; }
    /* Bruker de nye, dynamiske verdiene med standardverdier som fallback */
    body { 
        font-family: {{ $pdfStyles['font_family'] ?? 'DejaVu Sans, Arial, sans-serif' }}; 
        font-size: {{ $pdfStyles['font_size'] ?? 11 }}pt; 
        color: #222; 
        line-height: 1.5; 
    }
    header { margin-bottom: 12mm; }
    h1 { font-size: 20pt; margin: 0 0 2mm; }
    h2 { font-size: 14pt; margin: 10mm 0 3mm; border-bottom: 1px solid #ddd; padding-bottom: 2mm; }
    .block { 
        margin: 4mm 0;
        /* Legger til linje dynamisk basert på innstillinger */
        @if(($pdfStyles['separator_style'] ?? 'none') !== 'none')
            border-bottom: {{ $pdfStyles['separator_thickness'] ?? 1 }}px {{ $pdfStyles['separator_style'] ?? 'solid' }} {{ $pdfStyles['separator_color'] ?? '#dddddd' }};
            padding-bottom: 4mm;
        @endif
    }
    .meta { color:#666; font-size:10pt; }
    .icon { display:inline-block; width: 14mm; }
    .sev-warn { color:#d58512; }
    .sev-crit { color:#c9302c; }
    .sev-info { color:#31708f; }
    footer { margin-top: 14mm; font-size: 9pt; color:#777; }
    .customer-info { margin-bottom: 12mm; border: 1px solid #eee; padding: 4mm; border-radius: 4px; background: #fdfdfd; }
    .customer-info h3 { font-size: 12pt; margin: 0 0 3mm; color: #333; }
    .customer-info p { margin: 1mm 0; }
  </style>
  <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
</head>
<body>
    <header style="display:flex; align-items:center; gap:12px; margin-bottom:8mm;">
    @if(!empty($company['logo']))
        <img src="storage/{{ $company['logo'] }}" alt="logo" style="height:48px;">
    @endif
    <div>
        <h1 style="margin:0;">{{ $project->title }}</h1>
        <div class="meta">{{ $company['name'] }} · {{ now()->format('d.m.Y') }}</div>
    </div>
    </header>

    @if($project->customer)
    <div class="customer-info">
        <h3>Kundeinformasjon</h3>
        <p><strong>Navn:</strong> {{ $project->customer->name }}</p>
        @if($project->customer->org_no)
        <p><strong>Org.nr:</strong> {{ $project->customer->org_no }}</p>
        @endif
        @if(!empty($project->customer->domains))
        <p><strong>Domene(r):</strong> {{ implode(', ', $project->customer->domains) }}</p>
        @endif
    </div>
    @endif

    @foreach($reportSections as $s)
        <h2>{{ $s['title'] }}</h2>
        @foreach($s['blocks'] as $b)
          <div class="block">
            <strong>
              <span class="icon"><i class="{{ $b['icon'] }}"></i></span>
              {{ $b['label'] }}
              <span class="meta {{ 'sev-'.$b['severity'] }}"> ({{ $b['severity'] }})</span>
            </strong>
            <div>{!! nl2br(e($b['text'])) !!}</div>
          </div>
        @endforeach
    @endforeach

    <footer>{{ $company['footer'] }}</footer>
</body>
</html>