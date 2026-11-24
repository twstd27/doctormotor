import { useQuery } from '@tanstack/react-query'
import { Info } from '@phosphor-icons/react'
import { Link, Navigate } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { EstadoBadge, Placa } from '../components/ui'
import { descargarHistorialClinico, misOrdenesTrabajo, misVehiculos, type Vehiculo } from '../lib/garaje'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'

const API_URL = import.meta.env.VITE_API_URL as string

export default function VehiculosPage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const token = useAuthStore((s) => s.token)
  const showToast = useToastStore((s) => s.show)

  const { data: vehiculos = [], isLoading } = useQuery({
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

  async function compartirQr(v: Vehiculo) {
    if (!v.qr_token) return
    const url = `${window.location.origin}/qr/${v.qr_token}`
    try {
      if (navigator.share) {
        await navigator.share({ title: `Historial de ${v.marca} ${v.modelo}`, url })
        return
      }
      await navigator.clipboard.writeText(url)
      showToast('Enlace del historial copiado')
    } catch {
      // el usuario canceló el menú de compartir
    }
  }

  return (
    <AppShell
      title="Mis vehículos"
      subtitle={`${vehiculos.length} ${vehiculos.length === 1 ? 'registrado' : 'registrados'} a tu nombre`}
    >
      {isLoading && <p className="text-sm text-app-muted">Cargando…</p>}

      {vehiculos.map((v) => {
        const activa = ordenes.find((o) => o.vehiculo.id === v.id && o.estado !== 'entregado' && o.estado !== 'cancelado')
        return (
          <div key={v.id} className="dm-card flex flex-col gap-3.5">
            <div className="flex items-start gap-3">
              <div className="min-w-0 flex-1">
                <Placa placa={v.placa} className="text-[13px]" />
                <p className="dm-display mt-2.5 text-[19px] font-bold">
                  {v.marca} {v.modelo} {v.anio}
                </p>
                <p className="mt-0.5 text-[13px] text-app-faint">
                  {[v.color, `${v.kilometraje_actual.toLocaleString('es-BO')} km`].filter(Boolean).join(' · ')}
                </p>
              </div>
              {activa ? (
                <EstadoBadge estado={activa.estado} />
              ) : (
                <span className="inline-flex h-6 items-center rounded-full border border-app-line px-2.5 text-[11.5px] font-semibold text-app-faint">
                  Sin órdenes
                </span>
              )}
            </div>

            {activa ? (
              <Link to={`/seguimiento/${activa.id}`} className="dm-btn p sm">
                Ver cómo va
              </Link>
            ) : (
              <p className="text-[13px] text-app-faint">No está en el taller en este momento.</p>
            )}

            <div className="grid grid-cols-2 gap-2.5">
              <button
                type="button"
                onClick={() => token && descargarHistorialClinico(v.id, token, API_URL)}
                className="dm-btn s sm"
              >
                Historial (PDF)
              </button>
              <button type="button" onClick={() => compartirQr(v)} disabled={!v.qr_token} className="dm-btn s sm">
                Compartir QR
              </button>
            </div>
          </div>
        )
      })}

      {!isLoading && vehiculos.length === 0 && (
        <div className="dm-card text-sm text-app-muted">Todavía no tienes vehículos registrados en el taller.</div>
      )}

      {vehiculos.length > 0 && (
        <div className="flex gap-2.5 rounded-xl bg-cya-bg px-3.5 py-3 text-[13px] leading-normal text-app-muted">
          <Info size={16} className="mt-0.5 shrink-0 text-cya" />
          <span>
            El QR muestra el historial de servicios del vehículo. Sirve para venderlo o para que otro taller vea qué se le hizo.
          </span>
        </div>
      )}
    </AppShell>
  )
}
