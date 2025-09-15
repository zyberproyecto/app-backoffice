@extends('layout')
@section('title','Exoneraciones')

@section('content')
<div class="bo-panel">
  <div class="bo-panel__title">
    Exoneraciones
    @if(isset($estado) && $estado!=='todas')
      <span class="bo-muted" style="font-weight:400;">({{ $estado }})</span>
    @endif
  </div>

  <div style="overflow-x:auto;">
    <table class="bo-table">
      <thead>
        <tr>
          <th>#</th>
          <th>CI</th>
          <th>Semana</th>
          <th>Motivo</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($exoneraciones as $e)
        <tr>
          <td>{{ $e->id }}</td>
          <td>{{ $e->ci_usuario }}</td>
          <td>{{ \Illuminate\Support\Str::of($e->semana_inicio)->limit(10) }}</td>
          <td>{{ \Illuminate\Support\Str::limit($e->motivo,40) }}</td>
          <td>
            @if($e->estado === 'pendiente')
              <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
            @elseif($e->estado === 'aprobada')
              <span style="color:var(--bo-success); font-weight:600;">Aprobada</span>
            @else
              <span class="bo-muted">{{ ucfirst($e->estado) }}</span>
            @endif
          </td>
          <td style="text-align:right;">
            <a class="bo-btn bo-btn--ghost" href="{{ route('admin.exoneraciones.show',$e->id) }}">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center; padding:24px;" class="bo-muted">
            No hay registros.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($exoneraciones,'links'))
    <div style="margin-top:12px;">
      {{ $exoneraciones->links() }}
    </div>
  @endif
</div>
@endsection