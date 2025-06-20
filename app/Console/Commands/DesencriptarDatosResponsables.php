<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Responsable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;

class DesencriptarDatosResponsables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:desencriptar-datos-responsables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desencripta los datos sensibles (nombre_completo, ci) de los responsables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de desencriptación de datos de responsables...');
        $this->warn('Este proceso revertirá el cifrado de los campos nombre_completo y ci.');

        if (!$this->confirm('¿Está seguro de que desea continuar? Esta acción no se puede deshacer fácilmente.')) {
            $this->info('Operación cancelada.');
            return;
        }

        try {
            DB::beginTransaction();

            $responsables = Responsable::all();
            $actualizados = 0;

            foreach ($responsables as $responsable) {
                $cambios = [];

                // Desencriptar nombre_completo
                $rawNombreCompleto = $responsable->getRawOriginal('nombre_completo');
                try {
                    $cambios['nombre_completo'] = Crypt::decryptString($rawNombreCompleto);
                } catch (DecryptException $e) {
                    // El dato no estaba encriptado o no se puede desencriptar, se ignora
                }

                // Desencriptar CI
                $rawCi = $responsable->getRawOriginal('ci');
                try {
                    $cambios['ci'] = Crypt::decryptString($rawCi);
                } catch (DecryptException $e) {
                    // El dato no estaba encriptado o no se puede desencriptar, se ignora
                }

                if (!empty($cambios)) {
                    DB::table('responsables')->where('id', $responsable->id)->update($cambios);
                    $this->line("Responsable {$responsable->id}: Datos desencriptados.");
                    $actualizados++;
                }
            }

            DB::commit();
            $this->info("Proceso completado. Responsables actualizados: $actualizados");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la desencriptación: {$e->getMessage()}");
            return 1;
        }
    }
}
