import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check } from '@phosphor-icons/react'
import { useState, type ReactNode } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import AppShell from '../components/AppShell'
import PresupuestoSheet from '../components/PresupuestoSheet'
import RegistrarCostoSheet from '../components/RegistrarCostoSheet'
import { EstadoBadge, Placa, ProgresoEstados, etiquetaEstado } from '../components/ui'
import {
  ESTADOS,
  cambiarEstado,
  evidenciasDeOtServidor,
  obtenerOrdenTrabajo,
  siguienteEstado,
  type Estado,
} from '../lib/ordenesTrabajo'
import { listarPresupuestosDeOt } from '../lib/presupuestos'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'

type Marca = 'ok' | 'ahora' | 'falta'

function Tarea({
  marca,
  titulo,
  detalle,
  onClick,
  badge,
}: {
  marca: Marca
  titulo: string
  detalle: ReactNode
  onClick: () => void
  badge?: ReactNode
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="flex min-h-16 w-full items-center gap-3.5 px-4 py-3.5 text-left"
      style={{ borderBottom: '1px solid var(--color-app-line-2)' }}
    >
      <span
        className="flex size-7 shrink-0 items-center justify-center rounded-full"
        style={
          marca === 'ok'
            ? { background: 'rgba(197,248,42,.15)', color: 'var(--color-lime-500)' }
            : marca === 'ahora'
              ? { border: '2px solid var(--color-lime-500)' }
              : { border: '2px solid #3A434E' }
        }
      >
        {marca === 'ok' && <Check weight="bold" size={15} />}
      </span>
      <span className="min-w-0 flex-1">
        <span className="block text-[15px] font-semibold">{titulo}</span>
        <span className="mt-0.5 block text-[13px] text-app-faint">{detalle}</span>
      </span>
      {badge ?? <span className="text-app-faint">›</span>}
    </button>
  )
}

export default function OrdenPage() {
  const { id } = useParams<{ id: string }>()
  const otId = Number(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const showToast = useToastStore((s) => s.show)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const user = useAuthStore((s) => s.user)
  const [verPresupuesto, setVerPresupuesto] = useState(false)
  const [verCosto, setVerCosto] = useState(false)

  const { data: orden, isLoading, error } = useQuery({
    queryKey: ['orden-trabajo', otId],
    queryFn: () => obtenerOrdenTrabajo(otId),
    enabled: hasHydrated && !!user,
  })
  const { data: evidencias = [] } = useQuery({
    queryKey: ['ot-evidencias', otId],
    queryFn: () => evidenciasDeOtServidor(otId),
    enabled: hasHydrated && !!user,
  })
  const { data: presupuestos = [], refetch: refrescarPresupuestos } = useQuery({
    queryKey: ['ot-presupuestos', otId],
    queryFn: () => listarPresupuestosDeOt(otId),
    enabled: hasHydrated && !!user,
  })

  const mutation = useMutation({
    mutationFn: (estado: Estado) => cambiarEstado(otId, estado),
    onSuccess: (_d, estado) => {
      queryClient.invalidateQueries({ queryKey: ['ordenes-trabajo'] })
      queryClient.invalidateQueries({ queryKey: ['orden-trabajo', otId] })
      showToast(`Orden pasada a "${etiquetaEstado(estado)}"`)
    },
  })

  if (hasHydrated && !user) return <Navigate to="/login" replace />

  if (isLoading || !orden) {
    return (
      <AppShell title="Orden" back={{ label: 'Atrás', to: '/ordenes-trabajo' }}>
        <p className="text-sm text-app-muted">{error ? 'No se pudo cargar la orden.' : 'Cargando…'}</p>
      </AppShell>
    )
  }

  const cerrada = orden.estado === 'entregado' || orden.estado === 'cancelado'
  const siguiente = cerrada ? null : siguienteEstado(orden.estado)
  const presupuesto = presupuestos[0] ?? null
  const accesorios = orden.inspeccion?.accesorios?.length ?? 0
  const fotos = evidencias.filter((e) => e.tipo === 'foto').length
  const videos = evidencias.length - fotos

  const detallePresupuesto = !presupuesto
    ? 'Todavía no hay presupuesto'
    : presupuesto.estado === 'borrador'
      ? 'Borrador sin enviar'
      : presupuesto.estado === 'enviado'
        ? 'Esperando respuesta del cliente'
        : presupuesto.estado === 'aprobado'
          ? `Aprobado · Bs ${(presupuesto.items?.length
              ? presupuesto.items.filter((i) => i.aprobado !== false).reduce((acc, i) => acc + Number(i.subtotal), 0)
              : Number(presupuesto.total)
            ).toLocaleString('es-BO')}`
          : 'Rechazado por el cliente'

  return (
    <AppShell
      title={`${orden.vehiculo.marca} ${orden.vehiculo.modelo}`}
      back={{ label: 'Atrás', to: '/ordenes-trabajo' }}
      rightAction={<span className="font-mono text-[13px] font-semibold text-app-faint">{orden.codigo}</span>}
      subtitle={
        <span className="mt-1 flex items-center gap-2">
          <Placa placa={orden.vehiculo.placa} />
          <span>{orden.kilometraje_ingreso.toLocaleString('es-BO')} km</span>
        </span>
      }
      sticky={
        cerrada ? (
          <div className="dm-btn s" style={{ cursor: 'default', color: 'var(--color-app-faint)' }}>
            Esta orden ya fue {orden.estado === 'cancelado' ? 'cancelada' : 'entregada'}
          </div>
        ) : siguiente ? (
          <button type="button" className="dm-btn p" disabled={mutation.isPending} onClick={() => mutation.mutate(siguiente)}>
            {mutation.isPending ? 'Moviendo…' : `Listo · pasar a ${etiquetaEstado(siguiente).toLowerCase()}`}
          </button>
        ) : undefined
      }
    >
      <div className="dm-card flex flex-col gap-2.5">
        <div className="flex items-center justify-between">
          <EstadoBadge estado={orden.estado} />
          <span className="text-[13px] text-app-faint">
            Paso {Math.max(1, ESTADOS.findIndex((e) => e.value === orden.estado) + 1)} de {ESTADOS.length}
          </span>
        </div>
        <ProgresoEstados estado={orden.estado} />
      </div>

      <div className="dm-card flex flex-col gap-2">
        <span className="dm-lab">Lo que reportó el cliente</span>
        <p className="text-[15px] leading-normal">{orden.descripcion_problema}</p>
        <p className="text-[12.5px] text-app-faint">
          {orden.cliente.nombre} · ingresó el {new Date(orden.fecha_ingreso).toLocaleDateString('es-BO')}
        </p>
      </div>

      <span className="dm-lab mt-1">Trabajo de esta orden</span>
      <div className="dm-card overflow-hidden" style={{ padding: 0 }}>
        <Tarea
          marca={orden.inspeccion?.firmado_at ? 'ok' : 'ahora'}
          titulo="Inspección de ingreso"
          detalle={
            orden.inspeccion
              ? `${accesorios} ${accesorios === 1 ? 'accesorio' : 'accesorios'}${orden.inspeccion.firmado_at ? ' · firmada' : ' · falta la firma'}`
              : 'Accesorios, daños previos y firma del cliente'
          }
          onClick={() => navigate(`/ordenes-trabajo/${otId}/inspeccion`)}
        />
        <Tarea
          marca={presupuesto?.estado === 'aprobado' ? 'ok' : presupuesto ? 'ahora' : 'falta'}
          titulo="Presupuesto"
          detalle={detallePresupuesto}
          onClick={() => setVerPresupuesto(true)}
        />
        <Tarea
          marca={evidencias.length > 0 ? 'ahora' : 'falta'}
          titulo="Fotos y videos"
          detalle={
            evidencias.length > 0
              ? `${fotos} ${fotos === 1 ? 'foto' : 'fotos'}${videos > 0 ? ` · ${videos} ${videos === 1 ? 'video' : 'videos'}` : ''}`
              : 'Nada subido todavía'
          }
          onClick={() => navigate(`/ordenes-trabajo/${otId}/evidencias`)}
          badge={
            evidencias.length > 0 ? (
              <span className="rounded-full bg-app-surface-3 px-2 py-0.5 font-mono text-xs font-semibold">{evidencias.length}</span>
            ) : undefined
          }
        />
        <Tarea
          marca="falta"
          titulo="Repuestos y mano de obra"
          detalle="Registra lo que gastas en esta orden"
          onClick={() => setVerCosto(true)}
        />
      </div>

      {!cerrada && (
        <label className="block">
          <span className="sr-only">Cambiar directamente a otro estado</span>
          <select
            disabled={mutation.isPending}
            value=""
            onChange={(e) => e.target.value && mutation.mutate(e.target.value as Estado)}
            className="h-12 w-full rounded-xl bg-app-surface px-3.5 text-[13.5px] text-app-muted disabled:opacity-60"
            style={{ border: '1px solid var(--color-app-line)' }}
          >
            <option value="">Corregir el estado a mano…</option>
            {ESTADOS.filter((e) => e.value !== orden.estado).map((e) => (
              <option key={e.value} value={e.value}>
                {e.label}
              </option>
            ))}
          </select>
        </label>
      )}

      {verCosto && (
        <RegistrarCostoSheet otId={otId} onClose={() => setVerCosto(false)} onRegistrado={() => setVerCosto(false)} />
      )}
      {verPresupuesto && (
        <PresupuestoSheet
          otId={otId}
          onClose={() => {
            setVerPresupuesto(false)
            refrescarPresupuestos()
          }}
          onEnviado={() => {
            setVerPresupuesto(false)
            refrescarPresupuestos()
          }}
        />
      )}
    </AppShell>
  )
}
