<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('ocrs.institution_name')) — OCRS</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <style>
        :root { --primary:#0b3d2e; --primary-l:#156b52; --accent:#f0b429; --bg:#f4f7f5; --text:#1a2e28; --muted:#5a7268; --border:#d8e4de; --white:#fff; --danger:#c0392b; --success:#1e8449; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Instrument Sans',system-ui,sans-serif; background:var(--bg); color:var(--text); line-height:1.6; }
        a { color:inherit; text-decoration:none; }
        .container { max-width:1180px; margin:0 auto; padding:0 1.25rem; }
        .nav { background:var(--white); border-bottom:1px solid var(--border); padding:.85rem 0; }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .brand { font-weight:700; color:var(--primary); display:flex; align-items:center; gap:.5rem; }
        .brand-badge { background:var(--primary); color:var(--white); font-size:.7rem; padding:.15rem .45rem; border-radius:4px; }
        .nav-links { display:flex; gap:1rem; flex-wrap:wrap; list-style:none; }
        .nav-links a, .sidebar a { color:var(--muted); font-weight:500; font-size:.9rem; }
        .nav-links a:hover, .sidebar a:hover, .sidebar a.active { color:var(--primary); }
        .btn { display:inline-flex; align-items:center; padding:.55rem 1rem; border-radius:8px; border:none; font-weight:600; font-size:.875rem; cursor:pointer; font-family:inherit; }
        .btn-primary { background:var(--primary); color:var(--white); }
        .btn-accent { background:var(--accent); color:var(--primary); }
        .btn-outline { background:transparent; border:1px solid var(--border); color:var(--primary); }
        .portal { display:grid; grid-template-columns:220px 1fr; gap:1.5rem; padding:1.5rem 0 3rem; }
        .sidebar { background:var(--white); border:1px solid var(--border); border-radius:12px; padding:1rem; height:fit-content; }
        .sidebar nav { display:flex; flex-direction:column; gap:.35rem; }
        .sidebar a { padding:.45rem .65rem; border-radius:8px; }
        .sidebar a.active { background:rgba(11,61,46,.08); }
        .card { background:var(--white); border:1px solid var(--border); border-radius:12px; padding:1.25rem; margin-bottom:1rem; }
        .card h2, .card h3 { color:var(--primary); margin-bottom:.75rem; }
        .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; }
        .grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; }
        .stat { text-align:center; }
        .stat strong { display:block; font-size:1.6rem; color:var(--primary); }
        .stat span { font-size:.8rem; color:var(--muted); }
        .alert { padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem; }
        .alert-success { background:#d5f5e3; color:var(--success); }
        .alert-error { background:#fadbd8; color:var(--danger); }
        table { width:100%; border-collapse:collapse; font-size:.875rem; }
        th, td { text-align:left; padding:.65rem; border-bottom:1px solid var(--border); }
        th { color:var(--muted); font-weight:600; }
        .badge { display:inline-block; padding:.2rem .5rem; border-radius:100px; font-size:.75rem; font-weight:600; }
        .badge-green { background:#d5f5e3; color:var(--success); }
        .badge-amber { background:#fdebd0; color:#b7950b; }
        .badge-red { background:#fadbd8; color:var(--danger); }
        .form-group { margin-bottom:1rem; }
        .course-grid { display:grid; gap:.5rem; }
        .course-item { display:grid; grid-template-columns:auto 1fr; gap:.75rem; align-items:center; padding:.75rem 1rem; border:1px solid var(--border); border-radius:10px; background:rgba(11,61,46,.03); }
        .course-item input { margin:0; width:auto; height:auto; accent-color:var(--primary); }
        .course-item span { font-weight:600; }
        label { display:block; font-weight:600; font-size:.875rem; margin-bottom:.35rem; }
        input, select, textarea { width:100%; padding:.6rem .75rem; border:1px solid var(--border); border-radius:8px; font-family:inherit; font-size:.9rem; }
        .footer { text-align:center; padding:2rem; color:var(--muted); font-size:.8rem; }
        @media(max-width:768px) { .portal { grid-template-columns:1fr; } .grid-3,.grid-2 { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <header class="nav">
        <div class="container nav-inner">
            <a href="{{ route('landing') }}" class="brand">
                <span class="brand-badge">OCRS</span>
                {{ config('ocrs.institution_name') }}
            </a>
            @yield('nav')
        </div>
    </header>

    @if(session('success'))
        <div class="container" style="padding-top:1rem;"><div class="alert alert-success">{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="container" style="padding-top:1rem;"><div class="alert alert-error">{{ session('error') }}</div></div>
    @endif
    @if($errors->any())
        <div class="container" style="padding-top:1rem;">
            <div class="alert alert-error">
                @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        </div>
    @endif

    <main>@yield('content')</main>

    <footer class="footer">
        &copy; {{ date('Y') }} {{ config('ocrs.institution_name') }} — Online Course Registration System.
        Compliant with Kenya Data Protection Act 2019.
    </footer>
</body>
</html>
