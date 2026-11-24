import {
  FileText,
  FireExtinguisher,
  FirstAidKit,
  Info,
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
import { obtenerOrdenTrabajo, type OrdenTrabajoDetalle } from '../lib/ordenesTrabajo'
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

  const [cargando, setCargando] = useState(true)
  const [ot, setOt] = useState<OrdenTrabajoDetalle | null>(null)
  const [accesorios, setAccesorios] = useState<string[]>([])
  const [observaciones, setObservaciones] = useState('')
  const [firmaExistente, setFirmaExistente] = useState<string | null>(null)
  const [reFirmando, setReFirmando] = useState(false)
  const [guardando, setGuardando] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const editable = ot?.estado === 'recepcionado'
  const mostrarCanvas = editable && (!firmaExistente || reFirmando)

  useEffect(() => {
    obtenerOrdenTrabajo(otId)
      .then((data) => {
        setOt(data)
        setAccesorios(data.inspeccion?.accesorios ?? [])
        setObservaciones(data.inspeccion?.observaciones ?? '')
        setFirmaExistente(data.inspeccion?.firma_cliente_url ?? null)
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : 'No se pudo cargar la inspección.'))
      .finally(() => setCargando(false))
  }, [otId])

  useEffect(() => {
    if (!mostrarCanvas) return
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
  }, [mostrarCanvas])

  function borrarFirma() {
    padRef.current?.clear()
    setTieneTrazo(false)
  }

  function toggleAccesorio(nombre: string) {
    if (!editable) return
    setAccesorios((prev) => (prev.includes(nombre) ? prev.filter((a) => a !== nombre) : [...prev, nombre]))
  }

  async function handleGuardar() {
    const necesitaFirmaNueva = !firmaExistente || reFirmando

    if (necesitaFirmaNueva && (!padRef.current || padRef.current.isEmpty())) {
      setError('Falta la firma del cliente.')
      return
    }

    setGuardando(true)
    setError(null)
    try {
      await crearInspeccion(otId, { accesorios, observaciones })

      if (necesitaFirmaNueva) {
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

        await firmarInspeccion(otId, plano.toDataURL('image/png'))
      }

      showToast('Inspección guardada')
      navigate(`/ordenes-trabajo/${otId}`)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo guardar la inspección.')
    } finally {
      setGuardando(false)
    }
  }

  if (cargando) {
    return (
      <AppShell title="Inspección de ingreso" back={{ label: 'Orden', to: `/ordenes-trabajo/${otId}` }}>
        <p className="text-sm text-app-muted">Cargando…</p>
      </AppShell>
    )
  }

  const cuenta = accesorios.length

  return (
    <AppShell
      title="Inspección de ingreso"
      subtitle={ot ? `${ot.vehiculo.marca} ${ot.vehiculo.modelo} · ${ot.vehiculo.placa} · ${ot.codigo}` : undefined}
      back={{ label: 'Orden', to: `/ordenes-trabajo/${otId}` }}
      sticky={
        editable ? (
          <button type="button" disabled={guardando} onClick={handleGuardar} className="dm-btn p">
            {guardando ? 'Guardando…' : 'Guardar inspección'}
          </button>
        ) : undefined
      }
    >
      {!editable && (
        <div className="flex items-center gap-2.5 rounded-xl bg-cya-bg px-3.5 py-3 text-[13px] leading-normal text-app-muted">
          <Info size={18} className="shrink-0 text-cya" />
          <span>Solo lectura: la orden ya salió de recepción y la inspección no se puede cambiar.</span>
        </div>
      )}

      <section className="dm-card flex flex-col gap-3">
        <div className="flex items-baseline justify-between">
          <h2 className="dm-display text-base font-semibold">Accesorios que trae</h2>
          <span className="font-mono text-[13px]" style={{ color: cuenta === ACCESORIOS.length ? 'var(--color-lime-500)' : 'var(--color-amb)' }}>
            {cuenta}/{ACCESORIOS.length}
          </span>
        </div>
        <div className="grid grid-cols-2 gap-2">
          {ACCESORIOS.map(({ nombre }) => {
            const activo = accesorios.includes(nombre)
            return (
              <button
                key={nombre}
                type="button"
                role="checkbox"
                aria-checked={activo}
                disabled={!editable}
                onClick={() => toggleAccesorio(nombre)}
                className="flex h-14 items-center gap-2.5 rounded-xl px-3.5 text-left text-sm font-semibold"
                style={{
                  border: `1px solid ${activo ? 'rgba(68, 214, 44,.5)' : '#2A313A'}`,
                  background: activo ? 'rgba(68, 214, 44,.07)' : 'var(--color-app-surface-2)',
                  color: activo ? 'var(--color-app-text)' : '#C9D0D8',
                }}
              >
                <span
                  className="flex size-[22px] shrink-0 items-center justify-center rounded-md"
                  style={activo ? { background: 'var(--color-lime-500)', color: '#0B0D10' } : { border: '2px solid #3A434E' }}
                >
                  {activo && (
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M20 6 9 17l-5-5" />
                    </svg>
                  )}
                </span>
                {nombre}
              </button>
            )
          })}
        </div>
        {editable && cuenta < ACCESORIOS.length && (
          <button
            type="button"
            onClick={() => setAccesorios(ACCESORIOS.map((a) => a.nombre))}
            className="self-start text-sm font-semibold text-lime-500"
          >
            Marcar todos
          </button>
        )}
      </section>

      <section className="dm-card flex flex-col gap-3">
        <h2 className="dm-display text-base font-semibold">Rayones y golpes previos</h2>
        <textarea
          value={observaciones}
          onChange={(e) => setObservaciones(e.target.value)}
          readOnly={!editable}
          rows={3}
          placeholder={editable ? 'Ej. rayón en puerta delantera derecha, parachoques trasero con golpe leve…' : 'Sin observaciones.'}
          className="w-full resize-y rounded-xl bg-app-surface-2 p-3 text-[15px] leading-normal outline-none placeholder:text-app-faint focus-visible:border-lime-500"
          style={{ minHeight: 96, border: '1px solid #2A313A' }}
        />
      </section>

      <section className="dm-card flex flex-col gap-3">
        <div className="flex items-center justify-between">
          <h2 className="dm-display text-base font-semibold">Firma del cliente</h2>
          {mostrarCanvas && (
            <button type="button" onClick={borrarFirma} className="text-sm font-semibold text-app-muted">
              Borrar
            </button>
          )}
          {editable && firmaExistente && !reFirmando && (
            <button
              type="button"
              onClick={() => {
                setReFirmando(true)
                setTieneTrazo(false)
              }}
              className="text-sm font-semibold text-app-muted"
            >
              Volver a firmar
            </button>
          )}
        </div>

        {mostrarCanvas ? (
          <div className="relative h-[170px] w-full overflow-hidden rounded-xl bg-app-surface-2" style={{ border: '1px dashed #3A434E' }}>
            <canvas ref={canvasRef} className="absolute inset-0 size-full touch-none" />
            <span className="pointer-events-none absolute inset-x-0" style={{ bottom: 38, borderTop: '1px solid var(--color-app-line)' }} />
            {!tieneTrazo && (
              <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-app-faint">
                <Signature size={26} />
                <span className="text-xs">Firma aquí</span>
              </div>
            )}
          </div>
        ) : firmaExistente ? (
          <div className="flex h-[150px] w-full items-center justify-center overflow-hidden rounded-xl bg-[#F2F4EF]">
            <img src={firmaExistente} alt="Firma del cliente" className="max-h-full max-w-full object-contain" />
          </div>
        ) : (
          <div className="flex h-[110px] w-full items-center justify-center rounded-xl bg-app-surface-2 text-sm text-app-faint" style={{ border: '1px dashed #3A434E' }}>
            Sin firma registrada.
          </div>
        )}
        <p className="text-xs text-app-faint">El cliente firma con el dedo en el celular del técnico. Confirma accesorios y daños.</p>
      </section>

      {error && <p className="text-sm text-cor">{error}</p>}
    </AppShell>
  )
}
