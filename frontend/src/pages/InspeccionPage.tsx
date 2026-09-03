import {
  FileText,
  FireExtinguisher,
  FirstAidKit,
  Key,
  Signature,
  Tire,
  Warning,
  Wrench,
} from '@phosphor-icons/react'
import { useEffect, useRef, useState, type ComponentType } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import SignaturePad from 'signature_pad'
import AppShell from '../components/AppShell'
import { ApiError } from '../lib/api'
import { crearInspeccion, firmarInspeccion } from '../lib/inspeccion'
import { useToastStore } from '../store/toast'

const ACCESORIOS: { nombre: string; icon: ComponentType<{ size?: number; weight?: 'regular' | 'fill' }> }[] = [
  { nombre: 'Llave de repuesto', icon: Key },
  { nombre: 'Gata', icon: Wrench },
  { nombre: 'Llave de rueda', icon: Wrench },
  { nombre: 'Rueda de auxilio', icon: Tire },
  { nombre: 'Botiquín', icon: FirstAidKit },
  { nombre: 'Extintor', icon: FireExtinguisher },
  { nombre: 'Triángulos', icon: Warning },
  { nombre: 'Documentos', icon: FileText },
]

export default function InspeccionPage() {
  const { id } = useParams<{ id: string }>()
  const otId = Number(id)
  const navigate = useNavigate()
  const showToast = useToastStore((s) => s.show)

  const canvasRef = useRef<HTMLCanvasElement>(null)
  const padRef = useRef<SignaturePad | null>(null)
  const [tieneTrazo, setTieneTrazo] = useState(false)

  const [accesorios, setAccesorios] = useState<string[]>([])
  const [observaciones, setObservaciones] = useState('')
  const [guardando, setGuardando] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const canvas = canvasRef.current
    if (!canvas) return

    function resize() {
      if (!canvas) return
      const ratio = Math.max(window.devicePixelRatio || 1, 1)
      canvas.width = canvas.offsetWidth * ratio
      canvas.height = canvas.offsetHeight * ratio
      canvas.getContext('2d')?.scale(ratio, ratio)
    }
    resize()
    window.addEventListener('resize', resize)

    padRef.current = new SignaturePad(canvas, {
      backgroundColor: 'rgba(0,0,0,0)',
      penColor: '#E9EDF0',
      minWidth: 2.2,
      maxWidth: 2.2,
    })
    padRef.current.addEventListener('beginStroke', () => setTieneTrazo(true))

    return () => {
      window.removeEventListener('resize', resize)
      padRef.current?.off()
    }
  }, [])

  function borrarFirma() {
    padRef.current?.clear()
    setTieneTrazo(false)
  }

  function toggleAccesorio(nombre: string) {
    setAccesorios((prev) => (prev.includes(nombre) ? prev.filter((a) => a !== nombre) : [...prev, nombre]))
  }

  async function handleGuardar() {
    if (!padRef.current || padRef.current.isEmpty()) {
      setError('Falta la firma del cliente.')
      return
    }

    setGuardando(true)
    setError(null)
    try {
      // El pad se ve con trazo claro sobre fondo oscuro (para calzar con el tema de la app),
      // pero se guarda en negro sobre blanco: la firma también se imprime en el historial
      // clínico en PDF, y un trazo casi blanco quedaría invisible sobre papel.
      if (!canvasRef.current) return
      const origen = canvasRef.current
      const plano = document.createElement('canvas')
      plano.width = origen.width
      plano.height = origen.height
      const ctx = plano.getContext('2d')!
      ctx.fillStyle = '#FFFFFF'
      ctx.fillRect(0, 0, plano.width, plano.height)
      ctx.filter = 'invert(1)'
      ctx.drawImage(origen, 0, 0)

      await crearInspeccion(otId, { accesorios, observaciones })
      await firmarInspeccion(otId, plano.toDataURL('image/png'))
      showToast('Inspección guardada')
      navigate('/ordenes-trabajo')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo guardar la inspección.')
    } finally {
      setGuardando(false)
    }
  }

  return (
    <AppShell title="Inspección de ingreso" subtitle="Registrá el estado del vehículo" back={{ label: 'Tablero', to: '/ordenes-trabajo' }}>
      <div className="grid gap-3.5 md:grid-cols-2" style={{ alignItems: 'start' }}>
        <section className="rounded-2xl bg-app-surface p-[18px]" style={{ border: '1px solid var(--color-app-line)' }}>
          <h2 className="text-[15px] font-semibold">Accesorios presentes</h2>
          <p className="mt-0.5 text-[12.5px] text-app-muted">Marcá lo que el cliente trae con el vehículo.</p>
          <div className="mt-3 flex flex-wrap gap-2">
            {ACCESORIOS.map(({ nombre, icon: Icon }) => {
              const activo = accesorios.includes(nombre)
              return (
                <button
                  key={nombre}
                  type="button"
                  onClick={() => toggleAccesorio(nombre)}
                  className={
                    activo
                      ? 'flex h-11 items-center gap-1.5 rounded-full bg-lime-500/15 px-3.5 text-sm font-medium text-lime-txt'
                      : 'flex h-11 items-center gap-1.5 rounded-full bg-app-surface-2 px-3.5 text-sm text-app-muted'
                  }
                  style={{ border: `1px solid ${activo ? 'var(--color-lime-500)' : 'var(--color-app-line)'}` }}
                >
                  <Icon size={16} weight={activo ? 'fill' : 'regular'} />
                  {nombre}
                </button>
              )
            })}
          </div>
          <p className="mt-3 text-xs text-app-faint">
            {accesorios.length} de {ACCESORIOS.length} accesorios marcados
          </p>
        </section>

        <section className="rounded-2xl bg-app-surface p-[18px]" style={{ border: '1px solid var(--color-app-line)' }}>
          <h2 className="text-[15px] font-semibold">Observaciones y rayones previos</h2>
          <textarea
            value={observaciones}
            onChange={(e) => setObservaciones(e.target.value)}
            rows={4}
            placeholder="Ej. rayón en puerta delantera derecha, paragolpes trasero con golpe leve…"
            className="mt-3 w-full resize-y rounded-xl bg-app-surface-2 p-3 text-sm leading-[1.5] outline-none placeholder:text-app-faint focus-visible:border-lime-500"
            style={{ minHeight: 110, border: '1px solid var(--color-app-line)' }}
          />
        </section>

        <section className="col-span-full rounded-2xl bg-app-surface p-[18px]" style={{ border: '1px solid var(--color-app-line)' }}>
          <div className="flex items-center justify-between">
            <h2 className="text-[15px] font-semibold">Firma del cliente</h2>
            <button type="button" onClick={borrarFirma} className="flex h-10 items-center rounded-lg px-3 text-xs font-medium text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
              Borrar
            </button>
          </div>
          <div
            className="relative mt-3 h-[190px] w-full overflow-hidden rounded-[14px] bg-app-surface-2"
            style={{ border: '1px dashed var(--color-app-line)' }}
          >
            <canvas ref={canvasRef} className="absolute inset-0 size-full touch-none" />
            <span className="pointer-events-none absolute inset-x-0" style={{ bottom: 38, borderTop: '1px solid var(--color-app-line)' }} />
            {!tieneTrazo && (
              <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-app-faint">
                <Signature size={26} />
                <span className="text-xs">Firma acá</span>
              </div>
            )}
          </div>
        </section>
      </div>

      {error && <p className="mt-3 text-sm text-cor">{error}</p>}

      <button
        type="button"
        disabled={guardando}
        onClick={handleGuardar}
        className="mt-4 h-[52px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
        style={{ boxShadow: 'var(--shadow-cta-lime)' }}
      >
        {guardando ? 'Guardando…' : 'Guardar inspección'}
      </button>
    </AppShell>
  )
}
