<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .subtitle { color: #555; margin: 0 0 12px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary td { border: 1px solid #ddd; padding: 6px 4px; text-align: center; }
        .summary .value { font-size: 13px; font-weight: bold; display: block; }
        .summary .caption { color: #555; font-size: 9px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.data th { background: #f8fafc; }
        .footer { margin-top: 12px; color: #777; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="subtitle">{{ $universityName }} — dicetak {{ $generatedAt }}</p>

    @if (count($summary) > 0)
        <table class="summary">
            <tr>
                @foreach ($summary as $label => $value)
                    <td>
                        <span class="value">{{ is_scalar($value) ? $value : '-' }}</span>
                        <span class="caption">{{ ucwords(str_replace('_', ' ', (string) $label)) }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                @foreach ($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach ($columns as $column)
                        <td>{{ $row[$column['key']] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Total {{ count($rows) }} baris.</p>
</body>
</html>
