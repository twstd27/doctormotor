<?php

namespace App\Events;

use App\Models\OrdenTrabajo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenTrabajoActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OrdenTrabajo $ordenTrabajo) {}

    /**
     * Canal del taller (Kanban admin) + canal por-cliente (portal del cliente).
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('ordenes-trabajo'),
            new Channel("cliente.{$this->ordenTrabajo->cliente_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ot.actualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->ordenTrabajo->id,
            'codigo' => $this->ordenTrabajo->codigo,
            'estado' => $this->ordenTrabajo->estado,
            'tecnico_asignado_id' => $this->ordenTrabajo->tecnico_asignado_id,
        ];
    }
}
