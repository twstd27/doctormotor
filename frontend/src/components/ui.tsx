import type { ReactNode } from 'react'
import { ESTADOS, TONO_CLASES, TONO_ESTADO, type Estado } from '../lib/ordenesTrabajo'

/** Palabra de marca «DOCTOR MOTOR» para la línea bajo el saludo. */
export function Marca() {
  return (
    <span className="dm-marca">
      Doctor <b>Motor</b>
    </span>
  )
}

/** Subtítulo de las pantallas de inicio: marca · texto (fecha, resumen…). */
export function SubtituloMarca({ children }: { children: ReactNode }) {
  return (
    <span className="flex items-center gap-2.5">
      <Marca />
      <span aria-hidden="true">·</span>
      <span className="min-w-0 truncate">{children}</span>
    </span>
  )
}

export function Placa({ placa, className = '' }: { placa: string; className?: string }) {
  return <span className={`dm-plate text-[12px] ${className}`}>{placa}</span>
}

/** Barra de 7 pasos: hechos en lima oscuro, el actual en lima. `color` pinta el actual (ej. ámbar). */
export function ProgresoEstados({ estado, color }: { estado: Estado; color?: string }) {
  const idx = Math.max(0, ESTADOS.findIndex((e) => e.value === estado))
  return (
    <div className="dm-prog" role="img" aria-label={`Paso ${idx + 1} de ${ESTADOS.length}`}>
      {ESTADOS.map((e, i) => (
        <span
          key={e.value}
          className={i < idx ? 'd' : i === idx ? 'n' : ''}
          style={i === idx && color ? { background: color } : undefined}
        />
      ))}
    </div>
  )
}

export function EstadoBadge({ estado }: { estado: Estado }) {
  const tono = TONO_CLASES[TONO_ESTADO[estado]]
  const label = ESTADOS.find((e) => e.value === estado)?.label ?? estado
  return (
    <span className={`inline-flex h-6 items-center gap-1.5 rounded-full px-2.5 text-[11.5px] font-semibold ${tono.bg} ${tono.fg}`}>
      <span className={`size-1.5 rounded-full ${tono.dot}`} />
      {label}
    </span>
  )
}

export function pasoDe(estado: Estado) {
  return Math.max(0, ESTADOS.findIndex((e) => e.value === estado)) + 1
}

export function etiquetaEstado(estado: Estado) {
  return ESTADOS.find((e) => e.value === estado)?.label ?? estado
}

export function saludo(nombre: string) {
  return nombre.split(' ')[0]
}

export function fechaCorta(d = new Date()) {
  const s = d.toLocaleDateString('es-BO', { weekday: 'long', day: 'numeric', month: 'short' })
  return s.charAt(0).toUpperCase() + s.slice(1).replace('.', '')
}
