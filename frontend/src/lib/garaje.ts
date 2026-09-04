import { api } from './api'
import { ESTADOS, type Estado } from './ordenesTrabajo'

export interface Vehiculo {
  id: number
  placa: string
  marca: string
  modelo: string
  anio: number
  kilometraje_actual: number
}

export interface OrdenTrabajoCliente {
  id: number
  codigo: string
  estado: Estado
  descripcion_problema: string
  fecha_ingreso: string
  vehiculo: { id: number; placa: string; marca: string; modelo: string }
  tecnico_asignado: { id: number; nombre: string } | null
  presupuestos: { id: number; estado: string; total: string }[]
}

export interface EstadoHistorial {
  id: number
  estado_nuevo: string
  created_at: string
}

export async function misVehiculos(): Promise<Vehiculo[]> {
  const res = await api<{ data: Vehiculo[] }>('/me/vehiculos')
  return res.data
}

export async function descargarHistorialClinico(vehiculoId: number, token: string, apiUrl: string): Promise<void> {
  const res = await fetch(`${apiUrl}/vehiculos/${vehiculoId}/historial/pdf`, {
    headers: { Authorization: `Bearer ${token}` },
  })
  if (!res.ok) return
  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 60000)
}

export async function misOrdenesTrabajo(): Promise<OrdenTrabajoCliente[]> {
  const res = await api<{ data: OrdenTrabajoCliente[] }>('/me/ordenes-trabajo')
  return res.data
}

export async function historialEstados(otId: number): Promise<EstadoHistorial[]> {
  const res = await api<{ data: EstadoHistorial[] }>(`/ordenes-trabajo/${otId}/historial-estados`)
  return res.data
}

export function pasoCompletado(estadoOrden: Estado, estadoPaso: Estado): boolean {
  const iOrden = ESTADOS.findIndex((e) => e.value === estadoOrden)
  const iPaso = ESTADOS.findIndex((e) => e.value === estadoPaso)
  return iPaso <= iOrden
}

export { ESTADOS }
