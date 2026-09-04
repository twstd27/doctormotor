import { api } from './api'
import type { AuthUser } from '../store/auth'

export interface Invitacion {
  nombre: string
  rol: string
}

export async function obtenerInvitacion(token: string): Promise<Invitacion> {
  const res = await api<{ data: Invitacion }>(`/auth/invitacion/${token}`)
  return res.data
}

export async function aceptarInvitacion(
  token: string,
  password: string,
  passwordConfirmation: string,
): Promise<{ user: AuthUser; token: string }> {
  const res = await api<{ data: { user: AuthUser; token: string } }>(`/auth/invitacion/${token}/aceptar`, {
    method: 'POST',
    body: JSON.stringify({ password, password_confirmation: passwordConfirmation }),
  })
  return res.data
}
