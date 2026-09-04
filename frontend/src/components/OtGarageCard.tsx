import { CaretDown, CaretUp, FilePdf, Images } from '@phosphor-icons/react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ESTADOS, pasoCompletado, type OrdenTrabajoCliente } from '../lib/garaje'

interface OtGarageCardProps {
  ot: OrdenTrabajoCliente
  onDescargarHistorial: (vehiculoId: number) => void
}

export default function OtGarageCard({ ot, onDescargarHistorial }: OtGarageCardProps) {
  const [expandido, setExpandido] = useState(false)
  const pasoActualIdx = ESTADOS.findIndex((p) => p.value === ot.estado)

  return (
    <article
      className="w-[88vw] shrink-0 rounded-[18px] bg-app-surface p-4 md:w-auto"
      style={{ border: '1px solid var(--color-app-line)', boxShadow: 'var(--shadow-card)', scrollSnapAlign: 'start' }}
    >
      <p className="font-mono text-[11px] tracking-[0.04em] text-app-faint">{ot.codigo}</p>
      <h2 className="mt-0.5 text-[19px] font-semibold tracking-[-0.02em]">
        {ot.vehiculo.marca} {ot.vehiculo.modelo}
      </h2>
      <p className="text-xs text-app-faint">{ot.vehiculo.placa}</p>
      <p className="mt-2 text-[13px] leading-[1.5] text-app-muted">{ot.descripcion_problema}</p>

      <div className="mt-3.5">
        <div className="flex items-center gap-1.5">
          <span className="size-2.5 rounded-full" style={{ background: 'var(--color-lime-500)', boxShadow: '0 0 0 4px rgba(143,214,46,.18)' }} />
          <p className="text-[13.5px] font-semibold">{ESTADOS[pasoActualIdx]?.label}</p>
          <span className="ml-auto text-[11px] text-app-faint">
            Paso {pasoActualIdx + 1} de {ESTADOS.length}
          </span>
        </div>
        <div className="mt-2 flex gap-[5px]">
          {ESTADOS.map((_, i) => (
            <span
              key={i}
              className="h-1 flex-1 rounded-full"
              style={{ background: i <= pasoActualIdx ? 'var(--color-lime-500)' : 'var(--color-app-line)' }}
            />
          ))}
        </div>

        <button
          type="button"
          onClick={() => setExpandido((v) => !v)}
          className="mt-2.5 flex items-center gap-1 text-xs font-medium text-lime-txt"
        >
          {expandido ? 'Ocultar pasos' : 'Ver todos los pasos'}
          {expandido ? <CaretUp size={13} /> : <CaretDown size={13} />}
        </button>

        {expandido && (
          <ol className="dm-in mt-3 flex flex-col">
            {ESTADOS.map((paso, idx) => {
              const completado = pasoCompletado(ot.estado, paso.value)
              const esActual = idx === pasoActualIdx
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
        )}
      </div>

      <div className="mt-3 grid grid-cols-2 gap-2">
        <Link
          to={`/ordenes-trabajo/${ot.id}/evidencias`}
          className="flex h-12 items-center justify-center gap-2 rounded-xl text-sm font-medium text-cor"
          style={{ border: '1px solid var(--color-app-line)' }}
        >
          <Images size={18} />
          Ver fotos
        </Link>
        <button
          type="button"
          onClick={() => onDescargarHistorial(ot.vehiculo.id)}
          className="flex h-12 items-center justify-center gap-2 rounded-xl text-sm font-medium text-cor"
          style={{ border: '1px solid var(--color-app-line)' }}
        >
          <FilePdf size={18} />
          Historial
        </button>
      </div>
    </article>
  )
}
