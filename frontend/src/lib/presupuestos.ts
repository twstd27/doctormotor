import { api } from './api'

export interface PresupuestoItem {
  id: number
  descripcion: string
  tipo: string
  cantidad: string
  precio_unitario: string
  subtotal: string
  es_adicional: boolean
  aprobado: boolean | null
}

export interface Presupuesto {
  id: number
  version: number
  estado: 'borrador' | 'enviado' | 'aprobado' | 'rechazado'
  subtotal: string
  descuento: string
  total: string
  items: PresupuestoItem[]
  orden_trabajo: { id: number; codigo: string; vehiculo: { marca: string; modelo: string } }
}

export async function obtenerPresupuesto(id: number): Promise<Presupuesto> {
  const res = await api<{ data: Presupuesto }>(`/presupuestos/${id}`)
  return res.data
}

export async function responderPresupuesto(id: number, aprobado: boolean): Promise<Presupuesto> {
  const res = await api<{ data: Presupuesto }>(`/presupuestos/${id}/responder`, {
    method: 'POST',
    body: JSON.stringify({ aprobado }),
  })
  return res.data
}

export async function responderItem(presupuestoId: number, itemId: number, aprobado: boolean): Promise<void> {
  await api(`/presupuestos/${presupuestoId}/items/${itemId}/responder`, {
    method: 'POST',
    body: JSON.stringify({ aprobado }),
  })
}
