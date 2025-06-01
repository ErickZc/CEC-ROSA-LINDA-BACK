<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InasistenciaMailService;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionesInasistencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inasistencias:enviar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía correos de inasistencias a los responsables';

    /**
     * Execute the console command.
     *
     * @return int
     */

    protected $inasistenciaService;

    public function __construct(InasistenciaMailService $inasistenciaService)
    {
        parent::__construct();
        $this->inasistenciaService = $inasistenciaService;
    }

    public function handle()
    {
        $this->inasistenciaService->enviarCorreos();
        Log::info("Comando automático ejecutado a las " . now());
        $this->info('Correos encolados correctamente');
    }
}
