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

export interface NuevoPresupuestoItem {
  tipo: 'repuesto' | 'mano_obra' | 'tercerizado'
  producto_id?: number | null
  descripcion: string
  cantidad: number
  precio_unitario: number
}

export async function listarPresupuestosDeOt(otId: number): Promise<Presupuesto[]> {
  const res = await api<{ data: Presupuesto[] }>(`/ordenes-trabajo/${otId}/presupuestos`)
  return res.data
}

export async function crearPresupuesto(otId: number, items: NuevoPresupuestoItem[], descuento = 0): Promise<Presupuesto> {
  const res = await api<{ data: Presupuesto }>(`/ordenes-trabajo/${otId}/presupuestos`, {
    method: 'POST',
    body: JSON.stringify({ items, descuento }),
  })
  return res.data
}

export async function enviarPresupuesto(id: number): Promise<Presupuesto> {
  const res = await api<{ data: Presupuesto }>(`/presupuestos/${id}/enviar`, { method: 'POST' })
  return res.data
}

export async function agregarAdicional(otId: number, item: NuevoPresupuestoItem): Promise<void> {
  await api(`/ordenes-trabajo/${otId}/adicionales`, {
    method: 'POST',
    body: JSON.stringify(item),
  })
}
