<?php

namespace App\Support;

/**
 * Sugerencias para el alta de vehículos (marca, modelo y color). Son solo ayudas: el campo
 * deja escribir cualquier otro valor.
 */
class CatalogoVehiculos
{
    /** @var array<string, list<string>> */
    public const MARCAS = [
        'Toyota' => ['Hilux', 'Corolla', 'Yaris', 'RAV4', 'Land Cruiser', 'Prado', 'Fortuner', 'Hiace', 'Vitz', 'Probox', 'Rush'],
        'Suzuki' => ['Swift', 'Vitara', 'Jimny', 'Alto', 'Baleno', 'Grand Vitara', 'Ertiga', 'Celerio', 'S-Presso'],
        'Nissan' => ['Frontier', 'Sentra', 'Versa', 'X-Trail', 'Kicks', 'March', 'Navara', 'Patrol'],
        'Kia' => ['Rio', 'Sportage', 'Picanto', 'Seltos', 'Sorento', 'Cerato', 'Soul'],
        'Hyundai' => ['Accent', 'Tucson', 'Santa Fe', 'Creta', 'i10', 'Elantra', 'H1', 'Grand i10'],
        'Chevrolet' => ['Spark', 'Sail', 'Aveo', 'Onix', 'Tracker', 'S10', 'Captiva', 'Cruze'],
        'Mitsubishi' => ['L200', 'Montero', 'Outlander', 'ASX', 'Lancer'],
        'Honda' => ['Civic', 'CR-V', 'Fit', 'City', 'HR-V', 'Accord'],
        'Mazda' => ['Mazda 3', 'Mazda 2', 'CX-5', 'BT-50', 'CX-3'],
        'Ford' => ['Ranger', 'Fiesta', 'EcoSport', 'Explorer', 'Escape', 'F-150'],
        'Volkswagen' => ['Gol', 'Golf', 'Amarok', 'Polo', 'Tiguan', 'Saveiro'],
        'Subaru' => ['Impreza', 'Forester', 'XV', 'Outback'],
        'Jeep' => ['Wrangler', 'Cherokee', 'Compass', 'Renegade'],
        'Renault' => ['Duster', 'Logan', 'Sandero', 'Kwid', 'Clio'],
        'Peugeot' => ['206', '208', '301', '2008', '3008'],
        'Lifan' => ['X60', '620', '530'],
        'Great Wall' => ['Wingle', 'Haval H6'],
        'JAC' => ['T6', 'S2', 'J4'],
    ];

    /** @var array<string, string> nombre => color hexadecimal del círculo */
    public const COLORES = [
        'Blanco' => '#F2F2EE',
        'Plata' => '#B8BEC6',
        'Gris' => '#6B7179',
        'Negro' => '#16181B',
        'Rojo' => '#C8322E',
        'Azul' => '#2D5BD0',
        'Verde' => '#2F7A4D',
        'Beige' => '#CDB98F',
    ];

    /** @return list<string> */
    public static function marcas(): array
    {
        return array_keys(self::MARCAS);
    }

    /** @return list<string> modelos sugeridos para la marca escrita (sin distinguir mayúsculas) */
    public static function modelos(?string $marca): array
    {
        $marca = mb_strtolower(trim((string) $marca));

        foreach (self::MARCAS as $nombre => $modelos) {
            if (mb_strtolower($nombre) === $marca) {
                return $modelos;
            }
        }

        return [];
    }
}
