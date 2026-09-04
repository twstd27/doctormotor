import { Eye, EyeSlash, LockSimple, Wrench } from '@phosphor-icons/react'
import { useEffect, useState, type FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../lib/api'
import { aceptarInvitacion, obtenerInvitacion, type Invitacion } from '../lib/invitacion'
import { useAuthStore } from '../store/auth'

const ROL_LABEL: Record<string, string> = {
  operador_tecnico: 'Operador técnico',
  cajero: 'Cajero',
}

export default function InvitacionTecnicoPage() {
  const { token } = useParams<{ token: string }>()
  const navigate = useNavigate()
  const setAuth = useAuthStore((s) => s.setAuth)

  const [invitacion, setInvitacion] = useState<Invitacion | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [cargando, setCargando] = useState(true)
  const [enviando, setEnviando] = useState(false)
  const [pwVisible, setPwVisible] = useState(false)

  const [password, setPassword] = useState('')
  const [confirmacion, setConfirmacion] = useState('')

  useEffect(() => {
    if (!token) return
    obtenerInvitacion(token)
      .then(setInvitacion)
      .catch((err) => setError(err instanceof ApiError ? err.message : 'No se pudo cargar la invitación.'))
      .finally(() => setCargando(false))
  }, [token])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!token) return
    setEnviando(true)
    setError(null)
    try {
      const { user, token: authToken } = await aceptarInvitacion(token, password, confirmacion)
      setAuth(authToken, user)
      navigate('/')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo activar la cuenta.')
    } finally {
      setEnviando(false)
    }
  }

  return (
    <main className="flex min-h-svh flex-col items-center justify-center bg-app-bg px-[22px] pt-[52px] pb-[34px] text-app-text">
      <div className="dm-in flex w-full max-w-[460px] flex-col items-center gap-[30px]">
        <div className="flex flex-col items-center gap-5">
          <div className="flex size-[74px] items-center justify-center rounded-[22px] bg-lime-500" style={{ boxShadow: 'var(--shadow-cta-lime)' }}>
            <Wrench weight="fill" size={38} className="text-lime-ink" />
          </div>
          <div className="flex flex-col items-center gap-2 text-center">
            <h1 className="text-[30px] leading-[1.1] font-semibold tracking-[-0.03em]">Doctor Motor</h1>
            {invitacion && (
              <p className="max-w-[290px] text-[15px] leading-[1.5] text-app-muted">
                Te invitaron como <strong className="text-app-text">{ROL_LABEL[invitacion.rol] ?? invitacion.rol}</strong>,{' '}
                {invitacion.nombre}. Definí tu contraseña para activar la cuenta.
              </p>
            )}
          </div>
        </div>

        {cargando && <p className="text-sm text-app-muted">Cargando invitación…</p>}

        {!cargando && error && !invitacion && (
          <div className="w-full rounded-xl bg-cor-bg p-4 text-center" style={{ border: '1px solid var(--color-cor)' }}>
            <p className="text-sm text-cor-txt">{error}</p>
            <p className="mt-2 text-xs text-app-muted">Pedile a quien te invitó que te mande un enlace nuevo.</p>
          </div>
        )}

        {invitacion && (
          <form onSubmit={handleSubmit} className="flex w-full flex-col gap-3">
            <div className="relative">
              <LockSimple size={18} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-app-faint" />
              <input
                type={pwVisible ? 'text' : 'password'}
                required
                minLength={8}
                placeholder="Nueva contraseña"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="h-[52px] w-full rounded-xl bg-app-surface pr-11 pl-10 text-[15px] outline-none placeholder:text-app-faint focus-visible:border-lime-500"
                style={{ border: '1px solid var(--color-app-line)' }}
              />
              <button
                type="button"
                onClick={() => setPwVisible((v) => !v)}
                className="absolute top-1/2 right-1 flex size-11 -translate-y-1/2 items-center justify-center text-app-faint"
              >
                {pwVisible ? <EyeSlash size={18} /> : <Eye size={18} />}
              </button>
            </div>
            <div className="relative">
              <LockSimple size={18} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-app-faint" />
              <input
                type={pwVisible ? 'text' : 'password'}
                required
                minLength={8}
                placeholder="Repetí la contraseña"
                value={confirmacion}
                onChange={(e) => setConfirmacion(e.target.value)}
                className="h-[52px] w-full rounded-xl bg-app-surface pr-3.5 pl-10 text-[15px] outline-none placeholder:text-app-faint focus-visible:border-lime-500"
                style={{ border: '1px solid var(--color-app-line)' }}
              />
            </div>

            <button
              type="submit"
              disabled={enviando}
              className="h-[52px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
              style={{ boxShadow: 'var(--shadow-cta-lime)' }}
            >
              {enviando ? 'Activando…' : 'Activar cuenta e ingresar'}
            </button>

            {error && <p className="text-center text-sm text-cor">{error}</p>}
          </form>
        )}
      </div>
    </main>
  )
}
