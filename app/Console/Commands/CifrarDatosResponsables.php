<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Responsable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CifrarDatosResponsables extends Command
{
    
    protected $signature = 'responsables:cifrar-datos';
    protected $description = 'Cifra los datos sensibles (nombre_completo, ci) de los responsables si no están cifrados aún';

    public function handle()
    {
        $this->info('Iniciando proceso de cifrado de datos sensibles de responsables...');
        
        // Desactivamos temporalmente los mutators para acceder a los valores sin procesar
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $responsables = Responsable::all();
        $actualizados = 0;

        foreach ($responsables as $responsable) {
            $camposCifrados = 0;
            $cambios = [];
            
            // Cifrar campo nombre_completo
            $rawNombreCompleto = $responsable->getRawOriginal('nombre_completo');
            try {
                // Intentar descifrar para ver si ya está cifrado
                Crypt::decryptString($rawNombreCompleto);
                $this->line("Responsable {$responsable->id}: Nombre completo ya cifrado");
            } catch (\Exception $e) {
                // Si lanza excepción, no está cifrado
                $cambios['nombre_completo'] = Crypt::encryptString($rawNombreCompleto);
                $camposCifrados++;
            }
            
            // Cifrar campo CI
            $rawCi = $responsable->getRawOriginal('ci');
            try {
                Crypt::decryptString($rawCi);
                $this->line("Responsable {$responsable->id}: CI ya cifrado");
            } catch (\Exception $e) {
                $cambios['ci'] = Crypt::encryptString($rawCi);
                $camposCifrados++;
            }
            
            // Si hay cambios, actualizar el registro directamente en la base de datos
            // sin pasar por los mutators (que volverían a cifrar los datos)
            if ($camposCifrados > 0) {
                DB::table('responsables')
                    ->where('id', $responsable->id)
                    ->update($cambios);
                    
                $this->info("Responsable {$responsable->id}: {$camposCifrados} campos cifrados");
                $actualizados++;
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info("Proceso completado. Responsables actualizados: $actualizados");
        return 0;
    }
}
