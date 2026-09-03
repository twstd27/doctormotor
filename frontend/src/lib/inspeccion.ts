import { api } from './api'

export interface Inspeccion {
  id: number
  orden_trabajo_id: number
  accesorios: string[] | null
  observaciones: string | null
  firma_cliente_url: string | null
  firmado_at: string | null
}

export async function crearInspeccion(
  otId: number,
  data: { accesorios: string[]; observaciones: string },
): Promise<Inspeccion> {
  const res = await api<{ data: Inspeccion }>(`/ordenes-trabajo/${otId}/inspeccion`, {
    method: 'POST',
    body: JSON.stringify(data),
  })
  return res.data
}

export async function firmarInspeccion(otId: number, firmaBase64: string): Promise<Inspeccion> {
  const res = await api<{ data: Inspeccion }>(`/ordenes-trabajo/${otId}/inspeccion/firma`, {
    method: 'POST',
    body: JSON.stringify({ firma_base64: firmaBase64 }),
  })
  return res.data
}
