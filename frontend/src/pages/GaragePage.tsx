import { useQuery } from '@tanstack/react-query'
import { CarProfile, FilePdf, Receipt } from '@phosphor-icons/react'
import { Link, Navigate } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { ESTADOS, misOrdenesTrabajo, misVehiculos, pasoCompletado } from '../lib/garaje'
import { useAuthStore } from '../store/auth'

const API_URL = import.meta.env.VITE_API_URL as string

export default function GaragePage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const token = useAuthStore((s) => s.token)

  async function descargarHistorial(vehiculoId: number) {
    if (!token) return
    const res = await fetch(`${API_URL}/vehiculos/${vehiculoId}/historial/pdf`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    if (!res.ok) return
    const blob = await res.blob()
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  }

  const { data: vehiculos = [] } = useQuery({
    queryKey: ['me', 'vehiculos'],
    queryFn: misVehiculos,
    enabled: hasHydrated && !!user,
  })

  const { data: ordenes = [] } = useQuery({
    queryKey: ['me', 'ordenes-trabajo'],
    queryFn: misOrdenesTrabajo,
    enabled: hasHydrated && !!user,
    refetchInterval: 20000,
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />

  const ordenesActivas = ordenes.filter((o) => o.estado !== 'entregado' && o.estado !== 'cancelado')
  const conPresupuestoPendiente = ordenesActivas.find((o) => o.presupuestos[0]?.estado === 'enviado')

  return (
    <AppShell title="Mi garaje" subtitle="Seguí tu vehículo sin llamar al taller">
      {conPresupuestoPendiente && (
        <Link
          to={`/presupuestos/${conPresupuestoPendiente.presupuestos[0].id}`}
          className="mb-3.5 flex items-center gap-3 rounded-2xl px-4 py-3.5 text-left"
          style={{ border: '1px solid var(--color-amb)', background: 'var(--color-amb-bg)' }}
        >
          <Receipt weight="fill" size={24} className="text-amb shrink-0" />
          <div className="min-w-0">
            <p className="text-sm font-semibold text-amb-txt">Tenés un presupuesto para revisar</p>
            <p className="truncate text-xs text-app-muted">
              {conPresupuestoPendiente.codigo} · Bs {conPresupuestoPendiente.presupuestos[0].total} · el trabajo arranca cuando lo aprobés
            </p>
          </div>
        </Link>
      )}

      {ordenesActivas.length === 0 && vehiculos.length > 0 && (
        <p className="mb-4 rounded-2xl bg-app-surface p-4 text-sm text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
          No tenés ningún auto en el taller en este momento.
        </p>
      )}

      <div className="flex flex-col gap-3.5">
        {ordenesActivas.map((ot) => (
          <article key={ot.id} className="rounded-[18px] bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)', boxShadow: 'var(--shadow-card)' }}>
            <p className="font-mono text-[11px] tracking-[0.04em] text-app-faint">{ot.codigo}</p>
            <h2 className="mt-0.5 text-[19px] font-semibold tracking-[-0.02em]">
              {ot.vehiculo.marca} {ot.vehiculo.modelo}
            </h2>
            <p className="text-xs text-app-faint">{ot.vehiculo.placa}</p>
            <p className="mt-2 text-[13px] leading-[1.5] text-app-muted">{ot.descripcion_problema}</p>

            <ol className="mt-4 flex flex-col">
              {ESTADOS.map((paso, idx) => {
                const completado = pasoCompletado(ot.estado, paso.value)
                const esActual = ot.estado === paso.value
                return (
                  <li key={paso.value} className="flex items-start gap-3">
                    <div className="flex flex-col items-center">
                      <span
                        className="mt-1 flex size-[11px] shrink-0 rounded-full"
                        style={
                          esActual
                            ? { background: 'var(--color-lime-500)', boxShadow: '0 0 0 4px rgba(143,214,46,.18)' }
                            : completado
                              ? { background: 'var(--color-lime-500)' }
                              : { border: '1px solid var(--color-app-line)' }
                        }
                      />
                      {idx < ESTADOS.length - 1 && (
                        <span className="w-0.5 grow" style={{ minHeight: 20, background: completado ? 'var(--color-lime-500)' : 'var(--color-app-line)' }} />
                      )}
                    </div>
                    <p
                      className={
                        esActual
                          ? 'pb-4 text-[13.5px] font-semibold text-app-text'
                          : completado
                            ? 'pb-4 text-[13px] font-medium text-app-muted'
                            : 'pb-4 text-[13px] text-app-faint'
                      }
                    >
                      {paso.label}
                    </p>
                  </li>
                )
              })}
            </ol>

            <button
              type="button"
              onClick={() => descargarHistorial(ot.vehiculo.id)}
              className="flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-medium text-cor"
              style={{ border: '1px solid var(--color-app-line)' }}
            >
              <FilePdf size={18} />
              Descargar historial clínico
            </button>
          </article>
        ))}
      </div>

      <section className="mt-2">
        <h2 className="mb-2 text-sm font-semibold text-app-muted uppercase">Mis vehículos</h2>
        <div className="rounded-2xl bg-app-surface p-1.5" style={{ border: '1px solid var(--color-app-line)' }}>
          {vehiculos.map((v) => (
            <div key={v.id} className="flex items-center gap-3 rounded-xl p-2.5">
              <div className="flex size-[42px] shrink-0 items-center justify-center rounded-[11px] bg-app-surface-3">
                <CarProfile size={20} className="text-app-faint" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">
                  {v.marca} {v.modelo}
                </p>
                <p className="text-[11.5px] text-app-faint">
                  {v.anio} · {v.kilometraje_actual.toLocaleString('es-BO')} km
                </p>
              </div>
              <span className="shrink-0 rounded-md bg-app-surface-3 px-2 py-1 font-mono text-[11px]">{v.placa}</span>
            </div>
          ))}
          {vehiculos.length === 0 && <p className="p-3 text-sm text-app-muted">Todavía no tenés vehículos registrados.</p>}
        </div>
      </section>
    </AppShell>
  )
}
