import { api } from './api'

export type TipoCosto = 'repuesto' | 'mano_obra' | 'tercerizado'

export interface NuevoCostoDirecto {
  tipo: TipoCosto
  producto_id?: number | null
  tecnico_id?: number | null
  descripcion: string
  cantidad: number
  costo_unitario: number
}

export async function crearCostoDirecto(otId: number, datos: NuevoCostoDirecto): Promise<void> {
  await api(`/ordenes-trabajo/${otId}/costos-directos`, {
    method: 'POST',
    body: JSON.stringify(datos),
  })
}
