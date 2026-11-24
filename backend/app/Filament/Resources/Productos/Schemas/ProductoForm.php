<?php

namespace App\Filament\Resources\Productos\Schemas;

use App\Models\Producto;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Producto en tres secciones: cómo se identifica, cómo se vende y cuánto hay (HANDOFF §4,
 * Producto-form). A la derecha, cómo lo verá el técnico al cargarlo en una orden.
 */
class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    Group::make([
                        Section::make()
                            ->heading(new HtmlString('<span class="dm-num">1</span>Identificación'))
                            ->schema([
                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->autofocus()
                                    ->maxLength(150)
                                    ->live(debounce: 400)
                                    ->helperText('Como lo buscará el técnico: tipo de repuesto + posición + marca.'),
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->required()
                                        ->maxLength(50)
                                        ->live(debounce: 400)
                                        ->extraInputAttributes(['class' => 'dm-input-mono']),
                                    TextInput::make('categoria')
                                        ->label('Categoría')
                                        ->placeholder('Ej. Aceites, Filtros, Frenos')
                                        ->datalist(fn () => Producto::query()
                                            ->whereNotNull('categoria')
                                            ->orderBy('categoria')
                                            ->distinct()
                                            ->pluck('categoria')
                                            ->all())
                                        ->live(debounce: 400),
                                ]),
                            ]),

                        Section::make()
                            ->heading(new HtmlString('<span class="dm-num dm-num--gris">2</span>Venta'))
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('precio_venta')
                                        ->label('Precio de venta')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->placeholder('0,00')
                                        ->prefix('Bs')
                                        ->live(debounce: 400)
                                        ->extraInputAttributes(['class' => 'dm-input-mono']),
                                    TextInput::make('unidad_medida')
                                        ->label('Se vende por')
                                        ->required()
                                        ->placeholder('Ej. unidad')
                                        ->datalist(['unidad', 'litro', 'juego', 'par', 'kilo', 'metro'])
                                        ->live(debounce: 400),
                                ]),
                            ]),

                        Section::make()
                            ->heading(new HtmlString('<span class="dm-num dm-num--gris">3</span>Stock'))
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('stock_minimo')
                                        ->label('Stock mínimo')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->extraInputAttributes(['class' => 'dm-input-mono'])
                                        ->helperText('Cuando el stock llega a este número, el producto aparece como "Bajo mínimo".'),
                                    // El stock cambia solo con compras, salidas a órdenes y ajustes (kardex).
                                    View::make('filament.productos.stock-actual')
                                        ->viewData(fn (?Producto $record) => ['producto' => $record]),
                                ]),
                                Toggle::make('activo')
                                    ->label('Activo — disponible para cargar en órdenes y compras')
                                    ->default(true),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    Group::make([
                        View::make('filament.productos.vista-previa')
                            ->viewData(fn (Get $get) => [
                                'nombre' => $get('nombre'),
                                'sku' => $get('sku'),
                                'categoria' => $get('categoria'),
                                'precio' => (float) ($get('precio_venta') ?: 0),
                                'unidad' => $get('unidad_medida') ?: 'unidad',
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}
