@extends('admission::pdf._layout', ['title' => $title, 'universityName' => \Modules\Tenancy\Models\University::query()->whereKey(app(\App\Support\Tenancy\TenantContext::class)->universityId())->value('name')])
@section('content')
<h2 style="margin-top:0">{{ $title }}</h2>
<div class="muted">Pencetak: {{ $user->name }} · Filter: {{ json_encode($filters, JSON_UNESCAPED_UNICODE) }}</div>
<table class="grid" style="margin-top:8px">
    <tr>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
    @foreach ($rows as $row)
        <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
    @endforeach
</table>
@endsection
