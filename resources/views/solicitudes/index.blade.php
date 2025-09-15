@extends('layout')
@section('title','Solicitudes')

@section('content')
<div class="bo-panel">
  <div class="bo-panel__title">Solicitudes</div>

  <div style="overflow-x:auto;">
    <table class="bo-table">
      <thead>
        <tr>
          <th>#</th>
          <th>CI</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Estado</th>
          <th>Creado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($solicitudes as $s)
        <tr>
          <td>{{ $s->id }}</td>
          <td>{{ $s->ci }}</td>
          <td>{{ $s->nombre_completo }}</td>
          <td>{{ $s->email }}</td>
          <td>{{ $s->telefono }}</td>
          <td>
            @if($s->estado === 'pendiente')
              <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
            @elseif($s->estado === 'aprobado')
              <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
            @else
              <span class="bo-muted">{{ ucfirst($s->estado) }}</span>
            @endif
          </td>
          <td>{{ optional($s->created_at)->format('Y-m-d H:i') }}</td>
          <td style="text-align:right;">
            <a class="bo-btn bo-btn--ghost" href="{{ route('admin.solicitudes.show',$s->id) }}">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center; padding:24px;" class="bo-muted">
            No hay solicitudes.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($solicitudes,'links'))
    <div style="margin-top:12px;">
      {{ $solicitudes->links() }}
    </div>
  @endif
</div>
@endsection