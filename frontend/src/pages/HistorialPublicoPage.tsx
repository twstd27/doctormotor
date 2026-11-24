import { useQuery } from '@tanstack/react-query'
import { CarProfile, ShieldCheck } from '@phosphor-icons/react'
import { useParams } from 'react-router-dom'
import { Placa } from '../components/ui'
import { ApiError } from '../lib/api'
import { historialPublicoPorQr } from '../lib/garaje'
import { ESTADOS } from '../lib/ordenesTrabajo'
import { hayContactoTaller, whatsappTaller } from '../lib/taller'

function fecha(iso: string) {
  return new Date(iso).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

/** Historial público del vehículo (QR pegado en el auto): sin datos del dueño ni precios. */
export default function HistorialPublicoPage() {
  const { token } = useParams<{ token: string }>()

  const { data, isLoading, error } = useQuery({
    queryKey: ['qr', token],
    queryFn: () => historialPublicoPorQr(token!),
    enabled: !!token,
    retry: false,
  })

  const noEncontrado = error instanceof ApiError && error.status === 404
  const km = data?.ordenes.reduce((max, o) => Math.max(max, o.kilometraje_ingreso), 0) ?? 0

  return (
    <div className="min-h-svh bg-app-bg text-app-text">
      <div className="mx-auto max-w-[520px]">
        <div className="px-5 pt-7 pb-[18px]" style={{ borderBottom: '1px solid var(--color-app-line-2)' }}>
          <p className="font-mono text-[11px] tracking-[0.1em] text-app-faint uppercase">Doctor Motor · Mustang's Garage</p>
          <h1 className="dm-display mt-2.5 text-[26px] leading-[1.15] font-bold">Historial del vehículo</h1>
        </div>

        <div className="flex flex-col gap-3.5 px-5 py-[18px]">
          {isLoading && <p className="text-sm text-app-muted">Cargando…</p>}

          {noEncontrado && <p className="dm-card text-sm text-app-muted">Este código QR no corresponde a ningún vehículo registrado.</p>}

          {error && !noEncontrado && (
            <p className="dm-card text-sm text-app-muted">No se pudo cargar el historial. Intenta de nuevo en un momento.</p>
          )}

          {data && (
            <>
              <div className="dm-card flex items-center gap-3.5">
                <span className="flex size-14 shrink-0 items-center justify-center rounded-[14px] bg-app-surface-3">
                  <CarProfile size={28} className="text-app-muted" />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="dm-display text-lg font-bold">
                    {data.vehiculo.marca} {data.vehiculo.modelo} {data.vehiculo.anio}
                  </p>
                  <div className="mt-1.5 flex items-center gap-2">
                    <Placa placa={data.vehiculo.placa} />
                    <span className="text-[13px] text-app-faint">{data.vehiculo.color}</span>
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2.5">
                <div className="dm-card" style={{ padding: 14 }}>
                  <p className="text-xs text-app-faint">Servicios</p>
                  <p className="mt-1 font-mono text-[22px] font-bold">{data.ordenes.length}</p>
                </div>
                <div className="dm-card" style={{ padding: 14 }}>
                  <p className="text-xs text-app-faint">Último km</p>
                  <p className="mt-1 font-mono text-[22px] font-bold">{km.toLocaleString('es-BO')}</p>
                </div>
              </div>

              <div className="dm-card" style={{ padding: '4px 16px' }}>
                {data.ordenes.map((ot) => {
                  const enCurso = ot.estado !== 'entregado' && ot.estado !== 'cancelado'
                  return (
                    <div key={ot.codigo} className="dm-li items-start" style={{ padding: '16px 0' }}>
                      <span className="w-[70px] shrink-0 pt-0.5 font-mono text-xs text-app-faint">
                        {fecha(ot.fecha_entrega_real ?? ot.fecha_ingreso)}
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="font-semibold">{ot.descripcion_problema}</p>
                        <p className="mt-0.5 text-[12.5px] text-app-faint">{ot.kilometraje_ingreso.toLocaleString('es-BO')} km</p>
                        {enCurso && (
                          <span className="mt-2 inline-flex h-6 items-center gap-1.5 rounded-full bg-ok-bg px-2.5 text-[11.5px] font-semibold text-ok-txt">
                            <span className="size-1.5 rounded-full bg-lime-500" />
                            {ESTADOS.find((e) => e.value === ot.estado)?.label ?? ot.estado}
                          </span>
                        )}
                      </div>
                    </div>
                  )
                })}
                {data.ordenes.length === 0 && (
                  <p className="py-4 text-sm text-app-muted">Este vehículo todavía no tiene servicios registrados.</p>
                )}
              </div>

              <div className="flex items-center gap-2.5 px-0.5 py-1 text-xs text-app-faint">
                <ShieldCheck size={16} className="shrink-0 text-lime-500" />
                Registros cargados por el taller al cerrar cada orden. No muestra datos del dueño ni precios.
              </div>

              {hayContactoTaller && (
                <a href={whatsappTaller('Hola, vi el historial de un vehículo.')} className="dm-btn s sm" target="_blank" rel="noreferrer">
                  Escribir al taller
                </a>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  )
}
