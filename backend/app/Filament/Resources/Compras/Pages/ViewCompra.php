<?php

namespace App\Filament\Resources\Compras\Pages;

use App\Filament\Resources\Compras\CompraResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCompra extends ViewRecord
{
    protected static string $resource = CompraResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compra')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('proveedor.nombre')->label('Proveedor'),
                        TextEntry::make('fecha')->label('Fecha')->date('d/m/Y'),
                        TextEntry::make('numero_factura')->label('N.° factura')->placeholder('—'),
                        TextEntry::make('estado_pago')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'pendiente' => 'Pendiente',
                                'parcial' => 'Pago parcial',
                                'pagado' => 'Pagado',
                                default => $state,
                            })
                            ->color(fn (string $state) => match ($state) {
                                'pagado' => 'success',
                                'parcial' => 'warning',
                                default => 'gray',
                            }),
                    ]),
                Section::make('Ítems comprados')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('producto.nombre')->label('Producto'),
                                TextEntry::make('cantidad')->label('Cantidad')->numeric(2),
                                TextEntry::make('precio_unitario')->label('Precio unitario')->money('BOB'),
                                TextEntry::make('subtotal')->label('Subtotal')->money('BOB')->weight('bold'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        TextEntry::make('total')
                            ->label('Total de la compra')
                            ->money('BOB')
                            ->size('lg')
                            ->weight('bold'),
                    ]),
            ]);
    }
}
