import { CheckCircle, XCircle } from '@phosphor-icons/react'
import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { ApiError } from '../lib/api'
import {
  obtenerPresupuesto,
  responderItem,
  responderPresupuesto,
  type Presupuesto,
} from '../lib/presupuestos'

export default function PresupuestoPage() {
  const { id } = useParams<{ id: string }>()
  const presupuestoId = Number(id)

  const [presupuesto, setPresupuesto] = useState<Presupuesto | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

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

  async function responderGeneral(aprobado: boolean) {
    setEnviando(true)
    setError(null)
    try {
      const actualizado = await responderPresupuesto(presupuestoId, aprobado)
      setPresupuesto(actualizado)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo enviar tu respuesta.')
    } finally {
      setEnviando(false)
    }
  }

  async function responderUnItem(itemId: number, aprobado: boolean) {
    setEnviando(true)
    setError(null)
    try {
      await responderItem(presupuestoId, itemId, aprobado)
      await cargar()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo enviar tu respuesta.')
    } finally {
      setEnviando(false)
    }
  }

  if (error && !presupuesto) {
    return (
      <main className="flex min-h-svh items-center justify-center bg-app-bg px-6 text-center text-app-text">
        <p className="text-sm text-cor">{error}</p>
      </main>
    )
  }

  if (!presupuesto) {
    return <main className="flex min-h-svh items-center justify-center bg-app-bg text-app-muted">Cargando…</main>
  }

  // Todos los ítems (del presupuesto original o encontrados durante el trabajo) se aprueban
  // o rechazan igual, uno por uno — "aprobar presupuesto" abajo es un atajo que aprueba de
  // una todo lo que siga pendiente, no la única forma de responder.
  const items = presupuesto.items
  const rechazados = items.filter((i) => i.aprobado === false)

  // El total mostrado es "lo que costaría si se aprueba todo lo pendiente" — excluye solo lo
  // ya rechazado explícitamente. presupuesto.total no sirve solo: es el snapshot original y
  // no incluye los ítems agregados después como adicionales.
  const totalAprobar = items
    .filter((i) => i.aprobado !== false)
    .reduce((acc, i) => acc + Number(i.subtotal), 0)

  return (
    <AppShell title="Presupuesto" subtitle={presupuesto.orden_trabajo.codigo} back={{ label: 'Atrás', to: '/garaje' }}>
      <div className="mx-auto flex max-w-[660px] flex-col gap-3.5">
        {presupuesto.estado === 'aprobado' && (
          <div className="flex items-center gap-3 rounded-2xl px-4 py-3.5 bg-lime-500/15">
            <CheckCircle weight="fill" size={22} className="text-lime-500 shrink-0" />
            <div>
              <p className="text-sm font-semibold text-lime-txt">Presupuesto aprobado</p>
              <p className="text-xs text-app-muted">El taller ya puede empezar</p>
            </div>
          </div>
        )}
        {presupuesto.estado === 'rechazado' && (
          <div className="flex items-center gap-3 rounded-2xl px-4 py-3.5 bg-cor-bg">
            <XCircle weight="fill" size={22} className="text-cor shrink-0" />
            <p className="text-sm font-semibold text-cor-txt">Rechazaste este presupuesto</p>
          </div>
        )}

        <div className="rounded-[18px] bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
          <p className="font-mono text-[11px] tracking-[0.04em] text-app-faint">{presupuesto.orden_trabajo.codigo}</p>
          <h1 className="mt-0.5 text-[19px] font-semibold tracking-[-0.02em]">
            {presupuesto.orden_trabajo.vehiculo.marca} {presupuesto.orden_trabajo.vehiculo.modelo}
          </h1>

          <div className="mt-3 flex flex-col gap-3">
            {items.map((item) => (
              <div key={item.id} className="flex items-center justify-between gap-3 py-2.5" style={{ borderTop: '1px solid var(--color-app-line-2)' }}>
                <div className="min-w-0">
                  <p className="text-[13.5px] font-medium">{item.descripcion}</p>
                  <p className="text-[11.5px] text-app-faint">
                    {item.cantidad} × Bs {item.precio_unitario}
                    {item.es_adicional && ' · encontrado durante el trabajo'}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-2.5">
                  <p className="font-mono text-[13.5px] whitespace-nowrap">Bs {item.subtotal}</p>
                  {item.aprobado === null ? (
                    <div className="flex shrink-0 gap-1.5">
                      <button
                        type="button"
                        disabled={enviando}
                        onClick={() => responderUnItem(item.id, true)}
                        className="h-9 min-w-[72px] rounded-lg bg-lime-500 px-2.5 text-[11px] font-semibold text-lime-ink disabled:opacity-60"
                      >
                        Aprobar
                      </button>
                      <button
                        type="button"
                        disabled={enviando}
                        onClick={() => responderUnItem(item.id, false)}
                        className="h-9 min-w-[72px] rounded-lg px-2.5 text-[11px] text-app-muted disabled:opacity-60"
                        style={{ border: '1px solid var(--color-app-line)' }}
                      >
                        Rechazar
                      </button>
                    </div>
                  ) : (
                    <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10.5px] font-medium ${item.aprobado ? 'bg-lime-500/15 text-lime-txt' : 'bg-cor-bg text-cor-txt'}`}>
                      {item.aprobado ? 'Aprobado' : 'Rechazado'}
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        {rechazados.length > 0 && (
          <div className="rounded-[18px] bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
            <p className="text-xs text-app-muted">
              Lo que rechaces queda registrado en la orden para que el técnico sepa que no debe hacerlo — podés pedirlo en otra visita.
            </p>
          </div>
        )}

        <div className="rounded-[18px] bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
          <div className="flex items-center justify-between">
            <p className="text-sm font-medium">Total a aprobar</p>
            <p className="font-mono text-[26px] font-semibold tracking-[-0.02em] text-lime-500">
              Bs {totalAprobar.toLocaleString('es-BO')}
            </p>
          </div>
        </div>

        {presupuesto.estado === 'enviado' && (
          <div className="flex gap-3">
            <button
              type="button"
              disabled={enviando}
              onClick={() => responderGeneral(true)}
              className="h-[54px] flex-1 rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
              style={{ boxShadow: 'var(--shadow-cta-lime)' }}
            >
              Confirmar
            </button>
            <button
              type="button"
              disabled={enviando}
              onClick={() => responderGeneral(false)}
              className="h-[50px] flex-1 rounded-xl text-[15px] font-medium disabled:opacity-60"
              style={{ border: '1px solid var(--color-app-line)' }}
            >
              Rechazar todo
            </button>
          </div>
        )}

        {error && <p className="text-center text-sm text-cor">{error}</p>}
      </div>
    </AppShell>
  )
}
