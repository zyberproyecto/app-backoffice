@extends('layout')
@section('title','Comprobantes')

@section('content')
<div class="bo-panel">
  <div class="bo-panel__title">Comprobantes</div>

  <div style="overflow-x:auto;">
    <table class="bo-table">
      <thead>
        <tr>
          <th>#</th>
          <th>CI</th>
          <th>Tipo</th>
          <th>Periodo</th>
          <th>Monto</th>
          <th>Estado</th>
          <th>Subido</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($items as $c)
        @php
          $tipoVal = strtolower((string)($c->tipo ?? $c->tipo_aporte ?? ''));
          $tipoTxt = match(true) {
            in_array($tipoVal, ['inicial','aporte_inicial']) => 'Aporte inicial',
            $tipoVal === 'aporte_mensual' => 'Aporte mensual',
            $tipoVal === 'compensatorio'  => 'Compensatorio',
            default => $tipoVal !== '' ? ucfirst($tipoVal) : '—',
          };
          $estado  = strtolower((string)$c->estado);
        @endphp
        <tr>
          <td>{{ $c->id }}</td>
          <td>{{ $c->ci_usuario }}</td>
          <td>{{ $tipoTxt }}</td>
          <td>{{ $c->periodo ?? '—' }}</td>
          <td>
            @if(isset($c->monto))
              {{ number_format((float)$c->monto,2,',','.') }}
            @else
              —
            @endif
          </td>
          <td>
            @if($estado === 'pendiente')
              <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
            @elseif($estado === 'aprobado')
              <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
            @elseif($estado === 'rechazado')
              <span class="bo-muted">Rechazado</span>
            @else
              <span class="bo-muted">{{ ucfirst($c->estado ?? '—') }}</span>
            @endif
          </td>
          <td>{{ $c->created_at ?? '—' }}</td>
          <td style="text-align:right;">
            <a class="bo-btn bo-btn--ghost" href="{{ route('admin.comprobantes.show',$c->id) }}">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center; padding:24px;" class="bo-muted">
            No hay registros.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($items,'links'))
    <div style="margin-top:12px;">
      {{ $items->appends(request()->query())->links() }}
    </div>
  @endif
</div>
@endsection