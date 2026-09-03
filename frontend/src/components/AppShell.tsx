import { CaretLeft, Camera, CarProfile, House, Receipt, Signature, SquaresFour } from '@phosphor-icons/react'
import type { ReactNode } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store/auth'
import Toast from './Toast'

interface NavItem {
  to: string
  label: string
  icon: (weight: 'regular' | 'fill') => ReactNode
}

const TECNICO_NAV: NavItem[] = [
  { to: '/', label: 'Inicio', icon: (w) => <House weight={w} size={22} /> },
  { to: '/ordenes-trabajo', label: 'Órdenes', icon: (w) => <SquaresFour weight={w} size={22} /> },
  { to: '/ordenes-trabajo', label: 'Evidencias', icon: (w) => <Camera weight={w} size={22} /> },
  { to: '/ordenes-trabajo', label: 'Inspección', icon: (w) => <Signature weight={w} size={22} /> },
]

const CLIENTE_NAV: NavItem[] = [
  { to: '/', label: 'Inicio', icon: (w) => <House weight={w} size={22} /> },
  { to: '/garaje', label: 'Mi garaje', icon: (w) => <CarProfile weight={w} size={22} /> },
  { to: '/garaje', label: 'Presupuesto', icon: (w) => <Receipt weight={w} size={22} /> },
]

function iniciales(nombre: string) {
  return nombre
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join('')
}

function Nav({ items, layout }: { items: NavItem[]; layout: 'rail' | 'tabbar' }) {
  const location = useLocation()

  if (layout === 'rail') {
    return (
      <nav className="flex flex-col gap-1 p-3">
        {items.map((item) => {
          const active = location.pathname === item.to
          return (
            <Link
              key={item.label}
              to={item.to}
              className={
                active
                  ? 'flex items-center gap-3 rounded-xl bg-app-surface-2 px-3 py-2.5 text-sm font-medium text-lime-txt'
                  : 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-app-faint hover:text-app-muted'
              }
            >
              {item.icon(active ? 'fill' : 'regular')}
              {item.label}
            </Link>
          )
        })}
      </nav>
    )
  }

  return (
    <nav
      className="no-scrollbar fixed inset-x-0 bottom-0 z-30 flex items-center justify-around bg-app-bg/90 backdrop-blur-[18px]"
      style={{ borderTop: '1px solid var(--color-app-line-2)', padding: '8px 10px 22px' }}
    >
      {items.map((item) => {
        const active = location.pathname === item.to
        return (
          <Link
            key={item.label}
            to={item.to}
            className={
              active
                ? 'flex min-w-[64px] flex-col items-center gap-1 py-1 text-lime-txt'
                : 'flex min-w-[64px] flex-col items-center gap-1 py-1 text-app-faint'
            }
          >
            {item.icon(active ? 'fill' : 'regular')}
            <span className="text-[10.5px] font-medium">{item.label}</span>
          </Link>
        )
      })}
    </nav>
  )
}

interface AppShellProps {
  title: string
  subtitle?: string
  back?: { label: string; to: string }
  rightAction?: ReactNode
  children: ReactNode
}

export default function AppShell({ title, subtitle, back, rightAction, children }: AppShellProps) {
  const user = useAuthStore((s) => s.user)
  const navigate = useNavigate()
  const esTecnico = user?.rol !== 'cliente'
  const items = esTecnico ? TECNICO_NAV : CLIENTE_NAV

  return (
    <div className="min-h-svh bg-app-bg text-app-text md:flex">
      <aside
        className="sticky top-0 hidden h-svh w-[232px] shrink-0 md:block"
        style={{ borderRight: '1px solid var(--color-app-line)' }}
      >
        <Nav items={items} layout="rail" />
      </aside>

      <div className="min-w-0 flex-1">
        <header
          className="sticky top-0 z-30 bg-app-bg/90 backdrop-blur-[18px]"
          style={{ borderBottom: '1px solid var(--color-app-line-2)' }}
        >
          <div className="mx-auto flex h-12 max-w-[1180px] items-center justify-between px-[var(--pad-page)]">
            {back ? (
              <button
                type="button"
                onClick={() => navigate(back.to)}
                className="flex items-center gap-1 text-sm font-medium text-lime-txt"
              >
                <CaretLeft weight="bold" size={18} />
                {back.label}
              </button>
            ) : (
              <span />
            )}
            {rightAction}
          </div>
          <div className="mx-auto flex max-w-[1180px] items-center justify-between px-[var(--pad-page)] pb-4">
            <div>
              <h1 className="text-[27px] leading-[1.15] font-semibold tracking-[-0.03em]">{title}</h1>
              {subtitle && <p className="mt-0.5 text-[13px] text-app-muted">{subtitle}</p>}
            </div>
            {user && (
              <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-app-surface-3 text-sm font-semibold">
                {iniciales(user.nombre)}
              </div>
            )}
          </div>
        </header>

        <main className="mx-auto max-w-[1180px] px-[var(--pad-page)] pt-4 pb-[110px] md:pb-10">{children}</main>
      </div>

      <div className="md:hidden">
        <Nav items={items} layout="tabbar" />
      </div>
      <Toast />
    </div>
  )
}
