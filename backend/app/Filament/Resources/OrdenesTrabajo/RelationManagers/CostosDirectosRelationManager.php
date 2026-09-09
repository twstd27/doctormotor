<?php

namespace App\Filament\Resources\OrdenesTrabajo\RelationManagers;

use App\Http\Controllers\Api\ProductoController;
use App\Models\Producto;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CostosDirectosRelationManager extends RelationManager
{
    protected static string $relationship = 'costosDirectos';

    protected static ?string $title = 'Costos directos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'repuesto' => 'Repuesto',
                        'mano_obra' => 'Mano de obra',
                        'tercerizado' => 'Trabajo tercerizado',
                    ])
                    ->required()
                    ->live(),
                Select::make('producto_id')
                    ->label('Producto')
                    ->options(fn () => Producto::where('activo', true)->pluck('nombre', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get) => $get('tipo') === 'repuesto'),
                Select::make('tecnico_id')
                    ->label('Técnico')
                    ->options(fn () => User::where('rol', 'operador_tecnico')->pluck('nombre', 'id'))
                    ->searchable()
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['mano_obra', 'tercerizado'])),
                TextInput::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->minValue(0.01)
                    ->step(0.01)
                    ->required(),
                TextInput::make('costo_unitario')
                    ->label('Costo unitario')
                    ->numeric()
                    ->prefix('Bs')
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->description(function (self $livewire) {
                $ot = $livewire->getOwnerRecord();
                $ingresos = (float) $ot->pagos()->sum('monto');
                $costos = (float) $ot->costosDirectos()->sum('costo_total');

                return 'Ingresos cobrados: Bs '.number_format($ingresos, 2)
                    .' · Costos directos: Bs '.number_format($costos, 2)
                    .' · Margen neto: Bs '.number_format($ingresos - $costos, 2);
            })
            ->columns([
                BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'info' => 'repuesto',
                        'warning' => 'mano_obra',
                        'gray' => 'tercerizado',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'repuesto' => 'Repuesto',
                        'mano_obra' => 'Mano de obra',
                        'tercerizado' => 'Tercerizado',
                        default => $state,
                    }),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->placeholder('—'),
                TextColumn::make('tecnico.nombre')
                    ->label('Técnico')
                    ->placeholder('—'),
                TextColumn::make('cantidad')
                    ->label('Cant.')
                    ->numeric(2),
                TextColumn::make('costo_unitario')
                    ->label('Costo unitario')
                    ->money('BOB'),
                TextColumn::make('costo_total')
                    ->label('Costo total')
                    ->money('BOB')
                    ->weight('bold')
                    ->summarize(Sum::make()->money('BOB')->label('Total')),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar costo')
                    ->using(function (array $data, self $livewire) {
                        $cantidad = $data['cantidad'] ?? 1;
                        $data['cantidad'] = $cantidad;
                        $data['costo_total'] = $cantidad * $data['costo_unitario'];

                        $costo = $livewire->getOwnerRecord()->costosDirectos()->create($data);

                        if ($data['tipo'] === 'repuesto' && ! empty($data['producto_id'])) {
                            app(ProductoController::class)->registrarMovimiento(
                                productoId: $data['producto_id'],
                                tipo: 'salida_ot',
                                cantidad: -$cantidad,
                                referenciaId: $livewire->getOwnerRecord()->id,
                                referenciaTipo: 'orden_trabajo',
                                userId: auth()->id(),
                            );
                        }

                        return $costo;
                    }),
            ]);
    }
}
