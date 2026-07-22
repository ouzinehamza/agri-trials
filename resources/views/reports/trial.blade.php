<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
@page{margin:28px 34px 34px}body{font-family:DejaVu Sans,sans-serif;color:#24332d;font-size:10px;line-height:1.45}h1{font-size:21px;margin:0;color:#153f32}h2{font-size:13px;margin:18px 0 8px;color:#153f32;border-bottom:1px solid #dce6e1;padding-bottom:5px}.header{border-bottom:3px solid #1d9e75;padding-bottom:14px;margin-bottom:16px}.brand{font-size:11px;color:#1d9e75;font-weight:bold;letter-spacing:.5px}.meta{color:#68756f;margin-top:5px}.grid{width:100%;border-collapse:separate;border-spacing:8px 0;margin-left:-8px}.card{border:1px solid #dce6e1;border-radius:6px;padding:9px;vertical-align:top}.label{font-size:8px;text-transform:uppercase;color:#7d8983}.value{font-size:11px;margin-top:2px}.stats{width:100%;border-collapse:collapse;margin:12px 0}.stats td{width:25%;padding:9px;border:1px solid #dce6e1}.stat{font-size:18px;color:#153f32;font-weight:bold}table.data{width:100%;border-collapse:collapse}table.data th{background:#eef5f2;color:#4c5d55;text-align:left;font-size:8px;text-transform:uppercase;padding:6px;border-bottom:1px solid #ccd9d3}table.data td{padding:6px;border-bottom:1px solid #e4ebe8;vertical-align:top}table.workflow{font-size:8.5px}table.workflow th,table.workflow td{padding:4px 6px}.good{color:#16845f;font-weight:bold}.bad{color:#b54747;font-weight:bold}.pill{display:inline-block;padding:3px 7px;border-radius:4px;background:#e8f6ef;color:#16845f;font-weight:bold}.muted{color:#7d8983}.footer{position:fixed;bottom:-22px;left:0;right:0;text-align:center;font-size:8px;color:#89958f}.page-break{page-break-before:always}.avoid{page-break-inside:avoid}
</style></head><body>
<div class="footer">Rapport {{ $trial->code }} - genere le {{ $generated_at }}</div>
<div class="header"><div class="brand">AGRI TRIALS - RAPPORT VARIETAL</div><h1>{{ $trial->variety }}</h1><div class="meta">{{ $trial->code }} | {{ $trial->culture }} | {{ $trial->site }} | Saison {{ $trial->season }} | {{ $trial->status }}</div></div>
<table class="stats"><tr><td><div class="label">Recoltes</div><div class="stat">{{ $trial->harvests->count() }}</div></td><td><div class="label">Comparaisons</div><div class="stat">{{ count($summary) }}</div></td><td><div class="label">Decisions</div><div class="stat">{{ $trial->decisions->count() }}</div></td><td><div class="label">Charges</div><div class="stat">{{ number_format($expense_total,0,',',' ') }}</div><div class="muted">MAD</div></td></tr></table>
<h2>Fiche d'identite</h2>
<table class="grid"><tr><td class="card"><div class="label">Espace</div><div class="value">{{ $trial->workspace?->name ?? '-' }}</div><div class="label" style="margin-top:7px">Responsable</div><div class="value">{{ $trial->owner ?? '-' }}</div></td><td class="card"><div class="label">Fournisseur</div><div class="value">{{ $trial->supplier ?? '-' }}</div><div class="label" style="margin-top:7px">Conduite</div><div class="value">{{ $trial->conduct ?? '-' }}</div></td><td class="card"><div class="label">Temoins</div><div class="value">{{ implode(', ', $trial->controls ?? []) ?: '-' }}</div><div class="label" style="margin-top:7px">Segment</div><div class="value">{{ $trial->segment ?? '-' }}</div></td></tr></table>
<h2>Synthese des performances</h2>
<table class="data"><thead><tr><th>Mesure</th><th>Agregation</th><th>Essai</th><th>Temoin</th><th>Ecart</th><th>Conclusion</th></tr></thead><tbody>
@foreach($summary as $row)
    @php($dev = $row['temoin'] ? (($row['essai'] - $row['temoin']) / $row['temoin'] * 100) : 0)
    <tr><td><b>{{ $row['label'] }}</b><br><span class="muted">vs {{ $row['control_label'] }}</span></td><td>{{ $row['aggregation'] }}</td><td>{{ $row['essai'] ?? '-' }} {{ $row['unit'] }}</td><td>{{ $row['temoin'] ?? '-' }} {{ $row['unit'] }}</td><td class="{{ $dev >= 0 ? 'good' : 'bad' }}">{{ $dev >= 0 ? '+' : '' }}{{ number_format($dev, 1) }}%</td><td class="{{ $row['beats_control'] ? 'good' : 'bad' }}">{{ $row['beats_control'] ? 'Meilleur' : 'En retrait' }}</td></tr>
@endforeach
</tbody></table>
<h2>Workflow</h2>
<table class="data workflow"><thead><tr><th>#</th><th>Etape</th><th>Statut</th><th>Debut</th><th>Fin</th></tr></thead><tbody>
@foreach($trial->stageRecords->sortBy('sort_order') as $stage)
    <tr><td>{{ $stage->sort_order + 1 }}</td><td>{{ $stage->stage_name['fr'] ?? $stage->stage_key }}</td><td>{{ $stage->status }}</td><td>{{ $stage->started_at?->format('d/m/Y') ?? '-' }}</td><td>{{ $stage->completed_at?->format('d/m/Y') ?? '-' }}</td></tr>
@endforeach
</tbody></table>
<div class="page-break"></div>
<div class="header"><div class="brand">DONNEES DETAILLEES</div><h1>Recoltes et decisions</h1><div class="meta">{{ $trial->code }} - {{ $trial->variety }}</div></div>
<h2>Recoltes</h2>
@foreach($trial->harvests->sortBy('sequence') as $harvest)
    <div class="avoid"><p><b>Recolte #{{ $harvest->sequence }}</b> - {{ $harvest->harvested_on->format('d/m/Y') }} - {{ $harvest->location ?? '-' }} - {{ $harvest->recorder?->name }}</p><table class="data"><thead><tr><th>Mesure</th><th>Sujet</th><th>Type</th><th>Valeur</th></tr></thead><tbody>
    @foreach($harvest->values as $value)
        <tr><td>{{ $value->measurement?->name['fr'] ?? $value->measurement?->code }}</td><td>{{ $value->subject_label }}</td><td>{{ $value->subject_type }}</td><td>{{ $value->value ?? data_get($value->value_json, 'value') }} {{ $value->measurement?->unit }}</td></tr>
    @endforeach
    </tbody></table></div>
@endforeach
<h2>Journal des decisions</h2>
@forelse($trial->decisions as $decision)
    <div class="card avoid" style="margin-bottom:8px"><span class="pill">{{ \App\Models\Decision::VERDICT_LABELS[$decision->verdict] ?? $decision->verdict }}</span> <b style="float:right">{{ $decision->score }}/100</b><p>{{ $decision->justification }}</p><div class="muted">{{ $decision->decider?->name }} - {{ $decision->decided_at?->format('d/m/Y H:i') }}</div></div>
@empty
    <p class="muted">Aucune decision enregistree.</p>
@endforelse
@if($expenses->count())
    <h2>Charges</h2><table class="data"><thead><tr><th>Date</th><th>Categorie</th><th>Libelle</th><th>Montant</th></tr></thead><tbody>
    @foreach($expenses as $expense)
        <tr><td>{{ $expense->incurred_on->format('d/m/Y') }}</td><td>{{ $expense->category }}</td><td>{{ $expense->label }}</td><td>{{ number_format((float) $expense->amount, 2, ',', ' ') }} {{ $expense->currency }}</td></tr>
    @endforeach
    <tr><td colspan="3"><b>Total</b></td><td><b>{{ number_format($expense_total, 2, ',', ' ') }} MAD</b></td></tr></tbody></table>
@endif
</body></html>
