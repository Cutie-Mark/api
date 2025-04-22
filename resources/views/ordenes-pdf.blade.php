<!DOCTYPE html>
<html>
<head>
    <title>Orden de Pago - {{ $orden->lista->codigo_lista }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
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
    </div>

    <h3>Inscripciones</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre del Postulante</th>
                <th>CI</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orden->inscripciones as $inscripcion)
                <tr>
                    <td>{{ $inscripcion->postulante->nombre_completo }}</td>
                    <td>{{ $inscripcion->postulante->ci }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>