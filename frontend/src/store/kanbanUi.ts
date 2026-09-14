import { create } from 'zustand'

interface KanbanUiState {
  activeStage: number
  setActiveStage: (i: number) => void
  otSeleccionadaId: number | null
  setOtSeleccionadaId: (id: number | null) => void
}

/**
 * Estado de navegación del tablero (columna activa en móvil, OT abierta) — vive fuera del
 * componente para sobrevivir a que React Router desmonte KanbanPage al ir a Inspección o
 * Evidencias y volver; sin esto, cada "atrás" reseteaba el tablero a la primera columna.
 */
export const useKanbanUiStore = create<KanbanUiState>((set) => ({
  activeStage: 0,
  setActiveStage: (activeStage) => set({ activeStage }),
  otSeleccionadaId: null,
  setOtSeleccionadaId: (otSeleccionadaId) => set({ otSeleccionadaId }),
}))
