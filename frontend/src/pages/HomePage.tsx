import { useQuery } from '@tanstack/react-query'
import { CarProfile, Hourglass, SquaresFour, Wrench } from '@phosphor-icons/react'
import { Link, Navigate } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { misOrdenesTrabajo, misVehiculos } from '../lib/garaje'
import { listarOrdenesTrabajo } from '../lib/ordenesTrabajo'
import { useAuthStore } from '../store/auth'

const ROL_LABEL: Record<string, string> = {
  super_admin: 'Super admin',
  cajero: 'Cajero',
  operador_tecnico: 'Operador técnico',
  cliente: 'Cliente',
}

function StatCard({
  icon,
  tone,
  label,
  value,
  hint,
}: {
  icon: React.ReactNode
  tone: 'cya' | 'amb'
  label: string
  value: number
  hint: string
}) {
  const toneClass = tone === 'cya' ? 'text-cya' : 'text-amb'
  return (
    <div className="rounded-2xl bg-app-surface p-[18px]" style={{ border: '1px solid var(--color-app-line)' }}>
      <div className={`flex items-center gap-1.5 text-xs font-medium ${toneClass}`}>
        {icon}
        {label}
      </div>
      <p className="mt-2 text-[26px] font-semibold tracking-[-0.02em]">{value}</p>
      <p className="mt-1 text-[12.5px] text-app-muted">{hint}</p>
    </div>
  )
}

export default function HomePage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const esCliente = user?.rol === 'cliente'
  const esTecnico = user?.rol === 'operador_tecnico'
  const esAdmin = user?.rol === 'super_admin' || user?.rol === 'cajero'

  const { data: ordenes = [] } = useQuery({
    queryKey: ['ordenes-trabajo'],
    queryFn: listarOrdenesTrabajo,
    enabled: hasHydrated && !!user && !esCliente,
  })
  const { data: vehiculos = [] } = useQuery({
    queryKey: ['me', 'vehiculos'],
    queryFn: misVehiculos,
    enabled: hasHydrated && !!user && esCliente,
  })
  const { data: ordenesCliente = [] } = useQuery({
    queryKey: ['me', 'ordenes-trabajo'],
    queryFn: misOrdenesTrabajo,
    enabled: hasHydrated && !!user && esCliente,
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />
  if (!user) return null

  const activas = ordenes.filter((o) => o.estado !== 'entregado' && o.estado !== 'cancelado')
  const asignadasAMi = ordenes.filter((o) => o.tecnico_asignado?.id === user.id)
  const esperandoAprobacion = ordenes.filter((o) => o.estado === 'esperando_aprobacion')
  const enTaller = ordenesCliente.filter((o) => o.estado !== 'entregado' && o.estado !== 'cancelado')

  return (
    <AppShell title="Hola" subtitle={ROL_LABEL[user.rol] ?? user.rol}>
      <div className="grid gap-3.5 md:grid-cols-2">
        <div
          className="relative col-span-full overflow-hidden rounded-[18px] bg-app-surface p-[26px_22px]"
          style={{ border: '1px solid var(--color-app-line)', boxShadow: 'var(--shadow-card)' }}
        >
          <span
            className="pointer-events-none absolute -top-10 -right-10 size-[180px] rounded-full"
            style={{ background: 'rgba(143,214,46,.10)' }}
          />
          <p className="relative text-[13px] text-app-muted">Bienvenida/o</p>
          <h2 className="relative mt-1 text-2xl font-semibold tracking-[-0.02em]">{user.nombre.split(' ')[0]}</h2>
          <p className="relative mt-2 max-w-[34ch] text-sm leading-[1.5] text-app-muted">
            {esCliente
              ? 'Seguí el avance de tu vehículo y aprobá presupuestos sin llamar al taller.'
              : 'Revisá el tablero de órdenes de trabajo y avanzá cada vehículo por su etapa.'}
          </p>
          <Link
            to={esCliente ? '/garaje' : '/ordenes-trabajo'}
            className="relative mt-6 flex h-[54px] w-full max-w-[340px] items-center justify-center rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink"
            style={{ boxShadow: 'var(--shadow-cta-lime)' }}
          >
            {esCliente ? 'Ver mi garaje' : 'Abrir tablero'}
          </Link>
        </div>

        {esCliente ? (
          <>
            <StatCard icon={<CarProfile size={16} />} tone="cya" label="VEHÍCULOS" value={vehiculos.length} hint="Registrados a tu nombre" />
            <StatCard icon={<Wrench size={16} />} tone="amb" label="EN EL TALLER" value={enTaller.length} hint="En proceso ahora mismo" />
          </>
        ) : esTecnico ? (
          <>
            <StatCard icon={<SquaresFour size={16} />} tone="cya" label="ÓRDENES ACTIVAS" value={activas.length} hint="En todo el tablero" />
            <StatCard icon={<Wrench size={16} />} tone="amb" label="ASIGNADAS A MÍ" value={asignadasAMi.length} hint="Bajo tu responsabilidad" />
          </>
        ) : (
          esAdmin && (
            <>
              <StatCard icon={<SquaresFour size={16} />} tone="cya" label="ÓRDENES ACTIVAS" value={activas.length} hint="En todo el tablero" />
              <StatCard icon={<Hourglass size={16} />} tone="amb" label="ESPERANDO APROBACIÓN" value={esperandoAprobacion.length} hint="Presupuestos pendientes del cliente" />
            </>
          )
        )}
      </div>
    </AppShell>
  )
}
