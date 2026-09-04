import { ArrowsClockwise, Camera, CloudArrowUp, CloudSlash, Image, ImagesSquare, MagnifyingGlassPlus, VideoCamera, Video, X } from '@phosphor-icons/react'
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
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
  imagenUrl?: string
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
  const user = useAuthStore((s) => s.user)
  const esCliente = user?.rol === 'cliente'
  const fotoRef = useRef<HTMLInputElement>(null)
  const videoRef = useRef<HTMLInputElement>(null)
  const galeriaRef = useRef<HTMLInputElement>(null)

  const [items, setItems] = useState<EvidenciaPendiente[]>([])
  const [servidor, setServidor] = useState<EvidenciaServidor[]>([])
  const [enLinea, setEnLinea] = useState(navigator.onLine)
  const [sincronizando, setSincronizando] = useState(false)
  const [previews, setPreviews] = useState<Record<string, string>>({})
  const [modal, setModal] = useState<{ url: string; nombre: string } | null>(null)

  const refrescar = useCallback(async () => {
    if (!esCliente) setItems(await evidenciasDeOt(otId))
    if (navigator.onLine) {
      try {
        setServidor(await evidenciasDeOtServidor(otId))
      } catch {
        // sin conexión o error de red — se conserva la última lista del servidor conocida
      }
    }
  }, [otId, esCliente])

  const sincronizar = useCallback(async () => {
    if (esCliente || !token || !navigator.onLine) return
    setSincronizando(true)
    await sincronizarCola(token, API_URL, () => refrescar())
    await refrescar()
    setSincronizando(false)
  }, [token, refrescar, esCliente])

  useEffect(() => {
    refrescar()
  }, [refrescar])

  useEffect(() => {
    if (esCliente) return
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
  }, [sincronizar, esCliente])

  useEffect(() => {
    const nuevos: Record<string, string> = {}
    for (const item of items) {
      if (item.tipoArchivo.startsWith('image')) nuevos[item.uuid] = URL.createObjectURL(item.archivo)
    }
    setPreviews(nuevos)
    return () => {
      Object.values(nuevos).forEach((url) => URL.revokeObjectURL(url))
    }
  }, [items])

  useEffect(() => {
    if (!modal) return
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === 'Escape') setModal(null)
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [modal])

  async function agregarArchivos(files: FileList | null) {
    if (!files) return
    for (const archivo of Array.from(files)) {
      await encolarEvidencia(otId, archivo, '')
    }
    await refrescar()
    sincronizar()
  }

  const localesPendientes = items.filter((i) => i.estado !== 'sincronizado')

  const itemsServidor: ItemVista[] = useMemo(
    () =>
      servidor.map((e) => ({
        key: e.uuid_cliente,
        nombre: e.etiqueta || e.url.split('/').pop() || 'archivo',
        tipo: e.tipo,
        tomadaAt: e.tomada_at,
        subidoPor: e.subido_por?.nombre,
        imagenUrl: e.tipo === 'foto' ? e.url : undefined,
        chip: { label: 'Sincronizado', className: 'bg-lime-500/15 text-lime-txt' },
      })),
    [servidor],
  )

  const itemsLocales: ItemVista[] = useMemo(
    () =>
      localesPendientes.map((item) => ({
        key: item.uuid,
        nombre: item.nombreArchivo,
        tipo: item.tipoArchivo,
        tomadaAt: item.tomadaAt,
        tamano: item.archivo.size,
        imagenUrl: previews[item.uuid],
        chip: chipLocal(item, sincronizando),
      })),
    [localesPendientes, previews, sincronizando],
  )

  const itemsVista = [...itemsLocales, ...itemsServidor].sort(
    (a, b) => new Date(b.tomadaAt).getTime() - new Date(a.tomadaAt).getTime(),
  )
  const pendientes = localesPendientes.length

  return (
    <AppShell
      title="Fotos y videos"
      subtitle="Evidencia del trabajo"
      back={esCliente ? { label: 'Mi garaje', to: '/garaje' } : { label: 'Tablero', to: '/ordenes-trabajo' }}
    >
      {!esCliente && (
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
      )}

      {!esCliente && (
        <>
          {/* Un input por tipo — Android no ofrece la cámara si accept mezcla image/* y
              video/* junto con capture, así que cada acción necesita su propio input. */}
          <input ref={fotoRef} type="file" accept="image/*" capture="environment" className="hidden" onChange={(e) => agregarArchivos(e.target.files)} />
          <input ref={videoRef} type="file" accept="video/*" capture="environment" className="hidden" onChange={(e) => agregarArchivos(e.target.files)} />
          <input ref={galeriaRef} type="file" accept="image/*,video/*" multiple className="hidden" onChange={(e) => agregarArchivos(e.target.files)} />

          <button
            type="button"
            onClick={() => fotoRef.current?.click()}
            className="mt-3.5 flex h-[140px] w-full flex-col items-center justify-center gap-1 rounded-[18px] bg-lime-500 text-lime-ink"
            style={{ boxShadow: 'var(--shadow-cta-lime-lg)' }}
          >
            <Camera weight="fill" size={34} />
            <span className="text-[17px] font-semibold">Tomar foto</span>
            <span className="text-[12.5px] opacity-70">Se abre la cámara del celular</span>
          </button>

          <div className="mt-2.5 grid grid-cols-2 gap-2.5">
            <button
              type="button"
              onClick={() => videoRef.current?.click()}
              className="flex h-12 items-center justify-center gap-2 rounded-xl text-sm font-medium"
              style={{ border: '1px solid var(--color-app-line)' }}
            >
              <VideoCamera size={18} />
              Grabar video
            </button>
            <button
              type="button"
              onClick={() => galeriaRef.current?.click()}
              className="flex h-12 items-center justify-center gap-2 rounded-xl text-sm font-medium"
              style={{ border: '1px solid var(--color-app-line)' }}
            >
              <ImagesSquare size={18} />
              Elegir de galería
            </button>
          </div>
        </>
      )}

      <section className={`rounded-2xl bg-app-surface p-4 ${esCliente ? '' : 'mt-3.5'}`} style={{ border: '1px solid var(--color-app-line)' }}>
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-semibold">Evidencias de OT-{otId}</h2>
          <span className="text-[11.5px] text-app-faint">
            {itemsVista.length} {itemsVista.length === 1 ? 'archivo' : 'archivos'}
            {pendientes > 0 && ` · ${pendientes} pendiente${pendientes === 1 ? '' : 's'}`}
          </span>
        </div>

        <div className="mt-3 grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-6">
          {itemsVista.map((item) => {
            const contenido = item.imagenUrl ? (
              <img src={item.imagenUrl} alt={item.nombre} className="size-full object-cover" />
            ) : item.tipo.startsWith('video') ? (
              <Video size={26} className="text-app-faint" />
            ) : (
              <Image size={26} className="text-app-faint" />
            )
            return (
              <div key={item.key} className="flex flex-col gap-1">
                {item.imagenUrl ? (
                  <button
                    type="button"
                    onClick={() => setModal({ url: item.imagenUrl as string, nombre: item.nombre })}
                    className="relative aspect-square w-full overflow-hidden rounded-[11px] bg-app-surface-3"
                  >
                    {contenido}
                    <span className="absolute right-1 bottom-1 flex size-5 items-center justify-center rounded-full bg-black/55 text-white">
                      <MagnifyingGlassPlus size={12} weight="bold" />
                    </span>
                  </button>
                ) : (
                  <div className="flex aspect-square w-full items-center justify-center overflow-hidden rounded-[11px] bg-app-surface-3">
                    {contenido}
                  </div>
                )}
                <span className={`self-start rounded-full px-1.5 py-0.5 text-[9.5px] font-medium ${item.chip.className}`}>{item.chip.label}</span>
                <p className="truncate text-[10.5px] text-app-faint">{item.subidoPor ?? tamano(item.tamano ?? 0)} · {hace(item.tomadaAt)}</p>
              </div>
            )
          })}
          {itemsVista.length === 0 && (
            <p className="col-span-full py-4 text-center text-sm text-app-muted">
              {esCliente ? 'Todavía no hay fotos ni videos de este trabajo.' : 'Todavía no agregaste fotos o videos.'}
            </p>
          )}
        </div>

        {!esCliente && itemsVista.length > 0 && (
          <p className="mt-3 text-[11.5px] text-app-faint">
            Las evidencias se guardan en el celular y se suben solas cuando vuelve la señal.
          </p>
        )}
      </section>

      {modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-6" onClick={() => setModal(null)}>
          <button
            type="button"
            onClick={() => setModal(null)}
            aria-label="Cerrar"
            className="absolute top-4 right-4 flex size-10 items-center justify-center rounded-full bg-white/10 text-white"
          >
            <X size={20} weight="bold" />
          </button>
          <img
            src={modal.url}
            alt={modal.nombre}
            onClick={(e) => e.stopPropagation()}
            className="max-h-[88vh] max-w-full rounded-xl object-contain"
          />
        </div>
      )}
    </AppShell>
  )
}
