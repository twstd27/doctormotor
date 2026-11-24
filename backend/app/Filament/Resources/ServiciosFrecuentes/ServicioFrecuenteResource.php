<?php

namespace App\Filament\Resources\ServiciosFrecuentes;

use App\Filament\Concerns\TituloEnOracion;
use App\Models\ServicioFrecuente;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Servicios que se repiten (lavado, diagnóstico…). En la app del técnico aparecen como botones
 * en «Agregar ítem»: un toque agrega el servicio con su precio, sin armar mano de obra a mano.
 * El cajero puede dar de alta uno nuevo; cambiarlo o borrarlo es del super_admin (cambia precios).
 */
class ServicioFrecuenteResource extends Resource
{
    use TituloEnOracion;

    protected static ?string $model = ServicioFrecuente::class;

    protected static ?string $slug = 'servicios-frecuentes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'servicio frecuente';

    protected static ?string $pluralModelLabel = 'servicios frecuentes';

    protected static ?string $navigationLabel = 'Servicios frecuentes';

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->heading(new HtmlString('<span class="dm-num">1</span>Servicio'))
                ->description('Aparece como botón al agregar un ítem a un presupuesto.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('nombre')
                            ->label('Nombre del botón')
                            ->placeholder('Ej. Lavado')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('descripcion')
                            ->label('Descripción en el presupuesto')
                            ->placeholder('Ej. Lavado completo exterior e interior')
                            ->required()
                            ->maxLength(150)
                            ->helperText('Es lo que ve el cliente en el presupuesto.'),
                    ]),
                    ToggleButtons::make('tipo')
                        ->label('Tipo')
                        ->options(['mano_obra' => 'Mano de obra', 'tercerizado' => 'Trabajo tercerizado'])
                        ->default('mano_obra')
                        ->inline()
                        ->required(),
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('precio_unitario')
                            ->label('Precio sugerido')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('Bs')
                            ->extraInputAttributes(['class' => 'dm-input-mono'])
                            ->helperText('El técnico puede ajustarlo antes de enviar el presupuesto.'),
                        TextInput::make('orden')
                            ->label('Posición')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Los números menores salen primero.'),
                    ]),
                    Toggle::make('activo')->label('Mostrar en la app')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Servicio')->searchable()->sortable(),
                TextColumn::make('descripcion')->label('En el presupuesto')->limit(48)->toggleable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state) => $state === 'mano_obra' ? 'Mano de obra' : 'Tercerizado'),
                TextColumn::make('precio_unitario')
                    ->label('Precio')
                    ->money('BOB')
                    ->extraAttributes(['class' => 'dm-mono'])
                    ->sortable(),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->defaultSort('orden')
            ->recordUrl(fn (ServicioFrecuente $record) => static::canEdit($record) ? static::getUrl('edit', ['record' => $record]) : null)
            ->recordActions([
                EditAction::make()->label('Editar')->hiddenLabel()->tooltip('Editar')
                    ->visible(fn (ServicioFrecuente $record) => static::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Todavía no hay servicios frecuentes')
            ->emptyStateDescription('Crea «Lavado» o «Diagnóstico» para agregarlos a un presupuesto con un toque.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListarServiciosFrecuentes::route('/'),
            'create' => Pages\CrearServicioFrecuente::route('/create'),
            'edit' => Pages\EditarServicioFrecuente::route('/{record}/edit'),
        ];
    }
}
