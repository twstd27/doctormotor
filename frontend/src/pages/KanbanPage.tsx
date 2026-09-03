import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CaretLeft, CaretRight, CarProfile, Tray } from '@phosphor-icons/react'
import { useState } from 'react'
import AppShell from '../components/AppShell'
import OtDetailSheet from '../components/OtDetailSheet'
import {
  ESTADOS,
  TONO_CLASES,
  TONO_ESTADO,
  antiguedad,
  cambiarEstado,
  listarOrdenesTrabajo,
  type Estado,
  type OrdenTrabajo,
} from '../lib/ordenesTrabajo'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'

function CardOt({ ot, compact, onClick }: { ot: OrdenTrabajo; compact?: boolean; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={
        compact
          ? 'flex w-full flex-col rounded-[13px] bg-app-surface p-[13px] text-left'
          : 'dm-in flex w-full flex-col gap-2.5 rounded-2xl bg-app-surface p-4 text-left'
      }
      style={{ border: '1px solid var(--color-app-line)', boxShadow: compact ? undefined : 'var(--shadow-card)' }}
    >
      <div className="flex items-center justify-between">
        <span className="font-mono text-[11px] tracking-[0.04em] text-app-faint">{ot.codigo}</span>
        <span className="rounded-full bg-app-surface-3 px-2 py-0.5 text-[10.5px] font-medium text-app-faint">
          {antiguedad(ot.fecha_ingreso)}
        </span>
      </div>

      {compact ? (
        <>
          <p className="mt-1.5 text-[13.5px] font-semibold">
            {ot.vehiculo.marca} {ot.vehiculo.modelo}
          </p>
          <p className="text-sm text-app-muted" style={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
            {ot.descripcion_problema}
          </p>
        </>
      ) : (
        <>
          <div className="flex items-center gap-3">
            <div className="flex size-[46px] shrink-0 items-center justify-center rounded-[13px] bg-app-surface-3">
              <CarProfile size={23} className="text-app-faint" />
            </div>
            <div className="min-w-0">
              <p className="truncate text-base font-semibold tracking-[-0.02em]">
                {ot.vehiculo.marca} {ot.vehiculo.modelo}
              </p>
              <div className="mt-0.5 flex items-center gap-1.5">
                <span className="rounded bg-app-surface-3 px-1.5 py-0.5 font-mono text-[10.5px]">{ot.vehiculo.placa}</span>
                <span className="truncate text-xs text-app-muted">{ot.cliente.nombre}</span>
              </div>
            </div>
          </div>
          <p className="text-[13px] leading-[1.45] text-app-muted">{ot.descripcion_problema}</p>
          {ot.tecnico_asignado && (
            <div className="flex items-center gap-2 pt-2.5" style={{ borderTop: '1px solid var(--color-app-line-2)' }}>
              <div className="flex size-6 items-center justify-center rounded-full bg-app-surface-3 text-[10px] font-semibold">
                {ot.tecnico_asignado.nombre[0]}
              </div>
              <span className="text-xs text-app-muted">{ot.tecnico_asignado.nombre}</span>
              <span className="ml-auto text-xs text-lime-txt">Ver ›</span>
            </div>
          )}
        </>
      )}
    </button>
  )
}

function Vacio({ compact }: { compact?: boolean }) {
  return (
    <div
      className={compact ? 'rounded-[13px] p-4 text-center' : 'rounded-2xl p-6 text-center'}
      style={{ border: '1px dashed var(--color-app-line)' }}
    >
      <Tray size={compact ? 20 : 28} className="mx-auto text-app-faint" />
      <p className="mt-2 text-xs text-app-faint">{compact ? 'Sin órdenes' : 'No hay órdenes en esta etapa'}</p>
    </div>
  )
}

export default function KanbanPage() {
  const queryClient = useQueryClient()
  const showToast = useToastStore((s) => s.show)
  const [seleccionada, setSeleccionada] = useState<OrdenTrabajo | null>(null)
  const [activeStage, setActiveStage] = useState(0)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)

  const { data: ordenes = [], isLoading } = useQuery({
    queryKey: ['ordenes-trabajo'],
    queryFn: listarOrdenesTrabajo,
    refetchInterval: 15000,
    enabled: hasHydrated,
  })

  const mutation = useMutation({
    mutationFn: ({ id, estado }: { id: number; estado: Estado }) => cambiarEstado(id, estado),
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['ordenes-trabajo'] })
      setSeleccionada(null)
      const label = ESTADOS.find((e) => e.value === variables.estado)?.label
      showToast(`Orden movida a "${label}"`)
    },
  })

  const porEtapa = ESTADOS.map((e) => ordenes.filter((o) => o.estado === e.value))
  const etapaActiva = ESTADOS[activeStage]
  const itemsActivos = porEtapa[activeStage]

  return (
    <AppShell title="Tablero" subtitle={`${ordenes.length} ${ordenes.length === 1 ? 'orden' : 'órdenes'} en total`}>
      {isLoading && <p className="text-sm text-app-muted">Cargando…</p>}

      {/* Móvil: una etapa a la vez */}
      <div className="md:hidden">
        <div className="no-scrollbar -mx-[var(--pad-page)] flex gap-2 overflow-x-auto px-[var(--pad-page)]">
          {ESTADOS.map((e, i) => {
            const tono = TONO_CLASES[TONO_ESTADO[e.value]]
            const activo = i === activeStage
            return (
              <button
                key={e.value}
                type="button"
                onClick={() => setActiveStage(i)}
                className={
                  activo
                    ? `flex h-[38px] shrink-0 items-center gap-1.5 rounded-full px-3.5 text-[11px] font-semibold ${tono.bg} ${tono.fg}`
                    : 'flex h-[38px] shrink-0 items-center gap-1.5 rounded-full border border-app-line bg-app-surface px-3.5 text-[11px] font-semibold text-app-muted'
                }
              >
                {e.label}
                <span className="opacity-60">{porEtapa[i].length}</span>
              </button>
            )
          })}
        </div>

        <div className="mt-3 flex items-center justify-between rounded-2xl bg-app-surface p-2" style={{ border: '1px solid var(--color-app-line)' }}>
          <button
            type="button"
            disabled={activeStage === 0}
            onClick={() => setActiveStage((s) => Math.max(0, s - 1))}
            className="flex size-11 items-center justify-center rounded-xl text-app-faint disabled:opacity-30"
          >
            <CaretLeft size={18} />
          </button>
          <div className="flex flex-col items-center">
            <div className="flex items-center gap-1.5">
              <span className={`size-2 rounded-full ${TONO_CLASES[TONO_ESTADO[etapaActiva.value]].dot}`} />
              <span className="text-[15px] font-semibold">{etapaActiva.label}</span>
            </div>
            <span className="text-[11.5px] text-app-faint">
              {itemsActivos.length} {itemsActivos.length === 1 ? 'orden' : 'órdenes'}
            </span>
          </div>
          <button
            type="button"
            disabled={activeStage === ESTADOS.length - 1}
            onClick={() => setActiveStage((s) => Math.min(ESTADOS.length - 1, s + 1))}
            className="flex size-11 items-center justify-center rounded-xl text-app-faint disabled:opacity-30"
          >
            <CaretRight size={18} />
          </button>
        </div>

        <div className="mt-3 flex gap-[5px]">
          {ESTADOS.map((_, i) => (
            <button
              key={i}
              type="button"
              onClick={() => setActiveStage(i)}
              className="h-1 flex-1 rounded-full"
              style={{ background: i === activeStage ? 'var(--color-lime-500)' : i < activeStage ? 'var(--color-app-line)' : 'var(--color-app-line-2)' }}
            />
          ))}
        </div>

        <div className="mt-4 flex flex-col gap-2.5">
          {itemsActivos.map((ot) => (
            <CardOt key={ot.id} ot={ot} onClick={() => setSeleccionada(ot)} />
          ))}
          {itemsActivos.length === 0 && <Vacio />}
        </div>
      </div>

      {/* Escritorio: tablero real */}
      <div
        className="no-scrollbar hidden gap-3 overflow-x-auto md:grid min-[1780px]:overflow-visible"
        style={{ gridAutoFlow: 'column', gridAutoColumns: 'minmax(238px,1fr)', height: 'max(560px, calc(100vh - 232px))' }}
      >
        {ESTADOS.map((e, i) => {
          const tono = TONO_CLASES[TONO_ESTADO[e.value]]
          const items = porEtapa[i]
          return (
            <section
              key={e.value}
              className="flex min-h-0 flex-col rounded-2xl bg-app-surface-2 p-2.5"
              style={{ border: '1px solid var(--color-app-line-2)' }}
            >
              <header className="flex items-center gap-2 px-1 pb-2">
                <span className={`size-[7px] rounded-full ${tono.dot}`} />
                <h2 className="text-[12.5px] font-semibold">{e.label}</h2>
                <span className="ml-auto text-[11.5px] text-app-faint">{items.length}</span>
              </header>
              <div className="no-scrollbar flex flex-1 flex-col gap-2 overflow-y-auto">
                {items.map((ot) => (
                  <CardOt key={ot.id} ot={ot} compact onClick={() => setSeleccionada(ot)} />
                ))}
                {items.length === 0 && <Vacio compact />}
              </div>
            </section>
          )
        })}
      </div>

      {seleccionada && (
        <OtDetailSheet
          orden={seleccionada}
          onClose={() => setSeleccionada(null)}
          avanzando={mutation.isPending}
          onAvanzar={() => {
            const siguiente = ESTADOS[ESTADOS.findIndex((e) => e.value === seleccionada.estado) + 1]
            if (siguiente) mutation.mutate({ id: seleccionada.id, estado: siguiente.value })
          }}
        />
      )}
    </AppShell>
  )
}
