<!DOCTYPE html>
<html>
<head>
    <title>Orden de Pago - {{ $orden->lista->codigo_lista }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        .table th, .table td { 
            border-bottom: 2px solid #ddd; /* Línea separadora */
            padding: 12px; 
            text-align: left; 
            vertical-align: top;
        }
        .table th { 
            background-color: #f2f2f2;
            border-bottom: 3px solid #666; 
        }
        .col-nombre { width: 30%; }
        .col-ci { width: 20%; }
        .col-categorias { width: 50%; }
        .categoria-item { 
            margin: 4px 0;
            padding: 4px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Orden de Pago</h2>
        <p>Código de Lista: {{ $orden->lista->codigo_lista }}</p>
    </div>

    <div class="details">
        <p><strong>Monto:</strong> ${{ number_format($orden->monto, 2) }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($orden->estado) }}</p>
        <p><strong>Emitido por:</strong> {{ $orden->emitido_por }}</p>
        <p><strong>NIT/CI:</strong> {{ $orden->nitci }}</p>
        @if($orden->senior)
        <p><strong>Responsable:</strong> {{ $orden->senior }}</p>
        @endif
    </div>

    <h3>Inscripciones</h3>
    <table class="table">
        <thead>
            <tr>
                <th class="col-nombre">Nombre Completo</th>
                <th class="col-ci">CI</th>
                <th class="col-categorias">Categorías Inscritas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orden->lista->inscripciones->groupBy('postulante_id') as $grupo)
                @php
                    $primeraInscripcion = $grupo->first();
                    $postulante = $primeraInscripcion->postulante;
                @endphp
                <tr>
                    <td>{{ $postulante->nombres }} {{ $postulante->apellidos }}</td>
                    <td>{{ $postulante->ci }}</td>
                    <td>
                        @foreach ($grupo as $inscripcion)
                            <div class="categoria-item">
                                {{ $inscripcion->nivelCompetencia->area->nombre }} - 
                                {{ $inscripcion->nivelCompetencia->categoria->nombre }}
                            </div>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>