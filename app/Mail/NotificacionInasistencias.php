<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionInasistencias extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $responsable;
    public $estudiante;
    public $fecha;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($responsable, $estudiante, $fecha)
    {
        $this->responsable = $responsable;
        $this->estudiante = $estudiante;
        $this->fecha = $fecha;
    }

    public function build()
    {
        return $this->view('emails.notificacion-inasistencia')
            ->with([
                'responsable' => $this->responsable,
                'estudiante' => $this->estudiante,
                'fecha' => $this->fecha
            ]);
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Notificacion de Inasistencia',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
