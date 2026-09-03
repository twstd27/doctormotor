import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { api, ApiError } from '../lib/api'
import { useAuthStore, type AuthUser } from '../store/auth'

export default function WhatsappVerifyPage() {
  const { token } = useParams<{ token: string }>()
  const navigate = useNavigate()
  const setAuth = useAuthStore((s) => s.setAuth)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!token) return

    api<{ data: { user: AuthUser; token: string } }>(`/auth/whatsapp/verify/${token}`, {
      method: 'POST',
    })
      .then((res) => {
        setAuth(res.data.token, res.data.user)
        navigate('/')
      })
      .catch((err) => {
        setError(err instanceof ApiError ? err.message : 'El enlace expiró o ya fue utilizado.')
      })
  }, [token, navigate, setAuth])

  return (
    <main className="flex min-h-svh flex-col items-center justify-center gap-3 bg-app-bg px-6 text-center text-app-text">
      {error ? (
        <p className="text-sm text-cor">{error}</p>
      ) : (
        <p className="text-sm text-app-muted">Verificando enlace…</p>
      )}
    </main>
  )
}
