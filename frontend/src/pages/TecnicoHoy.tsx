import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import AppShell, { iniciales } from '../components/AppShell'
import { EstadoBadge, Placa, ProgresoEstados, etiquetaEstado, fechaCorta, pasoDe, saludo } from '../components/ui'
import { ESTADOS, antiguedad, listarOrdenesTrabajo, type OrdenTrabajo } from '../lib/ordenesTrabajo'
import { useAuthStore } from '../store/auth'

// Qué le toca hacer al técnico en cada etapa — texto del botón de "Sigue con esto".
const SIGUE_CON: Record<string, string> = {
  recepcionado: 'Continuar: inspección de ingreso',
  en_diagnostico: 'Continuar: diagnóstico y presupuesto',
  esperando_aprobacion: 'Ver la orden',
  en_reparacion: 'Continuar: repuestos usados y fotos',
  control_calidad: 'Continuar: prueba y cierre',
  listo_entrega: 'Ver la orden',
}

function Estadistica({ valor, etiqueta, color }: { valor: number; etiqueta: string; color?: string }) {
  return (
    <div className="dm-card" style={{ padding: 12 }}>
      <p className="font-mono text-2xl font-bold" style={color ? { color } : undefined}>
        {valor}
      </p>
      <p className="mt-0.5 text-[12.5px] text-app-faint">{etiqueta}</p>
    </div>
  )
}

export default function TecnicoHoy() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)

  const { data: ordenes = [], isLoading } = useQuery({
    queryKey: ['ordenes-trabajo'],
    queryFn: listarOrdenesTrabajo,
    refetchInterval: 20000,
    enabled: hasHydrated && !!user,
  })

  if (!user) return null

  const activas = ordenes.filter((o) => o.estado !== 'entregado' && o.estado !== 'cancelado')
  // Lo que le toca al técnico ahora: primero lo que está en sus manos (reparación, control,
  // diagnóstico, recepción); al final lo que espera al cliente o ya está listo para entregar.
  const ORDEN_PRIORIDAD = ['en_reparacion', 'control_calidad', 'en_diagnostico', 'recepcionado', 'esperando_aprobacion', 'listo_entrega']
  const prioridad = [...activas].sort((a: OrdenTrabajo, b: OrdenTrabajo) => {
    const peso = (o: OrdenTrabajo) => {
      const i = ORDEN_PRIORIDAD.indexOf(o.estado)
      return i === -1 ? ORDEN_PRIORIDAD.length : i
    }
    return peso(a) - peso(b) || a.fecha_ingreso.localeCompare(b.fecha_ingreso)
  })
  const sigue = prioridad[0]
  const otras = prioridad.slice(1)
  const esperanAlCliente = activas.filter((o) => o.estado === 'esperando_aprobacion').length
  const listas = activas.filter((o) => o.estado === 'listo_entrega').length

  return (
    <AppShell
      title={`Hola, ${saludo(user.nombre)}`}
      subtitle={fechaCorta()}
      rightAction={
        <span className="flex size-11 items-center justify-center rounded-full bg-cya-bg text-sm font-bold text-cya">
          {iniciales(user.nombre)}
        </span>
      }
    >
      <div className="grid grid-cols-3 gap-2">
        <Estadistica valor={activas.length} etiqueta="Asignadas" />
        <Estadistica valor={listas} etiqueta="Listas para entregar" color="var(--color-amb)" />
        <Estadistica valor={esperanAlCliente} etiqueta="Esperan al cliente" color="var(--color-cya)" />
      </div>

      {isLoading && <p className="text-sm text-app-muted">Cargando…</p>}

      {sigue ? (
        <>
          <span className="dm-lab mt-1.5">Sigue con esto</span>
          <div className="dm-card overflow-hidden" style={{ padding: 0, borderColor: 'rgba(197,248,42,.35)' }}>
            <div className="flex flex-col gap-3 p-4">
              <div className="flex items-center justify-between">
                <span className="font-mono text-xs font-semibold text-app-faint">{sigue.codigo}</span>
                <span className="rounded-full border border-app-line px-2.5 py-0.5 text-[11.5px] text-app-faint">
                  Hace {antiguedad(sigue.fecha_ingreso)}
                </span>
              </div>
              <div className="flex items-center gap-3">
                <div className="min-w-0 flex-1">
                  <p className="dm-display truncate text-xl font-bold">
                    {sigue.vehiculo.marca} {sigue.vehiculo.modelo}
                  </p>
                  <p className="mt-0.5 truncate text-[13px] text-app-faint">{sigue.descripcion_problema}</p>
                </div>
                <Placa placa={sigue.vehiculo.placa} className="shrink-0 text-[13px]" />
              </div>
              <div>
                <div className="mb-1.5 flex justify-between text-xs">
                  <span className="font-semibold">{etiquetaEstado(sigue.estado)}</span>
                  <span className="text-app-faint">Paso {pasoDe(sigue.estado)} de {ESTADOS.length}</span>
                </div>
                <ProgresoEstados estado={sigue.estado} />
              </div>
            </div>
            <Link
              to={`/ordenes-trabajo/${sigue.id}`}
              className="dm-btn p"
              style={{ borderRadius: 0, height: 58, fontSize: 15 }}
            >
              {SIGUE_CON[sigue.estado] ?? 'Abrir la orden'}
            </Link>
          </div>
        </>
      ) : (
        !isLoading && (
          <div className="dm-card text-center">
            <p className="dm-display text-lg font-bold">Nada pendiente por ahora</p>
            <p className="mt-1 text-sm text-app-faint">Cuando te asignen un vehículo va a aparecer aquí.</p>
          </div>
        )
      )}

      {otras.length > 0 && (
        <>
          <div className="mt-1.5 flex items-center justify-between">
            <span className="dm-lab">Mis otras órdenes</span>
            <Link to="/ordenes-trabajo" className="text-[13px] font-semibold text-lime-500">
              Ver tablero
            </Link>
          </div>
          {otras.map((o) => (
            <Link key={o.id} to={`/ordenes-trabajo/${o.id}`} className="dm-card flex flex-col gap-2.5">
              <div className="flex items-center gap-3">
                <div className="min-w-0 flex-1">
                  <p className="truncate text-base font-semibold">
                    {o.vehiculo.marca} {o.vehiculo.modelo}
                  </p>
                  <p className="truncate text-[13px] text-app-faint">{o.descripcion_problema}</p>
                </div>
                <Placa placa={o.vehiculo.placa} />
              </div>
              <div className="flex items-center justify-between pt-2.5" style={{ borderTop: '1px solid var(--color-app-line-2)' }}>
                <EstadoBadge estado={o.estado} />
                <span className="text-[13px] text-app-muted">Hace {antiguedad(o.fecha_ingreso)}</span>
              </div>
            </Link>
          ))}
        </>
      )}
    </AppShell>
  )
}
