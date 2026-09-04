import { useQuery } from '@tanstack/react-query'
import { CalendarBlank, CaretDown, CaretUp, CarProfile, FilePdf, Gauge } from '@phosphor-icons/react'
import { useState } from 'react'
import { Navigate } from 'react-router-dom'
import AppShell from '../components/AppShell'
import { descargarHistorialClinico, misVehiculos } from '../lib/garaje'
import { useAuthStore } from '../store/auth'

const API_URL = import.meta.env.VITE_API_URL as string

export default function VehiculosPage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const token = useAuthStore((s) => s.token)
  const [expandidoId, setExpandidoId] = useState<number | null>(null)

  const { data: vehiculos = [] } = useQuery({
    queryKey: ['me', 'vehiculos'],
    queryFn: misVehiculos,
    enabled: hasHydrated && !!user,
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />

  return (
    <AppShell title="Vehículos" subtitle="Los autos registrados a tu nombre">
      <div className="flex flex-col gap-3 md:grid md:grid-cols-2">
        {vehiculos.map((v) => {
          const expandido = expandidoId === v.id
          return (
            <div key={v.id} className="rounded-2xl bg-app-surface p-4" style={{ border: '1px solid var(--color-app-line)' }}>
              <button
                type="button"
                onClick={() => setExpandidoId(expandido ? null : v.id)}
                className="flex w-full items-center gap-3 text-left"
              >
                <div className="flex size-[46px] shrink-0 items-center justify-center rounded-[13px] bg-app-surface-3">
                  <CarProfile size={23} className="text-app-faint" />
                </div>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-base font-semibold tracking-[-0.02em]">
                    {v.marca} {v.modelo}
                  </p>
                  <span className="mt-0.5 inline-block rounded bg-app-surface-3 px-1.5 py-0.5 font-mono text-[10.5px]">{v.placa}</span>
                </div>
                {expandido ? <CaretUp size={18} className="shrink-0 text-app-faint" /> : <CaretDown size={18} className="shrink-0 text-app-faint" />}
              </button>

              {expandido && (
                <div className="dm-in mt-3.5 flex flex-col gap-3" style={{ borderTop: '1px solid var(--color-app-line-2)', paddingTop: 14 }}>
                  <div className="grid grid-cols-2 gap-3">
                    <div className="flex items-center gap-2 rounded-xl bg-app-surface-2 p-3">
                      <CalendarBlank size={18} className="text-app-faint" />
                      <div>
                        <p className="text-[10.5px] text-app-faint uppercase">Año</p>
                        <p className="text-sm font-medium">{v.anio}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2 rounded-xl bg-app-surface-2 p-3">
                      <Gauge size={18} className="text-app-faint" />
                      <div>
                        <p className="text-[10.5px] text-app-faint uppercase">Kilometraje</p>
                        <p className="text-sm font-medium">{v.kilometraje_actual.toLocaleString('es-BO')} km</p>
                      </div>
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={() => token && descargarHistorialClinico(v.id, token, API_URL)}
                    className="flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-medium text-cor"
                    style={{ border: '1px solid var(--color-app-line)' }}
                  >
                    <FilePdf size={18} />
                    Descargar historial clínico
                  </button>
                </div>
              )}
            </div>
          )
        })}
        {vehiculos.length === 0 && (
          <p className="col-span-full rounded-2xl bg-app-surface p-4 text-sm text-app-muted" style={{ border: '1px solid var(--color-app-line)' }}>
            Todavía no tenés vehículos registrados.
          </p>
        )}
      </div>
    </AppShell>
  )
}
