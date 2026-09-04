import { ArrowsClockwise, Camera, CloudArrowUp, CloudSlash, Image, Video } from '@phosphor-icons/react'
import { useCallback, useEffect, useRef, useState } from 'react'
import { useParams } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { encolarEvidencia, evidenciasDeOt, sincronizarCola, type EvidenciaPendiente } from '../lib/offlineQueue'
import { evidenciasDeOtServidor, type EvidenciaServidor } from '../lib/ordenesTrabajo'
import { useAuthStore } from '../store/auth'

const API_URL = import.meta.env.VITE_API_URL as string

interface ItemVista {
  key: string
  nombre: string
  tipo: string
  tomadaAt: string
  tamano?: number
  subidoPor?: string
  chip: { label: string; className: string }
}

function tamano(bytes: number) {
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function hace(iso: string) {
  const min = Math.floor((Date.now() - new Date(iso).getTime()) / 60000)
  if (min < 1) return 'recién'
  if (min < 60) return `hace ${min} min`
  return `hace ${Math.floor(min / 60)}h`
}

function chipLocal(item: EvidenciaPendiente, sincronizando: boolean) {
  if (item.estado === 'pendiente' && sincronizando) return { label: 'Subiendo', className: 'bg-cya-bg text-cya-txt' }
  if (item.estado === 'error') return { label: 'Reintentando', className: 'bg-amb-bg text-amb-txt' }
  return { label: 'Pendiente', className: 'bg-amb-bg text-amb-txt' }
}

export default function EvidenciasPage() {
  const { id } = useParams<{ id: string }>()
  const otId = Number(id)
  const token = useAuthStore((s) => s.token)
  const inputRef = useRef<HTMLInputElement>(null)

  const [items, setItems] = useState<EvidenciaPendiente[]>([])
  const [servidor, setServidor] = useState<EvidenciaServidor[]>([])
  const [enLinea, setEnLinea] = useState(navigator.onLine)
  const [sincronizando, setSincronizando] = useState(false)

  const refrescar = useCallback(async () => {
    setItems(await evidenciasDeOt(otId))
    if (navigator.onLine) {
      try {
        setServidor(await evidenciasDeOtServidor(otId))
      } catch {
        // sin conexión o error de red — se conserva la última lista del servidor conocida
      }
    }
  }, [otId])

  const sincronizar = useCallback(async () => {
    if (!token || !navigator.onLine) return
    setSincronizando(true)
    await sincronizarCola(token, API_URL, () => refrescar())
    await refrescar()
    setSincronizando(false)
  }, [token, refrescar])

  useEffect(() => {
    refrescar()
  }, [refrescar])

  useEffect(() => {
    function onOnline() {
      setEnLinea(true)
      sincronizar()
    }
    function onOffline() {
      setEnLinea(false)
    }
    window.addEventListener('online', onOnline)
    window.addEventListener('offline', onOffline)

    const intervalo = setInterval(sincronizar, 30000)
    sincronizar()

    return () => {
      window.removeEventListener('online', onOnline)
      window.removeEventListener('offline', onOffline)
      clearInterval(intervalo)
    }
  }, [sincronizar])

  async function agregarArchivos(files: FileList | null) {
    if (!files) return
    for (const archivo of Array.from(files)) {
      await encolarEvidencia(otId, archivo, '')
    }
    await refrescar()
    sincronizar()
  }

  const localesPendientes = items.filter((i) => i.estado !== 'sincronizado')

  const itemsServidor: ItemVista[] = servidor.map((e) => ({
    key: e.uuid_cliente,
    nombre: e.etiqueta || e.url.split('/').pop() || 'archivo',
    tipo: e.tipo,
    tomadaAt: e.tomada_at,
    subidoPor: e.subido_por?.nombre,
    chip: { label: 'Sincronizado', className: 'bg-lime-500/15 text-lime-txt' },
  }))

  const itemsLocales: ItemVista[] = localesPendientes.map((item) => ({
    key: item.uuid,
    nombre: item.nombreArchivo,
    tipo: item.tipoArchivo,
    tomadaAt: item.tomadaAt,
    tamano: item.archivo.size,
    chip: chipLocal(item, sincronizando),
  }))

  const itemsVista = [...itemsLocales, ...itemsServidor].sort(
    (a, b) => new Date(b.tomadaAt).getTime() - new Date(a.tomadaAt).getTime(),
  )
  const pendientes = localesPendientes.length

  return (
    <AppShell title="Fotos y videos" subtitle="Evidencia del trabajo" back={{ label: 'Tablero', to: '/ordenes-trabajo' }}>
      <div
        className={`flex items-center gap-3 rounded-2xl px-[15px] py-[13px] ${enLinea ? 'bg-cya-bg text-cya-txt' : 'bg-amb-bg text-amb-txt'}`}
        style={{ border: `1px solid ${enLinea ? 'var(--color-cya)' : 'var(--color-amb)'}` }}
      >
        {enLinea ? <CloudArrowUp weight="fill" size={20} /> : <CloudSlash weight="fill" size={20} />}
        <p className="text-sm font-medium">
          {enLinea ? 'Conectado — Las evidencias se suben al instante' : 'Sin conexión — Se guardan en el celular y se suben después'}
        </p>
        {sincronizando && <ArrowsClockwise size={18} className="ml-auto animate-spin opacity-60" />}
      </div>

      <input
        ref={inputRef}
        type="file"
        accept="image/*,video/*"
        capture="environment"
        multiple
        className="hidden"
        onChange={(e) => agregarArchivos(e.target.files)}
      />

      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className="mt-3.5 flex h-[172px] w-full flex-col items-center justify-center gap-1 rounded-[18px] bg-lime-500 text-lime-ink"
        style={{ boxShadow: 'var(--shadow-cta-lime-lg)' }}
      >
        <Camera weight="fill" size={38} />
        <span className="text-[17px] font-semibold">Tomar foto o video</span>
        <span className="text-[12.5px] opacity-70">Se abre la cámara del celular</span>
      </button>

      <section className="mt-3.5 rounded-2xl bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-semibold">Evidencias de OT-{otId}</h2>
          <span className="text-[11.5px] text-app-faint">
            {itemsVista.length} {itemsVista.length === 1 ? 'archivo' : 'archivos'}
            {pendientes > 0 && ` · ${pendientes} pendiente${pendientes === 1 ? '' : 's'}`}
          </span>
        </div>

        <div className="mt-3 flex flex-col gap-3">
          {itemsVista.map((item) => (
            <div key={item.key} className="flex items-center gap-3">
              <div className="flex size-[52px] shrink-0 items-center justify-center rounded-[11px] bg-app-surface-3">
                {item.tipo.startsWith('video') ? <Video size={22} className="text-app-faint" /> : <Image size={22} className="text-app-faint" />}
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate text-[13.5px] font-medium">{item.nombre}</p>
                <p className="text-[11.5px] text-app-faint">
                  {item.tamano !== undefined ? `${tamano(item.tamano)} · ` : ''}
                  {item.subidoPor ? `${item.subidoPor} · ` : ''}
                  {hace(item.tomadaAt)}
                </p>
              </div>
              <span className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium ${item.chip.className}`}>{item.chip.label}</span>
            </div>
          ))}
          {itemsVista.length === 0 && <p className="py-4 text-center text-sm text-app-muted">Todavía no hay fotos o videos.</p>}
        </div>

        {itemsVista.length > 0 && (
          <p className="mt-3 text-[11.5px] text-app-faint">
            Las evidencias se guardan en el celular y se suben solas cuando vuelve la señal.
          </p>
        )}
      </section>
    </AppShell>
  )
}
