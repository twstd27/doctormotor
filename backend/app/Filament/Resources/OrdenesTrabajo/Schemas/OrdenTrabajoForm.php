<?php

namespace App\Filament\Resources\OrdenesTrabajo\Schemas;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Support\EstadoOrden;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Formulario de la orden de trabajo. En "crear" es la recepción del vehículo (se empieza por
 * la placa); en "editar" muestra el vehículo como lectura y deja corregir el resto
 * (HANDOFF §4, Recepción de vehículo).
 */
class OrdenTrabajoForm
{
    /**
     * Una OT entregada o cancelada queda de solo lectura en el panel — el historial no
     * debe alterarse una vez cerrado el trabajo (ver App\Models\OrdenTrabajo::estaCerrada).
     */
    private static function cerrada(?OrdenTrabajo $record): bool
    {
        return $record?->estaCerrada() ?? false;
    }

    /** "2847 kdr" y "2847-KDR" son la misma placa. */
    private static function normalizarPlaca(?string $placa): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $placa));
    }

    private static function buscarVehiculo(?string $placa): ?Vehiculo
    {
        $normalizada = self::normalizarPlaca($placa);

        if (strlen($normalizada) < 3) {
            return null;
        }

        return Vehiculo::query()
            ->whereRaw("UPPER(REPLACE(REPLACE(placa, '-', ''), ' ', '')) = ?", [$normalizada])
            ->first();
    }

    /** @return array<int, string> técnicos activos, del más libre al más cargado */
    private static function tecnicosPorCarga(): array
    {
        $cargas = OrdenTrabajo::query()
            ->whereNotIn('estado', EstadoOrden::CERRADOS)
            ->whereNotNull('tecnico_asignado_id')
            ->selectRaw('tecnico_asignado_id, COUNT(*) as activas')
            ->groupBy('tecnico_asignado_id')
            ->pluck('activas', 'tecnico_asignado_id');

        return User::query()
            ->where('rol', 'operador_tecnico')
            ->where('activo', true)
            ->get(['id', 'nombre'])
            ->sortBy([
                fn (User $a, User $b) => ($cargas[$a->id] ?? 0) <=> ($cargas[$b->id] ?? 0),
                fn (User $a, User $b) => $a->nombre <=> $b->nombre,
            ])
            ->mapWithKeys(function (User $tecnico) use ($cargas) {
                $activas = (int) ($cargas[$tecnico->id] ?? 0);

                return [$tecnico->id => $tecnico->nombre.' · '.($activas === 1 ? '1 activa' : "{$activas} activas")];
            })
            ->all();
    }

    private static function titulo(int $numero, string $texto): HtmlString
    {
        return new HtmlString('<span class="dm-num">'.$numero.'</span>'.e($texto));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('cliente_id'),
                Hidden::make('vehiculo_id'),

                // ── 1 · Vehículo ───────────────────────────────────────────────
                Section::make()
                    ->heading(self::titulo(1, 'Vehículo'))
                    ->description(fn (string $operation) => $operation === 'create'
                        ? 'Busca por placa. Si no existe, lo registras aquí mismo.'
                        : 'El vehículo y el cliente de una orden no se cambian.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])
                            ->extraAttributes(['class' => 'dm-fila-placa'])
                            ->visibleOn('create')
                            ->schema([
                                TextInput::make('placa_busqueda')
                                    ->label('Placa')
                                    ->required()
                                    ->maxLength(15)
                                    ->autofocus()
                                    ->placeholder('Ej. 2847-KDR')
                                    ->extraInputAttributes(['class' => 'dm-input-placa', 'autocomplete' => 'off'])
                                    ->dehydrated(false)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (?string $state, Set $set) {
                                        $vehiculo = self::buscarVehiculo($state);

                                        $set('vehiculo_id', $vehiculo?->id);
                                        $set('cliente_id', $vehiculo?->cliente_id);

                                        if ($vehiculo) {
                                            $set('kilometraje_ingreso', $vehiculo->kilometraje_actual);
                                        }
                                    })
                                    ->rules([
                                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            if (blank($get('vehiculo_id'))) {
                                                $fail('Esa placa no está registrada. Usa «Vehículo nuevo» para crearla.');
                                            }
                                        },
                                    ])
                                    ->columnSpan(['md' => 2]),
                                Actions::make([self::accionVehiculoNuevo()])
                                    ->extraAttributes(['class' => 'dm-accion-placa']),
                            ]),

                        View::make('filament.ordenes.recepcion-vehiculo')
                            ->viewData(fn (Get $get, ?OrdenTrabajo $record) => [
                                'vehiculo' => ($id = $get('vehiculo_id') ?: $record?->vehiculo_id)
                                    ? Vehiculo::with('cliente')->withCount('ordenesTrabajo')->find($id)
                                    : null,
                                'buscada' => $get('placa_busqueda'),
                            ]),
                    ]),

                // ── 2 · Estado al ingreso ─────────────────────────────────────
                Section::make()
                    ->heading(self::titulo(2, 'Estado al ingreso'))
                    ->description('Lo que se registra aquí respalda al taller ante el cliente.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('kilometraje_ingreso')
                                ->label('Kilometraje')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->suffix('km')
                                ->extraInputAttributes(['class' => 'dm-input-mono'])
                                ->helperText(function (Get $get, ?OrdenTrabajo $record) {
                                    $vehiculo = Vehiculo::find($get('vehiculo_id') ?: $record?->vehiculo_id);
                                    $ultimo = $vehiculo?->ordenesTrabajo()->max('kilometraje_ingreso');
                                    $ultimo = max((int) $ultimo, (int) $vehiculo?->kilometraje_actual);

                                    if (! $vehiculo || $ultimo <= 0) {
                                        return null;
                                    }

                                    $actual = (int) $get('kilometraje_ingreso');
                                    $diferencia = $actual - $ultimo;
                                    $texto = 'Último registrado: '.number_format($ultimo, 0, ',', '.').' km';

                                    if ($actual > 0) {
                                        $texto .= $diferencia >= 0
                                            ? ' · +'.number_format($diferencia, 0, ',', '.').' km'
                                            : ' · '.number_format($diferencia, 0, ',', '.').' km (menos que la última vez, revísalo)';
                                    }

                                    return $texto;
                                })
                                ->live(onBlur: true)
                                ->disabled(fn (?OrdenTrabajo $record) => self::cerrada($record)),
                            ToggleButtons::make('nivel_gasolina')
                                ->label('Nivel de combustible')
                                ->options(['E' => 'Vacío', '1/4' => '1/4', '1/2' => '1/2', '3/4' => '3/4', 'F' => 'Lleno'])
                                ->inline()
                                ->grouped()
                                ->required()
                                ->disabled(fn (?OrdenTrabajo $record) => self::cerrada($record)),
                        ]),
                    ]),

                // ── 3 · Motivo de ingreso ─────────────────────────────────────
                Section::make()
                    ->heading(self::titulo(3, 'Motivo de ingreso'))
                    ->description('Anota lo que cuenta el cliente, con sus palabras.')
                    ->schema([
                        Textarea::make('descripcion_problema')
                            ->label('Lo que reporta el cliente')
                            ->required()
                            ->rows(4)
                            ->disabled(fn (?OrdenTrabajo $record) => self::cerrada($record)),
                    ]),

                // ── 4 · Asignación ────────────────────────────────────────────
                Section::make()
                    ->heading(self::titulo(4, 'Asignación'))
                    ->description('Opcional: se puede asignar después desde la ficha de la orden.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            Select::make('tecnico_asignado_id')
                                ->label('Técnico')
                                ->options(fn () => self::tecnicosPorCarga())
                                ->searchable()
                                ->placeholder('Sin asignar')
                                ->helperText('Ordenado por carga de trabajo: el primero es el más libre.')
                                ->disabled(fn (?OrdenTrabajo $record) => self::cerrada($record)),
                            Grid::make(1)->gap(false)->schema([
                                DatePicker::make('fecha_entrega_estimada')
                                    ->label('Entrega estimada')
                                    ->minDate(fn (string $operation) => $operation === 'create' ? today() : null)
                                    ->disabled(fn (?OrdenTrabajo $record) => self::cerrada($record)),
                                Actions::make([
                                    Action::make('entregaHoy')
                                        ->label('Hoy')
                                        ->color('gray')
                                        ->size('sm')
                                        ->action(fn (Set $set) => $set('fecha_entrega_estimada', today()->toDateString())),
                                    Action::make('entregaManana')
                                        ->label('Mañana')
                                        ->color('gray')
                                        ->size('sm')
                                        ->action(fn (Set $set) => $set('fecha_entrega_estimada', today()->addDay()->toDateString())),
                                    Action::make('entregaTresDias')
                                        ->label('En 3 días')
                                        ->color('gray')
                                        ->size('sm')
                                        ->action(fn (Set $set) => $set('fecha_entrega_estimada', today()->addDays(3)->toDateString())),
                                ])->visible(fn (?OrdenTrabajo $record) => ! self::cerrada($record)),
                            ]),
                        ]),
                    ]),
            ]);
    }

    /** Alta de un vehículo (y de su dueño, si hace falta) sin salir de la recepción. */
    private static function accionVehiculoNuevo(): Action
    {
        return Action::make('vehiculoNuevo')
            ->label('Vehículo nuevo')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->modalHeading('Registrar vehículo nuevo')
            ->modalDescription('Si el dueño ya es cliente, elígelo; si no, completa sus datos.')
            ->modalSubmitActionLabel('Registrar vehículo')
            ->slideOver()
            ->fillForm(fn (Get $get) => ['placa' => strtoupper(trim((string) $get('placa_busqueda')))])
            ->schema([
                Section::make('Vehículo')->schema([
                    TextInput::make('placa')->label('Placa')->required()->maxLength(15)
                        ->extraInputAttributes(['class' => 'dm-input-mono']),
                    Grid::make(2)->schema([
                        TextInput::make('marca')->label('Marca')->required()->maxLength(50),
                        TextInput::make('modelo')->label('Modelo')->required()->maxLength(50),
                        TextInput::make('anio')->label('Año')->required()->numeric()->minValue(1950)->maxValue(2100),
                        TextInput::make('color')->label('Color')->required()->maxLength(30),
                        TextInput::make('motor')->label('Motor')->maxLength(50),
                        TextInput::make('kilometraje_actual')->label('Kilometraje actual')->required()->numeric()->minValue(0)->suffix('km'),
                    ]),
                ]),
                Section::make('Dueño')->schema([
                    Select::make('cliente_existente')
                        ->label('Cliente existente')
                        ->searchable()
                        ->live()
                        ->getSearchResultsUsing(fn (string $search) => Cliente::query()
                            ->where(fn ($q) => $q
                                ->where('nombre', 'ilike', "%{$search}%")
                                ->orWhere('ci_nit', 'ilike', "%{$search}%")
                                ->orWhere('telefono_whatsapp', 'ilike', "%{$search}%"))
                            ->limit(20)
                            ->pluck('nombre', 'id'))
                        ->getOptionLabelUsing(fn ($value) => Cliente::find($value)?->nombre)
                        ->placeholder('Buscar por nombre, CI o WhatsApp')
                        ->helperText('Déjalo vacío para registrar un cliente nuevo.'),
                    Fieldset::make('Cliente nuevo')
                        ->visible(fn (Get $get) => blank($get('cliente_existente')))
                        ->schema([
                            TextInput::make('nombre')->label('Nombre completo')
                                ->required(fn (Get $get) => blank($get('cliente_existente')))->maxLength(150),
                            TextInput::make('ci_nit')->label('CI / NIT')
                                ->required(fn (Get $get) => blank($get('cliente_existente')))->maxLength(20),
                            TextInput::make('telefono_whatsapp')->label('WhatsApp')->tel()
                                ->required(fn (Get $get) => blank($get('cliente_existente')))->maxLength(20),
                            TextInput::make('correo')->label('Correo')->email()->maxLength(150),
                        ]),
                ]),
            ])
            ->action(function (array $data, Set $set) {
                $placa = strtoupper(trim($data['placa']));

                if (self::buscarVehiculo($placa)) {
                    Notification::make()->title('Esa placa ya está registrada')->danger()->send();

                    return;
                }

                $vehiculo = DB::transaction(function () use ($data, $placa) {
                    $clienteId = $data['cliente_existente'] ?? null;

                    if (blank($clienteId)) {
                        $clienteId = Cliente::create([
                            'nombre' => $data['nombre'],
                            'ci_nit' => $data['ci_nit'],
                            'telefono_whatsapp' => $data['telefono_whatsapp'],
                            'correo' => $data['correo'] ?? null,
                        ])->id;
                    }

                    return Vehiculo::create([
                        'cliente_id' => $clienteId,
                        'placa' => $placa,
                        'marca' => $data['marca'],
                        'modelo' => $data['modelo'],
                        'anio' => $data['anio'],
                        'color' => $data['color'],
                        'motor' => $data['motor'] ?? null,
                        'kilometraje_actual' => $data['kilometraje_actual'],
                    ]);
                });

                $set('placa_busqueda', $vehiculo->placa);
                $set('vehiculo_id', $vehiculo->id);
                $set('cliente_id', $vehiculo->cliente_id);
                $set('kilometraje_ingreso', $vehiculo->kilometraje_actual);

                Notification::make()->title('Vehículo registrado')->success()->send();
            });
    }
}
