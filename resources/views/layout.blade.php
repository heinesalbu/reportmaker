<!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Reportmaker')</title>
  <style>
    body{font-family:Arial, sans-serif; margin:2rem;}
    nav a{margin-right:1rem;}
    .flash{background:#e8fff1;border:1px solid #b5f0c9;padding:.6rem 1rem;margin:1rem 0;border-radius:6px;}
    label{display:block;margin:.5rem 0 .2rem;}
    input,select,textarea{width:100%;padding:.5rem;}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
    table{width:100%;border-collapse:collapse;margin-top:1rem;}
    th,td{padding:.5rem;border-bottom:1px solid #eee;text-align:left;}
    .actions a{margin-right:.5rem;}
  </style>
      <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="https://kit.fontawesome.com/04f12e36ac.css" crossorigin="anonymous">
      <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
  <nav>
    <a href="{{ route('projects.index') }}">Prosjekter</a>
    <a href="{{ route('customers.index') }}">Kunder</a>
    <a href="{{ route('sections.index') }}">Seksjoner</a>
    <a href="{{ route('blocks.index') }}">Blokker</a>
     <a href="{{ route('templates.index') }}">Maler</a>
    <a href="{{ route('settings.company') }}">Innstillinger</a>
    <a href="{{ route('settings.pdf') }}">PDF-innstillinger</a>

  </nav>

  @if(session('ok')) <div class="flash">{{ session('ok') }}</div> @endif

  @yield('content')
  <script src="{{ asset('js/icon-picker.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</body>
</html>
