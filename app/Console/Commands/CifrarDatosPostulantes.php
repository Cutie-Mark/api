<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Postulante;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;

class CifrarDatosPostulantes extends Command
{
    protected $signature = 'postulantes:cifrar-datos';
    protected $description = 'Cifra los datos sensibles (nombres, apellidos, ci) de los postulantes si no están cifrados aún';    public function handle()
    {
        $this->info('Iniciando proceso de cifrado de datos sensibles de postulantes (nombres, apellidos, ci)...');
        $this->warn('Este proceso solo necesita ejecutarse una vez después de implementar la encriptación.');
        
        // Desactivamos temporalmente los mutators para acceder a los valores sin procesar
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $postulantes = Postulante::all();
        $actualizados = 0;

        foreach ($postulantes as $postulante) {
            $camposCifrados = 0;
            $cambios = [];
              // Cifrar campo nombres
            $rawNombres = $postulante->getRawOriginal('nombres');
            if ($rawNombres === null) {
                $this->line("Postulante {$postulante->id}: Nombres es NULL, no requiere cifrado");
            } else {
                try {
                    // Intentar descifrar para ver si ya está cifrado
                    Crypt::decryptString($rawNombres);
                    $this->line("Postulante {$postulante->id}: Nombres ya cifrados");
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Si lanza excepción de desencriptación, no está cifrado
                    $cambios['nombres'] = Crypt::encryptString($rawNombres);
                    $camposCifrados++;
                }
            }
              // Cifrar campo apellidos
            $rawApellidos = $postulante->getRawOriginal('apellidos');
            if ($rawApellidos === null) {
                $this->line("Postulante {$postulante->id}: Apellidos es NULL, no requiere cifrado");
            } else {
                try {
                    Crypt::decryptString($rawApellidos);
                    $this->line("Postulante {$postulante->id}: Apellidos ya cifrados");
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    $cambios['apellidos'] = Crypt::encryptString($rawApellidos);
                    $camposCifrados++;
                }
            }
              // Cifrar campo CI
            $rawCi = $postulante->getRawOriginal('ci');
            if ($rawCi === null) {
                $this->line("Postulante {$postulante->id}: CI es NULL, no requiere cifrado");
            } else {
                try {
                    Crypt::decryptString($rawCi);
                    $this->line("Postulante {$postulante->id}: CI ya cifrado");
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    $cambios['ci'] = Crypt::encryptString($rawCi);
                    $camposCifrados++;
                }
            }
            
            // Si hay cambios, actualizar el registro directamente en la base de datos
            // sin pasar por los mutators (que volverían a cifrar los datos)
            if ($camposCifrados > 0) {
                DB::table('postulantes')
                    ->where('id', $postulante->id)
                    ->update($cambios);
                    
                $this->info("Postulante {$postulante->id}: {$camposCifrados} campos cifrados");
                $actualizados++;
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info("Proceso completado. Postulantes actualizados: $actualizados");
        return 0;
    }
}
