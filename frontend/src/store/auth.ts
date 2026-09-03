import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type Rol = 'super_admin' | 'cajero' | 'operador_tecnico' | 'cliente'

export interface AuthUser {
  id: number
  nombre: string
  email: string | null
  telefono_whatsapp: string | null
  rol: Rol
}

interface AuthState {
  token: string | null
  user: AuthUser | null
  hasHydrated: boolean
  setAuth: (token: string, user: AuthUser) => void
  clearAuth: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      hasHydrated: false,
      setAuth: (token, user) => set({ token, user }),
      clearAuth: () => set({ token: null, user: null }),
    }),
    {
      name: 'doctor-motor-auth',
      partialize: (state) => ({ token: state.token, user: state.user }),
    },
  ),
)

// Se dispara aparte porque onRehydrateStorage corre antes de que el store completo
// (con setHasHydrated) esté disponible en algunos casos de carga en frío.
useAuthStore.persist.onFinishHydration(() => {
  useAuthStore.setState({ hasHydrated: true })
})
if (useAuthStore.persist.hasHydrated()) {
  useAuthStore.setState({ hasHydrated: true })
}
