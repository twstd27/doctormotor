<?php

namespace App\Filament\Resources\Pagos\Schemas;

use App\Models\OrdenTrabajo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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
            ]);
    }
}
