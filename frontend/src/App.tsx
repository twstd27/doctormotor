import { Route, Routes } from 'react-router-dom'
import EvidenciasPage from './pages/EvidenciasPage'
import GaragePage from './pages/GaragePage'
import GoogleCallbackPage from './pages/GoogleCallbackPage'
import HomePage from './pages/HomePage'
import InspeccionPage from './pages/InspeccionPage'
import KanbanPage from './pages/KanbanPage'
import LoginPage from './pages/LoginPage'
import PresupuestoPage from './pages/PresupuestoPage'
import WhatsappVerifyPage from './pages/WhatsappVerifyPage'

function App() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/ordenes-trabajo" element={<KanbanPage />} />
      <Route path="/ordenes-trabajo/:id/inspeccion" element={<InspeccionPage />} />
      <Route path="/ordenes-trabajo/:id/evidencias" element={<EvidenciasPage />} />
      <Route path="/garaje" element={<GaragePage />} />
      <Route path="/presupuestos/:id" element={<PresupuestoPage />} />
      <Route path="/auth/google/callback" element={<GoogleCallbackPage />} />
      <Route path="/auth/whatsapp/:token" element={<WhatsappVerifyPage />} />
    </Routes>
  )
}

export default App
