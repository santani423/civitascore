<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10.5px; color: #1a1a1a; }
        .kop { border-bottom: 2px solid #1a1a1a; padding-bottom: 6px; margin-bottom: 14px; text-align: center; }
        .kop .university { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
        .kop .unit { font-size: 11px; color: #444; }
        h1 { font-size: 14px; text-align: center; margin: 0 0 2px; text-transform: uppercase; }
        .doc-subtitle { text-align: center; color: #555; margin: 0 0 14px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.meta td { padding: 2px 4px; vertical-align: top; }
        table.meta td.label { width: 120px; color: #555; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #bbb; padding: 4px 5px; }
        table.grid th { background: #f1f5f9; font-size: 9.5px; text-transform: uppercase; }
        table.grid td.num, table.grid th.num { text-align: center; }
        table.grid tfoot td { font-weight: bold; background: #f8fafc; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .summary td { border: 1px solid #ddd; padding: 6px; text-align: center; width: 25%; }
        .summary .value { font-size: 14px; font-weight: bold; display: block; }
        .summary .caption { color: #555; font-size: 9px; }
        .status { display: inline-block; padding: 1px 6px; border: 1px solid #94a3b8; border-radius: 3px; font-size: 9px; }
        .signature { width: 100%; margin-top: 28px; }
        .signature td { width: 50%; vertical-align: top; }
        .signature .space { height: 56px; }
        .footer-note { margin-top: 18px; font-size: 8.5px; color: #777; }
        p { line-height: 1.5; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="university">{{ $student['university'] ?? 'Universitas' }}</div>
        <div class="unit">{{ $student['faculty'] ? 'Fakultas '.$student['faculty'].' — ' : '' }}Program Studi {{ $student['study_program'] }}</div>
    </div>

    @yield('content')

    <p class="footer-note">
        Dokumen ini dihasilkan otomatis oleh Civitas One pada {{ $generated_at->translatedFormat('j F Y H:i') }}.
    </p>
</body>
</html>
