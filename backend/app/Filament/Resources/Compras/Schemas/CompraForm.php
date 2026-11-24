<?php

namespace App\Filament\Resources\Compras\Schemas;

use App\Models\Producto;
use App\Models\Proveedor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Registrar compra (HANDOFF §4, Compra-crear): proveedor y factura, los ítems en una tabla y,
 * a la derecha, lo que va a pasar al guardar. La compra a crédito con vencimiento es Fase 2:
 * por ahora toda compra genera su cuenta por pagar, como siempre.
 */
class CompraForm
{
    private static function bs(float $n): string
    {
        return 'Bs '.number_format($n, 2, ',', '.');
    }

    private static function cantidad(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }

    /**
     * Si se llega desde el aviso de stock (?bajo_minimo=1), se precargan los productos que
     * están en o bajo su mínimo, con una cantidad sugerida para volver al doble del mínimo.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function itemsIniciales(): array
    {
        if (! request()->boolean('bajo_minimo')) {
            return [];
        }

        return Producto::query()
            ->where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $p) => [
                'producto_id' => $p->id,
                'cantidad' => max((float) $p->stock_minimo * 2 - (float) $p->stock_actual, 1),
                'precio_unitario' => $p->precio_compra_promedio > 0 ? (float) $p->precio_compra_promedio : null,
            ])
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    Group::make([
                        Section::make()
                            ->heading(new HtmlString('Proveedor y factura'))
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 4])->schema([
                                    Select::make('proveedor_id')
                                        ->label('Proveedor')
                                        ->options(fn () => Proveedor::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->createOptionForm([
                                            TextInput::make('nombre')->label('Nombre')->required()->maxLength(150),
                                            TextInput::make('nit')->label('NIT'),
                                            TextInput::make('telefono')->label('Teléfono')->tel(),
                                        ])
                                        ->createOptionUsing(fn (array $data) => Proveedor::create([...$data, 'activo' => true])->id)
                                        ->columnSpan(['md' => 2]),
                                    TextInput::make('numero_factura')
                                        ->label('N.º de factura')
                                        ->maxLength(50)
                                        ->extraInputAttributes(['class' => 'dm-input-mono']),
                                    DatePicker::make('fecha')
                                        ->label('Fecha')
                                        ->default(today())
                                        ->maxDate(today())
                                        ->required(),
                                ]),
                            ]),

                        Section::make()
                            ->heading(new HtmlString('Ítems'))
                            ->description(fn () => request()->boolean('bajo_minimo')
                                ? 'Se precargaron los productos bajo mínimo. Ajusta cantidades y precios.'
                                : 'Agrega cada producto que llegó, con su cantidad y el precio al que lo compraste.')
                            ->schema([
                                Repeater::make('items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Producto'),
                                        TableColumn::make('Cantidad')->width('6rem'),
                                        TableColumn::make('Precio unit.')->width('8.5rem'),
                                        TableColumn::make('Subtotal')->width('7.5rem'),
                                    ])
                                    ->schema([
                                        Select::make('producto_id')
                                            ->label('Producto')
                                            ->options(fn () => Producto::where('activo', true)
                                                ->orderBy('nombre')
                                                ->get()
                                                ->mapWithKeys(fn (Producto $p) => [$p->id => $p->nombre.' · stock '.self::cantidad((float) $p->stock_actual)]))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $precio = Producto::find($state)?->precio_compra_promedio;
                                                if ($precio) {
                                                    $set('precio_unitario', (float) $precio);
                                                }
                                            }),
                                        TextInput::make('cantidad')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->step(0.01)
                                            ->default(1)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->extraInputAttributes(['class' => 'dm-input-mono']),
                                        TextInput::make('precio_unitario')
                                            ->label('Precio unit.')
                                            ->numeric()
                                            ->prefix('Bs')
                                            ->minValue(0)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->extraInputAttributes(['class' => 'dm-input-mono']),
                                        Placeholder::make('subtotal')
                                            ->label('Subtotal')
                                            ->content(fn (Get $get) => new HtmlString(
                                                '<span class="dm-mono dm-subtotal">'.self::bs((float) $get('cantidad') * (float) $get('precio_unitario')).'</span>'
                                            )),
                                    ])
                                    ->default(fn () => self::itemsIniciales() ?: [[]])
                                    ->addActionLabel('Agregar producto')
                                    ->live()
                                    ->minItems(1)
                                    ->required(),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    Group::make([
                        View::make('filament.compras.resumen')
                            ->viewData(fn (Get $get) => [
                                'items' => collect($get('items') ?? [])
                                    ->filter(fn ($i) => filled($i['producto_id'] ?? null))
                                    ->values()
                                    ->all(),
                                'proveedor' => filled($get('proveedor_id')) ? Proveedor::find($get('proveedor_id'))?->nombre : null,
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}
