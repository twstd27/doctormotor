import { CaretLeft, CarProfile, House, SquaresFour, UserCircle } from '@phosphor-icons/react'
import type { ReactNode } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store/auth'
import Toast from './Toast'

interface NavItem {
  to: string
  label: string
  icon: (weight: 'regular' | 'fill') => ReactNode
}

// Las pantallas de una orden (inspección, fotos, presupuesto) no llevan menú inferior: son
// pasos de trabajo con su propio botón de acción fijo abajo. Se llega a ellas desde la orden.
const TECNICO_NAV: NavItem[] = [
  { to: '/', label: 'Mi día', icon: (w) => <House weight={w} size={22} /> },
  { to: '/ordenes-trabajo', label: 'Tablero', icon: (w) => <SquaresFour weight={w} size={22} /> },
  { to: '/perfil', label: 'Perfil', icon: (w) => <UserCircle weight={w} size={22} /> },
]

const CLIENTE_NAV: NavItem[] = [
  { to: '/', label: 'Inicio', icon: (w) => <House weight={w} size={22} /> },
  { to: '/vehiculos', label: 'Mis vehículos', icon: (w) => <CarProfile weight={w} size={22} /> },
  { to: '/perfil', label: 'Perfil', icon: (w) => <UserCircle weight={w} size={22} /> },
]

export function iniciales(nombre: string) {
  return nombre
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join('')
}

function TabBar({ items }: { items: NavItem[] }) {
  const location = useLocation()
  return (
    <nav
      aria-label="Navegación principal"
      className="fixed inset-x-0 bottom-0 z-30 flex items-start"
      style={{
        height: 'calc(76px + env(safe-area-inset-bottom))',
        paddingTop: 8,
        background: 'var(--color-app-surface-2)',
        borderTop: '1px solid var(--color-app-line-2)',
      }}
    >
      {items.map((item) => {
        const active = location.pathname === item.to
        return (
          <Link
            key={item.label}
            to={item.to}
            aria-current={active ? 'page' : undefined}
            className={`flex h-[52px] flex-1 flex-col items-center justify-center gap-1 text-[11px] font-semibold ${
              active ? 'text-lime-500' : 'text-app-faint'
            }`}
          >
            {item.icon(active ? 'fill' : 'regular')}
            {item.label}
          </Link>
        )
      })}
    </nav>
  )
}

interface AppShellProps {
  title: string
  subtitle?: ReactNode
  back?: { label: string; to: string }
  rightAction?: ReactNode
  /** Botón(es) de acción fijos abajo; reemplazan al menú inferior. */
  sticky?: ReactNode
  children: ReactNode
}

export default function AppShell({ title, subtitle, back, rightAction, sticky, children }: AppShellProps) {
  const user = useAuthStore((s) => s.user)
  const navigate = useNavigate()
  const items = user?.rol === 'cliente' ? CLIENTE_NAV : TECNICO_NAV
  const conMenu = !back && !sticky

  return (
    <div className="min-h-svh bg-app-bg text-app-text">
      <div className="mx-auto min-h-svh max-w-[520px]">
        <header
          className="sticky top-0 z-20 bg-app-bg"
          style={{ borderBottom: '1px solid var(--color-app-line-2)', paddingTop: 'env(safe-area-inset-top)' }}
        >
          {back && (
            <div className="px-[18px] pt-1">
              <button
                type="button"
                onClick={() => navigate(back.to)}
                className="flex h-11 items-center gap-1 text-sm font-semibold text-lime-500"
              >
                <CaretLeft weight="bold" size={18} />
                {back.label}
              </button>
            </div>
          )}
          <div className={`flex items-center gap-3 px-[18px] pb-3.5 ${back ? 'pt-0' : 'pt-[18px]'}`}>
            <div className="min-w-0 flex-1">
              <h1 className={`dm-display leading-[1.15] font-bold ${back ? 'text-xl' : 'text-2xl'}`}>{title}</h1>
              {subtitle && <div className="mt-0.5 text-[13px] text-app-faint">{subtitle}</div>}
            </div>
            {rightAction}
          </div>
        </header>

        <main className="flex flex-col gap-3.5 px-[18px] pt-4" style={{ paddingBottom: sticky ? 150 : conMenu ? 110 : 32 }}>
          {children}
        </main>
      </div>

      {sticky && <div className="dm-sticky">{sticky}</div>}
      {conMenu && <TabBar items={items} />}
      <Toast />
    </div>
  )
}
