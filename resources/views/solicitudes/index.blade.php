@extends('layout')
@section('title','Solicitudes')

@section('content')
<div class="bo-panel">
  <div class="bo-panel__title">Solicitudes</div>

  {{-- mensajes flash --}}
  @if(session('ok'))    <div class="bo-alert bo-alert--success" style="margin:10px 0;">{{ session('ok') }}</div>@endif
  @if(session('error')) <div class="bo-alert" style="margin:10px 0; background:#fef2f2;color:#991b1b;border-color:#fecaca;">{{ session('error') }}</div>@endif

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
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
      @forelse($solicitudes as $s)
        @php $st = strtolower($s->estado ?? 'pendiente'); @endphp
        <tr>
          <td>{{ $s->id }}</td>
          <td>{{ $s->ci }}</td>
          <td>{{ $s->nombre_completo }}</td>
          <td>{{ $s->email }}</td>
          <td>{{ $s->telefono }}</td>
          <td>
            @if($st === 'pendiente')
              <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
            @elseif($st === 'aprobado')
              <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
            @else
              <span class="bo-muted">{{ ucfirst($s->estado) }}</span>
            @endif
          </td>
          <td>
            {{ $s->created_at ? \Illuminate\Support\Carbon::parse($s->created_at)->format('Y-m-d H:i') : '—' }}
          </td>
          <td style="text-align:right; white-space:nowrap;">
            <a class="bo-btn bo-btn--ghost" href="{{ route('admin.solicitudes.show',$s->id) }}">Ver</a>

            @if($st === 'pendiente')
              <form action="{{ route('admin.solicitudes.aprobar', $s->id) }}" method="POST" style="display:inline-block; margin-left:6px">
                @csrf @method('PUT')
                <button type="submit" class="bo-btn bo-btn--success">Aprobar</button>
              </form>

              <form action="{{ route('admin.solicitudes.rechazar', $s->id) }}" method="POST" style="display:inline-block; margin-left:6px">
                @csrf @method('PUT')
                <button type="submit" class="bo-btn bo-btn--danger">Rechazar</button>
              </form>
            @endif
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
      {{ $solicitudes->withQueryString()->links() }}
    </div>
  @endif
</div>
@endsection