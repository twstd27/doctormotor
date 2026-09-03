import Dexie, { type EntityTable } from 'dexie'

export interface EvidenciaPendiente {
  uuid: string
  otId: number
  archivo: Blob
  nombreArchivo: string
  tipoArchivo: string
  etiqueta: string
  tomadaAt: string
  estado: 'pendiente' | 'sincronizado' | 'error'
  intentos: number
  error?: string
}

const db = new Dexie('doctor-motor-offline') as Dexie & {
  evidencias: EntityTable<EvidenciaPendiente, 'uuid'>
}

db.version(1).stores({
  evidencias: 'uuid, otId, estado',
})

export async function encolarEvidencia(otId: number, archivo: File, etiqueta: string): Promise<EvidenciaPendiente> {
  const item: EvidenciaPendiente = {
    uuid: crypto.randomUUID(),
    otId,
    archivo,
    nombreArchivo: archivo.name,
    tipoArchivo: archivo.type,
    etiqueta,
    tomadaAt: new Date().toISOString(),
    estado: 'pendiente',
    intentos: 0,
  }
  await db.evidencias.add(item)
  return item
}

export function evidenciasDeOt(otId: number) {
  return db.evidencias.where('otId').equals(otId).toArray()
}

export async function marcarEstado(uuid: string, estado: EvidenciaPendiente['estado'], error?: string) {
  await db.evidencias.update(uuid, { estado, error })
}

export async function incrementarIntentos(uuid: string) {
  const item = await db.evidencias.get(uuid)
  if (item) await db.evidencias.update(uuid, { intentos: item.intentos + 1 })
}

/**
 * Intenta subir todas las evidencias pendientes (o con error) de la cola. Se llama al
 * reconectar y de forma periódica mientras la pestaña sigue abierta — no depende de la
 * Background Sync API (soporte parejo entre navegadores, no solo Chromium).
 */
export async function sincronizarCola(
  token: string,
  apiUrl: string,
  onProgreso?: (uuid: string, estado: EvidenciaPendiente['estado']) => void,
) {
  const pendientes = await db.evidencias.where('estado').anyOf('pendiente', 'error').toArray()

  for (const item of pendientes) {
    try {
      const form = new FormData()
      form.append('archivo', item.archivo, item.nombreArchivo)
      form.append('uuid_cliente', item.uuid)
      form.append('tipo', item.tipoArchivo.startsWith('video') ? 'video' : 'foto')
      form.append('tomada_at', item.tomadaAt)
      if (item.etiqueta) form.append('etiqueta', item.etiqueta)

      const res = await fetch(`${apiUrl}/ordenes-trabajo/${item.otId}/evidencias`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
        body: form,
      })

      if (res.ok) {
        await marcarEstado(item.uuid, 'sincronizado')
        onProgreso?.(item.uuid, 'sincronizado')
      } else {
        await incrementarIntentos(item.uuid)
        await marcarEstado(item.uuid, 'error', `HTTP ${res.status}`)
        onProgreso?.(item.uuid, 'error')
      }
    } catch {
      // Sin conexión — se queda en la cola, se reintenta en el próximo ciclo.
      await incrementarIntentos(item.uuid)
    }
  }
}

export { db as offlineDb }
