@extends('layout')
@section('title','Unidades')

@section('content')
<h1 class="bo-h1">Unidades</h1>

@if(session('ok'))
  <div class="bo-alert bo-alert--success">{{ session('ok') }}</div>
@endif
@if(session('error'))
  <div class="bo-alert bo-alert--error">{{ session('error') }}</div>
@endif

<form method="GET" action="{{ route('admin.unidades.index') }}" style="display:flex; gap:8px; align-items:center; margin-bottom:12px;">
  <input type="text" name="q" class="bo-input" placeholder="Buscar por código..." value="{{ $buscar }}">
  <button class="bo-btn" type="submit">Buscar</button>
</form>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
  {{-- Disponibles --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Disponibles</div>
    <div class="bo-panel__body">
      @if($disponibles->isEmpty())
        <div class="bo-muted">No hay unidades disponibles.</div>
      @else
      <div style="overflow-x:auto;">
        <table class="bo-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Dorm.</th>
              <th>Asignar a CI</th>
              <th>Nota</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($disponibles as $u)
            <tr>
              <td>{{ $u->codigo }}</td>
              <td>{{ $u->dormitorios }}</td>
              <td>
                <form method="POST" action="{{ route('admin.unidades.asignar') }}" style="display:flex; gap:8px; align-items:center; margin:0;">
                  @csrf
                  <input type="hidden" name="unidad_id" value="{{ $u->id }}">
                  <input
                    type="text"
                    name="ci_usuario"
                    class="bo-input"
                    placeholder="CI (7–8 díg.)"
                    required
                    inputmode="numeric"
                    pattern="[0-9]{7,8}"
                    title="Ingrese 7 u 8 dígitos"
                    style="max-width:160px;"
                  >
              </td>
              <td>
                  {{--  FIX: el controller espera "nota" --}}
                  <input type="text" name="nota" class="bo-input" placeholder="Opcional">
              </td>
              <td style="text-align:right;">
                  <button class="bo-btn" type="submit">Asignar</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </section>

  {{-- Asignadas --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Asignadas (activas)</div>
    <div class="bo-panel__body">
      @if($asignadas->isEmpty())
        <div class="bo-muted">No hay unidades asignadas.</div>
      @else
      <div style="overflow-x:auto;">
        <table class="bo-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Dorm.</th>
              <th>CI</th>
              <th>Socio</th>
              <th>Asignada</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($asignadas as $a)
            <tr>
              <td>{{ $a->codigo }}</td>
              <td>{{ $a->dormitorios }}</td>
              <td>{{ $a->ci_usuario }}</td>
              <td>{{ trim(($a->primer_nombre ?? '').' '.($a->primer_apellido ?? '')) }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($a->fecha_asignacion)->format('d/m/Y') }}</td>
              <td style="text-align:right;">
                {{-- FIX: pasar id de la ASIGNACIÓN, no el unidad_id --}}
                <form method="POST" action="{{ route('admin.unidades.liberar', $a->asignacion_id) }}" style="margin:0;">
                  @csrf
                  @method('PUT')
                  <button class="bo-btn bo-btn--ghost" type="submit">Liberar</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </section>
</div>
@endsection