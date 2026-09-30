<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CajaCierre;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PagoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'orden_trabajo_id' => ['nullable', 'exists:ordenes_trabajo,id'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'tipo' => ['required', 'in:anticipo,parcial,completo,abono_deuda'],
            'metodo' => ['required', 'in:efectivo,qr,tarjeta'],
            'tipo_documento' => ['nullable', 'in:recibo,factura'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'referencia_externa' => ['nullable', 'string', 'max:100'],
            'detalle_tipo' => ['nullable', 'in:resumen,detallado'],
            'detalle_servicio' => ['nullable', 'string'],
        ]);

        $cajaAbierta = CajaCierre::abiertaDe($request->user()->id);

        // El efectivo cobrado sin turno de caja abierto nunca se podría sumar en ningún
        // cierre (ver CajaController::cierre, que suma $caja->pagos()) — quedaría cobrado
        // pero invisible para el arqueo. Tarjeta y QR sí quedan trazados por fuera (banco/
        // billetera), así que no dependen del turno físico.
        if ($data['metodo'] === 'efectivo' && ! $cajaAbierta) {
            return response()->json(['message' => 'Debes abrir tu turno de caja antes de registrar cobros en efectivo.'], 422);
        }

        $detalleTipo = $data['detalle_tipo'] ?? 'resumen';
        $ordenTrabajo = ! empty($data['orden_trabajo_id']) ? OrdenTrabajo::find($data['orden_trabajo_id']) : null;

        $pago = Pago::create([
            ...$data,
            'cajero_id' => $request->user()->id,
            'caja_cierre_id' => $cajaAbierta?->id,
            'fecha' => now(),
            'detalle_tipo' => $detalleTipo,
            'detalle_items' => $detalleTipo === 'detallado' && $ordenTrabajo
                ? Pago::itemsAprobadosDe($ordenTrabajo)
                : null,
        ]);

        return response()->json(['data' => $pago], 201);
    }

    public function recibo(Pago $pago): Response
    {
        $pago->load('cliente', 'ordenTrabajo');

        return Pdf::loadView('pdf.recibo', ['pago' => $pago])->stream("recibo-{$pago->id}.pdf");
    }

    /**
     * Formato ticket (80mm) para impresoras térmicas con driver estándar de Windows.
     *
     * No es integración ESC/POS a bajo nivel — eso necesita el modelo real de impresora
     * del taller para poder probarse (ver docs/01-ARQUITECTURA.md, "Riesgos y supuestos").
     * Esto sí es 100% verificable sin hardware: un PDF angosto que cualquier impresora
     * térmica con driver de Windows imprime bien desde el diálogo de impresión normal.
     */
    public function reciboTicket(Pago $pago): Response
    {
        $pago->load('cliente', 'ordenTrabajo');

        $pdf = Pdf::loadView('pdf.recibo-ticket', ['pago' => $pago])
            ->setPaper([0, 0, 226.77, 700], 'portrait'); // 80mm de ancho

        return $pdf->stream("ticket-{$pago->id}.pdf");
    }

    /**
     * Estado de cuenta del cliente: historial de pagos y saldo pendiente.
     *
     * El saldo real se termina de calcular en Fase 2 contra el total facturado por OT
     * (costos_directos + presupuestos aprobados). Por ahora expone el historial de pagos.
     */
    public function cuenta(Cliente $cliente): JsonResponse
    {
        $pagos = $cliente->pagos()->orderByDesc('fecha')->get();

        return response()->json([
            'data' => [
                'cliente_id' => $cliente->id,
                'total_pagado' => $pagos->sum('monto'),
                'pagos' => $pagos,
            ],
        ]);
    }
}
