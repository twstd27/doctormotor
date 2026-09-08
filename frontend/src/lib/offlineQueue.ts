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
        const cuerpo = await res.text().catch(() => '')
        await incrementarIntentos(item.uuid)
        await marcarEstado(item.uuid, 'error', `HTTP ${res.status}${cuerpo ? ` — ${cuerpo.slice(0, 200)}` : ''}`)
        onProgreso?.(item.uuid, 'error')
      }
    } catch (e) {
      // Sin conexión real — se queda en la cola tal cual, se reintenta solo.
      // Si ya lleva varios intentos fallidos seguidos, se marca 'error' para que
      // deje de verse como "recién puesto en cola" y se muestre el motivo.
      await incrementarIntentos(item.uuid)
      if (item.intentos + 1 >= 3) {
        const motivo = e instanceof Error ? e.message : String(e)
        await marcarEstado(item.uuid, 'error', `Sin conexión al servidor — ${motivo}`)
        onProgreso?.(item.uuid, 'error')
      }
    }
  }
}

export { db as offlineDb }
