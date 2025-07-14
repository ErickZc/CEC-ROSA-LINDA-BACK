<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotasMailService;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionesNotas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notas:enviar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía correos de notas al finalizar el periodo a los responsables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected $notasService;

    public function __construct(NotasMailService $notasService)
    {
        parent::__construct();
        $this->notasService = $notasService;
    }

    public function handle()
    {
        $this->notasService->enviarCorreos();
        Log::info("[ENVÍO DE NOTAS] Comando automático ejecutado a las " . now());
        $this->info('Correos encolados correctamente');
    }
}
