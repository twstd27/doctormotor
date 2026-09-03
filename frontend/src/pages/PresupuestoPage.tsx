import { CheckCircle, PlusCircle, XCircle } from '@phosphor-icons/react'
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

  const items = presupuesto.items.filter((i) => !i.es_adicional)
  const adicionales = presupuesto.items.filter((i) => i.es_adicional)
  const adicionalesAprobados = adicionales.filter((i) => i.aprobado === true)
  const adicionalesPendientes = adicionales.filter((i) => i.aprobado === null)

  const totalConAprobados =
    Number(presupuesto.total) - adicionales.reduce((acc, i) => acc + (i.aprobado === true ? 0 : Number(i.subtotal)), 0)

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

          <div className="mt-3">
            {items.map((item) => (
              <div key={item.id} className="flex items-center justify-between gap-3 py-2.5" style={{ borderTop: '1px solid var(--color-app-line-2)' }}>
                <div className="min-w-0">
                  <p className="text-[13.5px] font-medium">{item.descripcion}</p>
                  <p className="text-[11.5px] text-app-faint">
                    {item.cantidad} × Bs {item.precio_unitario}
                  </p>
                </div>
                <p className="shrink-0 font-mono text-[13.5px] whitespace-nowrap">Bs {item.subtotal}</p>
              </div>
            ))}
          </div>

          <div className="mt-2 flex items-center justify-between rounded-xl bg-app-surface-2 p-3">
            <p className="text-sm font-medium">Subtotal presupuesto</p>
            <p className="font-mono text-[17px] font-semibold">Bs {presupuesto.subtotal}</p>
          </div>
        </div>

        {adicionales.length > 0 && (
          <div className="overflow-hidden rounded-[18px]" style={{ border: '1px solid var(--color-amb)' }}>
            <div className="flex items-center gap-2.5 bg-amb-bg px-4 py-3">
              <PlusCircle weight="fill" size={20} className="text-amb shrink-0" />
              <div>
                <p className="text-sm font-semibold text-amb-txt">Adicionales encontrados durante el trabajo</p>
                <p className="text-xs text-app-muted">Aprobá o rechazá cada uno por separado.</p>
              </div>
            </div>
            <div className="flex flex-col gap-3 bg-app-surface p-4">
              {adicionales.map((item) => (
                <div key={item.id} className="flex items-center justify-between gap-3">
                  <p className="min-w-0 text-sm">
                    {item.descripcion} — <span className="font-mono">Bs {item.subtotal}</span>
                  </p>
                  {item.aprobado === null ? (
                    <div className="flex shrink-0 gap-2">
                      <button
                        type="button"
                        disabled={enviando}
                        onClick={() => responderUnItem(item.id, true)}
                        className="h-11 min-w-[88px] rounded-lg bg-lime-500 px-3 text-xs font-semibold text-lime-ink disabled:opacity-60"
                      >
                        Aprobar
                      </button>
                      <button
                        type="button"
                        disabled={enviando}
                        onClick={() => responderUnItem(item.id, false)}
                        className="h-11 min-w-[88px] rounded-lg px-3 text-xs text-app-muted disabled:opacity-60"
                        style={{ border: '1px solid var(--color-app-line)' }}
                      >
                        Rechazar
                      </button>
                    </div>
                  ) : (
                    <span className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium ${item.aprobado ? 'bg-lime-500/15 text-lime-txt' : 'bg-cor-bg text-cor-txt'}`}>
                      {item.aprobado ? 'Aprobado' : 'Rechazado'}
                    </span>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        <div className="rounded-[18px] bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-semibold">Total a aprobar</p>
              {adicionalesAprobados.length > 0 && (
                <p className="text-xs text-app-muted">incluye {adicionalesAprobados.length} adicional aprobado</p>
              )}
            </div>
            <p className="font-mono text-[26px] font-semibold tracking-[-0.02em] text-lime-500">
              Bs {totalConAprobados.toLocaleString('es-BO')}
            </p>
          </div>
        </div>

        {presupuesto.estado === 'enviado' && adicionalesPendientes.length === 0 && (
          <div className="flex gap-3">
            <button
              type="button"
              disabled={enviando}
              onClick={() => responderGeneral(true)}
              className="h-[54px] flex-1 rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
              style={{ boxShadow: 'var(--shadow-cta-lime)' }}
            >
              Aprobar presupuesto
            </button>
            <button
              type="button"
              disabled={enviando}
              onClick={() => responderGeneral(false)}
              className="h-[50px] flex-1 rounded-xl text-[15px] font-medium disabled:opacity-60"
              style={{ border: '1px solid var(--color-app-line)' }}
            >
              Rechazar
            </button>
          </div>
        )}

        {error && <p className="text-center text-sm text-cor">{error}</p>}
      </div>
    </AppShell>
  )
}
