import { api } from './api'

/** Atajo de «Agregar ítem»: un servicio que se repite (lavado, diagnóstico…) con su precio sugerido. */
export interface ServicioFrecuente {
  id: number
  nombre: string
  tipo: 'mano_obra' | 'tercerizado'
  descripcion: string
  precio_unitario: string
}

export async function listarServiciosFrecuentes(): Promise<ServicioFrecuente[]> {
  const res = await api<{ data: ServicioFrecuente[] }>('/servicios-frecuentes')
  return res.data
}
