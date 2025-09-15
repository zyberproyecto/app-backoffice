@extends('layout')
@section('title','Horas de trabajo')

@section('content')
<div class="bo-panel">
  <div class="bo-panel__title">Horas de trabajo</div>

  <div style="overflow-x:auto;">
    <table class="bo-table">
      <thead>
        <tr>
          <th>#</th>
          <th>CI</th>
          <th>Semana</th>
          <th>Horas</th>
          <th>Motivo</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($items as $h)
        <tr>
          <td>{{ $h->id }}</td>
          <td>{{ $h->ci_usuario }}</td>
          <td>{{ $h->semana_inicio }} – {{ $h->semana_fin }}</td>
          <td>{{ $h->horas_reportadas }}</td>
          <td>{{ \Illuminate\Support\Str::limit($h->motivo,40) }}</td>
          <td>
            @if($h->estado === 'reportado')
              <span style="color:var(--bo-warning); font-weight:600;">Reportado</span>
            @elseif($h->estado === 'aprobado')
              <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
            @else
              <span class="bo-muted">{{ ucfirst($h->estado) }}</span>
            @endif
          </td>
          <td style="text-align:right;">
            <a class="bo-btn bo-btn--ghost" href="{{ route('admin.horas.show',$h->id) }}">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center; padding:24px;" class="bo-muted">
            No hay registros.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($items,'links'))
    <div style="margin-top:12px;">
      {{ $items->links() }}
    </div>
  @endif
</div>
@endsection