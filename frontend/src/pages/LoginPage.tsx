import { Envelope, Eye, EyeSlash, GoogleLogo, LockSimple, WhatsappLogo, Wrench } from '@phosphor-icons/react'
import { useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { api, ApiError } from '../lib/api'
import { useAuthStore, type AuthUser } from '../store/auth'

interface AuthResponse {
  data: { user: AuthUser; token: string }
}

export default function LoginPage() {
  const navigate = useNavigate()
  const setAuth = useAuthStore((s) => s.setAuth)

  const [whatsappAbierto, setWhatsappAbierto] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [pwVisible, setPwVisible] = useState(false)

  const [correo, setCorreo] = useState('')
  const [password, setPassword] = useState('')

  const [telefono, setTelefono] = useState('')
  const [enlaceEnviado, setEnlaceEnviado] = useState(false)
  const [debugToken, setDebugToken] = useState<string | null>(null)

  async function handleGoogle() {
    setError(null)
    try {
      const res = await api<{ url: string }>('/auth/google/redirect')
      window.location.href = res.url
    } catch {
      setError('No se pudo iniciar sesión con Google.')
    }
  }

  async function handleEmailSubmit(e: FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      const res = await api<AuthResponse>('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ login: correo, password }),
      })
      setAuth(res.data.token, res.data.user)
      navigate('/')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo iniciar sesión.')
    } finally {
      setLoading(false)
    }
  }

  async function handleWhatsappSubmit(e: FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      const res = await api<{ message: string; debug_token: string | null }>(
        '/auth/login/whatsapp-link',
        { method: 'POST', body: JSON.stringify({ telefono_whatsapp: telefono }) },
      )
      setEnlaceEnviado(true)
      setDebugToken(res.debug_token)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo enviar el enlace.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="flex min-h-svh flex-col items-center justify-center bg-app-bg px-[22px] pt-[52px] pb-[34px] text-app-text">
      <div className="dm-in flex w-full max-w-[460px] flex-col items-center gap-[30px]">
        <div className="flex flex-col items-center gap-5">
          <div className="relative flex size-[74px] items-center justify-center">
            <span
              className="absolute size-[82px] animate-[dmPulse_3.6s_ease-in-out_infinite] rounded-[24px]"
              style={{ background: 'rgba(143,214,46,.16)' }}
            />
            <div
              className="relative flex size-[74px] items-center justify-center rounded-[22px] bg-lime-500"
              style={{ boxShadow: 'var(--shadow-cta-lime)' }}
            >
              <Wrench weight="fill" size={38} className="text-lime-ink" />
            </div>
          </div>
          <div className="flex flex-col items-center gap-2 text-center">
            <h1 className="text-[30px] leading-[1.1] font-semibold tracking-[-0.03em]">Doctor Motor</h1>
            <p className="max-w-[290px] text-[15px] leading-[1.5] text-app-muted">
              Tu taller en el bolsillo. Seguí cada paso de tu vehículo.
            </p>
          </div>
        </div>

        <div className="flex w-full flex-col gap-3">
          <button
            type="button"
            onClick={handleGoogle}
            className="flex h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-app-surface text-[15px] font-medium text-app-text"
            style={{ border: '1px solid var(--color-app-line)' }}
          >
            <GoogleLogo size={20} />
            Continuar con Google
          </button>

          {!whatsappAbierto ? (
            <button
              type="button"
              onClick={() => setWhatsappAbierto(true)}
              className="flex h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink"
              style={{ boxShadow: 'var(--shadow-cta-lime)' }}
            >
              <WhatsappLogo weight="fill" size={20} />
              Continuar con WhatsApp
            </button>
          ) : enlaceEnviado ? (
            <div className="flex flex-col items-center gap-3 rounded-xl bg-app-surface p-4 text-center" style={{ border: '1px solid var(--color-app-line)' }}>
              <p className="text-sm">Te enviamos un enlace de acceso por WhatsApp.</p>
              {debugToken && (
                <a
                  href={`/auth/whatsapp/${debugToken}`}
                  className="rounded-lg px-3 py-2 text-xs text-amb-txt"
                  style={{ border: '1px solid var(--color-amb)', background: 'var(--color-amb-bg)' }}
                >
                  Modo local: abrir enlace de prueba
                </a>
              )}
            </div>
          ) : (
            <form onSubmit={handleWhatsappSubmit} className="flex flex-col gap-2 rounded-xl bg-app-surface p-3" style={{ border: '1px solid var(--color-app-line)' }}>
              <input
                type="tel"
                required
                autoFocus
                placeholder="59171234567"
                value={telefono}
                onChange={(e) => setTelefono(e.target.value)}
                className="h-[52px] rounded-xl bg-app-surface-2 px-3.5 text-[15px] outline-none placeholder:text-app-faint"
                style={{ border: '1px solid var(--color-app-line)' }}
              />
              <button
                type="submit"
                disabled={loading}
                className="h-[52px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
                style={{ boxShadow: 'var(--shadow-cta-lime)' }}
              >
                {loading ? 'Enviando…' : 'Enviar enlace de acceso'}
              </button>
            </form>
          )}

          <p className="text-center text-[12.5px] text-app-faint">Te enviamos un enlace de acceso. Sin contraseña.</p>

          <div className="my-1 flex items-center gap-3">
            <span className="h-px flex-1" style={{ background: 'var(--color-app-line)' }} />
            <span className="text-[11.5px] text-app-faint">o con tu correo</span>
            <span className="h-px flex-1" style={{ background: 'var(--color-app-line)' }} />
          </div>

          <form onSubmit={handleEmailSubmit} className="flex flex-col gap-3">
            <div className="relative">
              <Envelope size={18} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-app-faint" />
              <input
                type="text"
                required
                placeholder="correo@ejemplo.com"
                value={correo}
                onChange={(e) => setCorreo(e.target.value)}
                className="h-[52px] w-full rounded-xl bg-app-surface pr-3.5 pl-10 text-[15px] outline-none placeholder:text-app-faint focus-visible:border-lime-500"
                style={{ border: '1px solid var(--color-app-line)' }}
              />
            </div>
            <div className="relative">
              <LockSimple size={18} className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-app-faint" />
              <input
                type={pwVisible ? 'text' : 'password'}
                required
                placeholder="Contraseña"
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

            <button
              type="submit"
              disabled={loading}
              className="h-[52px] w-full rounded-xl bg-transparent text-[15px] font-semibold text-lime-txt disabled:opacity-60"
              style={{ border: '1px solid var(--color-lime-500)' }}
            >
              {loading ? 'Ingresando…' : 'Iniciar sesión'}
            </button>
          </form>

          <button type="button" className="mx-auto flex h-11 items-center text-[13px] font-medium text-app-muted">
            ¿Olvidaste tu contraseña?
          </button>

          <div className="my-1 flex items-center gap-3">
            <span className="h-px flex-1" style={{ background: 'var(--color-app-line)' }} />
            <span className="text-[11.5px] text-app-faint">¿No tienes cuenta?</span>
            <span className="h-px flex-1" style={{ background: 'var(--color-app-line)' }} />
          </div>
          <button type="button" className="mx-auto h-11 text-[13px] font-semibold text-lime-txt">
            Registrate
          </button>

          {error && <p className="text-center text-sm text-cor">{error}</p>}
        </div>
      </div>
    </main>
  )
}
