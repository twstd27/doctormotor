import { SignOut } from '@phosphor-icons/react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import AppShell, { iniciales } from '../components/AppShell'
import { useAuthStore } from '../store/auth'

const ROL_LABEL: Record<string, string> = {
  super_admin: 'Super admin',
  cajero: 'Cajero',
  operador_tecnico: 'Técnico',
  cliente: 'Cliente',
}

export default function PerfilPage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)
  const clearAuth = useAuthStore((s) => s.clearAuth)
  const navigate = useNavigate()

  if (hasHydrated && !user) return <Navigate to="/login" replace />
  if (!user) return null

  function salir() {
    clearAuth()
    navigate('/login')
  }

  return (
    <AppShell title="Perfil">
      <div className="dm-card flex items-center gap-4">
        <span className="flex size-14 shrink-0 items-center justify-center rounded-full bg-lime-500/15 text-lg font-bold text-lime-500">
          {iniciales(user.nombre)}
        </span>
        <div className="min-w-0">
          <p className="dm-display truncate text-lg font-bold">{user.nombre}</p>
          <p className="text-[13px] text-app-faint">{ROL_LABEL[user.rol] ?? user.rol}</p>
        </div>
      </div>

      <div className="dm-card" style={{ padding: '4px 16px' }}>
        {user.telefono_whatsapp && (
          <div className="dm-li justify-between">
            <span className="text-sm text-app-muted">WhatsApp</span>
            <span className="font-mono text-sm">{user.telefono_whatsapp}</span>
          </div>
        )}
        {user.email && (
          <div className="dm-li justify-between">
            <span className="text-sm text-app-muted">Correo</span>
            <span className="truncate text-sm">{user.email}</span>
          </div>
        )}
        <Link to="/privacidad" className="dm-li justify-between text-sm">
          <span>Política de privacidad</span>
          <span className="text-app-faint">›</span>
        </Link>
      </div>

      <button type="button" onClick={salir} className="dm-btn s" style={{ color: 'var(--color-cor)' }}>
        <SignOut size={20} />
        Cerrar sesión
      </button>
    </AppShell>
  )
}
