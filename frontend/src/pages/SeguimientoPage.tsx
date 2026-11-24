import { useQuery } from '@tanstack/react-query'
import { Check } from '@phosphor-icons/react'
import { Link, Navigate, useParams } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { Placa } from '../components/ui'
import { historialEstados, misOrdenesTrabajo } from '../lib/garaje'
import { ESTADOS, evidenciasDeOtServidor } from '../lib/ordenesTrabajo'
import { hayContactoTaller, whatsappTaller } from '../lib/taller'
import { useAuthStore } from '../store/auth'

const TEXTO_PASO: Record<string, string> = {
  recepcionado: 'Recibimos tu vehículo',
  en_diagnostico: 'Estamos revisando qué le pasa',
  esperando_aprobacion: 'El trabajo empieza cuando apruebes',
  en_reparacion: 'Estamos trabajando en tu vehículo',
  control_calidad: 'Probamos que todo quede bien',
  listo_entrega: 'Te avisamos por WhatsApp',
  entregado: 'Vehículo entregado',
}

const NOMBRE_PASO: Record<string, string> = {
  recepcionado: 'Recibido en el taller',
  listo_entrega: 'Listo para recoger',
}

function cuando(iso: string) {
  const d = new Date(iso)
  const hora = d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit', hour12: false })
  if (d.toDateString() === new Date().toDateString()) return `Hoy ${hora}`
  return `${d.toLocaleDateString('es-BO', { day: 'numeric', month: 'short' })} ${hora}`
}

export default function SeguimientoPage() {
  const { id } = useParams<{ id: string }>()
  const otId = Number(id)
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)

  const { data: ordenes = [], isLoading } = useQuery({
    queryKey: ['me', 'ordenes-trabajo'],
    queryFn: misOrdenesTrabajo,
    enabled: hasHydrated && !!user,
    refetchInterval: 20000,
  })
  const { data: historial = [] } = useQuery({
    queryKey: ['ot-historial-estados', otId],
    queryFn: () => historialEstados(otId).catch(() => []),
    enabled: hasHydrated && !!user,
  })
  const { data: evidencias = [] } = useQuery({
    queryKey: ['ot-evidencias', otId],
    queryFn: () => evidenciasDeOtServidor(otId).catch(() => []),
    enabled: hasHydrated && !!user,
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />

  const ot = ordenes.find((o) => o.id === otId)
  if (!ot) {
    return (
      <AppShell title="Seguimiento" back={{ label: 'Inicio', to: '/' }}>
        <p className="text-sm text-app-muted">{isLoading ? 'Cargando…' : 'No encontramos esta orden.'}</p>
      </AppShell>
    )
  }

  const idxActual = ESTADOS.findIndex((e) => e.value === ot.estado)
  const presupuestoPendiente = ot.presupuestos[0]?.estado === 'enviado' ? ot.presupuestos[0] : null
  const fotos = evidencias.filter((e) => e.tipo === 'foto').slice(0, 6)

  return (
    <AppShell
      title={`${ot.vehiculo.marca} ${ot.vehiculo.modelo}`}
      back={{ label: 'Inicio', to: '/' }}
      subtitle={
        <span className="mt-1 flex items-center gap-2">
          <span className="font-mono">{ot.codigo}</span>
          <Placa placa={ot.vehiculo.placa} />
        </span>
      }
    >
      <div className="dm-card" style={{ padding: '18px 16px 2px' }}>
        {ESTADOS.map((paso, i) => {
          const hecho = i < idxActual || (i === idxActual && paso.value === 'entregado')
          const actual = i === idxActual && !hecho
          const aprobacion = paso.value === 'esperando_aprobacion'
          const registro = historial.find((h) => h.estado_nuevo === paso.value)
          const ultimo = i === ESTADOS.length - 1
          return (
            <div key={paso.value} className="flex gap-3.5">
              <div className="flex flex-col items-center">
                <span
                  className="flex size-[26px] shrink-0 items-center justify-center rounded-full"
                  style={
                    hecho
                      ? { background: 'rgba(68, 214, 44,.15)', color: 'var(--color-lime-500)' }
                      : actual
                        ? {
                            border: `2px solid ${aprobacion ? 'var(--color-amb)' : 'var(--color-lime-500)'}`,
                            boxShadow: `0 0 0 4px ${aprobacion ? 'rgba(252, 170, 43,.15)' : 'rgba(68, 214, 44,.15)'}`,
                          }
                        : { border: '2px solid #373A38' }
                  }
                >
                  {hecho && <Check weight="bold" size={14} />}
                </span>
                {!ultimo && <span className="w-0.5 grow" style={{ minHeight: 26, background: hecho ? '#2E9B20' : 'var(--color-app-line)' }} />}
              </div>
              <div className="pb-5">
                <p
                  className="text-[15px]"
                  style={{
                    fontWeight: actual ? 700 : 600,
                    color: actual ? (aprobacion ? 'var(--color-amb)' : 'var(--color-app-text)') : hecho ? undefined : 'var(--color-app-muted)',
                  }}
                >
                  {NOMBRE_PASO[paso.value] ?? paso.label}
                </p>
                {(hecho || actual) && registro ? (
                  <p className="mt-0.5 text-[12.5px] text-app-faint">{cuando(registro.created_at)}</p>
                ) : (
                  (actual || paso.value === 'listo_entrega') && (
                    <p className="mt-0.5 text-[12.5px] text-app-faint">{TEXTO_PASO[paso.value]}</p>
                  )
                )}
                {actual && aprobacion && presupuestoPendiente && (
                  <Link to={`/presupuestos/${presupuestoPendiente.id}`} className="dm-btn p sm mt-2.5 px-4">
                    Revisar presupuesto
                  </Link>
                )}
              </div>
            </div>
          )
        })}
      </div>

      {fotos.length > 0 && (
        <Link to={`/ordenes-trabajo/${otId}/evidencias`} className="dm-card flex flex-col gap-3">
          <div className="flex items-center justify-between">
            <h2 className="dm-display text-base font-semibold">Fotos del taller</h2>
            <span className="text-[13px] font-semibold text-lime-500">Ver todas ›</span>
          </div>
          <div className="grid grid-cols-3 gap-2">
            {fotos.map((f) => (
              <img
                key={f.id}
                src={f.url}
                alt={f.etiqueta ?? 'Foto del trabajo'}
                className="aspect-square w-full rounded-xl object-cover"
                style={{ border: '1px solid var(--color-app-line)' }}
              />
            ))}
          </div>
        </Link>
      )}

      <div className="dm-card" style={{ padding: '4px 16px' }}>
        <div className="dm-li justify-between">
          <span className="text-sm text-app-muted">Técnico</span>
          <span className="text-sm font-medium">{ot.tecnico_asignado?.nombre ?? 'Por asignar'}</span>
        </div>
        <div className="dm-li justify-between gap-6">
          <span className="text-sm text-app-muted">Motivo</span>
          <span className="text-right text-sm font-medium">{ot.descripcion_problema}</span>
        </div>
      </div>

      {hayContactoTaller && (
        <a
          href={whatsappTaller(`Hola, consulto por mi orden ${ot.codigo} (${ot.vehiculo.placa}).`)}
          className="dm-btn s sm"
          target="_blank"
          rel="noreferrer"
        >
          Escribir al taller
        </a>
      )}
    </AppShell>
  )
}
