<?php

namespace App\Filament\Resources\Pagos\Schemas;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PagoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (Set $set) => $set('orden_trabajo_id', null)),
                Placeholder::make('saldo_cliente')
                    ->label('Estado de cuenta')
                    ->content(function (Get $get) {
                        $clienteId = $get('cliente_id');
                        if (blank($clienteId)) {
                            return 'Elegí un cliente para ver su saldo.';
                        }

                        $cliente = Cliente::find($clienteId);
                        if (! $cliente) {
                            return null;
                        }

                        $saldo = $cliente->saldoPendiente();

                        if ($saldo > 0.009) {
                            return new HtmlString("<span style=\"color: rgb(248 113 113)\">Debe Bs " . number_format($saldo, 2) . "</span>");
                        }

                        if ($saldo < -0.009) {
                            return new HtmlString("<span style=\"color: rgb(163 230 53)\">A favor Bs " . number_format(abs($saldo), 2) . " (pagó de más / anticipo)</span>");
                        }

                        return new HtmlString('<span style="color: rgb(163 230 53)">Al día — sin saldo pendiente</span>');
                    }),
                Select::make('orden_trabajo_id')
                    ->label('Orden de trabajo')
                    ->options(
                        fn (Get $get) => $get('cliente_id')
                            ? OrdenTrabajo::where('cliente_id', $get('cliente_id'))
                                ->orderByDesc('fecha_ingreso')
                                ->pluck('codigo', 'id')
                            : [],
                    )
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Get $get) => blank($get('cliente_id')))
                    ->helperText('Opcional — dejalo vacío para un abono general a la cuenta del cliente.'),
                Select::make('tipo')
                    ->label('Tipo de cobro')
                    ->options([
                        'anticipo' => 'Anticipo',
                        'parcial' => 'Pago parcial',
                        'completo' => 'Pago completo',
                        'abono_deuda' => 'Abono a deuda',
                    ])
                    ->required(),
                Select::make('metodo')
                    ->label('Método de pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'qr' => 'QR',
                        'tarjeta' => 'Tarjeta',
                    ])
                    ->required(),
                TextInput::make('monto')
                    ->label('Monto')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('Bs'),
                Select::make('tipo_documento')
                    ->label('Comprobante a emitir')
                    ->options([
                        'recibo' => 'Recibo',
                        'factura' => 'Factura (próximamente)',
                    ])
                    ->default('recibo')
                    ->required()
                    ->helperText('La facturación electrónica todavía no está activa (falta el NIT del taller y el proveedor de facturación) — mientras tanto, elegir "Factura" solo deja registrado que ese cobro debería facturarse cuando esté listo; se genera un recibo igual.'),
                TextInput::make('referencia_externa')
                    ->label('Referencia (QR / tarjeta)')
                    ->maxLength(100)
                    ->helperText('Número de operación o referencia del pago, si aplica.'),
                Select::make('detalle_tipo')
                    ->label('Detalle del recibo')
                    ->options([
                        'resumen' => 'Resumen (lo escribo yo)',
                        'detallado' => 'Detallado (líneas del presupuesto aprobado)',
                    ])
                    ->default('resumen')
                    ->live()
                    ->required()
                    ->helperText(fn (Get $get) => $get('detalle_tipo') === 'detallado' && blank($get('orden_trabajo_id'))
                        ? 'Elegí una orden de trabajo arriba para poder armar el detalle desde su presupuesto.'
                        : null),
                Textarea::make('detalle_servicio')
                    ->label('Detalle de servicio')
                    ->placeholder('Ej: Cambio de aceite y filtro, revisión de frenos')
                    ->visible(fn (Get $get) => $get('detalle_tipo') !== 'detallado')
                    ->maxLength(500),
            ]);
    }
}
