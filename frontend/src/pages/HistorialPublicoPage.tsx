import { useQuery } from '@tanstack/react-query'
import { CalendarBlank, CarProfile, CheckCircle, Gauge, UserCircle, Wrench } from '@phosphor-icons/react'
import { useParams } from 'react-router-dom'
import { ApiError } from '../lib/api'
import { historialPublicoPorQr } from '../lib/garaje'
import { ESTADOS, TONO_CLASES, TONO_ESTADO } from '../lib/ordenesTrabajo'

export default function HistorialPublicoPage() {
  const { token } = useParams<{ token: string }>()

  const { data, isLoading, error } = useQuery({
    queryKey: ['qr', token],
    queryFn: () => historialPublicoPorQr(token!),
    enabled: !!token,
    retry: false,
  })

  const noEncontrado = error instanceof ApiError && error.status === 404

  return (
    <div className="min-h-svh bg-app-bg px-5 py-10 text-app-text">
      <div className="mx-auto max-w-[560px]">
        <p className="font-mono text-[11px] tracking-[0.04em] text-app-faint">DOCTOR MOTOR · MUSTANG'S GARAGE</p>
        <h1 className="mt-1 text-[24px] font-semibold tracking-[-0.02em]">Historial del vehículo</h1>

        {isLoading && <p className="mt-6 text-sm text-app-muted">Cargando…</p>}

        {noEncontrado && (
          <p className="mt-6 rounded-2xl bg-app-surface p-4 text-sm text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
            Este código QR no corresponde a ningún vehículo registrado.
          </p>
        )}

        {error && !noEncontrado && (
          <p className="mt-6 rounded-2xl bg-app-surface p-4 text-sm text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
            No se pudo cargar el historial. Intenta de nuevo en un momento.
          </p>
        )}

        {data && (
          <>
            <div className="mt-5 rounded-2xl bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
              <div className="flex items-center gap-3">
                <div className="flex size-[46px] shrink-0 items-center justify-center rounded-[13px] bg-app-surface-3">
                  <CarProfile size={23} className="text-app-faint" />
                </div>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-base font-semibold tracking-[-0.02em]">
                    {data.vehiculo.marca} {data.vehiculo.modelo} ({data.vehiculo.anio})
                  </p>
                  <span className="mt-0.5 inline-block rounded bg-app-surface-3 px-1.5 py-0.5 font-mono text-[10.5px]">{data.vehiculo.placa}</span>
                  <span className="ml-1.5 text-[12px] text-app-muted">{data.vehiculo.color}</span>
                </div>
              </div>
            </div>

            <div className="mt-6 flex flex-col gap-3">
              {data.ordenes.map((ot) => {
                const tono = TONO_CLASES[TONO_ESTADO[ot.estado]]
                const label = ESTADOS.find((e) => e.value === ot.estado)?.label ?? ot.estado
                return (
                  <div key={ot.codigo} className="rounded-2xl bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
                    <div className="flex items-center justify-between gap-2">
                      <p className="font-mono text-[12px] text-app-faint">{ot.codigo}</p>
                      <span className={`inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-medium ${tono.bg} ${tono.fg} ${tono.border}`}>
                        <span className={`size-1.5 rounded-full ${tono.dot}`} />
                        {label}
                      </span>
                    </div>
                    <p className="mt-2 text-sm">{ot.descripcion_problema}</p>
                    <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-[12px] text-app-muted">
                      <span className="inline-flex items-center gap-1">
                        <CalendarBlank size={14} /> Ingreso: {new Date(ot.fecha_ingreso).toLocaleDateString('es-BO')}
                      </span>
                      {ot.fecha_entrega_real && (
                        <span className="inline-flex items-center gap-1">
                          <CheckCircle size={14} /> Entrega: {new Date(ot.fecha_entrega_real).toLocaleDateString('es-BO')}
                        </span>
                      )}
                      <span className="inline-flex items-center gap-1">
                        <Gauge size={14} /> {ot.kilometraje_ingreso.toLocaleString('es-BO')} km
                      </span>
                      {ot.tecnico_asignado && (
                        <span className="inline-flex items-center gap-1">
                          <UserCircle size={14} /> {ot.tecnico_asignado}
                        </span>
                      )}
                    </div>
                  </div>
                )
              })}

              {data.ordenes.length === 0 && (
                <p className="rounded-2xl bg-app-surface p-4 text-sm text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
                  <Wrench size={16} className="mr-1.5 inline-block text-app-faint" />
                  Este vehículo todavía no tiene órdenes de trabajo registradas.
                </p>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  )
}
