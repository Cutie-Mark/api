<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Postulante;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;

class DesencriptarDatosPostulantes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:desencriptar-datos-postulantes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desencripta los datos sensibles (nombres, apellidos, ci) de los postulantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de desencriptación de datos de postulantes...');
        $this->warn('Este proceso revertirá el cifrado de los campos nombres, apellidos y ci.');

        if (!$this->confirm('¿Está seguro de que desea continuar? Esta acción no se puede deshacer fácilmente.')) {
            $this->info('Operación cancelada.');
            return;
        }

        try {
            DB::beginTransaction();

            $postulantes = Postulante::all();
            $actualizados = 0;

            foreach ($postulantes as $postulante) {
                $cambios = [];

                // Desencriptar nombres
                $rawNombres = $postulante->getRawOriginal('nombres');
                try {
                    $cambios['nombres'] = Crypt::decryptString($rawNombres);
                } catch (DecryptException $e) {
                    // El dato no estaba encriptado, se ignora
                }

                // Desencriptar apellidos
                $rawApellidos = $postulante->getRawOriginal('apellidos');
                try {
                    $cambios['apellidos'] = Crypt::decryptString($rawApellidos);
                } catch (DecryptException $e) {
                    // El dato no estaba encriptado, se ignora
                }

                // Desencriptar CI
                $rawCi = $postulante->getRawOriginal('ci');
                try {
                    $cambios['ci'] = Crypt::decryptString($rawCi);
                } catch (DecryptException $e) {
                    // El dato no estaba encriptado, se ignora
                }

                if (!empty($cambios)) {
                    DB::table('postulantes')->where('id', $postulante->id)->update($cambios);
                    $this->line("Postulante {$postulante->id}: Datos desencriptados.");
                    $actualizados++;
                }
            }

            DB::commit();
            $this->info("Proceso completado. Postulantes actualizados: $actualizados");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la desencriptación: {$e->getMessage()}");
            return 1;
        }
    }
}
