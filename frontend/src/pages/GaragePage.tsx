import { useQuery } from '@tanstack/react-query'
import { Receipt } from '@phosphor-icons/react'
import { Link, Navigate } from 'react-router-dom'
import AppShell from '../components/AppShell'
import OtGarageCard from '../components/OtGarageCard'
import { descargarHistorialClinico, misOrdenesTrabajo, misVehiculos } from '../lib/garaje'
import { useAuthStore } from '../store/auth'

const API_URL = import.meta.env.VITE_API_URL as string

export default function GaragePage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const token = useAuthStore((s) => s.token)

  function descargarHistorial(vehiculoId: number) {
    if (!token) return
    descargarHistorialClinico(vehiculoId, token, API_URL)
  }

  const { data: vehiculos = [] } = useQuery({
    queryKey: ['me', 'vehiculos'],
    queryFn: misVehiculos,
    enabled: hasHydrated && !!user,
  })

  const { data: ordenes = [] } = useQuery({
    queryKey: ['me', 'ordenes-trabajo'],
    queryFn: misOrdenesTrabajo,
    enabled: hasHydrated && !!user,
    refetchInterval: 20000,
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />

  const ordenesActivas = ordenes.filter((o) => o.estado !== 'entregado' && o.estado !== 'cancelado')
  const conPresupuestoPendiente = ordenesActivas.find((o) => o.presupuestos[0]?.estado === 'enviado')

  return (
    <AppShell title="Mi garaje" subtitle="Seguí tu vehículo sin llamar al taller">
      {conPresupuestoPendiente && (
        <Link
          to={`/presupuestos/${conPresupuestoPendiente.presupuestos[0].id}`}
          className="mb-3.5 flex items-center gap-3 rounded-2xl px-4 py-3.5 text-left"
          style={{ border: '1px solid var(--color-amb)', background: 'var(--color-amb-bg)' }}
        >
          <Receipt weight="fill" size={24} className="text-amb shrink-0" />
          <div className="min-w-0">
            <p className="text-sm font-semibold text-amb-txt">Tenés un presupuesto para revisar</p>
            <p className="truncate text-xs text-app-muted">
              {conPresupuestoPendiente.codigo} · Bs {conPresupuestoPendiente.presupuestos[0].total} · el trabajo arranca cuando lo aprobés
            </p>
          </div>
        </Link>
      )}

      {ordenesActivas.length === 0 && (
        <p className="mb-4 rounded-2xl bg-app-surface p-4 text-sm text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
          {vehiculos.length > 0
            ? 'No tenés ningún auto en el taller en este momento.'
            : 'Todavía no tenés vehículos registrados en el taller.'}
        </p>
      )}

      <div className="flex flex-col gap-3">
        {ordenesActivas.map((ot) => (
          <OtGarageCard key={ot.id} ot={ot} onDescargarHistorial={descargarHistorial} />
        ))}
      </div>
    </AppShell>
  )
}
