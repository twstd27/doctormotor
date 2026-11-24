import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import AppShell, { iniciales } from '../components/AppShell'
import { Placa, ProgresoEstados, etiquetaEstado, pasoDe, saludo } from '../components/ui'
import { historialEstados, misOrdenesTrabajo, misVehiculos } from '../lib/garaje'
import { ESTADOS, type Estado } from '../lib/ordenesTrabajo'
import { hayContactoTaller, llamarTaller, whatsappTaller } from '../lib/taller'
import { useAuthStore } from '../store/auth'

function cuando(iso: string) {
  const d = new Date(iso)
  const hora = d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit', hour12: false })
  if (d.toDateString() === new Date().toDateString()) return `Hoy ${hora}`
  return `${d.toLocaleDateString('es-BO', { day: 'numeric', month: 'short' })} ${hora}`
}

export default function ClienteInicio() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)

  const { data: ordenes = [], isLoading } = useQuery({
    queryKey: ['me', 'ordenes-trabajo'],
    queryFn: misOrdenesTrabajo,
    enabled: hasHydrated && !!user,
    refetchInterval: 20000,
  })
  const { data: vehiculos = [] } = useQuery({
    queryKey: ['me', 'vehiculos'],
    queryFn: misVehiculos,
    enabled: hasHydrated && !!user,
  })

  const activas = ordenes.filter((o) => o.estado !== 'entregado' && o.estado !== 'cancelado')
  // Primero la que espera la respuesta del cliente; si no, la más reciente.
  const principal = activas.find((o) => o.presupuestos[0]?.estado === 'enviado') ?? activas[0]
  const pendiente = principal?.presupuestos[0]?.estado === 'enviado' ? principal.presupuestos[0] : null

  const { data: historial = [] } = useQuery({
    queryKey: ['ot-historial-estados', principal?.id],
    queryFn: () => historialEstados(principal!.id).catch(() => []),
    enabled: !!principal,
  })

  if (!user) return null

  const ultimos = [...historial].sort((a, b) => b.created_at.localeCompare(a.created_at)).slice(0, 2)
  const color = pendiente ? 'var(--color-amb)' : undefined

  return (
    <AppShell
      title={`Hola, ${saludo(user.nombre)}`}
      subtitle="Doctor Motor · Mustang's Garage"
      rightAction={
        <span className="flex size-11 items-center justify-center rounded-full bg-lime-500/15 text-sm font-bold text-lime-500">
          {iniciales(user.nombre)}
        </span>
      }
    >
      {isLoading && <p className="text-sm text-app-muted">Cargando…</p>}

      {principal ? (
        <div
          className="dm-card overflow-hidden"
          style={{
            padding: 0,
            borderColor: pendiente ? 'rgba(255,181,71,.4)' : 'rgba(68, 214, 44,.3)',
            background: pendiente ? '#17150F' : undefined,
          }}
        >
          <div className="flex flex-col gap-3.5 p-[18px]">
            <div className="flex items-center gap-3">
              <div className="min-w-0 flex-1">
                <p className="dm-display truncate text-xl font-bold">
                  {principal.vehiculo.marca} {principal.vehiculo.modelo}
                </p>
                <p className="mt-0.5 text-[13px] text-app-faint">
                  En el taller desde el{' '}
                  {new Date(principal.fecha_ingreso).toLocaleDateString('es-BO', { day: 'numeric', month: 'short' })}
                </p>
              </div>
              <Placa placa={principal.vehiculo.placa} className="text-[13px]" />
            </div>
            <div>
              <div className="mb-2 flex justify-between text-[13px]">
                <span className="font-bold" style={{ color: color ?? 'var(--color-lime-500)' }}>
                  {pendiente ? 'Esperando tu aprobación' : etiquetaEstado(principal.estado)}
                </span>
                <span className="text-app-faint">
                  Paso {pasoDe(principal.estado)} de {ESTADOS.length}
                </span>
              </div>
              <ProgresoEstados estado={principal.estado} color={color} />
            </div>
            <p className="text-sm leading-normal text-app-muted">
              {pendiente
                ? 'El técnico revisó tu vehículo. Revisa qué trabajos quieres hacer: el trabajo empieza cuando apruebes.'
                : principal.descripcion_problema}
            </p>
          </div>
          <Link
            to={pendiente ? `/presupuestos/${pendiente.id}` : `/seguimiento/${principal.id}`}
            className="dm-btn p"
            style={{ borderRadius: 0, height: 60 }}
          >
            {pendiente ? 'Revisar presupuesto' : 'Ver cómo va'}
          </Link>
        </div>
      ) : (
        !isLoading && (
          <div className="dm-card">
            <p className="dm-display text-lg font-bold">
              {vehiculos.length > 0 ? 'Tus vehículos no están en el taller' : 'Todavía no tienes vehículos registrados'}
            </p>
            <p className="mt-1 text-sm text-app-faint">
              Cuando dejes un vehículo vas a poder seguir su avance desde aquí, sin llamar al taller.
            </p>
            {vehiculos.length > 0 && (
              <Link to="/vehiculos" className="dm-btn s sm mt-3.5">
                Ver mis vehículos
              </Link>
            )}
          </div>
        )
      )}

      {principal && ultimos.length > 0 && (
        <Link to={`/seguimiento/${principal.id}`} className="dm-card flex flex-col gap-3">
          <div className="flex items-center justify-between">
            <h2 className="dm-display text-base font-semibold">Lo último</h2>
            <span className="text-[13px] font-semibold text-lime-500">Ver todo ›</span>
          </div>
          {ultimos.map((h, i) => (
            <div key={h.id} className="flex gap-3">
              <span
                className="mt-1.5 size-2 shrink-0 rounded-full"
                style={{ background: i === 0 ? 'var(--color-cya)' : '#5B6470' }}
              />
              <div>
                <p className="text-sm font-medium">{etiquetaEstado(h.estado_nuevo as Estado)}</p>
                <p className="text-[12.5px] text-app-faint">{cuando(h.created_at)}</p>
              </div>
            </div>
          ))}
        </Link>
      )}

      {hayContactoTaller && (
        <div className="dm-card flex flex-col gap-3">
          <h2 className="dm-display text-base font-semibold">Habla con el taller</h2>
          <div className="grid grid-cols-2 gap-2">
            <a href={whatsappTaller('Hola, quiero consultar por mi vehículo.')} className="dm-btn s sm" target="_blank" rel="noreferrer">
              WhatsApp
            </a>
            <a href={llamarTaller()} className="dm-btn s sm">
              Llamar
            </a>
          </div>
        </div>
      )}
    </AppShell>
  )
}
