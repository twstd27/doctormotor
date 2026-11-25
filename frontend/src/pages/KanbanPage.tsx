import { useQuery } from '@tanstack/react-query'
import { CaretRight } from '@phosphor-icons/react'
import { Link, Navigate } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { Placa, SubtituloMarca } from '../components/ui'
import { ESTADOS, TONO_CLASES, TONO_ESTADO, antiguedad, listarOrdenesTrabajo } from '../lib/ordenesTrabajo'
import { useAuthStore } from '../store/auth'

/** Tablero del técnico: sus órdenes agrupadas por etapa (la API ya solo devuelve las asignadas). */
export default function KanbanPage() {
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const user = useAuthStore((s) => s.user)

  const { data: ordenes = [], isLoading } = useQuery({
    queryKey: ['ordenes-trabajo'],
    queryFn: listarOrdenesTrabajo,
    refetchInterval: 15000,
    enabled: hasHydrated && !!user,
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />

  const activas = ordenes.filter((o) => o.estado !== 'cancelado')
  const grupos = ESTADOS.map((e) => ({ ...e, items: activas.filter((o) => o.estado === e.value) }))
  // "Entregado" solo se muestra si tiene algo; las etapas vacías de trabajo siguen visibles ("Nada aquí").
  const visibles = grupos.filter((g) => g.value !== 'entregado' || g.items.length > 0)

  return (
    <AppShell
      title="Tablero"
      subtitle={<SubtituloMarca>{activas.filter((o) => o.estado !== 'entregado').length} órdenes en curso</SubtituloMarca>}
    >
      {isLoading && <p className="text-sm text-app-muted">Cargando…</p>}

      <div className="flex flex-col gap-[18px]">
        {visibles.map((g) => {
          const tono = TONO_CLASES[TONO_ESTADO[g.value]]
          return (
            <section key={g.value} className="flex flex-col gap-2">
              <div className="flex items-center gap-2">
                <span className={`size-2 rounded-full ${tono.dot}`} />
                <h2 className="dm-display text-[15px] font-semibold">{g.label}</h2>
                <span className="rounded-full bg-app-surface-3 px-2 py-0.5 font-mono text-xs font-semibold text-app-muted">
                  {g.items.length}
                </span>
              </div>
              {g.items.map((ot) => (
                <Link
                  key={ot.id}
                  to={`/ordenes-trabajo/${ot.id}`}
                  className="dm-card flex items-center gap-3"
                  style={{ padding: 14 }}
                >
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="truncate text-[15px] font-semibold">
                        {ot.vehiculo.marca} {ot.vehiculo.modelo}
                      </span>
                      <Placa placa={ot.vehiculo.placa} className="shrink-0 text-[11px]" />
                    </div>
                    <p className="mt-0.5 truncate text-[13px] text-app-faint">{ot.descripcion_problema}</p>
                    <p className="mt-1.5 text-xs text-app-faint">
                      {ot.codigo} · ingresó hace {antiguedad(ot.fecha_ingreso)}
                    </p>
                  </div>
                  <CaretRight size={18} className="shrink-0 text-app-faint" />
                </Link>
              ))}
              {g.items.length === 0 && <p className="py-1 text-[13px] text-app-faint">Nada aquí</p>}
            </section>
          )
        })}
      </div>
    </AppShell>
  )
}
