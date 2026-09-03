import { CaretRight, Camera, Signature } from '@phosphor-icons/react'
import { useNavigate } from 'react-router-dom'
import {
  ESTADOS,
  TONO_CLASES,
  TONO_ESTADO,
  siguienteEstado,
  type OrdenTrabajo,
} from '../lib/ordenesTrabajo'
import BottomSheet from './BottomSheet'

interface OtDetailSheetProps {
  orden: OrdenTrabajo
  onClose: () => void
  onAvanzar: () => void
  avanzando: boolean
}

export default function OtDetailSheet({ orden, onClose, onAvanzar, avanzando }: OtDetailSheetProps) {
  const navigate = useNavigate()
  const tono = TONO_CLASES[TONO_ESTADO[orden.estado]]
  const estadoLabel = ESTADOS.find((e) => e.value === orden.estado)?.label ?? orden.estado
  const siguiente = siguienteEstado(orden.estado)
  const siguienteLabel = siguiente ? ESTADOS.find((e) => e.value === siguiente)?.label : null

  return (
    <BottomSheet onClose={onClose}>
      <p className="font-mono text-[11px] tracking-[0.04em] text-app-faint">{orden.codigo}</p>
      <div className="mt-1 flex flex-wrap items-center gap-2">
        <h2 className="text-xl font-semibold tracking-[-0.02em]">
          {orden.vehiculo.marca} {orden.vehiculo.modelo}
        </h2>
        <span className="rounded-md bg-app-surface-3 px-2 py-0.5 font-mono text-[11px]">{orden.vehiculo.placa}</span>
        <span className={`rounded-full px-2.5 py-1 text-xs ${tono.bg} ${tono.fg}`}>{estadoLabel}</span>
      </div>

      <p className="mt-3 text-sm leading-[1.55] text-app-muted">{orden.descripcion_problema}</p>

      <div className="mt-4 grid grid-cols-2 gap-2.5 rounded-xl bg-app-surface-2 p-3">
        <div>
          <p className="text-[11px] text-app-faint">Cliente</p>
          <p className="mt-0.5 text-[13.5px] font-medium">{orden.cliente.nombre}</p>
        </div>
        <div>
          <p className="text-[11px] text-app-faint">Ingreso</p>
          <p className="mt-0.5 text-[13.5px] font-medium">
            {new Date(orden.fecha_ingreso).toLocaleDateString('es-BO')}
          </p>
        </div>
        <div>
          <p className="text-[11px] text-app-faint">Técnico</p>
          <p className="mt-0.5 text-[13.5px] font-medium">{orden.tecnico_asignado?.nombre ?? 'Sin asignar'}</p>
        </div>
        <div>
          <p className="text-[11px] text-app-faint">Kilometraje</p>
          <p className="mt-0.5 text-[13.5px] font-medium">{orden.kilometraje_ingreso.toLocaleString('es-BO')} km</p>
        </div>
      </div>

      <div className="mt-4 flex flex-col gap-2">
        {orden.estado === 'recepcionado' && (
          <button
            type="button"
            onClick={() => navigate(`/ordenes-trabajo/${orden.id}/inspeccion`)}
            className="flex h-14 items-center gap-3 rounded-[13px] bg-app-surface-2 px-3.5 text-left"
            style={{ border: '1px solid var(--color-app-line)' }}
          >
            <Signature weight="fill" size={20} className="text-lime-500" />
            <span className="flex-1 text-sm font-medium">Inspección de ingreso + firma</span>
            <CaretRight size={16} className="text-app-faint" />
          </button>
        )}
        <button
          type="button"
          onClick={() => navigate(`/ordenes-trabajo/${orden.id}/evidencias`)}
          className="flex h-14 items-center gap-3 rounded-[13px] bg-app-surface-2 px-3.5 text-left"
          style={{ border: '1px solid var(--color-app-line)' }}
        >
          <Camera weight="fill" size={20} className="text-cya" />
          <span className="flex-1 text-sm font-medium">Fotos y videos</span>
          <CaretRight size={16} className="text-app-faint" />
        </button>
      </div>

      {siguiente ? (
        <button
          type="button"
          disabled={avanzando}
          onClick={onAvanzar}
          className="mt-5 h-[54px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
          style={{ boxShadow: 'var(--shadow-cta-lime)' }}
        >
          {avanzando ? 'Moviendo…' : `Pasar a "${siguienteLabel}"`}
        </button>
      ) : (
        <div className="mt-5 flex h-[54px] w-full items-center justify-center rounded-xl bg-app-surface-3 text-sm text-app-faint">
          Esta OT ya llegó al final del flujo
        </div>
      )}
    </BottomSheet>
  )
}
