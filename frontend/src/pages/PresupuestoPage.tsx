import { CheckCircle, XCircle } from '@phosphor-icons/react'
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { ApiError } from '../lib/api'
import {
  obtenerPresupuesto,
  responderItem,
  responderPresupuesto,
  type Presupuesto,
  type PresupuestoItem,
} from '../lib/presupuestos'
import { hayContactoTaller, whatsappTaller } from '../lib/taller'

function bs(n: number) {
  return n.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export default function PresupuestoPage() {
  const { id } = useParams<{ id: string }>()
  const presupuestoId = Number(id)

  const [presupuesto, setPresupuesto] = useState<Presupuesto | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)
  // Trabajos pendientes que el cliente decidió NO hacer (por defecto se aprueba todo).
  const [quitados, setQuitados] = useState<Set<number>>(new Set())

  async function cargar() {
    try {
      setPresupuesto(await obtenerPresupuesto(presupuestoId))
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo cargar el presupuesto.')
    }
  }

  useEffect(() => {
    cargar()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [presupuestoId])

  if (error && !presupuesto) {
    return (
      <AppShell title="Presupuesto" back={{ label: 'Inicio', to: '/' }}>
        <p className="text-sm text-cor">{error}</p>
      </AppShell>
    )
  }
  if (!presupuesto) {
    return (
      <AppShell title="Presupuesto" back={{ label: 'Inicio', to: '/' }}>
        <p className="text-sm text-app-muted">Cargando…</p>
      </AppShell>
    )
  }

  const abierto = presupuesto.estado === 'enviado'
  const items = presupuesto.items
  const decididos = (i: PresupuestoItem) => i.aprobado !== null
  const seApruebaItem = (i: PresupuestoItem) => (decididos(i) ? i.aprobado === true : !quitados.has(i.id))
  const aprobados = items.filter(seApruebaItem)
  const total = aprobados.reduce((acc, i) => acc + Number(i.subtotal), 0)

  function alternar(i: PresupuestoItem) {
    if (!abierto || decididos(i)) return
    setQuitados((prev) => {
      const sig = new Set(prev)
      if (sig.has(i.id)) sig.delete(i.id)
      else sig.add(i.id)
      return sig
    })
  }

  async function confirmar() {
    if (!presupuesto) return
    setEnviando(true)
    setError(null)
    try {
      const pendientesQuitados = items.filter((i) => !decididos(i) && quitados.has(i.id))
      const pendientesAprobados = items.filter((i) => !decididos(i) && !quitados.has(i.id))
      if (pendientesAprobados.length === 0 && !items.some((i) => i.aprobado === true)) {
        setPresupuesto(await responderPresupuesto(presupuestoId, false))
      } else {
        for (const item of pendientesQuitados) await responderItem(presupuestoId, item.id, false)
        setPresupuesto(await responderPresupuesto(presupuestoId, true))
      }
      setQuitados(new Set())
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo enviar tu respuesta.')
      await cargar()
    } finally {
      setEnviando(false)
    }
  }

  const grupos = [
    { nombre: 'Trabajos del presupuesto', items: items.filter((i) => !i.es_adicional) },
    { nombre: 'Encontrados durante el trabajo', items: items.filter((i) => i.es_adicional) },
  ].filter((g) => g.items.length > 0)

  return (
    <AppShell
      title="Presupuesto"
      back={{ label: 'Inicio', to: '/' }}
      subtitle={
        <span className="mt-1 flex items-center gap-2">
          {presupuesto.orden_trabajo.vehiculo.marca} {presupuesto.orden_trabajo.vehiculo.modelo}
          <span className="font-mono">{presupuesto.orden_trabajo.codigo}</span>
        </span>
      }
      sticky={
        abierto ? (
          <>
            <div className="flex items-baseline justify-between">
              <span className="text-sm text-app-muted">Total a aprobar</span>
              <span className="font-mono text-2xl font-bold text-lime-500">Bs {bs(total)}</span>
            </div>
            <button type="button" className="dm-btn p" disabled={enviando} onClick={confirmar}>
              {enviando
                ? 'Enviando…'
                : aprobados.length === 0
                  ? 'No aprobar ninguno'
                  : `Aprobar ${aprobados.length} ${aprobados.length === 1 ? 'trabajo' : 'trabajos'}`}
            </button>
            {hayContactoTaller && (
              <a
                href={whatsappTaller(`Hola, tengo dudas sobre el presupuesto de mi orden ${presupuesto.orden_trabajo.codigo}.`)}
                target="_blank"
                rel="noreferrer"
                className="py-1 text-center text-[13px] font-semibold text-lime-500"
              >
                Tengo dudas · escribir al taller
              </a>
            )}
          </>
        ) : undefined
      }
    >
      {presupuesto.estado === 'aprobado' && (
        <div className="flex items-center gap-3 rounded-2xl bg-lime-500/15 px-4 py-3.5">
          <CheckCircle weight="fill" size={22} className="shrink-0 text-lime-500" />
          <div>
            <p className="text-sm font-semibold text-lime-500">Presupuesto aprobado</p>
            <p className="text-xs text-app-muted">El taller ya puede empezar</p>
          </div>
        </div>
      )}
      {presupuesto.estado === 'rechazado' && (
        <div className="flex items-center gap-3 rounded-2xl bg-cor-bg px-4 py-3.5">
          <XCircle weight="fill" size={22} className="shrink-0 text-cor" />
          <p className="text-sm font-semibold text-cor">No aprobaste este presupuesto</p>
        </div>
      )}

      {abierto && (
        <p className="text-sm leading-normal text-app-muted">
          Toca un trabajo para quitarlo o volver a agregarlo. Lo que no apruebes no se hace y queda anotado para otra visita.
        </p>
      )}

      {grupos.map((g) => (
        <div key={g.nombre} className="flex flex-col gap-2">
          <span className="dm-lab">{g.nombre}</span>
          <div className="dm-card" style={{ padding: '0 16px' }}>
            {g.items.map((i) => {
              const activo = seApruebaItem(i)
              const bloqueado = !abierto || decididos(i)
              return (
                <button
                  key={i.id}
                  type="button"
                  role="checkbox"
                  aria-checked={activo}
                  disabled={bloqueado}
                  onClick={() => alternar(i)}
                  className="dm-li w-full items-start text-left"
                  style={{ minHeight: 64, opacity: activo ? 1 : 0.55 }}
                >
                  <span
                    aria-hidden="true"
                    className="mt-0.5 flex size-[22px] shrink-0 items-center justify-center rounded-md"
                    style={
                      activo
                        ? { background: 'var(--color-lime-500)', color: 'var(--color-lime-ink)' }
                        : { border: '2px solid #3A434E' }
                    }
                  >
                    {activo && (
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M20 6 9 17l-5-5" />
                      </svg>
                    )}
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block text-[15px] font-semibold" style={{ textDecoration: activo ? undefined : 'line-through' }}>
                      {i.descripcion}
                    </span>
                    <span className="mt-0.5 block text-[12.5px] text-app-faint">
                      {Number(i.cantidad)} × {bs(Number(i.precio_unitario))}
                      {decididos(i) && (i.aprobado ? ' · aprobado' : ' · no aprobado')}
                    </span>
                  </span>
                  <span className="font-mono text-[15px] font-semibold">{bs(Number(i.subtotal))}</span>
                </button>
              )
            })}
          </div>
        </div>
      ))}

      <Link to={`/ordenes-trabajo/${presupuesto.orden_trabajo.id}/evidencias`} className="dm-card flex items-center gap-3" style={{ padding: '12px 14px' }}>
        <div className="min-w-0 flex-1">
          <p className="text-sm font-semibold">Ver las fotos del diagnóstico</p>
          <p className="text-[12.5px] text-app-faint">Lo que encontró el técnico en tu vehículo</p>
        </div>
        <span className="text-app-faint">›</span>
      </Link>

      {!abierto && presupuesto.estado !== 'borrador' && (
        <div className="flex items-baseline justify-between px-1">
          <span className="text-sm text-app-muted">Total aprobado</span>
          <span className="font-mono text-xl font-bold text-lime-500">Bs {bs(total)}</span>
        </div>
      )}

      {error && <p className="text-center text-sm text-cor">{error}</p>}
    </AppShell>
  )
}
