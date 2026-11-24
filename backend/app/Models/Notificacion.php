<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'telefono_destino', 'canal', 'plantilla', 'orden_trabajo_id',
    'payload', 'estado', 'enviado_at', 'error',
])]
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    /** Nombre en lenguaje de persona de cada plantilla de WhatsApp. */
    public const PLANTILLAS = [
        'invitacion_cuenta_cliente' => 'Invitación al portal del cliente',
        'invitacion_tecnico' => 'Invitación de técnico',
        'ot_en_diagnostico' => 'Vehículo en diagnóstico',
        'ot_presupuesto_listo' => 'Presupuesto listo para aprobar',
        'presupuesto_enviado' => 'Presupuesto enviado',
        'ot_en_reparacion' => 'Vehículo en reparación',
        'ot_lista_entrega' => 'Vehículo listo para entrega',
        'solicitud_ingreso_cliente' => 'Solicitud de ingreso',
    ];

    /** Grupos para las pestañas de la lista. */
    public const GRUPOS = [
        'presupuestos' => ['presupuesto_enviado', 'ot_presupuesto_listo'],
        'avances' => ['ot_en_diagnostico', 'ot_en_reparacion', 'ot_lista_entrega'],
        'invitaciones' => ['invitacion_cuenta_cliente', 'invitacion_tecnico', 'solicitud_ingreso_cliente'],
    ];

    /** Errores frecuentes de Meta, dichos como los entendería quien atiende el mostrador. */
    private const ERRORES_META = [
        190 => 'La conexión con WhatsApp venció. Hay que reconectar el número.',
        131058 => 'Con este número solo se pueden enviar mensajes de prueba.',
        131030 => 'Ese número no está autorizado para recibir mensajes todavía.',
        131026 => 'El número no tiene WhatsApp o el mensaje no se pudo entregar.',
        131047 => 'Pasaron más de 24 horas desde el último mensaje del cliente: hay que usar una plantilla.',
        132000 => 'Faltan datos para armar la plantilla del mensaje.',
        132001 => 'La plantilla no existe o todavía no está aprobada por Meta.',
        132012 => 'Los datos de la plantilla no tienen el formato que Meta espera.',
        132015 => 'Meta pausó esta plantilla por mala calidad.',
        133010 => 'El número de WhatsApp del taller no está registrado en Meta.',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'enviado_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function nombrePlantilla(): string
    {
        return self::PLANTILLAS[$this->plantilla] ?? Str::of((string) $this->plantilla)->replace('_', ' ')->ucfirst()->toString();
    }

    /** El error de Meta traducido; null si el mensaje no falló. */
    public function errorLegible(): ?string
    {
        if (blank($this->error)) {
            return null;
        }

        $respuesta = json_decode((string) $this->error, true);
        $codigo = is_array($respuesta) ? ($respuesta['error']['code'] ?? null) : null;

        if ($codigo !== null && isset(self::ERRORES_META[(int) $codigo])) {
            return self::ERRORES_META[(int) $codigo];
        }

        $mensaje = is_array($respuesta) ? ($respuesta['error']['message'] ?? null) : null;

        return Str::limit((string) ($mensaje ?? $this->error), 140);
    }
}
