import { api } from './api'

export type Estado =
  | 'recepcionado'
  | 'en_diagnostico'
  | 'esperando_aprobacion'
  | 'en_reparacion'
  | 'control_calidad'
  | 'listo_entrega'
  | 'entregado'
  | 'cancelado'

export const ESTADOS: { value: Estado; label: string }[] = [
  { value: 'recepcionado', label: 'Recepcionado' },
  { value: 'en_diagnostico', label: 'En diagnóstico' },
  { value: 'esperando_aprobacion', label: 'Esperando aprobación' },
  { value: 'en_reparacion', label: 'En reparación' },
  { value: 'control_calidad', label: 'Control de calidad' },
  { value: 'listo_entrega', label: 'Listo para entrega' },
  { value: 'entregado', label: 'Entregado' },
]

export interface OrdenTrabajo {
  id: number
  codigo: string
  estado: Estado
  descripcion_problema: string
  fecha_ingreso: string
  kilometraje_ingreso: number
  vehiculo: { id: number; placa: string; marca: string; modelo: string }
  cliente: { id: number; nombre: string }
  tecnico_asignado: { id: number; nombre: string } | null
}

export type Tono = 'neutro' | 'cya' | 'amb' | 'lima'

export const TONO_ESTADO: Record<Estado, Tono> = {
  recepcionado: 'neutro',
  en_diagnostico: 'cya',
  esperando_aprobacion: 'amb',
  en_reparacion: 'lima',
  control_calidad: 'cya',
  listo_entrega: 'lima',
  entregado: 'neutro',
  cancelado: 'neutro',
}

export const TONO_CLASES: Record<Tono, { dot: string; bg: string; fg: string; border: string }> = {
  neutro: { dot: 'bg-app-faint', bg: 'bg-app-surface-3', fg: 'text-app-muted', border: 'border-app-line' },
  cya: { dot: 'bg-cya', bg: 'bg-cya-bg', fg: 'text-cya-txt', border: 'border-cya' },
  amb: { dot: 'bg-amb', bg: 'bg-amb-bg', fg: 'text-amb-txt', border: 'border-amb' },
  lima: { dot: 'bg-lime-500', fg: 'text-lime-txt', bg: 'bg-lime-500/15', border: 'border-lime-500' },
}

export function antiguedad(fechaIso: string): string {
  const ms = Date.now() - new Date(fechaIso).getTime()
  const horas = Math.floor(ms / 3_600_000)
  if (horas < 1) return 'recién'
  if (horas < 24) return `${horas}h`
  return `${Math.floor(horas / 24)}d`
}

interface PaginatedResponse<T> {
  data: T[]
}

export async function listarOrdenesTrabajo(): Promise<OrdenTrabajo[]> {
  const res = await api<PaginatedResponse<OrdenTrabajo>>('/ordenes-trabajo?estado=&per_page=100')
  return res.data
}

export async function cambiarEstado(id: number, estado: Estado): Promise<OrdenTrabajo> {
  const res = await api<{ data: OrdenTrabajo }>(`/ordenes-trabajo/${id}/estado`, {
    method: 'PATCH',
    body: JSON.stringify({ estado }),
  })
  return res.data
}

export function siguienteEstado(estado: Estado): Estado | null {
  const idx = ESTADOS.findIndex((e) => e.value === estado)
  if (idx === -1 || idx === ESTADOS.length - 1) return null
  return ESTADOS[idx + 1].value
}
