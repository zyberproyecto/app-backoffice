@extends('layout')
@section('title','Perfiles de socios')

@section('content')
<div class="bo-main">
  <h1 class="bo-h1">Perfiles de socios</h1>

  {{-- Filtros --}}
  <form method="GET" class="bo-panel" style="margin-bottom:14px; display:grid; grid-template-columns:1fr 1fr auto; gap:10px;">
    <div>
      <label for="estado" class="text-muted" style="display:block; font-size:.9rem;">Estado</label>
      <select name="estado" id="estado" class="form-control">
        @php $est = $estado ?? 'pendiente'; @endphp
        <option value="pendiente" {{ $est==='pendiente' ? 'selected' : '' }}>pendiente</option>
        <option value="aprobado"  {{ $est==='aprobado'  ? 'selected' : '' }}>aprobado</option>
        <option value="rechazado" {{ $est==='rechazado' ? 'selected' : '' }}>rechazado</option>
        <option value="todas"     {{ $est==='todas'     ? 'selected' : '' }}>todas</option>
      </select>
    </div>
    <div>
      <label for="ci" class="text-muted" style="display:block; font-size:.9rem;">CI (solo dígitos)</label>
      <input type="text" name="ci" id="ci" value="{{ request('ci') }}" class="form-control" placeholder="Ej: 43216543" />
    </div>
    <div style="align-self:end;">
      <button class="bo-btn">Filtrar</button>
    </div>
  </form>

  {{-- Mensajes --}}
  @if(session('ok'))    <div class="bo-alert bo-alert--success">{{ session('ok') }}</div>@endif
  @if(session('error')) <div class="bo-alert" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">{{ session('error') }}</div>@endif

  {{-- Tabla --}}
  <div class="bo-panel">
    @if($items->count() === 0)
      <div class="bo-panel__body">No hay perfiles para mostrar.</div>
    @else
      <div style="overflow:auto;">
        <table class="table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="text-align:left; border-bottom:1px solid var(--bo-border);">
              <th style="padding:8px;">CI</th>
              <th style="padding:8px;">Ocupación</th>
              <th style="padding:8px;">Ingresos (núcleo)</th>
              <th style="padding:8px;">Integrantes</th>
              <th style="padding:8px;">Estado</th>
              <th style="padding:8px;">Actualizado</th>
              <th style="padding:8px;"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $row)
              <tr style="border-bottom:1px solid #eef2f7;">
                <td style="padding:8px; font-weight:600;">{{ $row->ci_usuario }}</td>
                <td style="padding:8px;">{{ $row->ocupacion }}</td>
                <td style="padding:8px;">$ {{ number_format((float)$row->ingresos_nucleo_familiar, 2, ',', '.') }}</td>
                <td style="padding:8px;">{{ $row->integrantes_familia }}</td>
                <td style="padding:8px;">
                  <span style="padding:2px 8px; border-radius:999px; font-size:.85rem;
                    @switch(strtolower($row->estado_revision))
                      @case('aprobado')  background:#ecfdf5;color:#065f46; @break
                      @case('rechazado') background:#fef2f2;color:#991b1b; @break
                      @default           background:#fff7ed;color:#9a3412;
                    @endswitch
                  ">
                    {{ strtolower($row->estado_revision) }}
                  </span>
                </td>
                <td style="padding:8px; color:var(--bo-muted);">{{ \Illuminate\Support\Carbon::parse($row->updated_at)->format('Y-m-d H:i') }}</td>
                <td style="padding:8px;">
                  <a href="{{ route('admin.perfiles.show', $row->ci_usuario) }}" class="bo-btn">Ver</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px;">
        {{ $items->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection