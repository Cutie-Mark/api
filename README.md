**CARACTERISTICAS**
- PGSQL 15.12
- PHP 8.2
- LARAVEL 11

**PASOS PARA USO:**
- **MIGRACIONES:**
    - Crear: php artisan make:migration create_{nombre de tabla en plural}_table
    - Migrar: php artisan migrate
    - Revertir: php artisan migrate:rollback

- **INICIAR LA API:**
    - Copiar el archivo de entorno y renombrarlo como .env (o tener el tuyo propio, asegurate de que la base de datos sea postgres
    - Generar llave: php artisan key:generate
    - Iniciar la api: php artisan serve
    - Cargar todo: php artisan db:seed
        - Cargar departamentos: php artisan db:seed --class=DepartamentosSeeder
        - Cargar areas: php artisan db:seed --class=AreasSeeder
        - Cargar categorias: php artisan db:seed --class=CategoriasSeeder
    - Comando para ver todas las peticiones disponibles: php artisan route:list
