import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { api, ApiError } from '../lib/api'
import { useAuthStore, type AuthUser } from '../store/auth'

export default function GoogleCallbackPage() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const setAuth = useAuthStore((s) => s.setAuth)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const code = params.get('code')
    if (!code) {
      setError('Falta el código de autorización de Google.')
      return
    }

    api<{ data: { user: AuthUser; token: string } }>('/auth/google/callback', {
      method: 'POST',
      body: JSON.stringify({ code }),
    })
      .then((res) => {
        setAuth(res.data.token, res.data.user)
        navigate('/')
      })
      .catch((err) => {
        setError(err instanceof ApiError ? err.message : 'No se pudo completar el acceso con Google.')
      })
  }, [params, navigate, setAuth])

  return (
    <main className="flex min-h-svh flex-col items-center justify-center gap-3 bg-app-bg px-6 text-center text-app-text">
      {error ? (
        <p className="text-sm text-cor">{error}</p>
      ) : (
        <p className="text-sm text-app-muted">Completando acceso con Google…</p>
      )}
    </main>
  )
}
