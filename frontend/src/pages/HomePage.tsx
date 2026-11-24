import { Navigate } from 'react-router-dom'
import { useAuthStore } from '../store/auth'
import ClienteInicio from './ClienteInicio'
import TecnicoHoy from './TecnicoHoy'

/** Inicio según el rol: el cliente ve su vehículo; el técnico ve "Mi día". */
export default function HomePage() {
  const user = useAuthStore((s) => s.user)
  const hasHydrated = useAuthStore((s) => s.hasHydrated)

  if (hasHydrated && !user) return <Navigate to="/login" replace />
  if (!user) return null

  return user.rol === 'cliente' ? <ClienteInicio /> : <TecnicoHoy />
}
